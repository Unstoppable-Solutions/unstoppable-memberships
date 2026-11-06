<?php
/**
 * Admin: Settings view (general + content restriction).
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$s = get_option( 'umem_settings', array() );
$g = fn( $k, $d = '' ) => isset( $s[ $k ] ) ? $s[ $k ] : $d;
?>
<div class="wrap umem-wrap">
	<h1><?php esc_html_e( 'Settings', 'unstoppable-memberships' ); ?></h1>
	<?php UMEM_Admin::maybe_notice(); ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'umem_save_settings' ); ?>
		<input type="hidden" name="action" value="umem_save_settings" />

		<h2><?php esc_html_e( 'General', 'unstoppable-memberships' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="umem_currency"><?php esc_html_e( 'Currency', 'unstoppable-memberships' ); ?></label></th>
				<td><input type="text" id="umem_currency" name="umem_settings[currency]" maxlength="3" size="5" value="<?php echo esc_attr( $g( 'currency', 'USD' ) ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="umem_restricted_message"><?php esc_html_e( 'Default restricted message', 'unstoppable-memberships' ); ?></label></th>
				<td><textarea id="umem_restricted_message" name="umem_settings[restricted_message]" rows="3" class="large-text"><?php echo esc_textarea( $g( 'restricted_message' ) ); ?></textarea></td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Content Restriction', 'unstoppable-memberships' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="umem_restrict_mode"><?php esc_html_e( 'When a non-member opens restricted content', 'unstoppable-memberships' ); ?></label></th>
				<td>
					<select id="umem_restrict_mode" name="umem_settings[restrict_mode]">
						<option value="replace" <?php selected( $g( 'restrict_mode', 'replace' ), 'replace' ); ?>><?php esc_html_e( 'Replace the content with the restricted message', 'unstoppable-memberships' ); ?></option>
						<option value="redirect" <?php selected( $g( 'restrict_mode', 'replace' ), 'redirect' ); ?>><?php esc_html_e( 'Redirect them to another page (recommended for page builders like Elementor)', 'unstoppable-memberships' ); ?></option>
					</select>
					<p class="description"><?php esc_html_e( 'Replace works for standard posts and themes. Redirect is more reliable for pages built with Elementor or other builders, because it stops the page before it renders.', 'unstoppable-memberships' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="umem_restrict_redirect_page"><?php esc_html_e( 'Redirect to', 'unstoppable-memberships' ); ?></label></th>
				<td>
					<?php
					wp_dropdown_pages(
						array(
							'name'              => 'umem_settings[restrict_redirect_page]',
							'id'                => 'umem_restrict_redirect_page',
							'selected'          => (int) $g( 'restrict_redirect_page', 0 ),
							'show_option_none'  => __( '— Use the login page —', 'unstoppable-memberships' ),
							'option_none_value' => 0,
						)
					);
					?>
					<p class="description"><?php esc_html_e( 'Used only when the option above is set to Redirect. Falls back to the WordPress login page if none is selected. The original URL is passed along so members return after logging in.', 'unstoppable-memberships' ); ?></p>
				</td>
			</tr>
		</table>

		<p class="submit"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Settings', 'unstoppable-memberships' ); ?></button></p>
	</form>
</div>
