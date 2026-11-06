<?php
/**
 * Core plugin orchestrator.
 *
 * Loads dependencies, instantiates components, and registers the shared hooks.
 * A single instance is shared via UMEM_Plugin::instance() / umem().
 *
 * @package Unstoppable_Memberships
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class UMEM_Plugin
 */
final class UMEM_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var UMEM_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Subscriptions component.
	 *
	 * @var UMEM_Subscriptions|null
	 */
	public $subscriptions = null;

	/**
	 * Content restriction component.
	 *
	 * @var UMEM_Content_Restriction|null
	 */
	public $restriction = null;

	/**
	 * Shortcodes component.
	 *
	 * @var UMEM_Shortcodes|null
	 */
	public $shortcodes = null;

	/**
	 * Admin component.
	 *
	 * @var UMEM_Admin|null
	 */
	public $admin = null;

	/**
	 * Public/front component.
	 *
	 * @var UMEM_Public|null
	 */
	public $public = null;

	/**
	 * Get the shared instance.
	 *
	 * @return UMEM_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->setup();
		}
		return self::$instance;
	}

	/**
	 * Private constructor (singleton).
	 */
	private function __construct() {}

	/**
	 * Load files and register the bootstrap hook.
	 *
	 * @return void
	 */
	private function setup() {
		$this->includes();
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Require component class files.
	 *
	 * @return void
	 */
	private function includes() {
		require_once UMEM_PLUGIN_DIR . 'includes/class-umem-levels.php';
		require_once UMEM_PLUGIN_DIR . 'includes/class-umem-subscriptions.php';
		require_once UMEM_PLUGIN_DIR . 'includes/class-umem-content-restriction.php';
		require_once UMEM_PLUGIN_DIR . 'includes/class-umem-shortcodes.php';

		if ( is_admin() ) {
			require_once UMEM_PLUGIN_DIR . 'admin/class-umem-admin.php';
		}
		require_once UMEM_PLUGIN_DIR . 'public/class-umem-public.php';
	}

	/**
	 * Boot on plugins_loaded.
	 *
	 * @return void
	 */
	public function init() {
		// Run pending DB migrations.
		UMEM_Installer::maybe_upgrade();

		// Core components, always available.
		$this->subscriptions = new UMEM_Subscriptions();

		$this->restriction = new UMEM_Content_Restriction();
		$this->restriction->init();

		$this->shortcodes = new UMEM_Shortcodes();
		$this->shortcodes->init();

		// Context-specific.
		if ( is_admin() ) {
			$this->admin = new UMEM_Admin();
			$this->admin->init();
		}
		$this->public = new UMEM_Public();
		$this->public->init();

		// Cron handlers.
		add_action( 'umem_daily_event', array( $this, 'daily_tasks' ) );

		// Deferred rewrite flush after activation.
		add_action( 'init', array( $this, 'maybe_flush_rewrites' ), 99 );

		/**
		 * Fires once Unstoppable Memberships is fully loaded.
		 *
		 * @param UMEM_Plugin $plugin The plugin instance.
		 */
		do_action( 'umem_loaded', $this );
	}

	/**
	 * Flush rewrite rules once after activation.
	 *
	 * @return void
	 */
	public function maybe_flush_rewrites() {
		if ( 'yes' === get_option( 'umem_flush_rewrite' ) ) {
			flush_rewrite_rules();
			delete_option( 'umem_flush_rewrite' );
		}
	}

	/**
	 * Daily maintenance: expire memberships past their end date.
	 *
	 * @return void
	 */
	public function daily_tasks() {
		global $wpdb;
		$table = umem_table( 'subscriptions' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = 'expired', updated_at = %s
				 WHERE status IN ( 'active', 'cancelled', 'trialing' )
				 AND end_date IS NOT NULL AND end_date <> '0000-00-00 00:00:00' AND end_date < %s",
				current_time( 'mysql' ),
				current_time( 'mysql' )
			)
		);

		/**
		 * Fires on the daily cron after expirations are processed.
		 */
		do_action( 'umem_daily_maintenance' );
	}
}
