<?php
/**
 * Template: Restricted content message.
 *
 * Override by copying to /your-theme/unstoppable-memberships/restricted.php
 *
 * @var string $message The restriction message.
 * @package Unstoppable_Memberships
 */

if ( ! defined( "ABSPATH" ) ) {
	exit;
}

$message = isset( $message ) ? $message : umem_get_option( "restricted_message" );
?>
<div class="umem-restricted">
	<div class="umem-restricted-inner">
		<?php echo wp_kses_post( wpautop( $message ) ); ?>
		<?php if ( ! is_user_logged_in() ) : ?>
			<p>
				<a class="umem-button" href="<?php echo esc_url( wp_login_url( get_permalink() ) ); ?>"><?php esc_html_e( "Log in", "unstoppable-memberships" ); ?></a>
			</p>
		<?php endif; ?>
	</div>
</div>
