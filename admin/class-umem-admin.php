<?php
/**
 * Admin controller.
 *
 * Builds the admin menu, enqueues admin assets, routes the admin pages to
 * their view files, and processes the membership-level create/edit/delete
 * form submissions (all nonce + capability checked).
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UMEM_Admin
 */
class UMEM_Admin {

	/**
	 * Top-level menu slug.
	 */
	const MENU_SLUG = 'umem-dashboard';

	/**
	 * Capability required for management screens.
	 */
	const CAP = 'umem_manage_memberships';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_menu_style' ) );
		add_action( 'admin_notices', array( $this, 'render_flash_notices' ) );
		add_action( 'admin_post_umem_save_level', array( $this, 'handle_save_level' ) );
		add_action( 'admin_post_umem_add_member', array( $this, 'handle_add_member' ) );
		add_action( 'admin_post_umem_delete_level', array( $this, 'handle_delete_level' ) );
		add_action( 'admin_post_umem_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'wp_ajax_umem_search_users', array( $this, 'ajax_search_users' ) );
	}

	/**
	 * Enqueue the gold admin-menu stylesheet on every admin screen.
	 *
	 * The menu item appears site-wide in wp-admin, so its brand style must be
	 * loaded globally — and via an enqueued stylesheet, never inline markup.
	 *
	 * @return void
	 */
	public function enqueue_menu_style() {
		wp_enqueue_style(
			'umem-admin-menu',
			UMEM_PLUGIN_URL . 'assets/css/umem-admin-menu.css',
			array(),
			UMEM_VERSION
		);
	}

	/**
	 * Build the admin menu tree.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'Memberships', 'unstoppable-memberships' ),
			__( 'Memberships', 'unstoppable-memberships' ),
			self::CAP,
			self::MENU_SLUG,
			array( $this, 'route' ),
			'dashicons-id-alt',
			56
		);

		$subpages = array(
			self::MENU_SLUG => __( 'Dashboard', 'unstoppable-memberships' ),
			'umem-levels'   => __( 'Membership Levels', 'unstoppable-memberships' ),
			'umem-members'  => __( 'Members', 'unstoppable-memberships' ),
			'umem-reports'  => __( 'Reports', 'unstoppable-memberships' ),
			'umem-settings' => __( 'Settings', 'unstoppable-memberships' ),
			'umem-tools'    => __( 'Tools', 'unstoppable-memberships' ),
		);

		foreach ( $subpages as $slug => $title ) {
			add_submenu_page(
				self::MENU_SLUG,
				$title,
				$title,
				self::CAP,
				$slug,
				array( $this, 'route' )
			);
		}
	}

	/* ----------------------------------------------------------------------
	 * Flash notices (contextual success/error on our own screens only)
	 * ------------------------------------------------------------------- */

	/**
	 * Queue a one-time notice for the next page load.
	 *
	 * @param string $message Message text.
	 * @param string $type    success|error|warning|info.
	 * @return void
	 */
	public static function add_flash_notice( $message, $type = 'success' ) {
		$notices = get_transient( 'umem_flash_notices' );
		if ( ! is_array( $notices ) ) {
			$notices = array();
		}
		$notices[] = array(
			'message' => $message,
			'type'    => $type,
		);
		set_transient( 'umem_flash_notices', $notices, 60 );
	}

	/**
	 * Render queued flash notices, only on this plugin's own screens.
	 *
	 * @return void
	 */
	public function render_flash_notices() {
		$screen = get_current_screen();
		if ( ! $screen || false === strpos( $screen->id, 'umem' ) ) {
			return;
		}
		$notices = get_transient( 'umem_flash_notices' );
		if ( empty( $notices ) || ! is_array( $notices ) ) {
			return;
		}
		$allowed = array( 'success', 'error', 'warning', 'info' );
		foreach ( $notices as $notice ) {
			$type = ( isset( $notice['type'] ) && in_array( $notice['type'], $allowed, true ) ) ? $notice['type'] : 'info';
			$msg  = isset( $notice['message'] ) ? $notice['message'] : '';
			if ( '' === $msg ) {
				continue;
			}
			printf( '<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>', esc_attr( $type ), esc_html( $msg ) );
		}
		delete_transient( 'umem_flash_notices' );
	}

	/**
	 * Map the current page slug to a view file and render it.
	 *
	 * @return void
	 */
	public function route() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'unstoppable-memberships' ) );
		}

		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : self::MENU_SLUG; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing.

		$map = array(
			self::MENU_SLUG => 'dashboard',
			'umem-levels'   => 'levels',
			'umem-members'  => 'members',
			'umem-reports'  => 'reports',
			'umem-settings' => 'settings',
			'umem-tools'    => 'tools',
		);

		$view = isset( $map[ $page ] ) ? $map[ $page ] : 'dashboard';

		// Levels page has an edit sub-view.
		if ( 'levels' === $view ) {
			$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( in_array( $action, array( 'new', 'edit' ), true ) ) {
				$view = 'level-edit';
			}
		}

		// Members page has an add sub-view.
		if ( 'members' === $view ) {
			$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'add' === $action ) {
				$view = 'member-add';
			}
		}

		$file = UMEM_PLUGIN_DIR . 'admin/views/' . $view . '.php';
		if ( file_exists( $file ) ) {
			include $file;
		} else {
			echo '<div class="wrap"><h1>' . esc_html__( 'Coming soon', 'unstoppable-memberships' ) . '</h1></div>';
		}
	}

	/**
	 * Enqueue admin CSS/JS only on our pages.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'umem' ) ) {
			return;
		}

		wp_enqueue_style(
			'umem-admin',
			UMEM_PLUGIN_URL . 'assets/css/umem-admin.css',
			array(),
			UMEM_VERSION
		);

		wp_enqueue_script(
			'umem-admin',
			UMEM_PLUGIN_URL . 'assets/js/umem-admin.js',
			array( 'jquery', 'jquery-ui-autocomplete' ),
			UMEM_VERSION,
			true
		);

		wp_localize_script(
			'umem-admin',
			'umemAdmin',
			array(
				'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
				'userSearchNonce' => wp_create_nonce( 'umem_search_users' ),
				'selectedLabel'   => __( 'Selected:', 'unstoppable-memberships' ),
			)
		);
	}

	/* ----------------------------------------------------------------------
	 * Form handlers
	 * ------------------------------------------------------------------- */

	/**
	 * Handle the membership-level save form.
	 *
	 * @return void
	 */
	public function handle_save_level() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'unstoppable-memberships' ) );
		}
		check_admin_referer( 'umem_save_level' );

		$id   = isset( $_POST['level_id'] ) ? absint( $_POST['level_id'] ) : 0;
		$data = isset( $_POST['umem_level'] ) ? wp_unslash( $_POST['umem_level'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized inside model.

		$result = UMEM_Levels::save( $data, $id );

		if ( is_wp_error( $result ) ) {
			$redirect = add_query_arg(
				array(
					'page'      => 'umem-levels',
					'action'    => $id ? 'edit' : 'new',
					'level'     => $id,
					'umem_msg'  => 'error',
				),
				admin_url( 'admin.php' )
			);
		} else {
			$redirect = add_query_arg(
				array(
					'page'     => 'umem-levels',
					'umem_msg' => 'saved',
				),
				admin_url( 'admin.php' )
			);
		}

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handle the Add Member form: resolve/create the user, then create a
	 * subscription row for them.
	 *
	 * @return void
	 */
	public function handle_add_member() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'unstoppable-memberships' ) );
		}
		check_admin_referer( 'umem_add_member' );

		$raw = isset( $_POST['umem_member'] ) ? wp_unslash( $_POST['umem_member'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized field-by-field below.

		$back_to_form = add_query_arg(
			array(
				'page'   => 'umem-members',
				'action' => 'add',
			),
			admin_url( 'admin.php' )
		);

		// Resolve the target user.
		$mode    = isset( $raw['user_mode'] ) && 'new' === $raw['user_mode'] ? 'new' : 'existing';
		$user_id = 0;

		if ( 'existing' === $mode ) {
			$user_id = isset( $raw['user_id'] ) ? absint( $raw['user_id'] ) : 0;
			if ( ! $user_id || ! get_userdata( $user_id ) ) {
				self::add_flash_notice( __( 'Please select a valid user.', 'unstoppable-memberships' ), 'error' );
				wp_safe_redirect( $back_to_form );
				exit;
			}
		} else {
			$username = isset( $raw['username'] ) ? sanitize_user( $raw['username'], true ) : '';
			$email    = isset( $raw['email'] ) ? sanitize_email( $raw['email'] ) : '';

			if ( empty( $username ) || ! is_email( $email ) ) {
				self::add_flash_notice( __( 'A username and a valid email are required to create a user.', 'unstoppable-memberships' ), 'error' );
				wp_safe_redirect( $back_to_form );
				exit;
			}
			if ( username_exists( $username ) || email_exists( $email ) ) {
				self::add_flash_notice( __( 'That username or email already exists. Pick the existing user instead.', 'unstoppable-memberships' ), 'error' );
				wp_safe_redirect( $back_to_form );
				exit;
			}

			$user_id = wp_insert_user(
				array(
					'user_login' => $username,
					'user_email' => $email,
					'user_pass'  => wp_generate_password( 20 ),
					'first_name' => isset( $raw['first_name'] ) ? sanitize_text_field( $raw['first_name'] ) : '',
					'last_name'  => isset( $raw['last_name'] ) ? sanitize_text_field( $raw['last_name'] ) : '',
					'role'       => 'subscriber',
				)
			);

			if ( is_wp_error( $user_id ) ) {
				self::add_flash_notice( $user_id->get_error_message(), 'error' );
				wp_safe_redirect( $back_to_form );
				exit;
			}

			if ( ! empty( $raw['notify'] ) ) {
				wp_new_user_notification( $user_id, null, 'user' );
			}
		}

		// Validate the level.
		$level_id = isset( $raw['level_id'] ) ? absint( $raw['level_id'] ) : 0;
		if ( ! $level_id || ! UMEM_Levels::get( $level_id ) ) {
			self::add_flash_notice( __( 'Please choose a valid membership level.', 'unstoppable-memberships' ), 'error' );
			wp_safe_redirect( $back_to_form );
			exit;
		}

		// Normalise dates (date inputs -> MySQL datetimes).
		$start   = isset( $raw['start_date'] ) ? sanitize_text_field( $raw['start_date'] ) : '';
		$expires = isset( $raw['expires'] ) ? sanitize_text_field( $raw['expires'] ) : '';
		$start_dt   = $start ? gmdate( 'Y-m-d 00:00:00', strtotime( $start ) ) : current_time( 'mysql' );
		$expires_dt = $expires ? gmdate( 'Y-m-d 23:59:59', strtotime( $expires ) ) : '';

		$status  = isset( $raw['status'] ) ? sanitize_key( $raw['status'] ) : 'active';
		$allowed = array( 'active', 'trialing', 'paused', 'cancelled', 'expired' );
		if ( ! in_array( $status, $allowed, true ) ) {
			$status = 'active';
		}

		umem()->subscriptions->save(
			array(
				'user_id'            => $user_id,
				'level_id'           => $level_id,
				'status'             => $status,
				'gateway'            => 'manual',
				'start_date'         => $start_dt,
				'current_period_end' => $expires_dt,
			)
		);

		/**
		 * Fires after an admin manually adds a member.
		 *
		 * @param int $user_id  User ID.
		 * @param int $level_id Level ID.
		 */
		do_action( 'umem_member_added', $user_id, $level_id );

		self::add_flash_notice( __( 'Member added.', 'unstoppable-memberships' ), 'success' );
		wp_safe_redirect(
			add_query_arg( array( 'page' => 'umem-members' ), admin_url( 'admin.php' ) )
		);
		exit;
	}

	/**
	 * AJAX: search users for the Add Member autocomplete.
	 *
	 * Returns up to 20 matches on display name, username, or email.
	 *
	 * @return void
	 */
	public function ajax_search_users() {
		check_ajax_referer( 'umem_search_users', 'nonce' );

		if ( ! current_user_can( self::CAP ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'unstoppable-memberships' ) ) );
		}

		$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
		if ( strlen( $term ) < 2 ) {
			wp_send_json_success( array() );
		}

		$users = get_users(
			array(
				'search'         => '*' . $term . '*',
				'search_columns' => array( 'user_login', 'user_email', 'user_nicename', 'display_name' ),
				'number'         => 20,
				'orderby'        => 'display_name',
				'fields'         => array( 'ID', 'display_name', 'user_login', 'user_email' ),
			)
		);

		$results = array();
		foreach ( $users as $user ) {
			$results[] = array(
				'id'    => (int) $user->ID,
				/* translators: 1: display name, 2: email. */
				'label' => sprintf( '%1$s (%2$s)', $user->display_name, $user->user_email ),
				'value' => $user->display_name,
			);
		}

		wp_send_json_success( $results );
	}

	/**
	 * Handle membership-level deletion.
	 *
	 * @return void
	 */
	public function handle_delete_level() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Permission denied.', 'unstoppable-memberships' ) );
		}

		$id = isset( $_GET['level'] ) ? absint( $_GET['level'] ) : 0;
		check_admin_referer( 'umem_delete_level_' . $id );

		UMEM_Levels::delete( $id );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => 'umem-levels',
					'umem_msg' => 'deleted',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Handle the settings form.
	 *
	 * @return void
	 */
	public function handle_save_settings() {
		if ( ! current_user_can( 'umem_manage_settings' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'unstoppable-memberships' ) );
		}
		check_admin_referer( 'umem_save_settings' );

		$raw      = isset( $_POST['umem_settings'] ) ? wp_unslash( $_POST['umem_settings'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized below.
		$settings = get_option( 'umem_settings', array() );

		$settings['currency']              = isset( $raw['currency'] ) ? strtoupper( substr( sanitize_text_field( $raw['currency'] ), 0, 3 ) ) : 'USD';
		$settings['test_mode']             = ( isset( $raw['test_mode'] ) && 'yes' === $raw['test_mode'] ) ? 'yes' : 'no';
		$settings['restricted_message']    = isset( $raw['restricted_message'] ) ? wp_kses_post( $raw['restricted_message'] ) : '';
		$settings['restrict_mode']         = ( isset( $raw['restrict_mode'] ) && 'redirect' === $raw['restrict_mode'] ) ? 'redirect' : 'replace';
		$settings['restrict_redirect_page'] = isset( $raw['restrict_redirect_page'] ) ? absint( $raw['restrict_redirect_page'] ) : 0;

		update_option( 'umem_settings', $settings );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => 'umem-settings',
					'umem_msg' => 'saved',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * Render a standard admin notice based on the umem_msg query arg.
	 *
	 * @return void
	 */
	public static function maybe_notice() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only.
		$msg = isset( $_GET['umem_msg'] ) ? sanitize_key( wp_unslash( $_GET['umem_msg'] ) ) : '';
		if ( ! $msg ) {
			return;
		}
		$map = array(
			'saved'   => array( 'success', __( 'Saved.', 'unstoppable-memberships' ) ),
			'deleted' => array( 'success', __( 'Deleted.', 'unstoppable-memberships' ) ),
			'error'   => array( 'error', __( 'Something went wrong. Please check the form.', 'unstoppable-memberships' ) ),
		);
		if ( ! isset( $map[ $msg ] ) ) {
			return;
		}
		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $map[ $msg ][0] ),
			esc_html( $map[ $msg ][1] )
		);
	}
}
