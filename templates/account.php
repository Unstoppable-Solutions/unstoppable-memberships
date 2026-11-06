<?php
/**
 * Template: Member account / dashboard.
 *
 * Shows the current member's subscriptions, status, and billing history.
 * Override by copying to /your-theme/unstoppable-memberships/account.php
 *
 * @var int $user_id Current user ID (0 if logged out).
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$user_id = isset( $user_id ) ? absint( $user_id ) : 0;

if ( ! $user_id ) {
	$pages      = (array) umem_get_option( 'pages' );
	$login_page = isset( $pages['login'] ) ? (int) $pages['login'] : 0;
	$login_url  = $login_page ? get_permalink( $login_page ) : wp_login_url( get_permalink() );
	?>
	<div class="umem-account umem-account--guest">
		<p class="umem-notice"><?php esc_html_e( 'Please log in to view your account.', 'unstoppable-memberships' ); ?></p>
		<a class="umem-button" href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Log in', 'unstoppable-memberships' ); ?></a>
	</div>
	<?php
	return;
}

$user          = get_userdata( $user_id );
$pages         = (array) umem_get_option( 'pages' );
$subscriptions = UMEM_Plugin::instance()->subscriptions->get_user_subscriptions( $user_id );

global $wpdb;
$payments_table = umem_table( 'payments' );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$payments = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT * FROM {$payments_table} WHERE user_id = %d ORDER BY created_at DESC LIMIT 50",
		$user_id
	)
);

/**
 * Map a subscription status to a human label + CSS modifier.
 *
 * @param string $status Raw status.
 * @return array{label:string,class:string}
 */
$umem_status_meta = static function ( $status ) {
	switch ( $status ) {
		case 'active':
			return array( 'label' => __( 'Active', 'unstoppable-memberships' ), 'class' => 'is-active' );
		case 'trialing':
			return array( 'label' => __( 'Trialing', 'unstoppable-memberships' ), 'class' => 'is-trial' );
		case 'cancelled':
			return array( 'label' => __( 'Cancelled', 'unstoppable-memberships' ), 'class' => 'is-cancelled' );
		case 'paused':
			return array( 'label' => __( 'Paused', 'unstoppable-memberships' ), 'class' => 'is-paused' );
		case 'expired':
			return array( 'label' => __( 'Expired', 'unstoppable-memberships' ), 'class' => 'is-expired' );
		default:
			return array( 'label' => ucfirst( $status ), 'class' => 'is-other' );
	}
};
?>
<div class="umem-account">

	<?php
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only status from the free-join return.
	if ( ! empty( $_GET['umem_joined'] ) ) :
		?>
		<div class="umem-notice umem-notice--success">
			<?php esc_html_e( 'Thanks! Your membership is active.', 'unstoppable-memberships' ); ?>
		</div>
	<?php endif; ?>

	<header class="umem-account-header">
		<h2 class="umem-account-title">
			<?php
			/* translators: %s: member display name. */
			printf( esc_html__( 'Welcome, %s', 'unstoppable-memberships' ), esc_html( $user ? $user->display_name : '' ) );
			?>
		</h2>
		<a class="umem-logout-link" href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>">
			<?php esc_html_e( 'Log out', 'unstoppable-memberships' ); ?>
		</a>
	</header>

	<section class="umem-account-section">
		<h3 class="umem-account-subtitle"><?php esc_html_e( 'Your Memberships', 'unstoppable-memberships' ); ?></h3>

		<?php if ( empty( $subscriptions ) ) : ?>
			<p class="umem-notice"><?php esc_html_e( 'You do not have any memberships yet.', 'unstoppable-memberships' ); ?></p>
			<?php
			$pricing_page = isset( $pages['pricing'] ) ? (int) $pages['pricing'] : 0;
			if ( $pricing_page ) :
				?>
				<a class="umem-button" href="<?php echo esc_url( get_permalink( $pricing_page ) ); ?>">
					<?php esc_html_e( 'View Membership Options', 'unstoppable-memberships' ); ?>
				</a>
			<?php endif; ?>
		<?php else : ?>
			<div class="umem-sub-list">
				<?php foreach ( $subscriptions as $sub ) : ?>
					<?php
					$level = UMEM_Levels::get( $sub->level_id );
					$meta  = $umem_status_meta( $sub->status );
					?>
					<article class="umem-sub-card <?php echo esc_attr( $meta['class'] ); ?>">
						<div class="umem-sub-card-head">
							<span class="umem-sub-name"><?php echo esc_html( $level ? $level->name : __( 'Unknown level', 'unstoppable-memberships' ) ); ?></span>
							<span class="umem-badge umem-badge--<?php echo esc_attr( $meta['class'] ); ?>"><?php echo esc_html( $meta['label'] ); ?></span>
						</div>
						<ul class="umem-sub-meta">
							<?php if ( $level && 'free' !== $level->billing_type ) : ?>
								<li>
									<span class="umem-sub-meta-label"><?php esc_html_e( 'Price', 'unstoppable-memberships' ); ?></span>
									<span><?php echo esc_html( umem_format_price( $level->price, $level->currency ) ); ?><?php echo 'recurring' === $level->billing_type ? ' / ' . esc_html( $level->billing_interval ) : ''; ?></span>
								</li>
							<?php endif; ?>
							<?php if ( ! empty( $sub->current_period_end ) && '0000-00-00 00:00:00' !== $sub->current_period_end ) : ?>
								<li>
									<span class="umem-sub-meta-label"><?php echo 'cancelled' === $sub->status ? esc_html__( 'Access until', 'unstoppable-memberships' ) : esc_html__( 'Renews', 'unstoppable-memberships' ); ?></span>
									<span><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $sub->current_period_end ) ) ); ?></span>
								</li>
							<?php endif; ?>
							<?php if ( ! empty( $sub->trial_end ) && '0000-00-00 00:00:00' !== $sub->trial_end && 'trialing' === $sub->status ) : ?>
								<li>
									<span class="umem-sub-meta-label"><?php esc_html_e( 'Trial ends', 'unstoppable-memberships' ); ?></span>
									<span><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $sub->trial_end ) ) ); ?></span>
								</li>
							<?php endif; ?>
						</ul>

						<?php
						/**
						 * Render subscription management actions (cancel / pause / upgrade).
						 * Action handlers are wired in a later pass; the hook is exposed now.
						 *
						 * @param object $sub Subscription row.
						 */
						do_action( 'umem_account_subscription_actions', $sub );
						?>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</section>

	<section class="umem-account-section">
		<h3 class="umem-account-subtitle"><?php esc_html_e( 'Billing History', 'unstoppable-memberships' ); ?></h3>

		<?php if ( empty( $payments ) ) : ?>
			<p class="umem-notice"><?php esc_html_e( 'No payments recorded yet.', 'unstoppable-memberships' ); ?></p>
		<?php else : ?>
			<table class="umem-table umem-billing-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'unstoppable-memberships' ); ?></th>
						<th><?php esc_html_e( 'Amount', 'unstoppable-memberships' ); ?></th>
						<th><?php esc_html_e( 'Type', 'unstoppable-memberships' ); ?></th>
						<th><?php esc_html_e( 'Status', 'unstoppable-memberships' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $payments as $payment ) : ?>
						<tr>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $payment->created_at ) ) ); ?></td>
							<td><?php echo esc_html( umem_format_price( $payment->amount, $payment->currency ) ); ?></td>
							<td><?php echo esc_html( ucfirst( $payment->type ) ); ?></td>
							<td><span class="umem-badge umem-badge--<?php echo esc_attr( sanitize_html_class( $payment->status ) ); ?>"><?php echo esc_html( ucfirst( $payment->status ) ); ?></span></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</section>

	<?php
	/**
	 * Append extra account sections (payment method, downloads, profile, etc.).
	 *
	 * @param int $user_id Current member ID.
	 */
	do_action( 'umem_account_after_sections', $user_id );
	?>
</div>
