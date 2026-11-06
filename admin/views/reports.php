<?php
/**
 * Admin: Reports view.
 *
 * Member-focused metrics computed from the subscriptions table.
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$subs_table = umem_table( 'subscriptions' );

$since_30 = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days', current_time( 'timestamp' ) ) );

// phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$signups_30 = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$subs_table} WHERE created_at >= %s", $since_30 ) );
$cancels_30 = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$subs_table} WHERE status = 'cancelled' AND updated_at >= %s", $since_30 ) );
$active     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$subs_table} WHERE status = 'active'" );
$total      = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$subs_table}" );
// phpcs:enable

$churn = ( $active + $cancels_30 ) > 0 ? round( ( $cancels_30 / ( $active + $cancels_30 ) ) * 100, 1 ) : 0;
?>
<div class="wrap umem-wrap">
	<h1><?php esc_html_e( 'Reports', 'unstoppable-memberships' ); ?></h1>
	<p class="description"><?php esc_html_e( 'Figures below cover the last 30 days unless noted.', 'unstoppable-memberships' ); ?></p>

	<div class="umem-stats umem-stats-4">
		<div class="umem-stat"><span class="umem-stat-value"><?php echo esc_html( number_format_i18n( $active ) ); ?></span><span class="umem-stat-label"><?php esc_html_e( 'Active members', 'unstoppable-memberships' ); ?></span></div>
		<div class="umem-stat"><span class="umem-stat-value"><?php echo esc_html( number_format_i18n( $total ) ); ?></span><span class="umem-stat-label"><?php esc_html_e( 'Total members', 'unstoppable-memberships' ); ?></span></div>
		<div class="umem-stat"><span class="umem-stat-value"><?php echo esc_html( number_format_i18n( $signups_30 ) ); ?></span><span class="umem-stat-label"><?php esc_html_e( 'New signups (30d)', 'unstoppable-memberships' ); ?></span></div>
		<div class="umem-stat"><span class="umem-stat-value"><?php echo esc_html( number_format_i18n( $cancels_30 ) ); ?></span><span class="umem-stat-label"><?php esc_html_e( 'Cancellations (30d)', 'unstoppable-memberships' ); ?></span></div>
		<div class="umem-stat"><span class="umem-stat-value"><?php echo esc_html( $churn ); ?>%</span><span class="umem-stat-label"><?php esc_html_e( 'Churn (30d)', 'unstoppable-memberships' ); ?></span></div>
	</div>
</div>
