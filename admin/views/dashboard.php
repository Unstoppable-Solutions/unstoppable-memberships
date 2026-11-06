<?php
/**
 * Admin: Dashboard view.
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$subs           = umem()->subscriptions;
$active_members = $subs ? $subs->count_by_status( 'active' ) : 0;
$cancelled      = $subs ? $subs->count_by_status( 'cancelled' ) : 0;
$levels         = UMEM_Levels::get_all( array( 'status' => 'any' ) );
$setup_done     = 'yes' === umem_get_option( 'setup_complete', 'no' );
?>
<div class="wrap umem-wrap">
	<h1><?php esc_html_e( 'Memberships Dashboard', 'unstoppable-memberships' ); ?></h1>
	<?php UMEM_Admin::maybe_notice(); ?>

	<?php if ( ! $setup_done ) : ?>
		<div class="umem-card umem-setup">
			<h2><?php esc_html_e( 'Finish setting up', 'unstoppable-memberships' ); ?></h2>
			<ol class="umem-setup-steps">
				<li><?php esc_html_e( 'Create your membership levels', 'unstoppable-memberships' ); ?>
					— <a href="<?php echo esc_url( admin_url( 'admin.php?page=umem-levels&action=new' ) ); ?>"><?php esc_html_e( 'add a level', 'unstoppable-memberships' ); ?></a></li>
				<li><?php esc_html_e( 'Create your member pages (pricing, account, login)', 'unstoppable-memberships' ); ?>
					— <a href="<?php echo esc_url( admin_url( 'admin.php?page=umem-tools' ) ); ?>"><?php esc_html_e( 'one-click create', 'unstoppable-memberships' ); ?></a></li>
				<li><?php esc_html_e( 'Restrict a post or page from the editor', 'unstoppable-memberships' ); ?>
					— <a href="<?php echo esc_url( admin_url( 'admin.php?page=umem-settings' ) ); ?>"><?php esc_html_e( 'set restriction defaults', 'unstoppable-memberships' ); ?></a></li>
			</ol>
		</div>
	<?php endif; ?>

	<div class="umem-stats">
		<div class="umem-stat">
			<span class="umem-stat-value"><?php echo esc_html( number_format_i18n( $active_members ) ); ?></span>
			<span class="umem-stat-label"><?php esc_html_e( 'Active members', 'unstoppable-memberships' ); ?></span>
		</div>
		<div class="umem-stat">
			<span class="umem-stat-value"><?php echo esc_html( number_format_i18n( count( $levels ) ) ); ?></span>
			<span class="umem-stat-label"><?php esc_html_e( 'Membership levels', 'unstoppable-memberships' ); ?></span>
		</div>
		<div class="umem-stat">
			<span class="umem-stat-value"><?php echo esc_html( number_format_i18n( $cancelled ) ); ?></span>
			<span class="umem-stat-label"><?php esc_html_e( 'Cancelled', 'unstoppable-memberships' ); ?></span>
		</div>
	</div>

	<div class="umem-card">
		<h2><?php esc_html_e( 'Quick links', 'unstoppable-memberships' ); ?></h2>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=umem-levels&action=new' ) ); ?>"><?php esc_html_e( 'Add membership level', 'unstoppable-memberships' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=umem-members' ) ); ?>"><?php esc_html_e( 'View members', 'unstoppable-memberships' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=umem-reports' ) ); ?>"><?php esc_html_e( 'Reports', 'unstoppable-memberships' ); ?></a>
			<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=umem-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'unstoppable-memberships' ); ?></a>
		</p>
	</div>
</div>
