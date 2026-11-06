<?php
/**
 * Template: Checkout.
 *
 * Override by copying to /your-theme/unstoppable-memberships/checkout.php
 *
 * @var object $level Membership level being purchased.
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="umem-checkout">
	<h3><?php echo esc_html( $level->name ); ?></h3>
	<p class="umem-checkout-price">
		<?php esc_html_e( 'Free', 'unstoppable-memberships' ); ?>
	</p>

	<?php if ( ! is_user_logged_in() ) : ?>
		<p><?php esc_html_e( 'Create an account or log in to continue.', 'unstoppable-memberships' ); ?></p>
		<?php umem_get_template( 'register' ); ?>
	<?php elseif ( 'free' === $level->billing_type ) : ?>
		<a class="umem-button" href="<?php echo esc_url( add_query_arg( array( 'umem_join_free' => (int) $level->id, '_wpnonce' => wp_create_nonce( 'umem_join_free_' . (int) $level->id ) ) ) ); ?>">
			<?php esc_html_e( 'Activate membership', 'unstoppable-memberships' ); ?>
		</a>
	<?php else : ?>
		<p class="umem-notice"><?php esc_html_e( 'Online signup for this membership is not available here.', 'unstoppable-memberships' ); ?></p>
	<?php endif; ?>
</div>
