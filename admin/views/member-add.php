<?php
/**
 * Admin: Add Member.
 *
 * Attach a membership to an existing WordPress user, or create a new user and
 * attach one. Writes a subscription row via UMEM_Subscriptions::save().
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$levels = UMEM_Levels::get_all( array( 'status' => 'active' ) );
$today  = current_time( 'Y-m-d' );
?>
<div class="wrap umem-wrap">
	<h1><?php esc_html_e( 'Add Member', 'unstoppable-memberships' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=umem-members' ) ); ?>" class="page-title-action"><?php esc_html_e( '&larr; Back to Members', 'unstoppable-memberships' ); ?></a>
	<hr class="wp-header-end" />
	<?php UMEM_Admin::maybe_notice(); ?>

	<?php if ( empty( $levels ) ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php esc_html_e( 'You need at least one active membership level before you can add members.', 'unstoppable-memberships' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=umem-levels&action=new' ) ); ?>"><?php esc_html_e( 'Create a level', 'unstoppable-memberships' ); ?></a>
			</p>
		</div>
	<?php else : ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="umem-form">
		<?php wp_nonce_field( 'umem_add_member' ); ?>
		<input type="hidden" name="action" value="umem_add_member" />

		<h2><?php esc_html_e( 'Who is this membership for?', 'unstoppable-memberships' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'User', 'unstoppable-memberships' ); ?></th>
				<td>
					<label class="umem-radio-line">
						<input type="radio" name="umem_member[user_mode]" value="existing" checked /> <?php esc_html_e( 'Existing WordPress user', 'unstoppable-memberships' ); ?>
					</label>
					<label class="umem-radio-line">
						<input type="radio" name="umem_member[user_mode]" value="new" /> <?php esc_html_e( 'Create a new user', 'unstoppable-memberships' ); ?>
					</label>
				</td>
			</tr>
		</table>

		<div class="umem-existing-user">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="umem_user_search"><?php esc_html_e( 'Search user', 'unstoppable-memberships' ); ?></label></th>
					<td>
						<input type="text" id="umem_user_search" class="regular-text" autocomplete="off" placeholder="<?php esc_attr_e( 'Type a name, username, or email…', 'unstoppable-memberships' ); ?>" />
						<input type="hidden" id="umem_user_id" name="umem_member[user_id]" value="0" />
						<p id="umem_user_selected" class="umem-field-help"></p>
						<p class="umem-field-help"><?php esc_html_e( 'Start typing to search your users. Select one from the list.', 'unstoppable-memberships' ); ?></p>
					</td>
				</tr>
			</table>
		</div>

		<div class="umem-new-user umem-hidden">
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="umem_new_username"><?php esc_html_e( 'Username', 'unstoppable-memberships' ); ?></label></th>
					<td><input type="text" id="umem_new_username" name="umem_member[username]" class="regular-text" autocomplete="off" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="umem_new_email"><?php esc_html_e( 'Email', 'unstoppable-memberships' ); ?></label></th>
					<td><input type="email" id="umem_new_email" name="umem_member[email]" class="regular-text" autocomplete="off" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="umem_new_first"><?php esc_html_e( 'First name', 'unstoppable-memberships' ); ?></label></th>
					<td><input type="text" id="umem_new_first" name="umem_member[first_name]" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><label for="umem_new_last"><?php esc_html_e( 'Last name', 'unstoppable-memberships' ); ?></label></th>
					<td><input type="text" id="umem_new_last" name="umem_member[last_name]" class="regular-text" /></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Password', 'unstoppable-memberships' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="umem_member[notify]" value="1" checked />
							<?php esc_html_e( 'Generate a password and email the new user their account details.', 'unstoppable-memberships' ); ?>
						</label>
					</td>
				</tr>
			</table>
		</div>

		<h2><?php esc_html_e( 'Membership', 'unstoppable-memberships' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="umem_member_level"><?php esc_html_e( 'Level', 'unstoppable-memberships' ); ?></label></th>
				<td>
					<select id="umem_member_level" name="umem_member[level_id]" required>
						<?php foreach ( $levels as $level ) : ?>
							<option value="<?php echo esc_attr( $level->id ); ?>"><?php echo esc_html( $level->name ); ?></option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="umem_member_status"><?php esc_html_e( 'Status', 'unstoppable-memberships' ); ?></label></th>
				<td>
					<select id="umem_member_status" name="umem_member[status]">
						<option value="active"><?php esc_html_e( 'Active', 'unstoppable-memberships' ); ?></option>
						<option value="trialing"><?php esc_html_e( 'Trialing', 'unstoppable-memberships' ); ?></option>
						<option value="paused"><?php esc_html_e( 'Paused', 'unstoppable-memberships' ); ?></option>
						<option value="cancelled"><?php esc_html_e( 'Cancelled', 'unstoppable-memberships' ); ?></option>
						<option value="expired"><?php esc_html_e( 'Expired', 'unstoppable-memberships' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="umem_member_start"><?php esc_html_e( 'Start date', 'unstoppable-memberships' ); ?></label></th>
				<td><input type="date" id="umem_member_start" name="umem_member[start_date]" value="<?php echo esc_attr( $today ); ?>" /></td>
			</tr>
			<tr>
				<th scope="row"><label for="umem_member_expires"><?php esc_html_e( 'Expiration date', 'unstoppable-memberships' ); ?></label></th>
				<td>
					<input type="date" id="umem_member_expires" name="umem_member[expires]" value="" />
					<p class="umem-field-help"><?php esc_html_e( 'Leave blank for no expiration (e.g. a free or lifetime membership).', 'unstoppable-memberships' ); ?></p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Add Member', 'unstoppable-memberships' ); ?></button>
		</p>
	</form>

	<?php endif; ?>
</div>
