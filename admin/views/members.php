<?php
/**
 * Admin: Members view.
 *
 * Lists users who hold (or have held) a membership, joined against the
 * subscriptions table. Read-only foundation; CRUD/editing comes in a later pass.
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$subs_table = umem_table( 'subscriptions' );

// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$rows = $wpdb->get_results(
	"SELECT s.*, u.user_email, u.display_name
	 FROM {$subs_table} s
	 INNER JOIN {$wpdb->users} u ON u.ID = s.user_id
	 ORDER BY s.created_at DESC
	 LIMIT 200"
);
?>
<div class="wrap umem-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Members', 'unstoppable-memberships' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=umem-members&action=add' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add Member', 'unstoppable-memberships' ); ?></a>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=umem-tools' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Import / Export', 'unstoppable-memberships' ); ?></a>
	<hr class="wp-header-end" />
	<?php UMEM_Admin::maybe_notice(); ?>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Member', 'unstoppable-memberships' ); ?></th>
				<th><?php esc_html_e( 'Email', 'unstoppable-memberships' ); ?></th>
				<th><?php esc_html_e( 'Level', 'unstoppable-memberships' ); ?></th>
				<th><?php esc_html_e( 'Status', 'unstoppable-memberships' ); ?></th>
				<th><?php esc_html_e( 'Renews', 'unstoppable-memberships' ); ?></th>
				<th><?php esc_html_e( 'Joined', 'unstoppable-memberships' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $rows ) ) : ?>
			<tr><td colspan="6"><?php esc_html_e( 'No members yet.', 'unstoppable-memberships' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $rows as $row ) : ?>
				<?php $level = UMEM_Levels::get( $row->level_id ); ?>
				<tr>
					<td><strong><?php echo esc_html( $row->display_name ); ?></strong></td>
					<td><?php echo esc_html( $row->user_email ); ?></td>
					<td><?php echo $level ? esc_html( $level->name ) : '&mdash;'; ?></td>
					<td><span class="umem-badge umem-badge-<?php echo esc_attr( $row->status ); ?>"><?php echo esc_html( ucfirst( $row->status ) ); ?></span></td>
					<td><?php echo $row->current_period_end ? esc_html( mysql2date( get_option( 'date_format' ), $row->current_period_end ) ) : '&mdash;'; ?></td>
					<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $row->created_at ) ); ?></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
