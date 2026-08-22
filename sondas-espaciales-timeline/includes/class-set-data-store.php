<?php
/**
 * Acceso a los datos de destinos y sondas, guardados en tablas propias.
 *
 * Las tablas se crean y se siembran (a partir de includes/data/*.php) en
 * SET_Activator. Esta clase es la única que habla con la base de datos;
 * el shortcode y el panel de administración pasan siempre por aquí.
 *
 * @package Sondas_Espaciales_Timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SET_Data_Store {

	const STATUSES = array( 'activa', 'finalizada', 'perdida', 'fallida' );

	/**
	 * Etiquetas legibles de cada estado de misión.
	 *
	 * @return array
	 */
	public static function get_status_labels() {
		return array(
			'activa'     => __( 'Activa', 'sondas-espaciales-timeline' ),
			'finalizada' => __( 'Finalizada', 'sondas-espaciales-timeline' ),
			'perdida'    => __( 'Contacto perdido', 'sondas-espaciales-timeline' ),
			'fallida'    => __( 'Fallida', 'sondas-espaciales-timeline' ),
		);
	}

	/**
	 * Nombre de la tabla de sondas (con el prefijo de WordPress).
	 *
	 * @return string
	 */
	public static function probes_table() {
		global $wpdb;
		return $wpdb->prefix . 'set_probes';
	}

	/**
	 * Nombre de la tabla de destinos (con el prefijo de WordPress).
	 *
	 * @return string
	 */
	public static function destinations_table() {
		global $wpdb;
		return $wpdb->prefix . 'set_destinations';
	}

	/**
	 * Nombre de la tabla de destinos intermedios de cada sonda (con el
	 * prefijo de WordPress).
	 *
	 * @return string
	 */
	public static function waypoints_table() {
		global $wpdb;
		return $wpdb->prefix . 'set_probe_waypoints';
	}

	/**
	 * Listado de sondas "de fábrica", tal cual vienen en el repositorio.
	 * Se usa para sembrar las tablas en la primera activación y para el
	 * botón "Restaurar valores de fábrica" del panel de administración.
	 *
	 * @return array
	 */
	public static function get_default_probes() {
		return require SET_PLUGIN_DIR . 'includes/data/probes.php';
	}

	/**
	 * Catálogo de destinos "de fábrica".
	 *
	 * @return array
	 */
	public static function get_default_destinations() {
		return require SET_PLUGIN_DIR . 'includes/data/destinations.php';
	}

	/**
	 * Convierte una fila de la tabla `set_probes` al formato usado en el
	 * resto del plugin (end_year numérico o null, launch_year entero...).
	 *
	 * @param array $row Fila devuelta por $wpdb (ARRAY_A).
	 * @return array
	 */
	private static function normalize_probe_row( $row ) {
		return array(
			'id'          => $row['id'],
			'name'        => $row['name'],
			'agency'      => $row['agency'],
			'destination' => $row['destination'],
			'launch_year' => (int) $row['launch_year'],
			'end_year'    => ( null === $row['end_year'] || '' === $row['end_year'] ) ? null : (int) $row['end_year'],
			'launch_date' => ( empty( $row['launch_date'] ) ) ? null : $row['launch_date'],
			'end_date'    => ( empty( $row['end_date'] ) ) ? null : $row['end_date'],
			'status'      => $row['status'],
			'note'        => (string) $row['note'],
		);
	}

	/**
	 * Da formato a una fecha "Y-m-d" al estilo español (d/m/Y). Si no hay
	 * fecha completa, cae al año que se le pase (o un guion largo).
	 *
	 * @param string|null $date          Fecha en formato Y-m-d, o null.
	 * @param int|null    $fallback_year Año a mostrar si no hay fecha completa.
	 * @return string
	 */
	public static function format_date( $date, $fallback_year = null ) {
		if ( ! empty( $date ) ) {
			$time = strtotime( $date );
			if ( $time ) {
				return date_i18n( 'd/m/Y', $time );
			}
		}

		return $fallback_year ? (string) $fallback_year : '—';
	}

	/**
	 * Convierte una fila de `set_probe_waypoints` al formato usado en el
	 * resto del plugin.
	 *
	 * @param array $row Fila devuelta por $wpdb (ARRAY_A).
	 * @return array
	 */
	private static function normalize_waypoint_row( $row ) {
		return array(
			'destination' => $row['destination'],
			'date'        => empty( $row['event_date'] ) ? null : $row['event_date'],
			'year'        => ( null === $row['event_year'] || '' === $row['event_year'] ) ? null : (int) $row['event_year'],
		);
	}

	/**
	 * Destinos adicionales de una sonda (por ejemplo, los sobrevuelos de
	 * Júpiter, Saturno, Urano y Neptuno de una Voyager), ordenados tal y
	 * como se guardaron.
	 *
	 * @param string $probe_id Id de la sonda.
	 * @return array Lista de { destination, date, year }.
	 */
	public static function get_probe_waypoints( $probe_id ) {
		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT destination, event_date, event_year FROM ' . self::waypoints_table() . ' WHERE probe_id = %s ORDER BY sort_order ASC, id ASC',
				$probe_id
			),
			ARRAY_A
		);

		return array_map( array( __CLASS__, 'normalize_waypoint_row' ), (array) $rows );
	}

	/**
	 * Destinos adicionales de varias sondas a la vez, agrupados por
	 * probe_id. Evita N+1 consultas al listar todas las sondas.
	 *
	 * @param array $probe_ids Ids de sonda.
	 * @return array probe_id => lista de { destination, date, year }.
	 */
	public static function get_waypoints_for_probes( array $probe_ids ) {
		global $wpdb;

		if ( empty( $probe_ids ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $probe_ids ), '%s' ) );
		$sql          = 'SELECT probe_id, destination, event_date, event_year FROM ' . self::waypoints_table() . " WHERE probe_id IN ($placeholders) ORDER BY sort_order ASC, id ASC";
		$rows         = $wpdb->get_results( $wpdb->prepare( $sql, $probe_ids ), ARRAY_A );

		$grouped = array();
		foreach ( (array) $rows as $row ) {
			$grouped[ $row['probe_id'] ][] = self::normalize_waypoint_row( $row );
		}

		return $grouped;
	}

	/**
	 * Sustituye los destinos adicionales de una sonda por los indicados
	 * (borra todos los que tuviera y vuelve a insertar la lista nueva).
	 *
	 * @param string $probe_id  Id de la sonda.
	 * @param array  $waypoints Lista de { destination, date, year }.
	 */
	public static function save_probe_waypoints( $probe_id, array $waypoints ) {
		global $wpdb;

		$table = self::waypoints_table();
		$wpdb->delete( $table, array( 'probe_id' => $probe_id ), array( '%s' ) );

		foreach ( array_values( $waypoints ) as $order => $waypoint ) {
			$wpdb->insert(
				$table,
				array(
					'probe_id'    => $probe_id,
					'destination' => $waypoint['destination'],
					'event_date'  => $waypoint['date'] ?? null,
					'event_year'  => $waypoint['year'] ?? null,
					'sort_order'  => $order,
				),
				array( '%s', '%s', '%s', '%d', '%d' )
			);
		}
	}

	/**
	 * Borra todos los destinos adicionales de una sonda.
	 *
	 * @param string $probe_id Id de la sonda.
	 */
	public static function delete_probe_waypoints( $probe_id ) {
		global $wpdb;

		$wpdb->delete( self::waypoints_table(), array( 'probe_id' => $probe_id ), array( '%s' ) );
	}

	/**
	 * Devuelve el catálogo completo de destinos (id => label/code/color).
	 *
	 * @return array
	 */
	public static function get_destinations() {
		global $wpdb;

		$rows = $wpdb->get_results( 'SELECT id, label, code, color FROM ' . self::destinations_table() . ' ORDER BY label ASC', ARRAY_A );

		$destinations = array();
		foreach ( (array) $rows as $row ) {
			$destinations[ $row['id'] ] = array(
				'label' => $row['label'],
				'code'  => $row['code'],
				'color' => $row['color'],
			);
		}

		return $destinations;
	}

	/**
	 * Un destino concreto, o null si no existe.
	 *
	 * @param string $id Clave del destino.
	 * @return array|null
	 */
	public static function get_destination( $id ) {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT id, label, code, color FROM ' . self::destinations_table() . ' WHERE id = %s', $id ),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		return array(
			'label' => $row['label'],
			'code'  => $row['code'],
			'color' => $row['color'],
		);
	}

	/**
	 * Cuántas sondas usan actualmente un destino.
	 *
	 * @param string $id Clave del destino.
	 * @return int
	 */
	public static function count_probes_by_destination( $id ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::probes_table() . ' WHERE destination = %s', $id )
		);
	}

	/**
	 * Cuántos destinos adicionales (de cualquier sonda) usan actualmente
	 * este destino.
	 *
	 * @param string $id Clave del destino.
	 * @return int
	 */
	public static function count_waypoints_by_destination( $id ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::waypoints_table() . ' WHERE destination = %s', $id )
		);
	}

	/**
	 * Crea o actualiza un destino. Si se renombra la clave (id !== id
	 * original), las sondas que apuntaban a la clave antigua se
	 * reasignan automáticamente a la nueva.
	 *
	 * @param string      $id           Nueva clave del destino.
	 * @param array       $data         label/code/color.
	 * @param string|null $original_id  Clave original (null si es nuevo).
	 */
	public static function save_destination( $id, array $data, $original_id = null ) {
		global $wpdb;

		$table        = self::destinations_table();
		$original_id  = $original_id ? $original_id : $id;
		$already_here = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE id = %s", $original_id ) );

		$payload = array(
			'id'    => $id,
			'label' => $data['label'],
			'code'  => $data['code'],
			'color' => $data['color'],
		);

		if ( $already_here ) {
			if ( $id !== $original_id ) {
				$wpdb->update( self::probes_table(), array( 'destination' => $id ), array( 'destination' => $original_id ), array( '%s' ), array( '%s' ) );
				$wpdb->update( self::waypoints_table(), array( 'destination' => $id ), array( 'destination' => $original_id ), array( '%s' ), array( '%s' ) );
			}
			$wpdb->update( $table, $payload, array( 'id' => $original_id ), array( '%s', '%s', '%s', '%s' ), array( '%s' ) );
		} else {
			$wpdb->insert( $table, $payload, array( '%s', '%s', '%s', '%s' ) );
		}
	}

	/**
	 * Borra un destino, salvo que todavía haya sondas usándolo.
	 *
	 * @param string $id Clave del destino.
	 * @return int Número de sondas que impiden el borrado (0 si se borró).
	 */
	public static function delete_destination( $id ) {
		global $wpdb;

		$in_use = self::count_probes_by_destination( $id ) + self::count_waypoints_by_destination( $id );

		if ( $in_use > 0 ) {
			return $in_use;
		}

		$wpdb->delete( self::destinations_table(), array( 'id' => $id ), array( '%s' ) );

		return 0;
	}

	/**
	 * Devuelve todas las sondas (sin paginar), ordenadas por año de
	 * lanzamiento. Es la que usa el shortcode público.
	 *
	 * @return array
	 */
	public static function get_probes() {
		global $wpdb;

		$rows   = $wpdb->get_results( 'SELECT * FROM ' . self::probes_table() . ' ORDER BY launch_year ASC, name ASC', ARRAY_A );
		$probes = array_map( array( __CLASS__, 'normalize_probe_row' ), (array) $rows );

		$waypoints_by_probe = self::get_waypoints_for_probes( wp_list_pluck( $probes, 'id' ) );
		foreach ( $probes as &$probe ) {
			$probe['waypoints'] = $waypoints_by_probe[ $probe['id'] ] ?? array();
		}
		unset( $probe );

		return $probes;
	}

	/**
	 * Consulta paginada/filtrada/ordenada de sondas, para el panel de
	 * administración.
	 *
	 * @param array $args {
	 *     @type string $search      Búsqueda libre (nombre o agencia).
	 *     @type string $destination Filtrar por clave de destino.
	 *     @type bool   $active_only Solo sondas con status = activa.
	 *     @type string $orderby     name|agency|destination|launch_year|end_year|status.
	 *     @type string $order       asc|desc.
	 *     @type int    $per_page    Resultados por página.
	 *     @type int    $paged       Página (empieza en 1).
	 * }
	 * @return array { items, total, pages }
	 */
	public static function query_probes( array $args = array() ) {
		global $wpdb;

		$table  = self::probes_table();
		$where  = array( '1=1' );
		$params = array();

		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]  = '(name LIKE %s OR agency LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}

		if ( ! empty( $args['destination'] ) ) {
			$where[]  = 'destination = %s';
			$params[] = $args['destination'];
		}

		if ( ! empty( $args['active_only'] ) ) {
			$where[] = "status = 'activa'";
		}

		$where_sql = implode( ' AND ', $where );

		$count_sql = "SELECT COUNT(*) FROM $table WHERE $where_sql";
		$total     = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : $wpdb->get_var( $count_sql ) );

		$sortable = array( 'name', 'agency', 'destination', 'launch_year', 'end_year', 'status' );
		$orderby  = in_array( $args['orderby'] ?? '', $sortable, true ) ? $args['orderby'] : 'launch_year';
		$order    = ( isset( $args['order'] ) && 'desc' === strtolower( $args['order'] ) ) ? 'DESC' : 'ASC';

		$per_page = isset( $args['per_page'] ) ? max( 1, (int) $args['per_page'] ) : 50;
		$paged    = isset( $args['paged'] ) ? max( 1, (int) $args['paged'] ) : 1;
		$offset   = ( $paged - 1 ) * $per_page;

		$sql               = "SELECT * FROM $table WHERE $where_sql ORDER BY $orderby $order, name ASC LIMIT %d OFFSET %d";
		$params_with_limit = array_merge( $params, array( $per_page, $offset ) );
		$rows              = $wpdb->get_results( $wpdb->prepare( $sql, $params_with_limit ), ARRAY_A );

		return array(
			'items' => array_map( array( __CLASS__, 'normalize_probe_row' ), (array) $rows ),
			'total' => $total,
			'pages' => (int) max( 1, ceil( $total / $per_page ) ),
		);
	}

	/**
	 * Una sonda concreta, o null si no existe.
	 *
	 * @param string $id Id (slug) de la sonda.
	 * @return array|null
	 */
	public static function get_probe( $id ) {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::probes_table() . ' WHERE id = %s', $id ),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		$probe              = self::normalize_probe_row( $row );
		$probe['waypoints'] = self::get_probe_waypoints( $id );

		return $probe;
	}

	/**
	 * Crea o actualiza una sonda.
	 *
	 * @param array       $probe        id/name/agency/destination/launch_year/end_year/status/note.
	 * @param string|null $original_id  Id original (null si es nueva).
	 */
	public static function save_probe( array $probe, $original_id = null ) {
		global $wpdb;

		$table       = self::probes_table();
		$original_id = $original_id ? $original_id : $probe['id'];
		$exists      = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE id = %s", $original_id ) );

		$payload = array(
			'id'          => $probe['id'],
			'name'        => $probe['name'],
			'agency'      => $probe['agency'],
			'destination' => $probe['destination'],
			'launch_year' => $probe['launch_year'],
			'end_year'    => $probe['end_year'],
			'launch_date' => $probe['launch_date'] ?? null,
			'end_date'    => $probe['end_date'] ?? null,
			'status'      => $probe['status'],
			'note'        => $probe['note'],
			'updated_at'  => current_time( 'mysql' ),
		);
		$formats = array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' );

		if ( $exists ) {
			$wpdb->update( $table, $payload, array( 'id' => $original_id ), $formats, array( '%s' ) );
		} else {
			$wpdb->insert( $table, $payload, $formats );
		}
	}

	/**
	 * Borra una sonda.
	 *
	 * @param string $id Id (slug) de la sonda.
	 */
	public static function delete_probe( $id ) {
		global $wpdb;

		$wpdb->delete( self::probes_table(), array( 'id' => $id ), array( '%s' ) );
		self::delete_probe_waypoints( $id );
	}

	/**
	 * Número total de sondas registradas.
	 *
	 * @return int
	 */
	public static function count_probes() {
		global $wpdb;

		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::probes_table() );
	}

	/**
	 * Siembra las tablas con los datos "de fábrica" si están vacías. Se
	 * llama en la activación del plugin.
	 */
	public static function maybe_seed_defaults() {
		global $wpdb;

		if ( 0 === (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::destinations_table() ) ) {
			foreach ( self::get_default_destinations() as $key => $destination ) {
				$wpdb->insert(
					self::destinations_table(),
					array(
						'id'    => $key,
						'label' => $destination['label'],
						'code'  => $destination['code'],
						'color' => $destination['color'],
					),
					array( '%s', '%s', '%s', '%s' )
				);
			}
		}

		if ( 0 === (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . self::probes_table() ) ) {
			foreach ( self::get_default_probes() as $probe ) {
				$wpdb->insert(
					self::probes_table(),
					array(
						'id'          => $probe['id'],
						'name'        => $probe['name'],
						'agency'      => $probe['agency'],
						'destination' => $probe['destination'],
						'launch_year' => $probe['launch_year'],
						'end_year'    => $probe['end_year'],
						'launch_date' => $probe['launch_date'] ?? null,
						'end_date'    => $probe['end_date'] ?? null,
						'status'      => $probe['status'],
						'note'        => $probe['note'],
						'updated_at'  => current_time( 'mysql' ),
					),
					array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
				);

				if ( ! empty( $probe['waypoints'] ) ) {
					self::save_probe_waypoints( $probe['id'], $probe['waypoints'] );
				}
			}
		}
	}

	/**
	 * Rellena, para las sondas que ya existían en la base de datos (de una
	 * instalación previa), los campos de fecha completa y los destinos
	 * adicionales que la semilla de fábrica haya incorporado más tarde y
	 * que todavía estén vacíos en la base de datos del usuario.
	 *
	 * `maybe_seed_defaults()` solo siembra si las tablas están
	 * completamente vacías, así que una instalación ya existente nunca
	 * recibiría los datos nuevos de una versión posterior del plugin. Esta
	 * función se llama en cada activación/actualización (junto a
	 * `maybe_seed_defaults()`) para cerrar ese hueco, sin tocar nunca un
	 * valor que el usuario ya tenga puesto (nombre, agencia, destino,
	 * nota, años...) — solo completa fecha completa y destinos
	 * adicionales cuando están vacíos.
	 */
	public static function backfill_missing_data_from_seed() {
		global $wpdb;

		$table = self::probes_table();

		foreach ( self::get_default_probes() as $seed_probe ) {
			$live = $wpdb->get_row(
				$wpdb->prepare( "SELECT id, launch_date, end_date FROM $table WHERE id = %s", $seed_probe['id'] ),
				ARRAY_A
			);

			if ( ! $live ) {
				continue;
			}

			$updates = array();
			$formats = array();

			if ( empty( $live['launch_date'] ) && ! empty( $seed_probe['launch_date'] ) ) {
				$updates['launch_date'] = $seed_probe['launch_date'];
				$formats[]              = '%s';
			}

			if ( empty( $live['end_date'] ) && ! empty( $seed_probe['end_date'] ) ) {
				$updates['end_date'] = $seed_probe['end_date'];
				$formats[]           = '%s';
			}

			if ( $updates ) {
				$wpdb->update( $table, $updates, array( 'id' => $seed_probe['id'] ), $formats, array( '%s' ) );
			}

			if ( ! empty( $seed_probe['waypoints'] ) && empty( self::get_probe_waypoints( $seed_probe['id'] ) ) ) {
				self::save_probe_waypoints( $seed_probe['id'], $seed_probe['waypoints'] );
			}
		}
	}

	/**
	 * Vacía las dos tablas y las vuelve a sembrar con los datos de
	 * fábrica. Usado por el botón "Restaurar valores de fábrica".
	 */
	public static function reset_to_defaults() {
		global $wpdb;

		// TRUNCATE reinicia también el recuento, aunque en set_probes y
		// set_destinations no se use autoincremento (la clave primaria es
		// el slug); en set_probe_waypoints sí, y también nos interesa
		// reiniciarlo.
		$wpdb->query( 'TRUNCATE TABLE ' . self::probes_table() );
		$wpdb->query( 'TRUNCATE TABLE ' . self::destinations_table() );
		$wpdb->query( 'TRUNCATE TABLE ' . self::waypoints_table() );

		self::maybe_seed_defaults();
	}
}
