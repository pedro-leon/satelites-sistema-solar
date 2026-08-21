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
			'status'      => $row['status'],
			'note'        => (string) $row['note'],
		);
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

		$in_use = self::count_probes_by_destination( $id );

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

		$rows = $wpdb->get_results( 'SELECT * FROM ' . self::probes_table() . ' ORDER BY launch_year ASC, name ASC', ARRAY_A );

		return array_map( array( __CLASS__, 'normalize_probe_row' ), (array) $rows );
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

		return $row ? self::normalize_probe_row( $row ) : null;
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
			'status'      => $probe['status'],
			'note'        => $probe['note'],
			'updated_at'  => current_time( 'mysql' ),
		);
		$formats = array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' );

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
						'status'      => $probe['status'],
						'note'        => $probe['note'],
						'updated_at'  => current_time( 'mysql' ),
					),
					array( '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' )
				);
			}
		}
	}

	/**
	 * Vacía las dos tablas y las vuelve a sembrar con los datos de
	 * fábrica. Usado por el botón "Restaurar valores de fábrica".
	 */
	public static function reset_to_defaults() {
		global $wpdb;

		// TRUNCATE reinicia también el recuento, aunque aquí no se use
		// autoincremento (la clave primaria es el slug).
		$wpdb->query( 'TRUNCATE TABLE ' . self::probes_table() );
		$wpdb->query( 'TRUNCATE TABLE ' . self::destinations_table() );

		self::maybe_seed_defaults();
	}
}
