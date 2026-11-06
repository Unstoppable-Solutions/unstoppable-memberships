<?php
/**
 * Global helper functions.
 *
 * Small, reusable helpers that templates, integrations, and third-party
 * developers can call safely. Keep heavy logic inside classes; these are
 * thin convenience wrappers.
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return a prefixed custom table name.
 *
 * @param string $table Logical table key (e.g. 'levels', 'subscriptions').
 * @return string Full table name including $wpdb->prefix.
 */
function umem_table( $table ) {
	global $wpdb;
	return $wpdb->prefix . 'umem_' . sanitize_key( $table );
}

/**
 * Get a plugin option from the single umem_settings array.
 *
 * @param string $key     Option key.
 * @param mixed  $default Default value if not set.
 * @return mixed
 */
function umem_get_option( $key, $default = '' ) {
	$settings = get_option( 'umem_settings', array() );
	return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
}

/**
 * Update a single plugin option inside the umem_settings array.
 *
 * @param string $key   Option key.
 * @param mixed  $value Value to store.
 * @return bool
 */
function umem_update_option( $key, $value ) {
	$settings         = get_option( 'umem_settings', array() );
	$settings[ $key ] = $value;
	return update_option( 'umem_settings', $settings );
}

/**
 * Format an amount (stored in major currency units as a decimal) for display.
 *
 * @param float|int|string $amount   Amount, e.g. 9.99.
 * @param string           $currency 3-letter ISO currency code.
 * @return string
 */
function umem_format_price( $amount, $currency = '' ) {
	$currency = $currency ? strtoupper( $currency ) : strtoupper( umem_get_option( 'currency', 'USD' ) );
	$symbols  = array(
		'USD' => '$',
		'EUR' => '€',
		'GBP' => '£',
		'CAD' => 'CA$',
		'AUD' => 'A$',
	);
	$symbol = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : $currency . ' ';
	return $symbol . number_format_i18n( (float) $amount, 2 );
}

/**
 * Locate and load a frontend template.
 *
 * Theme overrides are supported: a theme can place a file at
 * /unstoppable-memberships/{template}.php to override the bundled one.
 *
 * @param string $template Template filename without extension.
 * @param array  $args     Variables exposed to the template.
 * @return void
 */
function umem_get_template( $template, $args = array() ) {
	$template = sanitize_file_name( $template . '.php' );

	$theme_file  = trailingslashit( 'unstoppable-memberships' ) . $template;
	$located     = locate_template( $theme_file );
	$plugin_file = UMEM_PLUGIN_DIR . 'templates/' . $template;

	$file = $located ? $located : $plugin_file;

	if ( ! file_exists( $file ) ) {
		return;
	}

	if ( ! empty( $args ) && is_array( $args ) ) {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- controlled, sanitized keys.
		extract( $args, EXTR_SKIP );
	}

	include $file;
}

/**
 * Is the given (or current) user an active member of any/specific level?
 *
 * @param int   $user_id   User ID. Defaults to current user.
 * @param int[] $level_ids Optional list of level IDs to check against.
 * @return bool
 */
function umem_user_has_access( $user_id = 0, $level_ids = array() ) {
	return UMEM_Plugin::instance()->subscriptions
		? UMEM_Plugin::instance()->subscriptions->user_has_access( $user_id, $level_ids )
		: false;
}
