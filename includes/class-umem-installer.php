<?php
/**
 * Database installer.
 *
 * Creates and upgrades the custom tables, and handles DB versioning so future
 * releases can migrate cleanly. Uses dbDelta() for safe, idempotent creation.
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UMEM_Installer
 */
class UMEM_Installer {

	/**
	 * Run installation/upgrade. Safe to call repeatedly (dbDelta is idempotent).
	 *
	 * @return void
	 */
	public static function install() {
		self::create_tables();
		self::set_default_options();

		update_option( 'umem_db_version', UMEM_DB_VERSION );
		update_option( 'umem_version', UMEM_VERSION );
	}

	/**
	 * Check the stored DB version and run migrations if the code is newer.
	 *
	 * Hooked on plugins_loaded so upgrades apply even when the plugin is
	 * updated via the WordPress updater (no activation hook fires then).
	 *
	 * @return void
	 */
	public static function maybe_upgrade() {
		$installed = get_option( 'umem_db_version', '0' );

		if ( version_compare( $installed, UMEM_DB_VERSION, '<' ) ) {
			self::install(); // dbDelta will add new columns/tables safely.
			/**
			 * Fires after a database upgrade completes.
			 *
			 * @param string $installed Previously installed version.
			 * @param string $new       Version just installed.
			 */
			do_action( 'umem_db_upgraded', $installed, UMEM_DB_VERSION );
		}
	}

	/**
	 * Create all custom tables.
	 *
	 * @return void
	 */
	private static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$levels        = umem_table( 'levels' );
		$subscriptions = umem_table( 'subscriptions' );
		$payments      = umem_table( 'payments' );
		$coupons       = umem_table( 'coupons' );
		$rules         = umem_table( 'content_rules' );
		$email_logs    = umem_table( 'email_logs' );
		$member_meta   = umem_table( 'member_meta' );
		$audit_log     = umem_table( 'audit_log' );

		$schemas = array();

		// Membership levels.
		$schemas[] = "CREATE TABLE {$levels} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL DEFAULT '',
			slug VARCHAR(191) NOT NULL DEFAULT '',
			description TEXT NULL,
			price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
			currency VARCHAR(3) NOT NULL DEFAULT 'USD',
			billing_type VARCHAR(20) NOT NULL DEFAULT 'free',
			billing_interval VARCHAR(20) NOT NULL DEFAULT 'month',
			billing_interval_count INT UNSIGNED NOT NULL DEFAULT 1,
			trial_days INT UNSIGNED NOT NULL DEFAULT 0,
			signup_fee DECIMAL(12,2) NOT NULL DEFAULT 0.00,
			grace_days INT UNSIGNED NOT NULL DEFAULT 0,
			roles VARCHAR(191) NOT NULL DEFAULT '',
			badge VARCHAR(191) NOT NULL DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			menu_order INT NOT NULL DEFAULT 0,
			stripe_price_id VARCHAR(191) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY slug (slug),
			KEY status (status)
		) {$charset_collate};";

		// Subscriptions / memberships (one row per user-level membership).
		$schemas[] = "CREATE TABLE {$subscriptions} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			level_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			gateway VARCHAR(30) NOT NULL DEFAULT '',
			gateway_customer_id VARCHAR(191) NOT NULL DEFAULT '',
			gateway_subscription_id VARCHAR(191) NOT NULL DEFAULT '',
			start_date DATETIME NULL,
			trial_end DATETIME NULL,
			current_period_end DATETIME NULL,
			cancel_at DATETIME NULL,
			end_date DATETIME NULL,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY level_id (level_id),
			KEY status (status),
			KEY gateway_subscription_id (gateway_subscription_id)
		) {$charset_collate};";

		// Payments / invoice records. No card data is ever stored.
		$schemas[] = "CREATE TABLE {$payments} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			subscription_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			level_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			gateway VARCHAR(30) NOT NULL DEFAULT '',
			gateway_customer_id VARCHAR(191) NOT NULL DEFAULT '',
			gateway_payment_id VARCHAR(191) NOT NULL DEFAULT '',
			gateway_invoice_id VARCHAR(191) NOT NULL DEFAULT '',
			amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
			currency VARCHAR(3) NOT NULL DEFAULT 'USD',
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			type VARCHAR(20) NOT NULL DEFAULT 'payment',
			coupon_code VARCHAR(191) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY subscription_id (subscription_id),
			KEY status (status),
			KEY gateway_payment_id (gateway_payment_id)
		) {$charset_collate};";

		// Coupons / discount codes.
		$schemas[] = "CREATE TABLE {$coupons} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			code VARCHAR(191) NOT NULL DEFAULT '',
			type VARCHAR(20) NOT NULL DEFAULT 'percent',
			amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
			level_ids VARCHAR(191) NOT NULL DEFAULT '',
			max_redemptions INT UNSIGNED NOT NULL DEFAULT 0,
			redemptions INT UNSIGNED NOT NULL DEFAULT 0,
			expires_at DATETIME NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY code (code),
			KEY status (status)
		) {$charset_collate};";

		// Content restriction rules (global rules; per-post rules live in post meta).
		$schemas[] = "CREATE TABLE {$rules} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL DEFAULT '',
			object_type VARCHAR(30) NOT NULL DEFAULT 'post',
			object_value VARCHAR(191) NOT NULL DEFAULT '',
			access_type VARCHAR(30) NOT NULL DEFAULT 'levels',
			level_ids VARCHAR(191) NOT NULL DEFAULT '',
			drip_type VARCHAR(20) NOT NULL DEFAULT 'none',
			drip_value VARCHAR(191) NOT NULL DEFAULT '',
			message TEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'active',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY object_type (object_type),
			KEY status (status)
		) {$charset_collate};";

		// Email send log (for auditing and recovery sequences).
		$schemas[] = "CREATE TABLE {$email_logs} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			email_key VARCHAR(60) NOT NULL DEFAULT '',
			recipient VARCHAR(191) NOT NULL DEFAULT '',
			subject VARCHAR(255) NOT NULL DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'sent',
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY email_key (email_key)
		) {$charset_collate};";

		// Flexible member meta (notes, health score, tags, etc.).
		$schemas[] = "CREATE TABLE {$member_meta} (
			meta_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			meta_key VARCHAR(191) NOT NULL DEFAULT '',
			meta_value LONGTEXT NULL,
			PRIMARY KEY  (meta_id),
			KEY user_id (user_id),
			KEY meta_key (meta_key(191))
		) {$charset_collate};";

		// Admin audit log.
		$schemas[] = "CREATE TABLE {$audit_log} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			action VARCHAR(60) NOT NULL DEFAULT '',
			object_type VARCHAR(30) NOT NULL DEFAULT '',
			object_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			details TEXT NULL,
			created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY action (action)
		) {$charset_collate};";

		foreach ( $schemas as $sql ) {
			dbDelta( $sql );
		}
	}

	/**
	 * Seed sensible default options on first install only.
	 *
	 * @return void
	 */
	private static function set_default_options() {
		if ( false !== get_option( 'umem_settings' ) ) {
			return; // Already configured; don't clobber.
		}

		$defaults = array(
			'currency'              => 'USD',
			'test_mode'             => 'yes',
			'stripe_test_pk'        => '',
			'stripe_test_sk'        => '',
			'stripe_live_pk'        => '',
			'stripe_live_sk'        => '',
			'stripe_webhook_secret' => '',
			'restricted_message'    => __( 'This content is for members only. Please log in or upgrade your membership to view it.', 'unstoppable-memberships' ),
			'restrict_mode'         => 'replace',
			'restrict_redirect_page' => 0,
			'pages'                 => array(
				'account'   => 0,
				'pricing'   => 0,
				'login'     => 0,
				'register'  => 0,
				'dashboard' => 0,
			),
			'setup_complete'        => 'no',
		);

		add_option( 'umem_settings', $defaults );
	}
}
