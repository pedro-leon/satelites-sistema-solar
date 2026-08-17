<?php
/**
 * Página de administración: estado de los datos y actualización manual.
 *
 * @package Satelites_Sistema_Solar
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SSS_Admin {

	const ACTION = 'sss_manual_refresh';

	/**
	 * Engancha los hooks de administración.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_post_' . self::ACTION, array( __CLASS__, 'handle_manual_refresh' ) );
		add_action( 'admin_notices', array( __CLASS__, 'show_notices' ) );
	}

	/**
	 * Añade la página del plugin al menú de administración.
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'Satélites del Sistema Solar', 'satelites-sistema-solar' ),
			__( 'Satélites SS', 'satelites-sistema-solar' ),
			'manage_options',
			'satelites-sistema-solar',
			array( __CLASS__, 'render_page' ),
			'dashicons-star-filled'
		);
	}

	/**
	 * Procesa la petición de actualización manual desde el panel.
	 */
	public static function handle_manual_refresh() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes.', 'satelites-sistema-solar' ) );
		}

		check_admin_referer( self::ACTION );

		$result = SSS_Data_Store::refresh_data();

		$redirect_args = array(
			'page'   => 'satelites-sistema-solar',
			'sss_ok' => is_wp_error( $result ) ? '0' : '1',
		);

		wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Muestra un aviso tras la actualización manual.
	 */
	public static function show_notices() {
		if ( ! isset( $_GET['page'], $_GET['sss_ok'] ) || 'satelites-sistema-solar' !== $_GET['page'] ) {
			return;
		}

		if ( '1' === $_GET['sss_ok'] ) {
			echo '<div class="notice notice-success is-dismissible"><p>' .
				esc_html__( 'Datos de satélites actualizados correctamente.', 'satelites-sistema-solar' ) .
				'</p></div>';
		} else {
			echo '<div class="notice notice-error is-dismissible"><p>' .
				esc_html__( 'No se pudieron actualizar los datos.', 'satelites-sistema-solar' ) . ' ' .
				esc_html( SSS_Data_Store::get_last_error() ) .
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

		$counts      = SSS_Data_Store::get_counts();
		$planets     = SSS_Data_Store::get_planets();
		$last_update = SSS_Data_Store::get_last_updated();
		$last_error  = SSS_Data_Store::get_last_error();
		$next_run    = wp_next_scheduled( SSS_CRON_HOOK );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Satélites del Sistema Solar', 'satelites-sistema-solar' ); ?></h1>

			<p>
				<?php
				if ( $last_update ) {
					echo esc_html(
						sprintf(
							/* translators: %s: fecha y hora. */
							__( 'Última actualización: %s', 'satelites-sistema-solar' ),
							wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $last_update )
						)
					);
				} else {
					esc_html_e( 'Todavía no se han descargado datos.', 'satelites-sistema-solar' );
				}
				?>
			</p>

			<?php if ( $next_run ) : ?>
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: fecha y hora. */
							__( 'Próxima actualización automática: %s', 'satelites-sistema-solar' ),
							wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_run )
						)
					);
					?>
				</p>
			<?php endif; ?>

			<?php if ( $last_error ) : ?>
				<p class="sss-admin-error" style="color:#b32d2e;">
					<?php echo esc_html( sprintf( /* translators: %s: mensaje de error. */ __( 'Último error: %s', 'satelites-sistema-solar' ), $last_error ) ); ?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $planets ) ) : ?>
				<table class="widefat striped" style="max-width:480px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Planeta', 'satelites-sistema-solar' ); ?></th>
							<th><?php esc_html_e( 'Satélites', 'satelites-sistema-solar' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $planets as $planet ) : ?>
							<tr>
								<td><?php echo esc_html( $planet['name'] ); ?></td>
								<td><?php echo esc_html( $counts['by_planet'][ $planet['id'] ] ?? 0 ); ?></td>
							</tr>
						<?php endforeach; ?>
						<tr>
							<td><strong><?php esc_html_e( 'Total', 'satelites-sistema-solar' ); ?></strong></td>
							<td><strong><?php echo esc_html( $counts['total'] ); ?></strong></td>
						</tr>
					</tbody>
				</table>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:1.5em;">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
				<?php wp_nonce_field( self::ACTION ); ?>
				<?php submit_button( __( 'Actualizar datos ahora', 'satelites-sistema-solar' ) ); ?>
			</form>

			<p class="description">
				<?php esc_html_e( 'Los datos se obtienen automáticamente una vez a la semana desde la API pública api.le-systeme-solaire.net.', 'satelites-sistema-solar' ); ?>
			</p>
		</div>
		<?php
	}
}
