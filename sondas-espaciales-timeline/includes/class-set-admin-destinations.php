<?php
/**
 * Panel de administración: listado, alta, edición y borrado de destinos.
 *
 * @package Sondas_Espaciales_Timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SET_Admin_Destinations {

	const ACTION_SAVE   = 'set_save_destination';
	const ACTION_DELETE = 'set_delete_destination';
	const ACTION_EXPORT = 'set_export_destinations';

	/**
	 * Engancha los manejadores de formularios.
	 */
	public static function init() {
		add_action( 'admin_post_' . self::ACTION_SAVE, array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_' . self::ACTION_DELETE, array( __CLASS__, 'handle_delete' ) );
		add_action( 'admin_post_' . self::ACTION_EXPORT, array( __CLASS__, 'handle_export' ) );
	}

	/**
	 * Punto de entrada de la página: decide entre listado y formulario.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'add' === $action ) {
			self::render_form_page( null );
		} elseif ( 'edit' === $action && isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			self::render_form_page( sanitize_key( wp_unslash( $_GET['id'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} else {
			self::render_list_page();
		}
	}

	/**
	 * Mensajes de éxito/error tras redirigir desde un admin_post_*.
	 */
	private static function render_list_notices() {
		if ( ! isset( $_GET['set_msg'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$key = sanitize_key( wp_unslash( $_GET['set_msg'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'saved' === $key ) {
			SET_Admin::render_notice( __( 'Destino guardado.', 'sondas-espaciales-timeline' ), 'success' );
		} elseif ( 'deleted' === $key ) {
			SET_Admin::render_notice( __( 'Destino eliminado.', 'sondas-espaciales-timeline' ), 'success' );
		} elseif ( 0 === strpos( $key, 'in-use-' ) ) {
			$count = (int) substr( $key, strlen( 'in-use-' ) );
			SET_Admin::render_notice(
				sprintf(
					/* translators: %d: número de sondas que usan ese destino. */
					_n(
						'No se puede borrar: hay %d sonda que todavía usa ese destino. Reasígnala primero.',
						'No se puede borrar: hay %d sondas que todavía usan ese destino. Reasígnalas primero.',
						$count,
						'sondas-espaciales-timeline'
					),
					$count
				),
				'error'
			);
		}
	}

	/**
	 * Listado de destinos, con cuántas sondas usa cada uno.
	 */
	private static function render_list_page() {
		$destinations = SET_Data_Store::get_destinations();
		?>
		<div class="wrap set-admin-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Destinos', 'sondas-espaciales-timeline' ); ?></h1>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => SET_Admin::PAGE_DESTINATIONS, 'action' => 'add' ), admin_url( 'admin.php' ) ) ); ?>" class="page-title-action"><?php esc_html_e( 'Añadir nuevo destino', 'sondas-espaciales-timeline' ); ?></a>
			<hr class="wp-header-end" />

			<?php self::render_list_notices(); ?>

			<p class="description"><?php esc_html_e( 'Los destinos que usan las sondas para su icono y el color de su barra en la línea de tiempo.', 'sondas-espaciales-timeline' ); ?></p>

			<table class="widefat striped set-admin-table">
				<thead>
					<tr>
						<th></th>
						<th><?php esc_html_e( 'Identificador', 'sondas-espaciales-timeline' ); ?></th>
						<th><?php esc_html_e( 'Etiqueta', 'sondas-espaciales-timeline' ); ?></th>
						<th><?php esc_html_e( 'Símbolo', 'sondas-espaciales-timeline' ); ?></th>
						<th><?php esc_html_e( 'Color', 'sondas-espaciales-timeline' ); ?></th>
						<th><?php esc_html_e( 'Sondas', 'sondas-espaciales-timeline' ); ?></th>
						<th><?php esc_html_e( 'Acciones', 'sondas-espaciales-timeline' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $destinations ) ) : ?>
						<tr>
							<td colspan="7"><?php esc_html_e( 'Todavía no hay destinos.', 'sondas-espaciales-timeline' ); ?></td>
						</tr>
					<?php endif; ?>
					<?php foreach ( $destinations as $key => $destination ) :
						$probe_count = SET_Data_Store::count_probes_by_destination( $key );
						$edit_url    = add_query_arg( array( 'page' => SET_Admin::PAGE_DESTINATIONS, 'action' => 'edit', 'id' => $key ), admin_url( 'admin.php' ) );
						$delete_url  = wp_nonce_url(
							add_query_arg( array( 'action' => self::ACTION_DELETE, 'id' => $key ), admin_url( 'admin-post.php' ) ),
							self::ACTION_DELETE . '_' . $key
						);
						?>
						<tr>
							<td><span class="set-admin-badge" style="--set-badge-color: <?php echo esc_attr( $destination['color'] ); ?>"><?php echo esc_html( $destination['code'] ); ?></span></td>
							<td><code><?php echo esc_html( $key ); ?></code></td>
							<td><a href="<?php echo esc_url( $edit_url ); ?>"><strong><?php echo esc_html( $destination['label'] ); ?></strong></a></td>
							<td><?php echo esc_html( $destination['code'] ); ?></td>
							<td>
								<span class="set-admin-swatch" style="background-color: <?php echo esc_attr( $destination['color'] ); ?>"></span>
								<?php echo esc_html( $destination['color'] ); ?>
							</td>
							<td>
								<?php echo (int) $probe_count; ?>
								<?php if ( $probe_count > 0 ) : ?>
									<a href="<?php echo esc_url( add_query_arg( array( 'page' => SET_Admin::PAGE_PROBES, 'destino' => $key ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( '(ver)', 'sondas-espaciales-timeline' ); ?></a>
								<?php endif; ?>
							</td>
							<td>
								<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Editar', 'sondas-espaciales-timeline' ); ?></a>
								<?php if ( 0 === $probe_count ) : ?>
									|
									<a href="<?php echo esc_url( $delete_url ); ?>" class="set-admin-delete-link" data-confirm="<?php echo esc_attr( sprintf( /* translators: %s: etiqueta del destino. */ __( '¿Borrar el destino «%s»?', 'sondas-espaciales-timeline' ), $destination['label'] ) ); ?>"><?php esc_html_e( 'Borrar', 'sondas-espaciales-timeline' ); ?></a>
								<?php else : ?>
									| <span class="description"><?php esc_html_e( 'en uso', 'sondas-espaciales-timeline' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p style="margin-top:1em;">
				<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => self::ACTION_EXPORT ), admin_url( 'admin-post.php' ) ), self::ACTION_EXPORT ) ); ?>">
					<?php esc_html_e( 'Exportar destinos a PHP', 'sondas-espaciales-timeline' ); ?>
				</a>
				<span class="description"><?php esc_html_e( 'Para guardar una copia en includes/data/destinations.php si quieres.', 'sondas-espaciales-timeline' ); ?></span>
			</p>
			<p class="description">
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %s: enlace a la pantalla de sondas. */
						__( 'Para restaurar los destinos de fábrica, usa el botón correspondiente en la pantalla de <a href="%s">Sondas</a>.', 'sondas-espaciales-timeline' ),
						esc_url( add_query_arg( array( 'page' => SET_Admin::PAGE_PROBES ), admin_url( 'admin.php' ) ) )
					),
					array( 'a' => array( 'href' => true ) )
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Formulario de alta/edición de un destino.
	 *
	 * @param string|null $id Clave del destino a editar, o null para uno nuevo.
	 */
	private static function render_form_page( $id ) {
		$is_edit     = null !== $id;
		$destination = $is_edit ? SET_Data_Store::get_destination( $id ) : null;

		if ( $is_edit && ! $destination ) {
			echo '<div class="wrap"><p>' . esc_html__( 'Ese destino ya no existe.', 'sondas-espaciales-timeline' ) . '</p></div>';
			return;
		}

		$values = array(
			'id'    => $is_edit ? $id : '',
			'label' => $destination ? $destination['label'] : '',
			'code'  => $destination ? $destination['code'] : '',
			'color' => $destination ? $destination['color'] : '#888888',
		);
		$errors = array();

		$pending = get_transient( 'set_destination_form_' . get_current_user_id() );
		if ( is_array( $pending ) && ( $pending['original_id'] ?? '' ) === (string) $id ) {
			$values = array_merge( $values, $pending['values'] );
			$errors = $pending['errors'];
			delete_transient( 'set_destination_form_' . get_current_user_id() );
		}
		?>
		<div class="wrap set-admin-wrap">
			<h1><?php echo $is_edit ? esc_html__( 'Editar destino', 'sondas-espaciales-timeline' ) : esc_html__( 'Añadir destino', 'sondas-espaciales-timeline' ); ?></h1>

			<?php if ( $errors ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( implode( ' ', $errors ) ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="set-admin-form">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_SAVE ); ?>" />
				<input type="hidden" name="original_id" value="<?php echo esc_attr( $is_edit ? $id : '' ); ?>" />
				<?php wp_nonce_field( self::ACTION_SAVE ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th><label for="set-dest-id"><?php esc_html_e( 'Identificador', 'sondas-espaciales-timeline' ); ?></label></th>
						<td>
							<input type="text" id="set-dest-id" name="id" value="<?php echo esc_attr( $values['id'] ); ?>" class="regular-text" pattern="[a-z0-9]+(-[a-z0-9]+)*" required />
							<p class="description"><?php esc_html_e( 'Solo minúsculas, números y guiones (p. ej. "marte"). Si lo cambias, las sondas que usen este destino se reasignan automáticamente.', 'sondas-espaciales-timeline' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="set-dest-label"><?php esc_html_e( 'Etiqueta', 'sondas-espaciales-timeline' ); ?></label></th>
						<td><input type="text" id="set-dest-label" name="label" value="<?php echo esc_attr( $values['label'] ); ?>" class="regular-text" required /></td>
					</tr>
					<tr>
						<th><label for="set-dest-code"><?php esc_html_e( 'Símbolo', 'sondas-espaciales-timeline' ); ?></label></th>
						<td>
							<input type="text" id="set-dest-code" name="code" value="<?php echo esc_attr( $values['code'] ); ?>" class="small-text" maxlength="4" required />
							<p class="description"><?php esc_html_e( 'Un carácter o símbolo corto que se ve dentro del icono redondo (p. ej. ♂, ☾, ◆).', 'sondas-espaciales-timeline' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="set-dest-color"><?php esc_html_e( 'Color', 'sondas-espaciales-timeline' ); ?></label></th>
						<td><input type="text" id="set-dest-color" name="color" value="<?php echo esc_attr( $values['color'] ); ?>" class="set-admin-color-field" data-default-color="#888888" /></td>
					</tr>
				</table>

				<?php submit_button( __( 'Guardar destino', 'sondas-espaciales-timeline' ) ); ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => SET_Admin::PAGE_DESTINATIONS ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Cancelar', 'sondas-espaciales-timeline' ); ?></a>
			</form>
		</div>
		<?php
	}

	/**
	 * Procesa el guardado (alta o edición) de un destino.
	 */
	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes.', 'sondas-espaciales-timeline' ) );
		}

		check_admin_referer( self::ACTION_SAVE );

		$original_id = isset( $_POST['original_id'] ) ? sanitize_key( wp_unslash( $_POST['original_id'] ) ) : '';
		$is_edit     = '' !== $original_id;

		$id    = sanitize_key( wp_unslash( $_POST['id'] ?? '' ) );
		$label = sanitize_text_field( wp_unslash( $_POST['label'] ?? '' ) );
		$code  = sanitize_text_field( wp_unslash( $_POST['code'] ?? '' ) );
		$color = sanitize_hex_color( wp_unslash( $_POST['color'] ?? '' ) );

		$errors = array();

		if ( '' === $id ) {
			$errors[] = __( 'El identificador es obligatorio.', 'sondas-espaciales-timeline' );
		} elseif ( ( ! $is_edit || $id !== $original_id ) && SET_Data_Store::get_destination( $id ) ) {
			$errors[] = __( 'Ya existe un destino con ese identificador.', 'sondas-espaciales-timeline' );
		}

		if ( '' === $label ) {
			$errors[] = __( 'La etiqueta es obligatoria.', 'sondas-espaciales-timeline' );
		}

		if ( '' === $code ) {
			$errors[] = __( 'El símbolo es obligatorio.', 'sondas-espaciales-timeline' );
		}

		if ( ! $color ) {
			$errors[] = __( 'El color no es un color hexadecimal válido.', 'sondas-espaciales-timeline' );
		}

		$values = compact( 'id', 'label', 'code', 'color' );

		if ( $errors ) {
			set_transient(
				'set_destination_form_' . get_current_user_id(),
				array(
					'original_id' => $is_edit ? $original_id : '',
					'errors'      => $errors,
					'values'      => $values,
				),
				60
			);

			$redirect_args = array(
				'page'   => SET_Admin::PAGE_DESTINATIONS,
				'action' => $is_edit ? 'edit' : 'add',
			);
			if ( $is_edit ) {
				$redirect_args['id'] = $original_id;
			}

			wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
			exit;
		}

		SET_Data_Store::save_destination( $id, $values, $is_edit ? $original_id : null );

		wp_safe_redirect( add_query_arg( array( 'page' => SET_Admin::PAGE_DESTINATIONS, 'set_msg' => 'saved' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Procesa el borrado de un destino (bloqueado si alguna sonda lo usa).
	 */
	public static function handle_delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes.', 'sondas-espaciales-timeline' ) );
		}

		$id = isset( $_GET['id'] ) ? sanitize_key( wp_unslash( $_GET['id'] ) ) : '';

		check_admin_referer( self::ACTION_DELETE . '_' . $id );

		$in_use = '' !== $id ? SET_Data_Store::delete_destination( $id ) : 0;

		$msg = $in_use > 0 ? 'in-use-' . $in_use : 'deleted';

		wp_safe_redirect( add_query_arg( array( 'page' => SET_Admin::PAGE_DESTINATIONS, 'set_msg' => $msg ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Descarga el catálogo de destinos como un fichero PHP, en el mismo
	 * formato que includes/data/destinations.php.
	 */
	public static function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes.', 'sondas-espaciales-timeline' ) );
		}

		check_admin_referer( self::ACTION_EXPORT );

		nocache_headers();
		header( 'Content-Type: application/x-httpd-php; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="destinations-' . gmdate( 'Y-m-d' ) . '.php"' );

		echo self::format_destinations_as_php( SET_Data_Store::get_destinations() ); // phpcs:ignore WordPress.Security.EscapeOutput

		exit;
	}

	/**
	 * Da formato al catálogo de destinos como código PHP exportable.
	 *
	 * @param array $destinations Destinos a exportar.
	 * @return string
	 */
	private static function format_destinations_as_php( array $destinations ) {
		$lines   = array();
		$lines[] = '<?php';
		$lines[] = '/**';
		$lines[] = ' * Exportado desde el panel de administración de "Sondas Espaciales - Línea de Tiempo"';
		$lines[] = ' * el ' . gmdate( 'Y-m-d H:i' ) . ' UTC.';
		$lines[] = ' */';
		$lines[] = '';
		$lines[] = "if ( ! defined( 'ABSPATH' ) ) {";
		$lines[] = "\texit;";
		$lines[] = '}';
		$lines[] = '';
		$lines[] = 'return array(';

		foreach ( $destinations as $key => $destination ) {
			$lines[] = "\t" . var_export( $key, true ) . ' => array(';
			$lines[] = "\t\t'label' => " . var_export( $destination['label'], true ) . ',';
			$lines[] = "\t\t'code'  => " . var_export( $destination['code'], true ) . ',';
			$lines[] = "\t\t'color' => " . var_export( $destination['color'], true ) . ',';
			$lines[] = "\t),";
		}

		$lines[] = ');';
		$lines[] = '';

		return implode( "\n", $lines );
	}
}
