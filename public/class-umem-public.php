<?php
/**
 * Public (front-end) controller.
 *
 * Enqueues lightweight front-end assets and handles the public registration
 * form submission. Kept deliberately small; most front output is via templates
 * and shortcodes.
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UMEM_Public
 */
class UMEM_Public {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'init', array( $this, 'handle_registration' ) );
		add_action( 'template_redirect', array( $this, 'handle_free_join' ) );
	}

	/**
	 * Activate a free membership from the checkout/pricing link.
	 *
	 * @return void
	 */
	public function handle_free_join() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce checked below.
		if ( empty( $_GET['umem_join_free'] ) ) {
			return;
		}
		$level_id = absint( $_GET['umem_join_free'] );

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'umem_join_free_' . $level_id ) ) {
			wp_die( esc_html__( 'Security check failed.', 'unstoppable-memberships' ) );
		}

		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( add_query_arg( array() ) ) );
			exit;
		}

		$level = UMEM_Levels::get( $level_id );
		if ( ! $level || 'free' !== $level->billing_type ) {
			wp_die( esc_html__( 'That membership is not available.', 'unstoppable-memberships' ) );
		}

		$user_id  = get_current_user_id();
		$existing = umem()->subscriptions->get_user_level_subscription( $user_id, $level_id );

		umem()->subscriptions->save(
			array(
				'user_id'    => $user_id,
				'level_id'   => $level_id,
				'status'     => 'active',
				'gateway'    => 'free',
				'start_date' => current_time( 'mysql' ),
			),
			$existing ? (int) $existing->id : 0
		);

		/**
		 * Fires when a user joins a free level.
		 *
		 * @param int $user_id  User ID.
		 * @param int $level_id Level ID.
		 */
		do_action( 'umem_free_join', $user_id, $level_id );

		$pages   = (array) umem_get_option( 'pages' );
		$account = isset( $pages['account'] ) ? (int) $pages['account'] : 0;
		$dest    = $account ? get_permalink( $account ) : home_url( '/' );
		wp_safe_redirect( add_query_arg( 'umem_joined', '1', $dest ) );
		exit;
	}

	/**
	 * Enqueue front-end CSS/JS.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			'umem-public',
			UMEM_PLUGIN_URL . 'assets/css/umem-public.css',
			array(),
			UMEM_VERSION
		);

		wp_register_script(
			'umem-public',
			UMEM_PLUGIN_URL . 'assets/js/umem-public.js',
			array(),
			UMEM_VERSION,
			true
		);

		wp_localize_script(
			'umem-public',
			'umemPublic',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'umem_public' ),
			)
		);

		wp_enqueue_script( 'umem-public' );
	}

	/**
	 * Process the [umem_register] form submission.
	 *
	 * Creates a standard WordPress user. Paid signups will then proceed to
	 * checkout via the Stripe integration; free levels are activated directly.
	 *
	 * @return void
	 */
	public function handle_registration() {
		if ( empty( $_POST['umem_register_submit'] ) ) {
			return;
		}
		if ( ! isset( $_POST['umem_register_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['umem_register_nonce'] ) ), 'umem_register' ) ) {
			return;
		}

		$username = isset( $_POST['umem_username'] ) ? sanitize_user( wp_unslash( $_POST['umem_username'] ) ) : '';
		$email    = isset( $_POST['umem_email'] ) ? sanitize_email( wp_unslash( $_POST['umem_email'] ) ) : '';
		$password = isset( $_POST['umem_password'] ) ? (string) wp_unslash( $_POST['umem_password'] ) : '';
		$level_id = isset( $_POST['umem_level_id'] ) ? absint( $_POST['umem_level_id'] ) : 0;

		$errors = new WP_Error();

		if ( '' === $username || ! validate_username( $username ) ) {
			$errors->add( 'username', __( 'Please choose a valid username.', 'unstoppable-memberships' ) );
		}
		if ( username_exists( $username ) ) {
			$errors->add( 'username_exists', __( 'That username is taken.', 'unstoppable-memberships' ) );
		}
		if ( ! is_email( $email ) ) {
			$errors->add( 'email', __( 'Please enter a valid email address.', 'unstoppable-memberships' ) );
		}
		if ( email_exists( $email ) ) {
			$errors->add( 'email_exists', __( 'An account already exists with that email.', 'unstoppable-memberships' ) );
		}
		if ( strlen( $password ) < 6 ) {
			$errors->add( 'password', __( 'Password must be at least 6 characters.', 'unstoppable-memberships' ) );
		}

		if ( $errors->has_errors() ) {
			// Expose errors to the template via a global the shortcode reads.
			$GLOBALS['umem_register_errors'] = $errors;
			return;
		}

		$user_id = wp_insert_user(
			array(
				'user_login' => $username,
				'user_email' => $email,
				'user_pass'  => $password,
				'role'       => 'subscriber',
			)
		);

		if ( is_wp_error( $user_id ) ) {
			$GLOBALS['umem_register_errors'] = $user_id;
			return;
		}

		/**
		 * Fires after a member registers via the front-end form.
		 *
		 * Integrations (e.g. Stripe checkout, welcome emails) hook here.
		 *
		 * @param int $user_id  New user ID.
		 * @param int $level_id Selected membership level (0 if none).
		 */
		do_action( 'umem_member_registered', $user_id, $level_id );

		// Log them in.
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );

		// Free level: activate immediately.
		$level = $level_id ? UMEM_Levels::get( $level_id ) : null;
		if ( $level && 'free' === $level->billing_type && umem()->subscriptions ) {
			umem()->subscriptions->save(
				array(
					'user_id'  => $user_id,
					'level_id' => $level_id,
					'status'   => 'active',
					'gateway'  => 'free',
				)
			);
		}

		// Redirect to account page if configured.
		$account_id = (int) umem_get_option( 'pages' )['account'] ?? 0;
		$redirect   = $account_id ? get_permalink( $account_id ) : home_url();
		wp_safe_redirect( $redirect );
		exit;
	}
}
