<?php
/**
 * Página de administración: ajustes, estado de la caché y refresco manual.
 *
 * @package Proximos_Lanzamientos
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PLE_Admin {

	const ACTION_REFRESH     = 'ple_manual_refresh';
	const ACTION_SAVE_SETTINGS = 'ple_save_settings';

	/**
	 * Engancha los hooks de administración.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_post_' . self::ACTION_REFRESH, array( __CLASS__, 'handle_manual_refresh' ) );
		add_action( 'admin_post_' . self::ACTION_SAVE_SETTINGS, array( __CLASS__, 'handle_save_settings' ) );
		add_action( 'admin_notices', array( __CLASS__, 'show_notices' ) );
	}

	/**
	 * Añade la página del plugin al menú de administración.
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'Próximos Lanzamientos', 'proximos-lanzamientos' ),
			__( 'Lanzamientos', 'proximos-lanzamientos' ),
			'manage_options',
			'proximos-lanzamientos',
			array( __CLASS__, 'render_page' ),
			'dashicons-airplane'
		);
	}

	/**
	 * Procesa la petición de actualización manual desde el panel.
	 */
	public static function handle_manual_refresh() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes.', 'proximos-lanzamientos' ) );
		}

		check_admin_referer( self::ACTION_REFRESH );

		$result = PLE_Data_Store::refresh_data();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'   => 'proximos-lanzamientos',
					'ple_ok' => is_wp_error( $result ) ? '0' : '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Procesa el guardado de ajustes (límite de lanzamientos e intervalo).
	 */
	public static function handle_save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes.', 'proximos-lanzamientos' ) );
		}

		check_admin_referer( self::ACTION_SAVE_SETTINGS );

		$limit = isset( $_POST['ple_limit'] ) ? (int) $_POST['ple_limit'] : PLE_Data_Store::DEFAULT_LIMIT;
		update_option( PLE_OPTION_LIMIT, max( 1, min( PLE_Data_Store::MAX_LIMIT, $limit ) ) );

		$refresh_minutes = isset( $_POST['ple_refresh_minutes'] ) ? (int) $_POST['ple_refresh_minutes'] : PLE_Data_Store::DEFAULT_REFRESH_MINUTES;
		if ( ! in_array( $refresh_minutes, PLE_Data_Store::ALLOWED_REFRESH_MINUTES, true ) ) {
			$refresh_minutes = PLE_Data_Store::DEFAULT_REFRESH_MINUTES;
		}
		update_option( PLE_OPTION_REFRESH_MINUTES, $refresh_minutes );

		// Reprograma el cron por si el intervalo ha cambiado, y refresca ya
		// para que un cambio en el límite se note sin esperar al próximo ciclo.
		PLE_Cron::schedule();
		PLE_Data_Store::refresh_data();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => 'proximos-lanzamientos',
					'ple_settings_ok' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Muestra un aviso tras el refresco manual o el guardado de ajustes.
	 */
	public static function show_notices() {
		if ( ! isset( $_GET['page'] ) || 'proximos-lanzamientos' !== $_GET['page'] ) {
			return;
		}

		if ( isset( $_GET['ple_settings_ok'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' .
				esc_html__( 'Ajustes guardados y caché actualizada.', 'proximos-lanzamientos' ) .
				'</p></div>';
		}

		if ( ! isset( $_GET['ple_ok'] ) ) {
			return;
		}

		if ( '1' === $_GET['ple_ok'] ) {
			echo '<div class="notice notice-success is-dismissible"><p>' .
				esc_html__( 'Lanzamientos actualizados correctamente.', 'proximos-lanzamientos' ) .
				'</p></div>';
		} else {
			echo '<div class="notice notice-error is-dismissible"><p>' .
				esc_html__( 'No se pudieron actualizar los lanzamientos.', 'proximos-lanzamientos' ) . ' ' .
				esc_html( PLE_Data_Store::get_last_error() ) .
				'</p></div>';
		}
	}

	/**
	 * Renderiza la página de administración.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$launches        = PLE_Data_Store::get_launches();
		$last_update     = PLE_Data_Store::get_last_updated();
		$last_error      = PLE_Data_Store::get_last_error();
		$next_run        = wp_next_scheduled( PLE_CRON_HOOK );
		$limit           = PLE_Data_Store::get_limit();
		$refresh_minutes = PLE_Data_Store::get_refresh_minutes();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Próximos Lanzamientos Espaciales', 'proximos-lanzamientos' ); ?></h1>

			<h2><?php esc_html_e( 'Ajustes', 'proximos-lanzamientos' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_SAVE_SETTINGS ); ?>" />
				<?php wp_nonce_field( self::ACTION_SAVE_SETTINGS ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="ple_limit"><?php esc_html_e( 'Lanzamientos a mostrar', 'proximos-lanzamientos' ); ?></label></th>
						<td>
							<input type="number" id="ple_limit" name="ple_limit" min="1" max="<?php echo esc_attr( PLE_Data_Store::MAX_LIMIT ); ?>" value="<?php echo esc_attr( $limit ); ?>" class="small-text" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="ple_refresh_minutes"><?php esc_html_e( 'Actualizar caché cada', 'proximos-lanzamientos' ); ?></label></th>
						<td>
							<select id="ple_refresh_minutes" name="ple_refresh_minutes">
								<?php foreach ( PLE_Data_Store::ALLOWED_REFRESH_MINUTES as $minutes ) : ?>
									<option value="<?php echo esc_attr( $minutes ); ?>" <?php selected( $refresh_minutes, $minutes ); ?>>
										<?php echo esc_html( sprintf( /* translators: %d: minutos. */ __( '%d minutos', 'proximos-lanzamientos' ), $minutes ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
							<p class="description"><?php esc_html_e( 'La página web siempre lee de esta caché local, nunca llama directamente a la API externa.', 'proximos-lanzamientos' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button( __( 'Guardar ajustes', 'proximos-lanzamientos' ) ); ?>
			</form>

			<hr />

			<p>
				<?php
				if ( $last_update ) {
					echo esc_html(
						sprintf(
							/* translators: %s: fecha y hora. */
							__( 'Última actualización: %s', 'proximos-lanzamientos' ),
							wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_update )
						)
					);
				} else {
					esc_html_e( 'Todavía no se han descargado datos.', 'proximos-lanzamientos' );
				}
				?>
			</p>

			<?php if ( $next_run ) : ?>
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: fecha y hora. */
							__( 'Próxima actualización automática: %s', 'proximos-lanzamientos' ),
							wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_run )
						)
					);
					?>
				</p>
			<?php endif; ?>

			<p><?php echo esc_html( sprintf( /* translators: %d: número de lanzamientos. */ __( 'Lanzamientos en caché: %d', 'proximos-lanzamientos' ), count( $launches ) ) ); ?></p>

			<?php if ( $last_error ) : ?>
				<p class="ple-admin-error" style="color:#b32d2e;">
					<?php echo esc_html( sprintf( /* translators: %s: mensaje de error. */ __( 'Último error: %s', 'proximos-lanzamientos' ), $last_error ) ); ?>
				</p>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:1.5em;">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_REFRESH ); ?>" />
				<?php wp_nonce_field( self::ACTION_REFRESH ); ?>
				<?php submit_button( __( 'Actualizar lanzamientos ahora', 'proximos-lanzamientos' ) ); ?>
			</form>

			<p class="description">
				<?php esc_html_e( 'Inserta el shortcode [proximos_lanzamientos] en cualquier página o entrada.', 'proximos-lanzamientos' ); ?>
			</p>
		</div>
		<?php
	}
}
