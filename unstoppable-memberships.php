<?php
/**
 * Plugin Name:       Unstoppable Memberships
 * Plugin URI:        https://unstoppablesolutions.app/plugins/unstoppable-memberships
 * Description:       A fast, clean, lightweight WordPress membership plugin: free membership levels, powerful content restriction, drip content, and member management.
 * Version:           2.0.0
 * Author:            Unstoppable Solutions
 * Author URI:        https://unstoppablesolutions.app
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       unstoppable-memberships
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Tested up to:      7.0
 * Requires PHP:      7.4
 *
 * @package Unstoppable_Memberships
 */

// Abort if this file is called directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * --------------------------------------------------------------------------
 * Constants
 * --------------------------------------------------------------------------
 * Everything is prefixed with UMEM_ to avoid collisions with other plugins.
 */
define( 'UMEM_VERSION', '2.0.0' );
define( 'UMEM_DB_VERSION', '1.0.0' );
define( 'UMEM_PLUGIN_SLUG', 'unstoppable-memberships' );
define( 'UMEM_OPTION_PREFIX', 'umem' );
define( 'UMEM_PLUGIN_FILE', __FILE__ );
define( 'UMEM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );   // …/unstoppable-memberships/
define( 'UMEM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );    // https://site/wp-content/plugins/unstoppable-memberships/
define( 'UMEM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/*
 * --------------------------------------------------------------------------
 * Core includes
 * --------------------------------------------------------------------------
 */
require_once UMEM_PLUGIN_DIR . 'includes/functions.php';
require_once UMEM_PLUGIN_DIR . 'includes/class-umem-installer.php';
require_once UMEM_PLUGIN_DIR . 'includes/class-umem-activator.php';
require_once UMEM_PLUGIN_DIR . 'includes/class-umem-deactivator.php';
require_once UMEM_PLUGIN_DIR . 'includes/class-umem-plugin.php';

/*
 * --------------------------------------------------------------------------
 * Activation / Deactivation hooks
 * --------------------------------------------------------------------------
 * These must be registered in the main file, not inside a hook.
 */
register_activation_hook( __FILE__, array( 'UMEM_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'UMEM_Deactivator', 'deactivate' ) );

/*
 * --------------------------------------------------------------------------
 * WooCommerce feature compatibility
 * --------------------------------------------------------------------------
 * This is a standalone plugin and never reads or writes WooCommerce orders,
 * so it is fully compatible with High-Performance Order Storage (HPOS) and
 * the cart/checkout blocks. Declaring this clears WooCommerce's
 * "incompatible plugins" admin notice on sites that also run WooCommerce.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', UMEM_PLUGIN_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', UMEM_PLUGIN_FILE, true );
		}
	}
);

/**
 * Boot the plugin once all plugins are loaded.
 *
 * Using a single shared instance keeps global state predictable and lets
 * integrations grab the running plugin via umem() if they need to.
 *
 * @return UMEM_Plugin
 */
function umem() {
	return UMEM_Plugin::instance();
}

// Kick everything off.
umem();
