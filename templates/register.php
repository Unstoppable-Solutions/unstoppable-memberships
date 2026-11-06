<?php
/**
 * Template: Registration form.
 *
 * Override by copying to /your-theme/unstoppable-memberships/register.php
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$errors   = isset( $GLOBALS['umem_register_errors'] ) ? $GLOBALS['umem_register_errors'] : null;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- prefill only.
$level_id = isset( $_GET['umem_level'] ) ? absint( $_GET['umem_level'] ) : 0;
?>
<form class="umem-form umem-register" method="post" action="">
	<?php wp_nonce_field( 'umem_register', 'umem_register_nonce' ); ?>
	<input type="hidden" name="umem_level_id" value="<?php echo (int) $level_id; ?>" />

	<?php if ( $errors && is_wp_error( $errors ) && $errors->has_errors() ) : ?>
		<div class="umem-errors">
			<?php foreach ( $errors->get_error_messages() as $message ) : ?>
				<p><?php echo esc_html( $message ); ?></p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<p>
		<label for="umem_username"><?php esc_html_e( 'Username', 'unstoppable-memberships' ); ?></label>
		<input type="text" name="umem_username" id="umem_username" required />
	</p>
	<p>
		<label for="umem_email"><?php esc_html_e( 'Email', 'unstoppable-memberships' ); ?></label>
		<input type="email" name="umem_email" id="umem_email" required />
	</p>
	<p>
		<label for="umem_password"><?php esc_html_e( 'Password', 'unstoppable-memberships' ); ?></label>
		<input type="password" name="umem_password" id="umem_password" autocomplete="new-password" required />
	</p>
	<p>
		<button type="submit" name="umem_register_submit" value="1" class="umem-button"><?php esc_html_e( 'Create account', 'unstoppable-memberships' ); ?></button>
	</p>
	<p class="umem-form-alt">
		<?php
		printf(
			/* translators: %s login URL */
			esc_html__( 'Already have an account? %s', 'unstoppable-memberships' ),
			'<a href="' . esc_url( wp_login_url() ) . '">' . esc_html__( 'Log in', 'unstoppable-memberships' ) . '</a>'
		);
		?>
	</p>
</form>
