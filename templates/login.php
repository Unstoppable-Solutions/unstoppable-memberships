<?php
/**
 * Template: Login prompt.
 *
 * Override by copying to /your-theme/unstoppable-memberships/login.php
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( "ABSPATH" ) ) {
	exit;
}
?>
<div class="umem-login-wrap">
	<?php
	wp_login_form(
		array(
			"echo"     => true,
			"redirect" => esc_url( ( is_ssl() ? "https://" : "http://" ) . wp_unslash( $_SERVER["HTTP_HOST"] ?? "" ) . wp_unslash( $_SERVER["REQUEST_URI"] ?? "" ) ),
		)
	);
	?>
	<p class="umem-form-alt">
		<a href="<?php echo esc_url( wp_registration_url() ); ?>"><?php esc_html_e( "Need an account? Register", "unstoppable-memberships" ); ?></a>
	</p>
</div>
