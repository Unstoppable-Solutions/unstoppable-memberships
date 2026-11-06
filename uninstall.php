<?php
/**
 * Uninstall handler for Unstoppable Memberships.
 *
 * Runs only when the user deletes the plugin from the WordPress admin.
 * Removes all custom tables, options, capabilities, and scheduled events.
 * Multisite-aware: cleans up every site in the network.
 *
 * @package Unstoppable_Memberships
 */

// Exit if uninstall is not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove every trace of the plugin from a single site.
 *
 * @return void
 */
function umem_uninstall_site() {
	global $wpdb;

	$prefix = $wpdb->prefix . 'umem_';
	$tables = array(
		$prefix . 'levels',
		$prefix . 'subscriptions',
		$prefix . 'payments',
		$prefix . 'coupons',
		$prefix . 'content_rules',
		$prefix . 'email_logs',
		$prefix . 'member_meta',
		$prefix . 'audit_log',
	);

	foreach ( $tables as $table ) {
		// Table name is built from a trusted prefix; cannot be parameterized.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}

	// Options.
	delete_option( 'umem_settings' );
	delete_option( 'umem_db_version' );
	delete_option( 'umem_version' );
	delete_option( 'umem_flush_rewrite' );

	// License + housekeeping options.
	delete_option( 'umem_license_key' );
	delete_option( 'umem_license_data' );
	delete_option( 'umem_install_date' );
	delete_option( 'umem_announcement' );
	delete_option( 'umem_announcement_dismissed_id' );
	delete_option( 'umem_welcome_notice_dismissed' );
	delete_option( 'umem_review_notice_dismissed' );
	delete_option( 'umem_license_notice_dismissed' );
	delete_option( 'umem_license_notice_dismissed_date' );
	delete_transient( 'umem_license_status' );
	delete_transient( 'umem_flash_notices' );
	delete_transient( 'umem_update_' . md5( 'unstoppable-memberships' ) );

	// Post meta written by the content-restriction metabox.
	delete_post_meta_by_key( '_umem_restriction' );

	// Capabilities.
	$caps = array(
		'umem_manage_memberships',
		'umem_manage_members',
		'umem_manage_settings',
		'umem_view_reports',
	);
	$role = get_role( 'administrator' );
	if ( $role ) {
		foreach ( $caps as $cap ) {
			$role->remove_cap( $cap );
		}
	}

	// Scheduled events.
	wp_clear_scheduled_hook( 'umem_daily_event' );
	wp_clear_scheduled_hook( 'umem_hourly_event' );
}

if ( is_multisite() ) {
	$site_ids = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		umem_uninstall_site();
		restore_current_blog();
	}
} else {
	umem_uninstall_site();
}
