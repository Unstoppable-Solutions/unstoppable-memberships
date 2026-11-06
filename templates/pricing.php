<?php
/**
 * Template: Pricing table.
 *
 * Override by copying to /your-theme/unstoppable-memberships/pricing.php
 *
 * @var object[] $levels Active membership levels.
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $levels ) ) {
	echo '<p class="umem-notice">' . esc_html__( 'No membership options are available yet.', 'unstoppable-memberships' ) . '</p>';
	return;
}
?>
<div class="umem-pricing">
	<?php foreach ( $levels as $level ) : ?>
		<div class="umem-plan">
			<?php if ( ! empty( $level->badge ) ) : ?>
				<span class="umem-plan-badge"><?php echo esc_html( $level->badge ); ?></span>
			<?php endif; ?>
			<h3 class="umem-plan-name"><?php echo esc_html( $level->name ); ?></h3>
			<div class="umem-plan-price">
				<?php if ( 'free' === $level->billing_type ) : ?>
					<?php esc_html_e( 'Free', 'unstoppable-memberships' ); ?>
				<?php else : ?>
					<strong><?php echo esc_html( umem_format_price( $level->price, $level->currency ) ); ?></strong>
					<?php if ( 'recurring' === $level->billing_type ) : ?>
						<span class="umem-plan-interval">/ <?php echo esc_html( $level->billing_interval ); ?></span>
					<?php endif; ?>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $level->description ) ) : ?>
				<div class="umem-plan-desc"><?php echo wp_kses_post( wpautop( $level->description ) ); ?></div>
			<?php endif; ?>
			<?php if ( (int) $level->trial_days > 0 ) : ?>
				<p class="umem-plan-trial"><?php printf( esc_html__( '%d-day free trial', 'unstoppable-memberships' ), (int) $level->trial_days ); ?></p>
			<?php endif; ?>
			<a class="umem-button umem-plan-cta" href="<?php echo esc_url( add_query_arg( 'umem_level', (int) $level->id, '' ) ); ?>">
				<?php echo 'free' === $level->billing_type ? esc_html__( 'Join free', 'unstoppable-memberships' ) : esc_html__( 'Choose plan', 'unstoppable-memberships' ); ?>
			</a>
		</div>
	<?php endforeach; ?>
</div>
