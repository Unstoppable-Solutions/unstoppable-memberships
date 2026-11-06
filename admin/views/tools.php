<?php
/**
 * Admin: Tools view (one-click member-page creation).
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Handle one-click page creation inline (POST to self, nonce-protected).
if ( isset( $_POST['umem_create_pages'] ) ) {
	check_admin_referer( 'umem_create_pages' );

	if ( current_user_can( 'umem_manage_settings' ) ) {
		$pages = array(
			'pricing'   => array( __( 'Pricing', 'unstoppable-memberships' ), '[umem_pricing]' ),
			'register'  => array( __( 'Register', 'unstoppable-memberships' ), '[umem_register]' ),
			'login'     => array( __( 'Login', 'unstoppable-memberships' ), '[umem_login]' ),
			'account'   => array( __( 'My Account', 'unstoppable-memberships' ), '[umem_account]' ),
			'dashboard' => array( __( 'Member Dashboard', 'unstoppable-memberships' ), '[umem_member_dashboard]' ),
		);

		$stored = umem_get_option( 'pages', array() );

		foreach ( $pages as $key => $info ) {
			if ( ! empty( $stored[ $key ] ) && get_post( $stored[ $key ] ) ) {
				continue; // Already exists.
			}
			$page_id = wp_insert_post(
				array(
					'post_title'   => $info[0],
					'post_content' => $info[1],
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);
			if ( $page_id && ! is_wp_error( $page_id ) ) {
				$stored[ $key ] = (int) $page_id;
			}
		}
		umem_update_option( 'pages', $stored );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Member pages created.', 'unstoppable-memberships' ) . '</p></div>';
	}
}

$stored = umem_get_option( 'pages', array() );
?>
<div class="wrap umem-wrap">
	<h1><?php esc_html_e( 'Tools', 'unstoppable-memberships' ); ?></h1>

	<div class="umem-card">
		<h2><?php esc_html_e( 'One-click page creation', 'unstoppable-memberships' ); ?></h2>
		<p><?php esc_html_e( 'Create the core member-facing pages with the right shortcodes already in place.', 'unstoppable-memberships' ); ?></p>
		<form method="post">
			<?php wp_nonce_field( 'umem_create_pages' ); ?>
			<button type="submit" name="umem_create_pages" value="1" class="button button-primary"><?php esc_html_e( 'Create / verify member pages', 'unstoppable-memberships' ); ?></button>
		</form>
		<?php if ( ! empty( array_filter( $stored ) ) ) : ?>
			<ul class="umem-page-list">
				<?php foreach ( $stored as $key => $pid ) : ?>
					<?php if ( $pid && get_post( $pid ) ) : ?>
						<li><?php echo esc_html( ucfirst( $key ) ); ?>: <a href="<?php echo esc_url( get_edit_post_link( $pid ) ); ?>"><?php esc_html_e( 'edit', 'unstoppable-memberships' ); ?></a> · <a href="<?php echo esc_url( get_permalink( $pid ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'view', 'unstoppable-memberships' ); ?></a></li>
					<?php endif; ?>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</div>
