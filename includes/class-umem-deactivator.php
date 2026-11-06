<?php
/**
 * Fired during plugin deactivation.
 *
 * Deactivation is non-destructive: scheduled events are cleared and rewrite
 * rules flushed, but no data or tables are removed. Data removal only happens
 * via uninstall.php when the user explicitly deletes the plugin.
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UMEM_Deactivator
 */
class UMEM_Deactivator {

	/**
	 * Deactivation routine.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'umem_daily_event' );
		wp_clear_scheduled_hook( 'umem_hourly_event' );
		flush_rewrite_rules();
	}
}
