<?php
/**
 * Fired during plugin activation.
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UMEM_Activator
 */
class UMEM_Activator {

	/**
	 * Activation routine.
	 *
	 * Runs the installer, registers capabilities, schedules cron events, and
	 * flags that rewrite rules need flushing on the next request.
	 *
	 * @param bool $network_wide Whether this is a network (multisite) activation.
	 * @return void
	 */
	public static function activate( $network_wide = false ) {
		// Multisite: run the installer on every site in the network.
		if ( is_multisite() && $network_wide ) {
			$sites = get_sites( array( 'number' => 0 ) );
			foreach ( $sites as $site ) {
				switch_to_blog( (int) $site->blog_id );
				self::activate_single_site();
				restore_current_blog();
			}
			return;
		}

		self::activate_single_site();
	}

	/**
	 * Per-site activation work.
	 *
	 * @return void
	 */
	private static function activate_single_site() {
		UMEM_Installer::install();
		self::add_capabilities();
		self::schedule_events();

		// Record install date once (used by the review-request notice).
		if ( ! get_option( 'umem_install_date' ) ) {
			update_option( 'umem_install_date', time() );
		}

		// Tell the plugin to flush rewrite rules on the next init.
		update_option( 'umem_flush_rewrite', 'yes' );
	}

	/**
	 * Grant management capabilities to administrators.
	 *
	 * @return void
	 */
	private static function add_capabilities() {
		$role = get_role( 'administrator' );
		if ( ! $role ) {
			return;
		}

		$caps = array(
			'umem_manage_memberships',
			'umem_manage_members',
			'umem_manage_settings',
			'umem_view_reports',
		);

		foreach ( $caps as $cap ) {
			$role->add_cap( $cap );
		}
	}

	/**
	 * Schedule recurring background jobs.
	 *
	 * @return void
	 */
	private static function schedule_events() {
		if ( ! wp_next_scheduled( 'umem_daily_event' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'umem_daily_event' );
		}
		if ( ! wp_next_scheduled( 'umem_hourly_event' ) ) {
			wp_schedule_event( time() + ( 5 * MINUTE_IN_SECONDS ), 'hourly', 'umem_hourly_event' );
		}
	}
}
