<?php
/**
 * Admin: Membership Level add/edit form.
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only routing arg.
$level_id = isset( $_GET['level'] ) ? absint( $_GET['level'] ) : 0;
$level    = $level_id ? UMEM_Levels::get( $level_id ) : null;

$defaults = array(
	'name'                   => '',
	'slug'                   => '',
	'description'            => '',
	'price'                  => '0.00',
	'currency'               => umem_get_option( 'currency', 'USD' ),
	'billing_type'           => 'free',
	'billing_interval'       => 'month',
	'billing_interval_count' => 1,
	'trial_days'             => 0,
	'signup_fee'             => '0.00',
	'grace_days'             => 0,
	'badge'                  => '',
	'status'                 => 'active',
	'menu_order'             => 0,
	'stripe_price_id'        => '',
);

$v = $level ? wp_parse_args( (array) $level, $defaults ) : $defaults;
?>
<div class="wrap umem-wrap">
	<h1><?php echo $level ? esc_html__( 'Edit Membership Level', 'unstoppable-memberships' ) : esc_html__( 'Add Membership Level', 'unstoppable-memberships' ); ?></h1>
	<?php UMEM_Admin::maybe_notice(); ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="umem-form">
		<?php wp_nonce_field( 'umem_save_level' ); ?>
		<input type="hidden" name="action" value="umem_save_level" />
		<input type="hidden" name="level_id" value="<?php echo (int) $level_id; ?>" />
			<input type="hidden" name="umem_level[billing_type]" value="free" />

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="umem_name"><?php esc_html_e( 'Name', 'unstoppable-memberships' ); ?></label></th>
				<td><input name="umem_level[name]" id="umem_name" type="text" class="regular-text" value="<?php echo esc_attr( $v['name'] ); ?>" required /></td>
			</tr>
			<tr>
				<th scope="row"><label for="umem_slug"><?php esc_html_e( 'Slug', 'unstoppable-memberships' ); ?></label></th>
				<td><input name="umem_level[slug]" id="umem_slug" type="text" class="regular-text" value="<?php echo esc_attr( $v['slug'] ); ?>" />
				<p class="description"><?php esc_html_e( 'Leave blank to auto-generate from the name.', 'unstoppable-memberships' ); ?></p></td>
			</tr>
			<tr>
				<th scope="row"><label for="umem_desc"><?php esc_html_e( 'Description', 'unstoppable-memberships' ); ?></label></th>
				<td><textarea name="umem_level[description]" id="umem_desc" class="large-text" rows="4"><?php echo esc_textarea( $v['description'] ); ?></textarea></td>
			</tr>
			<?php
			/**
			 * Pro add-ons render paid billing fields (price, interval, trial,
			 * gateway mapping) here. The free plugin creates free levels only.
			 *
			 * @param array $v Current level values.
			 */
			do_action( 'umem_level_edit_fields', $v );
			?>
			<tr>
				<th scope="row"><label for="umem_badge"><?php esc_html_e( 'Badge / label', 'unstoppable-memberships' ); ?></label></th>
				<td><input name="umem_level[badge]" id="umem_badge" type="text" class="regular-text" value="<?php echo esc_attr( $v['badge'] ); ?>" placeholder="<?php esc_attr_e( 'e.g. Most popular', 'unstoppable-memberships' ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="umem_status"><?php esc_html_e( 'Status', 'unstoppable-memberships' ); ?></label></th>
				<td>
					<select name="umem_level[status]" id="umem_status">
						<option value="active" <?php selected( $v['status'], 'active' ); ?>><?php esc_html_e( 'Active', 'unstoppable-memberships' ); ?></option>
						<option value="inactive" <?php selected( $v['status'], 'inactive' ); ?>><?php esc_html_e( 'Inactive', 'unstoppable-memberships' ); ?></option>
					</select>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Level', 'unstoppable-memberships' ); ?></button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=umem-levels' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'unstoppable-memberships' ); ?></a>
		</p>
	</form>
</div>
