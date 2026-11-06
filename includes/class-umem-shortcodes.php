<?php
/**
 * Shortcodes.
 *
 * Each shortcode is a thin wrapper that loads a template and returns markup.
 * Output is buffered so templates can use normal HTML/PHP.
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UMEM_Shortcodes
 */
class UMEM_Shortcodes {

	/**
	 * Register all shortcodes.
	 *
	 * @return void
	 */
	public function init() {
		add_shortcode( 'umem_register', array( $this, 'register_form' ) );
		add_shortcode( 'umem_login', array( $this, 'login_form' ) );
		add_shortcode( 'umem_pricing', array( $this, 'pricing' ) );
		add_shortcode( 'umem_checkout', array( $this, 'checkout' ) );
		add_shortcode( 'umem_account', array( $this, 'account' ) );
		add_shortcode( 'umem_member_dashboard', array( $this, 'dashboard' ) );
		add_shortcode( 'umem_restrict', array( $this, 'restrict' ) );
		add_shortcode( 'umem_logout_link', array( $this, 'logout_link' ) );
	}

	/**
	 * Buffer a template into a string.
	 *
	 * @param string $template Template name.
	 * @param array  $args     Template args.
	 * @return string
	 */
	private function render( $template, $args = array() ) {
		ob_start();
		umem_get_template( $template, $args );
		return ob_get_clean();
	}

	/**
	 * [umem_register]
	 *
	 * @return string
	 */
	public function register_form() {
		if ( is_user_logged_in() ) {
			return '<p class="umem-notice">' . esc_html__( 'You are already registered and logged in.', 'unstoppable-memberships' ) . '</p>';
		}
		return $this->render( 'register' );
	}

	/**
	 * [umem_login]
	 *
	 * @return string
	 */
	public function login_form() {
		if ( is_user_logged_in() ) {
			return $this->logout_link();
		}
		ob_start();
		wp_login_form(
			array(
				'redirect' => esc_url( ( is_ssl() ? 'https://' : 'http://' ) . wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) . wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ),
				'echo'     => true,
			)
		);
		return '<div class="umem-login">' . ob_get_clean() . '</div>';
	}

	/**
	 * [umem_pricing]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function pricing( $atts ) {
		$atts   = shortcode_atts( array( 'levels' => '' ), $atts, 'umem_pricing' );
		$levels = UMEM_Levels::get_all( array( 'status' => 'active' ) );

		if ( '' !== $atts['levels'] ) {
			$only   = array_map( 'absint', explode( ',', $atts['levels'] ) );
			$levels = array_filter( $levels, fn( $l ) => in_array( (int) $l->id, $only, true ) );
		}

		return $this->render( 'pricing', array( 'levels' => $levels ) );
	}

	/**
	 * [umem_checkout level="1"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function checkout( $atts ) {
		$atts  = shortcode_atts( array( 'level' => 0 ), $atts, 'umem_checkout' );
		$level = UMEM_Levels::get( absint( $atts['level'] ) );

		if ( ! $level ) {
			return '<p class="umem-notice">' . esc_html__( 'Membership level not found.', 'unstoppable-memberships' ) . '</p>';
		}
		return $this->render( 'checkout', array( 'level' => $level ) );
	}

	/**
	 * [umem_account]
	 *
	 * @return string
	 */
	public function account() {
		if ( ! is_user_logged_in() ) {
			return $this->render( 'login' );
		}
		return $this->render( 'account', array( 'user_id' => get_current_user_id() ) );
	}

	/**
	 * [umem_member_dashboard]
	 *
	 * @return string
	 */
	public function dashboard() {
		if ( ! is_user_logged_in() ) {
			return $this->render( 'login' );
		}
		return $this->render( 'account', array( 'user_id' => get_current_user_id() ) );
	}

	/**
	 * [umem_restrict levels="1,2"]…content…[/umem_restrict]
	 *
	 * @param array       $atts    Shortcode attributes.
	 * @param string|null $content Enclosed content.
	 * @return string
	 */
	public function restrict( $atts, $content = null ) {
		$atts = shortcode_atts(
			array(
				'levels'  => '',
				'access'  => '', // public|loggedin|members|levels (optional override)
				'message' => '',
			),
			$atts,
			'umem_restrict'
		);

		$level_ids = '' !== $atts['levels'] ? array_map( 'absint', explode( ',', $atts['levels'] ) ) : array();

		// Determine access mode.
		if ( $atts['access'] ) {
			$mode = sanitize_key( $atts['access'] );
		} elseif ( $level_ids ) {
			$mode = 'levels';
		} else {
			$mode = 'members';
		}

		$pass = false;
		switch ( $mode ) {
			case 'loggedin':
				$pass = is_user_logged_in();
				break;
			case 'levels':
				$pass = umem_user_has_access( get_current_user_id(), $level_ids );
				break;
			case 'members':
			default:
				$pass = umem_user_has_access( get_current_user_id() );
				break;
		}

		if ( $pass ) {
			return do_shortcode( $content );
		}

		$message = $atts['message'] ? $atts['message'] : umem_get_option( 'restricted_message' );
		return $this->render( 'restricted', array( 'message' => $message ) );
	}

	/**
	 * [umem_logout_link]
	 *
	 * @return string
	 */
	public function logout_link() {
		if ( ! is_user_logged_in() ) {
			return '';
		}
		return '<a class="umem-logout-link" href="' . esc_url( wp_logout_url( home_url() ) ) . '">' . esc_html__( 'Log out', 'unstoppable-memberships' ) . '</a>';
	}
}
