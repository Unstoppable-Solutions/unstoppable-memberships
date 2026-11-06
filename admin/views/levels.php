<?php
/**
 * Admin: Membership Levels list view.
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$levels = UMEM_Levels::get_all( array( 'status' => 'any' ) );
?>
<div class="wrap umem-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Membership Levels', 'unstoppable-memberships' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=umem-levels&action=new' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'unstoppable-memberships' ); ?></a>
	<hr class="wp-header-end" />

	<?php UMEM_Admin::maybe_notice(); ?>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Name', 'unstoppable-memberships' ); ?></th>
				<th><?php esc_html_e( 'Price', 'unstoppable-memberships' ); ?></th>
				<th><?php esc_html_e( 'Billing', 'unstoppable-memberships' ); ?></th>
				<th><?php esc_html_e( 'Members', 'unstoppable-memberships' ); ?></th>
				<th><?php esc_html_e( 'Status', 'unstoppable-memberships' ); ?></th>
				<th><?php esc_html_e( 'Shortcode', 'unstoppable-memberships' ); ?></th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $levels ) ) : ?>
			<tr><td colspan="6"><?php esc_html_e( 'No membership levels yet. Create your first one.', 'unstoppable-memberships' ); ?></td></tr>
		<?php else : ?>
			<?php foreach ( $levels as $level ) : ?>
				<?php
				$edit_url   = admin_url( 'admin.php?page=umem-levels&action=edit&level=' . (int) $level->id );
				$delete_url = wp_nonce_url(
					admin_url( 'admin-post.php?action=umem_delete_level&level=' . (int) $level->id ),
					'umem_delete_level_' . (int) $level->id
				);
				$billing    = 'free' === $level->billing_type
					? __( 'Free', 'unstoppable-memberships' )
					: ( 'onetime' === $level->billing_type
						? __( 'One-time', 'unstoppable-memberships' )
						/* translators: %s billing interval */
						: sprintf( __( 'Every %s', 'unstoppable-memberships' ), esc_html( $level->billing_interval ) ) );
				?>
				<tr>
					<td>
						<strong><a href="<?php echo esc_url( $edit_url ); ?>"><?php echo esc_html( $level->name ); ?></a></strong>
						<div class="row-actions">
							<span class="edit"><a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'unstoppable-memberships' ); ?></a> | </span>
							<span class="trash"><a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this level?', 'unstoppable-memberships' ) ); ?>');"><?php esc_html_e( 'Delete', 'unstoppable-memberships' ); ?></a></span>
						</div>
					</td>
					<td><?php echo 'free' === $level->billing_type ? esc_html__( 'Free', 'unstoppable-memberships' ) : esc_html( umem_format_price( $level->price, $level->currency ) ); ?></td>
					<td><?php echo esc_html( $billing ); ?></td>
					<td><?php echo esc_html( number_format_i18n( UMEM_Levels::count_members( $level->id ) ) ); ?></td>
					<td><span class="umem-badge umem-badge-<?php echo esc_attr( $level->status ); ?>"><?php echo esc_html( ucfirst( $level->status ) ); ?></span></td>
					<td><code>[umem_checkout level="<?php echo (int) $level->id; ?>"]</code></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		</tbody>
	</table>
</div>
