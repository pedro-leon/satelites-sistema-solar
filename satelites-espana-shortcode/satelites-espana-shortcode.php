<?php
/**
 * Plugin Name: Satélites España Shortcode
 * Description: Muestra mediante shortcode una tabla de satélites espaciales asociados a España según GCAT.
 * Version: 1.1.5
 * Author: Pedro León with Codex
 * License: GPL-2.0-or-later
 * Text Domain: satelites-espana-shortcode
 */

/*
 * Evita que alguien ejecute este archivo directamente desde el navegador.
 * En WordPress, ABSPATH solo existe cuando el archivo se carga dentro del CMS.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Toda la logica del plugin vive en una clase para no llenar el espacio global
 * de funciones. Los metodos son estaticos porque WordPress los registra y llama
 * como callbacks, y este plugin no necesita guardar estado en objetos.
 */
final class Satelites_Espana_Shortcode {
	/*
	 * URL del archivo TSV de GCAT. Este formato es mas seguro que el HTML
	 * porque cada campo viene separado por tabuladores.
	 */
	private const SOURCE_URL = 'https://planet4589.org/space/gcat/tsv/derived/launchlog.tsv';

	/*
	 * Opciones donde WordPress guarda los datos automaticos, los datos manuales,
	 * la fecha de la fuente y la ultima sincronizacion realizada por el plugin.
	 */
	private const OPTION_DATA = 'ses_gcat_spain_satellites';
	private const OPTION_MANUAL_DATA = 'ses_manual_spain_satellites';
	private const OPTION_SOURCE_UPDATED = 'ses_gcat_source_updated';
	private const OPTION_LAST_SYNC = 'ses_gcat_last_sync';

	/* Nombre interno del evento semanal de WP-Cron. */
	private const CRON_HOOK = 'ses_update_spain_satellites';

	/**
	 * Registra shortcodes, evento de actualizacion, frecuencia semanal y pagina
	 * de administracion.
	 */
	public static function init(): void {
		/* Shortcode principal que se coloca en paginas o entradas. */
		add_shortcode( 'satelites_espana', array( __CLASS__, 'render_shortcode' ) );

		/* Alias del shortcode, util si se prefiere un nombre mas descriptivo. */
		add_shortcode( 'satelites_espaciales_espana', array( __CLASS__, 'render_shortcode' ) );

		/* Evento interno que ejecuta la descarga periodica desde GCAT. */
		add_action( self::CRON_HOOK, array( __CLASS__, 'update_data' ) );

		/* Crea la pagina de ajustes del plugin en el administrador. */
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_page' ) );

		/* Procesa altas y borrados manuales antes de pintar la pagina admin. */
		add_action( 'admin_init', array( __CLASS__, 'handle_admin_actions' ) );

		/* Registra la frecuencia semanal usada por wp_schedule_event(). */
		add_filter( 'cron_schedules', array( __CLASS__, 'add_weekly_schedule' ) );
	}

	/**
	 * Se ejecuta al activar el plugin.
	 *
	 * Programa la actualizacion semanal y hace una primera descarga para que la
	 * tabla tenga datos desde el principio.
	 */
	public static function activate(): void {
		/*
		 * Evita duplicar eventos si el plugin se activa varias veces. El primer
		 * evento se programa para dentro de una hora y luego se repite semanalmente.
		 */
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'ses_weekly', self::CRON_HOOK );
		}

		/*
		 * Descarga inicial inmediata para que el shortcode tenga datos sin esperar
		 * a que se ejecute el primer evento de WP-Cron.
		 */
		self::update_data();
	}

	/**
	 * Se ejecuta al desactivar el plugin.
	 *
	 * Elimina el evento programado para que WordPress no siga intentando
	 * actualizar datos de un plugin desactivado.
	 */
	public static function deactivate(): void {
		/* WordPress necesita el timestamp exacto del evento para desprogramarlo. */
		$timestamp = wp_next_scheduled( self::CRON_HOOK );

		if ( $timestamp ) {
			/* Limpia la tarea para que no siga ejecutandose con el plugin apagado. */
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Anade una frecuencia semanal personalizada a WP-Cron.
	 *
	 * WordPress trae algunas frecuencias por defecto, pero no siempre incluye
	 * una semanal; por eso se define aqui.
	 */
	public static function add_weekly_schedule( array $schedules ): array {
		/* Solo se anade si no existe ya para no pisar otra definicion. */
		if ( ! isset( $schedules['ses_weekly'] ) ) {
			$schedules['ses_weekly'] = array(
				'interval' => WEEK_IN_SECONDS,
				'display'  => __( 'Una vez a la semana', 'satelites-espana-shortcode' ),
			);
		}

		return $schedules;
	}

	/**
	 * Genera la salida HTML del shortcode.
	 *
	 * Junta los satelites de GCAT con los introducidos manualmente, los ordena
	 * cronologicamente y los pinta en una tabla numerada.
	 */
	public static function render_shortcode(): string {
		/*
		 * La cache automatica se revisa por separado de los registros manuales.
		 * Los manuales no deben provocar descargas ni borrarse si GCAT falla.
		 */
		$automatic_items = get_option( self::OPTION_DATA, array() );

		/*
		 * Si no hay cache, si la cache es de una version antigua que aun no
		 * tenia la columna Piece, o si la ultima sincronizacion tiene mas de una
		 * semana, se fuerza una nueva sincronizacion.
		 */
		if (
			empty( $automatic_items )
			|| ! self::cached_items_have_piece( $automatic_items )
			|| self::should_refresh_cached_data()
		) {
			/*
			 * Si la descarga falla, se mantiene la ultima cache valida. Por eso
			 * no se comprueba el valor devuelto aqui.
			 */
			self::update_data();
		}

		/*
		 * Junta la cache de GCAT con los registros manuales y devuelve una sola
		 * lista ordenada para que el pintado de la tabla sea uniforme.
		 */
		$items = self::get_display_items();

		if ( empty( $items ) ) {
			/* Caso extremo: no hay cache util ni registros manuales que mostrar. */
			return '<p class="satelites-espana-empty">' . esc_html__( 'No hay datos disponibles de satélites españoles en este momento.', 'satelites-espana-shortcode' ) . '</p>';
		}

		/*
		 * source_updated es la fecha oficial que publica GCAT en el TSV.
		 * last_sync es la fecha local de WordPress cuando se guardo la cache.
		 */
		$source_updated = get_option( self::OPTION_SOURCE_UPDATED, '' );
		$last_sync      = get_option( self::OPTION_LAST_SYNC, '' );

		/*
		 * Se usa buffer de salida para poder escribir HTML mezclado con PHP de
		 * forma legible y devolverlo como string, que es lo que espera un shortcode.
		 */
		ob_start();
		?>
		<div class="satelites-espana-wrapper">
			<style>
				/*
				 * CSS embebido y acotado al shortcode: garantiza que la fila de
				 * ano destaque aunque el tema activo no aporte estilos propios.
				 */
				.satelites-espana-table .satelites-espana-year-row th {
					background: #1f2937;
					color: #ffffff;
					font-weight: 700;
					text-align: left;
				}
			</style>
			<table class="satelites-espana-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Nº', 'satelites-espana-shortcode' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Fecha de lanzamiento', 'satelites-espana-shortcode' ); ?></th>
						<th scope="col"><?php esc_html_e( 'ID', 'satelites-espana-shortcode' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Nombre', 'satelites-espana-shortcode' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Propietario del satélite', 'satelites-espana-shortcode' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Vehículo de lanzamiento', 'satelites-espana-shortcode' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					/*
					 * Al recorrer la lista ya ordenada, basta con recordar el
					 * ultimo ano pintado. Si el siguiente satelite pertenece a
					 * otro ano, se inserta una fila separadora antes de su fila.
					 */
					$current_year = '';

					foreach ( $items as $index => $item ) :
						/* El ano sale de la clave de ordenacion para evitar ambiguedades. */
						$item_year = self::get_item_launch_year( $item );

						if ( '' !== $item_year && $item_year !== $current_year ) :
							$current_year = $item_year;
							?>
							<tr class="satelites-espana-year-row">
								<th scope="rowgroup" colspan="6">
									<?php
									printf(
										/* translators: %s: launch year. */
										esc_html__( 'Año %s', 'satelites-espana-shortcode' ),
										esc_html( $item_year )
									);
									?>
								</th>
							</tr>
						<?php endif; ?>
						<tr>
							<!-- El indice pertenece al array de satelites, no a las filas separadoras. -->
							<td><?php echo esc_html( (string) ( $index + 1 ) ); ?></td>
							<td><?php echo esc_html( self::format_launch_date( $item['launch_date'] ?? '' ) ); ?></td>
							<td><?php echo esc_html( $item['piece'] ?? '' ); ?></td>
							<td><?php echo esc_html( $item['name'] ?? '' ); ?></td>
							<td><?php echo esc_html( $item['sat_owner'] ?? '' ); ?></td>
							<td><?php echo esc_html( $item['launch_vehicle'] ?? '' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $source_updated || $last_sync ) : ?>
				<p class="satelites-espana-meta">
					Fuente: data from GCAT (J. McDowell, <a href="https://planet4589.org/space/gcat" target="_blank">planet4589.org/space/gcat</a>) | 
					<?php
					if ( $source_updated ) {
						/* Formatea "2026 May 15 0115:41" como "15/05/2026 01:15:41". */
						printf(
							/* translators: %s: GCAT source update text. */
							esc_html__( 'Fuente actualizada: %s', 'satelites-espana-shortcode' ),
							esc_html( self::format_meta_date( $source_updated ) )
						);
					}

					if ( $source_updated && $last_sync ) {
						/* Solo se muestra el separador cuando existen ambos textos. */
						echo esc_html( ' | ' );
					}

					if ( $last_sync ) {
						/* Formatea la fecha MySQL guardada por WordPress. */
						printf(
							/* translators: %s: WordPress sync date. */
							esc_html__( 'Última sincronización: %s', 'satelites-espana-shortcode' ),
							esc_html( self::format_meta_date( $last_sync ) )
						);
					}
					?>
				</p>
			<?php endif; ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Registra la pantalla de administracion del plugin dentro de Ajustes.
	 */
	public static function register_admin_page(): void {
		/*
		 * add_options_page coloca la pantalla bajo Ajustes. La capacidad
		 * manage_options limita el acceso a usuarios administradores.
		 */
		add_options_page(
			__( 'Satélites España', 'satelites-espana-shortcode' ),
			__( 'Satélites España', 'satelites-espana-shortcode' ),
			'manage_options',
			'satelites-espana-shortcode',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	/**
	 * Procesa las acciones de la pantalla de administracion.
	 *
	 * Permite crear registros manuales y eliminar los que ya existan. Los datos
	 * manuales se guardan aparte para que una actualizacion de GCAT no los borre.
	 */
	public static function handle_admin_actions(): void {
		/* Barrera de seguridad: si no puede administrar opciones, no procesa nada. */
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		/*
		 * Alta de satelite manual. Se detecta por POST y por el campo oculto
		 * ses_action para distinguir esta accion de otros formularios.
		 */
		if ( isset( $_POST['ses_action'] ) && 'add_manual_satellite' === sanitize_text_field( wp_unslash( $_POST['ses_action'] ) ) ) {
			/* Verifica que el formulario se creo desde esta misma pantalla. */
			check_admin_referer( 'ses_add_manual_satellite' );

			/* Fecha y hora se separan porque sirven tanto para mostrar como para ordenar. */
			$date = isset( $_POST['ses_launch_date'] ) ? sanitize_text_field( wp_unslash( $_POST['ses_launch_date'] ) ) : '';
			$time = isset( $_POST['ses_launch_time'] ) ? sanitize_text_field( wp_unslash( $_POST['ses_launch_time'] ) ) : '';

			/*
			 * El registro manual imita la estructura de los registros de GCAT.
			 * Gracias a eso, la tabla publica puede mezclar ambos origenes.
			 */
			$item = array(
				'id'             => uniqid( 'manual_', true ),
				'launch_date'    => self::format_manual_launch_date( $date, $time ),
				'sort_key'       => self::build_sort_key_from_manual_date( $date, $time ),
				'piece'          => isset( $_POST['ses_piece'] ) ? sanitize_text_field( wp_unslash( $_POST['ses_piece'] ) ) : '',
				'name'           => isset( $_POST['ses_name'] ) ? sanitize_text_field( wp_unslash( $_POST['ses_name'] ) ) : '',
				'sat_owner'      => isset( $_POST['ses_sat_owner'] ) ? sanitize_text_field( wp_unslash( $_POST['ses_sat_owner'] ) ) : '',
				'launch_vehicle' => isset( $_POST['ses_launch_vehicle'] ) ? sanitize_text_field( wp_unslash( $_POST['ses_launch_vehicle'] ) ) : '',
				'source'         => 'manual',
			);

			/*
			 * Campos minimos para que la fila tenga sentido publico. Propietario
			 * y vehiculo son opcionales.
			 */
			if ( $item['launch_date'] && $item['piece'] && $item['name'] ) {
				/* Conserva los manuales existentes, anade el nuevo y reordena. */
				$manual_items   = get_option( self::OPTION_MANUAL_DATA, array() );
				$manual_items[] = $item;

				update_option( self::OPTION_MANUAL_DATA, self::sort_items_chronologically( $manual_items ), false );

				/* Redireccion POST/Redirect/GET para evitar duplicados al recargar. */
				wp_safe_redirect( add_query_arg( 'ses_message', 'added', menu_page_url( 'satelites-espana-shortcode', false ) ) );
				exit;
			}

			/* Si faltan obligatorios, vuelve a la pantalla con un aviso de error. */
			wp_safe_redirect( add_query_arg( 'ses_message', 'missing', menu_page_url( 'satelites-espana-shortcode', false ) ) );
			exit;
		}

		/*
		 * Eliminacion de satelite manual. Va por GET porque se lanza desde un
		 * enlace de la tabla, pero siempre protegido con nonce.
		 */
		if ( isset( $_GET['ses_action'], $_GET['ses_id'], $_GET['_wpnonce'] ) && 'delete_manual_satellite' === sanitize_text_field( wp_unslash( $_GET['ses_action'] ) ) ) {
			$id = sanitize_text_field( wp_unslash( $_GET['ses_id'] ) );

			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'ses_delete_manual_satellite_' . $id ) ) {
				$manual_items = get_option( self::OPTION_MANUAL_DATA, array() );

				/*
				 * Deja todos los registros excepto el que coincide con el id.
				 * array_values reindexa el array antes de guardarlo.
				 */
				$manual_items = array_values(
					array_filter(
						$manual_items,
						static function ( $item ) use ( $id ) {
							return ( $item['id'] ?? '' ) !== $id;
						}
					)
				);

				update_option( self::OPTION_MANUAL_DATA, $manual_items, false );

				/* Limpia la URL despues de borrar y muestra mensaje de exito. */
				wp_safe_redirect( add_query_arg( 'ses_message', 'deleted', menu_page_url( 'satelites-espana-shortcode', false ) ) );
				exit;
			}
		}
	}

	/**
	 * Pinta la pantalla de administracion para anadir satelites manuales.
	 */
	public static function render_admin_page(): void {
		/* La pantalla solo tiene sentido para administradores. */
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		/* Los manuales se muestran ordenados igual que en la tabla publica. */
		$manual_items = self::sort_items_chronologically( get_option( self::OPTION_MANUAL_DATA, array() ) );

		/* Mensaje corto que llega por query string tras anadir o borrar. */
		$message      = isset( $_GET['ses_message'] ) ? sanitize_text_field( wp_unslash( $_GET['ses_message'] ) ) : '';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Satélites España', 'satelites-espana-shortcode' ); ?></h1>

			<?php if ( 'added' === $message ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Satélite manual añadido correctamente.', 'satelites-espana-shortcode' ); ?></p></div>
			<?php elseif ( 'deleted' === $message ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Satélite manual eliminado.', 'satelites-espana-shortcode' ); ?></p></div>
			<?php elseif ( 'missing' === $message ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Revisa los campos obligatorios: fecha, pieza y nombre.', 'satelites-espana-shortcode' ); ?></p></div>
			<?php endif; ?>

			<h2><?php esc_html_e( 'Añadir satélite manual', 'satelites-espana-shortcode' ); ?></h2>
			<form method="post">
				<?php /* Nonce especifico para proteger el alta manual. */ ?>
				<?php wp_nonce_field( 'ses_add_manual_satellite' ); ?>
				<?php /* Campo de accion para que handle_admin_actions() sepa que procesar. */ ?>
				<input type="hidden" name="ses_action" value="add_manual_satellite" />

				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="ses_launch_date"><?php esc_html_e( 'Fecha de lanzamiento', 'satelites-espana-shortcode' ); ?></label></th>
						<td><input type="date" id="ses_launch_date" name="ses_launch_date" required /></td>
					</tr>
					<tr>
						<th scope="row"><label for="ses_launch_time"><?php esc_html_e( 'Hora', 'satelites-espana-shortcode' ); ?></label></th>
						<td>
							<?php /* La hora es opcional y se normaliza despues en format_launch_time(). */ ?>
							<input type="text" id="ses_launch_time" name="ses_launch_time" placeholder="07:33" />
							<p class="description"><?php esc_html_e( 'Opcional. Puedes escribir 0733, 07:33 o 07:33:20.', 'satelites-espana-shortcode' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ses_piece"><?php esc_html_e( 'Pieza', 'satelites-espana-shortcode' ); ?></label></th>
						<td><input type="text" id="ses_piece" name="ses_piece" class="regular-text" required /></td>
					</tr>
					<tr>
						<th scope="row"><label for="ses_name"><?php esc_html_e( 'Nombre', 'satelites-espana-shortcode' ); ?></label></th>
						<td><input type="text" id="ses_name" name="ses_name" class="regular-text" required /></td>
					</tr>
					<tr>
						<th scope="row"><label for="ses_sat_owner"><?php esc_html_e( 'Propietario del satélite', 'satelites-espana-shortcode' ); ?></label></th>
						<td><input type="text" id="ses_sat_owner" name="ses_sat_owner" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="ses_launch_vehicle"><?php esc_html_e( 'Vehículo de lanzamiento', 'satelites-espana-shortcode' ); ?></label></th>
						<td><input type="text" id="ses_launch_vehicle" name="ses_launch_vehicle" class="regular-text" /></td>
					</tr>
				</table>

				<?php submit_button( __( 'Añadir satélite', 'satelites-espana-shortcode' ) ); ?>
			</form>

			<h2><?php esc_html_e( 'Satélites manuales', 'satelites-espana-shortcode' ); ?></h2>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Fecha', 'satelites-espana-shortcode' ); ?></th>
						<th><?php esc_html_e( 'Pieza', 'satelites-espana-shortcode' ); ?></th>
						<th><?php esc_html_e( 'Nombre', 'satelites-espana-shortcode' ); ?></th>
						<th><?php esc_html_e( 'Propietario', 'satelites-espana-shortcode' ); ?></th>
						<th><?php esc_html_e( 'Vehículo', 'satelites-espana-shortcode' ); ?></th>
						<th><?php esc_html_e( 'Acciones', 'satelites-espana-shortcode' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $manual_items ) ) : ?>
						<?php /* Estado vacio de la tabla de administracion. */ ?>
						<tr><td colspan="6"><?php esc_html_e( 'Todavía no hay satélites manuales.', 'satelites-espana-shortcode' ); ?></td></tr>
					<?php else : ?>
						<?php /* Lista de registros manuales ya ordenados cronologicamente. */ ?>
						<?php foreach ( $manual_items as $item ) : ?>
							<tr>
								<td><?php echo esc_html( self::format_launch_date( $item['launch_date'] ?? '' ) ); ?></td>
								<td><?php echo esc_html( $item['piece'] ?? '' ); ?></td>
								<td><?php echo esc_html( $item['name'] ?? '' ); ?></td>
								<td><?php echo esc_html( $item['sat_owner'] ?? '' ); ?></td>
								<td><?php echo esc_html( $item['launch_vehicle'] ?? '' ); ?></td>
								<td>
									<?php
									/*
									 * Enlace de borrado protegido con nonce unico por id.
									 * Asi solo se borra si el usuario viene desde esta pantalla.
									 */
									$delete_url = wp_nonce_url(
										add_query_arg(
											array(
												'page'       => 'satelites-espana-shortcode',
												'ses_action' => 'delete_manual_satellite',
												'ses_id'     => $item['id'] ?? '',
											),
											admin_url( 'options-general.php' )
										),
										'ses_delete_manual_satellite_' . ( $item['id'] ?? '' )
									);
									?>
									<a href="<?php echo esc_url( $delete_url ); ?>" class="submitdelete"><?php esc_html_e( 'Eliminar', 'satelites-espana-shortcode' ); ?></a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Descarga el TSV de GCAT, lo convierte en filas utiles y actualiza la cache.
	 *
	 * Devuelve true si la sincronizacion termina correctamente y false si falla
	 * la descarga, la respuesta HTTP o el parseo.
	 */
	public static function update_data(): bool {
		/*
		 * wp_remote_get usa la API HTTP de WordPress, respetando proxies,
		 * certificados y filtros del hosting. El timeout evita dejar la pagina
		 * esperando demasiado si GCAT no responde.
		 */
		$response = wp_remote_get(
			self::SOURCE_URL,
			array(
				'timeout'     => 30,
				'redirection' => 3,
				'user-agent'  => 'WordPress/Satelites-Espana-Shortcode',
			)
		);

		if ( is_wp_error( $response ) ) {
			/* Error de red, DNS, SSL, timeout, etc. Se conserva la cache anterior. */
			return false;
		}

		/* Solo una respuesta HTTP 200 se considera valida para refrescar cache. */
		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status_code ) {
			return false;
		}

		/* El cuerpo contiene el TSV completo con cabecera, metadatos y filas. */
		$body = wp_remote_retrieve_body( $response );
		$data = self::parse_launch_log( $body );

		if ( empty( $data['items'] ) ) {
			/* Si el parseo no produce satelites espanoles, no se pisa la cache. */
			return false;
		}

		/*
		 * Se guardan tres cosas separadas:
		 * - datos filtrados para la tabla;
		 * - fecha oficial de actualizacion de la fuente;
		 * - fecha local de la sincronizacion realizada por este plugin.
		 */
		update_option( self::OPTION_DATA, $data['items'], false );
		update_option( self::OPTION_SOURCE_UPDATED, $data['source_updated'], false );
		update_option( self::OPTION_LAST_SYNC, current_time( 'mysql' ), false );

		return true;
	}

	/**
	 * Combina los satelites descargados de GCAT con los anadidos manualmente.
	 */
	private static function get_display_items(): array {
		/* Datos descargados automaticamente desde GCAT. */
		$automatic_items = get_option( self::OPTION_DATA, array() );

		/* Datos anadidos desde el administrador de WordPress. */
		$manual_items    = get_option( self::OPTION_MANUAL_DATA, array() );

		if ( ! is_array( $automatic_items ) ) {
			/* Defensa ante opciones corruptas o editadas a mano. */
			$automatic_items = array();
		}

		if ( ! is_array( $manual_items ) ) {
			/* Misma defensa para los datos manuales. */
			$manual_items = array();
		}

		/* Mezcla ambos origenes y aplica un unico criterio cronologico. */
		return self::sort_items_chronologically( array_merge( $automatic_items, $manual_items ) );
	}

	/**
	 * Ordena los registros de mas antiguo a mas reciente usando sort_key.
	 */
	private static function sort_items_chronologically( array $items ): array {
		usort(
			$items,
			static function ( $a, $b ) {
				/* sort_key permite comparar fechas como texto: YYYYMMDDHHMMSS. */
				$a_key = self::get_item_sort_key( $a );
				$b_key = self::get_item_sort_key( $b );

				if ( $a_key === $b_key ) {
					/* Si dos objetos comparten fecha exacta, se estabiliza por nombre. */
					return strcmp( $a['name'] ?? '', $b['name'] ?? '' );
				}

				/* strcmp funciona porque la clave esta rellenada con ceros. */
				return strcmp( $a_key, $b_key );
			}
		);

		return $items;
	}

	/**
	 * Obtiene la clave de ordenacion de un registro.
	 *
	 * Los registros nuevos ya la guardan. Para caches antiguas, se calcula a
	 * partir de la fecha visible para mantener compatibilidad.
	 */
	private static function get_item_sort_key( array $item ): string {
		if ( ! empty( $item['sort_key'] ) ) {
			/* Camino normal para registros nuevos: ya traen clave calculada. */
			return (string) $item['sort_key'];
		}

		/* Compatibilidad con caches antiguas que solo guardaban la fecha visible. */
		return self::build_sort_key_from_display_date( $item['launch_date'] ?? '' );
	}

	/**
	 * Obtiene el ano de lanzamiento para separar visualmente la tabla.
	 */
	private static function get_item_launch_year( array $item ): string {
		/* Preferimos sort_key porque ya es la fuente usada para ordenar. */
		$sort_key = self::get_item_sort_key( $item );

		if ( preg_match( '/^(\d{4})/', $sort_key, $matches ) && '9999' !== $matches[1] ) {
			return $matches[1];
		}

		/*
		 * Fallback para registros raros: busca un ano de cuatro cifras en la
		 * fecha visible. Se usa solo si sort_key no aporta un ano valido.
		 */
		$launch_date = (string) ( $item['launch_date'] ?? '' );

		if ( preg_match( '/\b(\d{4})\b/', $launch_date, $matches ) ) {
			return $matches[1];
		}

		return '';
	}

	/**
	 * Indica si la cache automatica debe refrescarse al visitar el shortcode.
	 */
	private static function should_refresh_cached_data(): bool {
		/* Sin fecha de ultima sincronizacion no podemos saber si la cache es fresca. */
		$last_sync = get_option( self::OPTION_LAST_SYNC, '' );

		if ( ! is_string( $last_sync ) || '' === trim( $last_sync ) ) {
			return true;
		}

		try {
			/* last_sync se guarda como fecha local de WordPress en formato MySQL. */
			$last_sync_date = new DateTimeImmutable( $last_sync, wp_timezone() );
		} catch ( Exception $exception ) {
			/* Si la fecha guardada es invalida, se fuerza refresco. */
			return true;
		}

		/* current_time( ..., true ) devuelve timestamp UTC; DateTime tambien lo compara bien. */
		return $last_sync_date->getTimestamp() <= current_time( 'timestamp', true ) - WEEK_IN_SECONDS;
	}

	/**
	 * Detecta si una cache antigua todavia usa launch_tag en vez de piece.
	 */
	private static function cached_items_have_piece( $items ): bool {
		if ( ! is_array( $items ) || empty( $items ) ) {
			/* Sin array valido no hay forma de considerarlo cache moderna. */
			return false;
		}

		/*
		 * Basta mirar el primer registro: la cache se genera toda con la misma
		 * estructura. Si no existe piece, procede de una version anterior.
		 */
		$first = reset( $items );

		return is_array( $first ) && array_key_exists( 'piece', $first );
	}

	/**
	 * Convierte el contenido TSV de GCAT en un array de satelites espanoles.
	 *
	 * El archivo contiene una primera linea de cabeceras precedida por "#", una
	 * linea "# Updated ..." con la fecha de actualizacion de la fuente, y luego
	 * las filas de datos separadas por tabuladores.
	 */
	private static function parse_launch_log( string $tsv ): array {
		/* Texto de la linea "# Updated ..." del TSV, si existe. */
		$source_updated = '';

		/* Divide el archivo en lineas respetando saltos Unix, Windows o mixtos. */
		$lines          = preg_split( '/\R/', $tsv );

		if ( empty( $lines ) || ! is_array( $lines ) ) {
			/* Respuesta vacia o ilegible: devuelve estructura vacia y segura. */
			return array(
				'items'          => array(),
				'source_updated' => $source_updated,
			);
		}

		/* Cabecera del TSV; se rellena cuando se encuentra la linea que empieza por "#". */
		$header = array();

		/* Filas finales ya filtradas para SatState = E. */
		$items  = array();

		foreach ( $lines as $line ) {
			/* Quita saltos finales, pero conserva tabuladores internos del TSV. */
			$line = rtrim( $line, "\r\n" );

			if ( '' === trim( $line ) ) {
				/* Ignora lineas en blanco para simplificar el parseo. */
				continue;
			}

			/* Guarda la fecha oficial de actualizacion que publica GCAT. */
			if ( preg_match( '/^#\s*Updated\s+(.+)$/i', $line, $matches ) ) {
				$source_updated = trim( $matches[1] );
				continue;
			}

			/* La cabecera empieza por "#"; se elimina ese caracter antes de leerla. */
			if ( 0 === strpos( $line, '#' ) ) {
				/*
				 * La primera linea comentada contiene las cabeceras reales.
				 * Se quita "#" y se usa str_getcsv con tabulador como separador.
				 */
				$header = str_getcsv( ltrim( $line, '#' ), "\t" );
				$header = array_map( 'trim', $header );
				continue;
			}

			if ( empty( $header ) ) {
				/* No se procesan filas hasta conocer los nombres de columnas. */
				continue;
			}

			/* Lee una fila TSV. str_getcsv maneja separadores de forma fiable. */
			$values = str_getcsv( $line, "\t" );
			$row    = array();

			/* Combina cabeceras y valores para poder acceder por nombre de columna. */
			foreach ( $header as $index => $column ) {
				$row[ $column ] = isset( $values[ $index ] ) ? trim( $values[ $index ] ) : '';
			}

			/* En GCAT, SatState = E identifica los satelites asociados a Espana. */
			if ( 'E' !== ( $row['SatState'] ?? '' ) ) {
				continue;
			}

			/* Se guardan solo las columnas que la tabla necesita mostrar. */
			$items[] = array(
				/*
				 * launch_date se guarda ya formateada para pintar rapido, pero
				 * sort_key conserva una representacion ordenable.
				 */
				'launch_date'    => self::format_launch_date( $row['Launch_Date'] ?? '' ),
				'sort_key'       => self::build_sort_key_from_gcat_date( $row['Launch_Date'] ?? '' ),
				'piece'          => $row['Piece'] ?? '',
				'name'           => $row['Name'] ?? '',
				'sat_owner'      => $row['SatOwner'] ?? '',
				'launch_vehicle' => $row['LV_Type'] ?? '',
				'source'         => 'gcat',
			);
		}

		return array(
			'items'          => $items,
			'source_updated' => $source_updated,
		);
	}

	/**
	 * Convierte fechas de GCAT al formato espanol dia/mes/ano.
	 *
	 * Ejemplos:
	 * - "1993 Jul 22 2258:55" pasa a "22/07/1993 22:58:55".
	 * - "1958 Feb 5 0733" pasa a "05/02/1958 07:33".
	 * - "1978 Aug 5 0500?" conserva la duda como "05/08/1978 05:00?".
	 */
	private static function format_launch_date( string $launch_date ): string {
		/* Normaliza espacios dobles del TSV para que la expresion regular sea estable. */
		$launch_date = trim( preg_replace( '/\s+/', ' ', $launch_date ) );

		if ( ! preg_match( '/^(\d{4})\s+([A-Za-z]{3})\s+(\d{1,2}\??)(?:\s+(.+))?$/', $launch_date, $matches ) ) {
			/* Si no reconoce el formato, devuelve el texto original sin romper la tabla. */
			return $launch_date;
		}

		/* Convierte "May" en "05"; si el mes no existe, conserva el original. */
		$month_number = self::month_name_to_number( $matches[2] );

		if ( '' === $month_number ) {
			return $launch_date;
		}

		$year       = $matches[1];
		$day        = $matches[3];
		$day_suffix = '?' === substr( $day, -1 ) ? '?' : '';
		$day_number = rtrim( $day, '?' );

		if ( ! ctype_digit( $day_number ) ) {
			/* Proteccion ante dias incompletos o inesperados en la fuente. */
			return $launch_date;
		}

		/* Monta dd/mm/yyyy y conserva el signo ? si GCAT marca duda en el dia. */
		$formatted = sprintf( '%02d/%s/%s%s', (int) $day_number, $month_number, $year, $day_suffix );

		/* La hora es opcional; si existe, tambien se normaliza. */
		$time      = isset( $matches[4] ) ? self::format_launch_time( $matches[4] ) : '';

		if ( '' !== $time ) {
			$formatted .= ' ' . $time;
		}

		return $formatted;
	}

	/**
	 * Formatea las fechas informativas que se muestran bajo la tabla.
	 */
	private static function format_meta_date( string $date ): string {
		/* Acepta tanto fechas GCAT como fechas MySQL guardadas por WordPress. */
		$date = trim( preg_replace( '/\s+/', ' ', $date ) );

		if ( '' === $date ) {
			return '';
		}

		$formatted_gcat_date = self::format_launch_date( $date );

		if ( $formatted_gcat_date !== $date ) {
			/* Si format_launch_date la entendio, ya tenemos dd/mm/yyyy. */
			return $formatted_gcat_date;
		}

		/* Formato usado por current_time( 'mysql' ). */
		$date_object = DateTime::createFromFormat( 'Y-m-d H:i:s', $date );

		if ( $date_object ) {
			return $date_object->format( 'd/m/Y H:i:s' );
		}

		/* Fallback por si alguna vez se guarda solo la fecha sin hora. */
		$date_object = DateTime::createFromFormat( 'Y-m-d', $date );

		if ( $date_object ) {
			return $date_object->format( 'd/m/Y' );
		}

		return $date;
	}

	/**
	 * Formatea la fecha introducida manualmente desde el administrador.
	 */
	private static function format_manual_launch_date( string $date, string $time ): string {
		/* El input date del admin envia Y-m-d. */
		$date_object = DateTime::createFromFormat( 'Y-m-d', $date );

		if ( ! $date_object ) {
			/* Fecha invalida: no se guarda el registro manual. */
			return '';
		}

		/* La hora puede venir vacia, con dos puntos o compacta. */
		$formatted_time = self::format_launch_time( $time );
		$formatted_date = $date_object->format( 'd/m/Y' );

		return '' === $formatted_time ? $formatted_date : $formatted_date . ' ' . $formatted_time;
	}

	/**
	 * Normaliza la hora cuando GCAT o el administrador la publican sin separador.
	 *
	 * Convierte "0733" en "07:33" y respeta formatos que ya tienen segundos,
	 * por ejemplo "2258:55" pasa a "22:58:55".
	 */
	private static function format_launch_time( string $time ): string {
		$time = trim( $time );

		if ( '' === $time ) {
			/* Sin hora: la fecha se muestra solo como dia/mes/ano. */
			return '';
		}

		/*
		 * Inserta dos puntos cuando los cuatro primeros caracteres son HHMM.
		 * Mantiene segundos y signos de duda si ya venian en la fuente.
		 */
		return preg_replace( '/^(\d{2})(\d{2})(?=(:|\?|$))/', '$1:$2', $time );
	}

	/**
	 * Construye la clave de ordenacion desde una fecha original de GCAT.
	 */
	private static function build_sort_key_from_gcat_date( string $launch_date ): string {
		/* GCAT publica fechas tipo "1993 Jul 22 2258:55". */
		$launch_date = trim( preg_replace( '/\s+/', ' ', $launch_date ) );

		if ( ! preg_match( '/^(\d{4})\s+([A-Za-z]{3})\s+(\d{1,2}\??)(?:\s+(.+))?$/', $launch_date, $matches ) ) {
			/* Clave alta para mandar fechas desconocidas al final. */
			return '99999999999999';
		}

		$month = self::month_name_to_number( $matches[2] );

		if ( '' === $month ) {
			/* Mes no reconocido: se trata como fecha no ordenable. */
			return '99999999999999';
		}

		/* El dia puede venir con "?", que se elimina para poder ordenar. */
		$day  = str_pad( rtrim( $matches[3], '?' ), 2, '0', STR_PAD_LEFT );
		$time = isset( $matches[4] ) ? $matches[4] : '';

		return self::build_sort_key( $matches[1], $month, $day, $time );
	}

	/**
	 * Construye la clave de ordenacion desde una fecha manual del formulario.
	 */
	private static function build_sort_key_from_manual_date( string $date, string $time ): string {
		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches ) ) {
			/* Fecha manual invalida: se envia al final si llegara a ordenarse. */
			return '99999999999999';
		}

		return self::build_sort_key( $matches[1], $matches[2], $matches[3], $time );
	}

	/**
	 * Construye una clave de ordenacion desde la fecha visible.
	 *
	 * Sirve para mantener ordenadas caches antiguas que aun no tenian sort_key.
	 */
	private static function build_sort_key_from_display_date( string $date ): string {
		$date = trim( $date );

		if ( preg_match( '/^(\d{2})\/(\d{2})\/(\d{4})(?:\s+(.+))?$/', $date, $matches ) ) {
			/* Convierte dd/mm/yyyy visible de vuelta a una clave comparable. */
			return self::build_sort_key( $matches[3], $matches[2], $matches[1], $matches[4] ?? '' );
		}

		/* Si no era formato visible, prueba si aun conserva el formato original GCAT. */
		return self::build_sort_key_from_gcat_date( $date );
	}

	/**
	 * Crea una cadena comparable con strcmp: YYYYMMDDHHMMSS.
	 */
	private static function build_sort_key( string $year, string $month, string $day, string $time ): string {
		/* La hora se normaliza antes para aceptar "0733" y "07:33". */
		$formatted_time = self::format_launch_time( $time );

		/* Se eliminan separadores y dudas; solo importan digitos para ordenar. */
		$digits         = preg_replace( '/\D/', '', $formatted_time );

		/* Asegura HHMMSS aunque solo haya hora/minuto o no haya hora. */
		$digits         = str_pad( substr( $digits, 0, 6 ), 6, '0' );

		return sprintf( '%04d%02d%02d%s', (int) $year, (int) $month, (int) $day, $digits );
	}

	/**
	 * Convierte los meses abreviados de GCAT a numero de mes.
	 */
	private static function month_name_to_number( string $month_name ): string {
		/* Mapa cerrado de meses tal como aparecen abreviados en GCAT. */
		$months = array(
			'Jan' => '01',
			'Feb' => '02',
			'Mar' => '03',
			'Apr' => '04',
			'May' => '05',
			'Jun' => '06',
			'Jul' => '07',
			'Aug' => '08',
			'Sep' => '09',
			'Oct' => '10',
			'Nov' => '11',
			'Dec' => '12',
		);

		/* Normaliza mayusculas/minusculas por si la fuente varia el estilo. */
		$month_name = ucfirst( strtolower( trim( $month_name ) ) );

		return $months[ $month_name ] ?? '';
	}
}

/* Inicializa el plugin y registra los hooks de activacion/desactivacion. */
Satelites_Espana_Shortcode::init();

register_activation_hook( __FILE__, array( 'Satelites_Espana_Shortcode', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Satelites_Espana_Shortcode', 'deactivate' ) );
