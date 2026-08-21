<?php
/**
 * Panel de administración: listado, alta, edición y borrado de sondas.
 *
 * @package Sondas_Espaciales_Timeline
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SET_Admin_Probes {

	const ACTION_SAVE   = 'set_save_probe';
	const ACTION_DELETE = 'set_delete_probe';
	const ACTION_RESET  = 'set_reset_probes';
	const ACTION_EXPORT = 'set_export_probes';

	const PER_PAGE = 50;

	/**
	 * Engancha los manejadores de formularios.
	 */
	public static function init() {
		add_action( 'admin_post_' . self::ACTION_SAVE, array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_' . self::ACTION_DELETE, array( __CLASS__, 'handle_delete' ) );
		add_action( 'admin_post_' . self::ACTION_RESET, array( __CLASS__, 'handle_reset' ) );
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
			self::render_form_page( sanitize_title( wp_unslash( $_GET['id'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
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

		$messages = array(
			'saved'               => array( __( 'Sonda guardada.', 'sondas-espaciales-timeline' ), 'success' ),
			'deleted'              => array( __( 'Sonda eliminada.', 'sondas-espaciales-timeline' ), 'success' ),
			'reset'                => array( __( 'Se han restaurado los datos de fábrica.', 'sondas-espaciales-timeline' ), 'success' ),
			'reset_needs_confirm'  => array( __( 'Marca la casilla de confirmación para restaurar los datos de fábrica.', 'sondas-espaciales-timeline' ), 'error' ),
		);

		$key = sanitize_key( wp_unslash( $_GET['set_msg'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( isset( $messages[ $key ] ) ) {
			SET_Admin::render_notice( $messages[ $key ][0], $messages[ $key ][1] );
		}
	}

	/**
	 * Listado paginado, filtrable y ordenable de sondas.
	 */
	private static function render_list_page() {
		$search      = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$destination = isset( $_GET['destino'] ) ? sanitize_key( wp_unslash( $_GET['destino'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$active_only = ! empty( $_GET['solo_activas'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby     = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'launch_year'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order       = isset( $_GET['order'] ) && 'desc' === $_GET['order'] ? 'desc' : 'asc'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged       = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$result = SET_Data_Store::query_probes(
			array(
				'search'      => $search,
				'destination' => $destination,
				'active_only' => $active_only,
				'orderby'     => $orderby,
				'order'       => $order,
				'per_page'    => self::PER_PAGE,
				'paged'       => $paged,
			)
		);

		$destinations  = SET_Data_Store::get_destinations();
		$status_labels = SET_Data_Store::get_status_labels();
		$total_probes  = SET_Data_Store::count_probes();

		$base_args = array_filter(
			array(
				'page'         => SET_Admin::PAGE_PROBES,
				's'            => $search,
				'destino'      => $destination,
				'solo_activas' => $active_only ? '1' : '',
			)
		);
		?>
		<div class="wrap set-admin-wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Sondas espaciales', 'sondas-espaciales-timeline' ); ?></h1>
			<a href="<?php echo esc_url( add_query_arg( array( 'page' => SET_Admin::PAGE_PROBES, 'action' => 'add' ), admin_url( 'admin.php' ) ) ); ?>" class="page-title-action"><?php esc_html_e( 'Añadir nueva sonda', 'sondas-espaciales-timeline' ); ?></a>
			<hr class="wp-header-end" />

			<?php self::render_list_notices(); ?>

			<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="set-admin-filters">
				<input type="hidden" name="page" value="<?php echo esc_attr( SET_Admin::PAGE_PROBES ); ?>" />
				<p class="search-box">
					<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Buscar por nombre o agencia…', 'sondas-espaciales-timeline' ); ?>" />
					<select name="destino">
						<option value=""><?php esc_html_e( 'Todos los destinos', 'sondas-espaciales-timeline' ); ?></option>
						<?php foreach ( $destinations as $key => $destination_data ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $destination, $key ); ?>><?php echo esc_html( $destination_data['label'] ); ?></option>
						<?php endforeach; ?>
					</select>
					<label>
						<input type="checkbox" name="solo_activas" value="1" <?php checked( $active_only ); ?> />
						<?php esc_html_e( 'Solo activas', 'sondas-espaciales-timeline' ); ?>
					</label>
					<button type="submit" class="button"><?php esc_html_e( 'Filtrar', 'sondas-espaciales-timeline' ); ?></button>
					<?php if ( $search || $destination || $active_only ) : ?>
						<a href="<?php echo esc_url( add_query_arg( array( 'page' => SET_Admin::PAGE_PROBES ), admin_url( 'admin.php' ) ) ); ?>" class="button-link"><?php esc_html_e( 'Quitar filtros', 'sondas-espaciales-timeline' ); ?></a>
					<?php endif; ?>
				</p>
			</form>

			<p class="set-admin-count">
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: sondas que cumplen el filtro, 2: total de sondas. */
						__( 'Mostrando %1$d de %2$d sondas en total.', 'sondas-espaciales-timeline' ),
						$result['total'],
						$total_probes
					)
				);
				?>
			</p>

			<table class="widefat striped set-admin-table">
				<thead>
					<tr>
						<th></th>
						<th><?php echo self::sort_link( 'name', __( 'Nombre', 'sondas-espaciales-timeline' ), $orderby, $order, $base_args ); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
						<th><?php echo self::sort_link( 'agency', __( 'Agencia', 'sondas-espaciales-timeline' ), $orderby, $order, $base_args ); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
						<th><?php echo self::sort_link( 'destination', __( 'Destino', 'sondas-espaciales-timeline' ), $orderby, $order, $base_args ); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
						<th><?php echo self::sort_link( 'launch_year', __( 'Lanzamiento', 'sondas-espaciales-timeline' ), $orderby, $order, $base_args ); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
						<th><?php echo self::sort_link( 'end_year', __( 'Fin', 'sondas-espaciales-timeline' ), $orderby, $order, $base_args ); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
						<th><?php echo self::sort_link( 'status', __( 'Estado', 'sondas-espaciales-timeline' ), $orderby, $order, $base_args ); // phpcs:ignore WordPress.Security.EscapeOutput ?></th>
						<th><?php esc_html_e( 'Acciones', 'sondas-espaciales-timeline' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $result['items'] ) ) : ?>
						<tr>
							<td colspan="8"><?php esc_html_e( 'No hay sondas que coincidan con el filtro.', 'sondas-espaciales-timeline' ); ?></td>
						</tr>
					<?php endif; ?>
					<?php foreach ( $result['items'] as $probe ) :
						$destination_data = $destinations[ $probe['destination'] ] ?? array(
							'label' => $probe['destination'],
							'code'  => '?',
							'color' => '#888888',
						);
						$edit_url   = add_query_arg( array( 'page' => SET_Admin::PAGE_PROBES, 'action' => 'edit', 'id' => $probe['id'] ), admin_url( 'admin.php' ) );
						$delete_url = wp_nonce_url(
							add_query_arg( array( 'action' => self::ACTION_DELETE, 'id' => $probe['id'] ), admin_url( 'admin-post.php' ) ),
							self::ACTION_DELETE . '_' . $probe['id']
						);
						?>
						<tr>
							<td><span class="set-admin-badge" style="--set-badge-color: <?php echo esc_attr( $destination_data['color'] ); ?>"><?php echo esc_html( $destination_data['code'] ); ?></span></td>
							<td><a href="<?php echo esc_url( $edit_url ); ?>"><strong><?php echo esc_html( $probe['name'] ); ?></strong></a></td>
							<td><?php echo esc_html( $probe['agency'] ); ?></td>
							<td><?php echo esc_html( $destination_data['label'] ); ?></td>
							<td><?php echo (int) $probe['launch_year']; ?></td>
							<td><?php echo $probe['end_year'] ? (int) $probe['end_year'] : '—'; ?></td>
							<td><?php echo esc_html( $status_labels[ $probe['status'] ] ?? $probe['status'] ); ?></td>
							<td>
								<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Editar', 'sondas-espaciales-timeline' ); ?></a>
								|
								<a href="<?php echo esc_url( $delete_url ); ?>" class="set-admin-delete-link" data-confirm="<?php echo esc_attr( sprintf( /* translators: %s: nombre de la sonda. */ __( '¿Borrar la sonda «%s»? Esta acción no se puede deshacer.', 'sondas-espaciales-timeline' ), $probe['name'] ) ); ?>"><?php esc_html_e( 'Borrar', 'sondas-espaciales-timeline' ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $result['pages'] > 1 ) : ?>
				<p class="set-admin-pagination">
					<?php for ( $page_number = 1; $page_number <= $result['pages']; $page_number++ ) :
						$page_url = add_query_arg( array_merge( $base_args, array( 'orderby' => $orderby, 'order' => $order, 'paged' => $page_number ) ), admin_url( 'admin.php' ) );
						?>
						<?php if ( $page_number === $paged ) : ?>
							<strong><?php echo (int) $page_number; ?></strong>
						<?php else : ?>
							<a href="<?php echo esc_url( $page_url ); ?>"><?php echo (int) $page_number; ?></a>
						<?php endif; ?>
					<?php endfor; ?>
				</p>
			<?php endif; ?>

			<hr />

			<div class="set-admin-tools">
				<h2><?php esc_html_e( 'Herramientas', 'sondas-espaciales-timeline' ); ?></h2>

				<p>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => self::ACTION_EXPORT ), admin_url( 'admin-post.php' ) ), self::ACTION_EXPORT ) ); ?>">
						<?php esc_html_e( 'Exportar todas las sondas a PHP', 'sondas-espaciales-timeline' ); ?>
					</a>
					<span class="description"><?php esc_html_e( 'Descarga un fichero en el mismo formato que includes/data/probes.php, para guardar una copia en el repositorio si quieres.', 'sondas-espaciales-timeline' ); ?></span>
				</p>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="set-admin-reset-form">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_RESET ); ?>" />
					<?php wp_nonce_field( self::ACTION_RESET ); ?>
					<label>
						<input type="checkbox" name="confirm" value="yes" required />
						<?php esc_html_e( 'Sí, quiero descartar mis cambios y restaurar sondas y destinos a los valores de fábrica del plugin.', 'sondas-espaciales-timeline' ); ?>
					</label>
					<?php submit_button( __( 'Restaurar valores de fábrica', 'sondas-espaciales-timeline' ), 'delete', 'submit', false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Formulario de alta/edición de una sonda.
	 *
	 * @param string|null $id Id de la sonda a editar, o null para dar de alta una nueva.
	 */
	private static function render_form_page( $id ) {
		$is_edit = null !== $id;
		$probe   = $is_edit ? SET_Data_Store::get_probe( $id ) : null;

		if ( $is_edit && ! $probe ) {
			echo '<div class="wrap"><p>' . esc_html__( 'Esa sonda ya no existe.', 'sondas-espaciales-timeline' ) . '</p></div>';
			return;
		}

		$values = $probe ? $probe : array(
			'id'          => '',
			'name'        => '',
			'agency'      => '',
			'destination' => '',
			'launch_year' => '',
			'end_year'    => '',
			'status'      => 'finalizada',
			'note'        => '',
		);
		$errors = array();

		$pending = get_transient( 'set_probe_form_' . get_current_user_id() );
		if ( is_array( $pending ) && ( $pending['original_id'] ?? '' ) === (string) $id ) {
			$values = array_merge( $values, $pending['values'] );
			$errors = $pending['errors'];
			delete_transient( 'set_probe_form_' . get_current_user_id() );
		}

		$destinations  = SET_Data_Store::get_destinations();
		$status_labels = SET_Data_Store::get_status_labels();
		?>
		<div class="wrap set-admin-wrap">
			<h1><?php echo $is_edit ? esc_html__( 'Editar sonda', 'sondas-espaciales-timeline' ) : esc_html__( 'Añadir sonda', 'sondas-espaciales-timeline' ); ?></h1>

			<?php if ( $errors ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( implode( ' ', $errors ) ); ?></p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="set-admin-form">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_SAVE ); ?>" />
				<input type="hidden" name="original_id" value="<?php echo esc_attr( $is_edit ? $id : '' ); ?>" />
				<?php wp_nonce_field( self::ACTION_SAVE ); ?>

				<table class="form-table" role="presentation">
					<tr>
						<th><label for="set-id"><?php esc_html_e( 'Identificador', 'sondas-espaciales-timeline' ); ?></label></th>
						<td>
							<?php if ( $is_edit ) : ?>
								<input type="text" value="<?php echo esc_attr( $values['id'] ); ?>" class="regular-text" disabled="disabled" />
								<input type="hidden" name="id" value="<?php echo esc_attr( $values['id'] ); ?>" />
							<?php else : ?>
								<input type="text" id="set-id" name="id" value="<?php echo esc_attr( $values['id'] ); ?>" class="regular-text" pattern="[a-z0-9]+(-[a-z0-9]+)*" required />
								<p class="description"><?php esc_html_e( 'Solo minúsculas, números y guiones (p. ej. "voyager-1"). No se puede cambiar después.', 'sondas-espaciales-timeline' ); ?></p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th><label for="set-name"><?php esc_html_e( 'Nombre', 'sondas-espaciales-timeline' ); ?></label></th>
						<td><input type="text" id="set-name" name="name" value="<?php echo esc_attr( $values['name'] ); ?>" class="regular-text" required /></td>
					</tr>
					<tr>
						<th><label for="set-agency"><?php esc_html_e( 'Agencia', 'sondas-espaciales-timeline' ); ?></label></th>
						<td><input type="text" id="set-agency" name="agency" value="<?php echo esc_attr( $values['agency'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th><label for="set-destination"><?php esc_html_e( 'Destino', 'sondas-espaciales-timeline' ); ?></label></th>
						<td>
							<select id="set-destination" name="destination" required>
								<option value=""><?php esc_html_e( '— Selecciona —', 'sondas-espaciales-timeline' ); ?></option>
								<?php foreach ( $destinations as $key => $destination_data ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $values['destination'], $key ); ?>><?php echo esc_html( $destination_data['label'] ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="set-launch-year"><?php esc_html_e( 'Año de lanzamiento', 'sondas-espaciales-timeline' ); ?></label></th>
						<td><input type="number" id="set-launch-year" name="launch_year" value="<?php echo esc_attr( $values['launch_year'] ); ?>" class="small-text" min="1957" max="<?php echo esc_attr( (int) current_time( 'Y' ) + 15 ); ?>" required /></td>
					</tr>
					<tr>
						<th><label for="set-status"><?php esc_html_e( 'Estado', 'sondas-espaciales-timeline' ); ?></label></th>
						<td>
							<select id="set-status" name="status" required>
								<?php foreach ( $status_labels as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $values['status'], $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr class="set-admin-end-year-row">
						<th><label for="set-end-year"><?php esc_html_e( 'Año de fin', 'sondas-espaciales-timeline' ); ?></label></th>
						<td>
							<input type="number" id="set-end-year" name="end_year" value="<?php echo esc_attr( $values['end_year'] ); ?>" class="small-text" min="1957" max="2100" />
							<p class="description"><?php esc_html_e( 'Déjalo en blanco si el estado es "Activa": la línea de tiempo dibujará la barra hasta hoy.', 'sondas-espaciales-timeline' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="set-note"><?php esc_html_e( 'Nota', 'sondas-espaciales-timeline' ); ?></label></th>
						<td><textarea id="set-note" name="note" rows="3" class="large-text"><?php echo esc_textarea( $values['note'] ); ?></textarea></td>
					</tr>
				</table>

				<?php submit_button( __( 'Guardar sonda', 'sondas-espaciales-timeline' ) ); ?>
				<a href="<?php echo esc_url( add_query_arg( array( 'page' => SET_Admin::PAGE_PROBES ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Cancelar', 'sondas-espaciales-timeline' ); ?></a>
			</form>
		</div>
		<?php
	}

	/**
	 * Enlace ordenable de una cabecera de columna.
	 *
	 * @param string $column          Clave de la columna.
	 * @param string $label           Etiqueta visible.
	 * @param string $current_orderby Columna de orden actual.
	 * @param string $current_order   Dirección actual (asc|desc).
	 * @param array  $base_args       Argumentos de query a preservar (filtros).
	 * @return string
	 */
	private static function sort_link( $column, $label, $current_orderby, $current_order, array $base_args ) {
		$next_order = ( $current_orderby === $column && 'asc' === $current_order ) ? 'desc' : 'asc';
		$url        = add_query_arg( array_merge( $base_args, array( 'orderby' => $column, 'order' => $next_order ) ), admin_url( 'admin.php' ) );
		$indicator  = '';

		if ( $current_orderby === $column ) {
			$indicator = 'asc' === $current_order ? ' ▲' : ' ▼';
		}

		return '<a href="' . esc_url( $url ) . '">' . esc_html( $label . $indicator ) . '</a>';
	}

	/**
	 * Procesa el guardado (alta o edición) de una sonda.
	 */
	public static function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes.', 'sondas-espaciales-timeline' ) );
		}

		check_admin_referer( self::ACTION_SAVE );

		$original_id = isset( $_POST['original_id'] ) ? sanitize_title( wp_unslash( $_POST['original_id'] ) ) : '';
		$is_edit     = '' !== $original_id;

		$id          = sanitize_title( wp_unslash( $_POST['id'] ?? '' ) );
		$name        = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$agency      = sanitize_text_field( wp_unslash( $_POST['agency'] ?? '' ) );
		$destination = sanitize_key( wp_unslash( $_POST['destination'] ?? '' ) );
		$launch_year = isset( $_POST['launch_year'] ) ? (int) $_POST['launch_year'] : 0;
		$status      = sanitize_key( wp_unslash( $_POST['status'] ?? '' ) );
		$end_year_in = isset( $_POST['end_year'] ) ? trim( wp_unslash( $_POST['end_year'] ) ) : '';
		$end_year    = ( '' === $end_year_in ) ? null : (int) $end_year_in;
		$note        = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );

		if ( 'activa' === $status ) {
			$end_year = null;
		}

		$destinations = SET_Data_Store::get_destinations();
		$errors       = array();

		if ( '' === $id ) {
			$errors[] = __( 'El identificador es obligatorio.', 'sondas-espaciales-timeline' );
		} elseif ( ! $is_edit && SET_Data_Store::get_probe( $id ) ) {
			$errors[] = __( 'Ya existe una sonda con ese identificador.', 'sondas-espaciales-timeline' );
		}

		if ( '' === $name ) {
			$errors[] = __( 'El nombre es obligatorio.', 'sondas-espaciales-timeline' );
		}

		if ( ! isset( $destinations[ $destination ] ) ) {
			$errors[] = __( 'Selecciona un destino válido.', 'sondas-espaciales-timeline' );
		}

		if ( $launch_year < 1957 || $launch_year > ( (int) current_time( 'Y' ) + 15 ) ) {
			$errors[] = __( 'El año de lanzamiento no parece válido.', 'sondas-espaciales-timeline' );
		}

		if ( ! in_array( $status, SET_Data_Store::STATUSES, true ) ) {
			$errors[] = __( 'Selecciona un estado válido.', 'sondas-espaciales-timeline' );
		} elseif ( 'activa' !== $status && null === $end_year ) {
			$errors[] = __( 'Indica el año de fin, o cambia el estado a "Activa".', 'sondas-espaciales-timeline' );
		}

		if ( null !== $end_year && $end_year < $launch_year ) {
			$errors[] = __( 'El año de fin no puede ser anterior al de lanzamiento.', 'sondas-espaciales-timeline' );
		}

		$values = compact( 'id', 'name', 'agency', 'destination', 'launch_year', 'end_year', 'status', 'note' );

		if ( $errors ) {
			set_transient(
				'set_probe_form_' . get_current_user_id(),
				array(
					'original_id' => $is_edit ? $original_id : '',
					'errors'      => $errors,
					'values'      => $values,
				),
				60
			);

			$redirect_args = array(
				'page'   => SET_Admin::PAGE_PROBES,
				'action' => $is_edit ? 'edit' : 'add',
			);
			if ( $is_edit ) {
				$redirect_args['id'] = $original_id;
			}

			wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'admin.php' ) ) );
			exit;
		}

		SET_Data_Store::save_probe( $values, $is_edit ? $original_id : null );

		wp_safe_redirect( add_query_arg( array( 'page' => SET_Admin::PAGE_PROBES, 'set_msg' => 'saved' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Procesa el borrado de una sonda.
	 */
	public static function handle_delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes.', 'sondas-espaciales-timeline' ) );
		}

		$id = isset( $_GET['id'] ) ? sanitize_title( wp_unslash( $_GET['id'] ) ) : '';

		check_admin_referer( self::ACTION_DELETE . '_' . $id );

		if ( '' !== $id ) {
			SET_Data_Store::delete_probe( $id );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => SET_Admin::PAGE_PROBES, 'set_msg' => 'deleted' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Restaura sondas y destinos a los valores de fábrica.
	 */
	public static function handle_reset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes.', 'sondas-espaciales-timeline' ) );
		}

		check_admin_referer( self::ACTION_RESET );

		if ( empty( $_POST['confirm'] ) || 'yes' !== $_POST['confirm'] ) {
			wp_safe_redirect( add_query_arg( array( 'page' => SET_Admin::PAGE_PROBES, 'set_msg' => 'reset_needs_confirm' ), admin_url( 'admin.php' ) ) );
			exit;
		}

		SET_Data_Store::reset_to_defaults();

		wp_safe_redirect( add_query_arg( array( 'page' => SET_Admin::PAGE_PROBES, 'set_msg' => 'reset' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Descarga todas las sondas como un fichero PHP, en el mismo formato
	 * que includes/data/probes.php.
	 */
	public static function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'No tienes permisos suficientes.', 'sondas-espaciales-timeline' ) );
		}

		check_admin_referer( self::ACTION_EXPORT );

		nocache_headers();
		header( 'Content-Type: application/x-httpd-php; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="probes-' . gmdate( 'Y-m-d' ) . '.php"' );

		echo self::format_probes_as_php( SET_Data_Store::get_probes() ); // phpcs:ignore WordPress.Security.EscapeOutput

		exit;
	}

	/**
	 * Da formato a un array de sondas como código PHP exportable.
	 *
	 * @param array $probes Sondas a exportar.
	 * @return string
	 */
	private static function format_probes_as_php( array $probes ) {
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

		foreach ( $probes as $probe ) {
			$lines[] = "\tarray(";
			$lines[] = "\t\t'id'          => " . var_export( $probe['id'], true ) . ',';
			$lines[] = "\t\t'name'        => " . var_export( $probe['name'], true ) . ',';
			$lines[] = "\t\t'agency'      => " . var_export( $probe['agency'], true ) . ',';
			$lines[] = "\t\t'destination' => " . var_export( $probe['destination'], true ) . ',';
			$lines[] = "\t\t'launch_year' => " . (int) $probe['launch_year'] . ',';
			$lines[] = "\t\t'end_year'    => " . ( null === $probe['end_year'] ? 'null' : (int) $probe['end_year'] ) . ',';
			$lines[] = "\t\t'status'      => " . var_export( $probe['status'], true ) . ',';
			$lines[] = "\t\t'note'        => " . var_export( $probe['note'], true ) . ',';
			$lines[] = "\t),";
		}

		$lines[] = ');';
		$lines[] = '';

		return implode( "\n", $lines );
	}
}
