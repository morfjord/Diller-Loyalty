<?php

/**
 *
 * Plugin Name:          Diller Loyalty 2
 * Plugin URI:           https://diller.no/
 * Description:          Diller is a loyalty platform for businesses that is easy, affordable and profitable and integrates seamlessly with your WooCommerce shop.
 * Version:              2.3.0
 * Author:               Diller AS
 * Author URI:           https://diller.no/kontakt/
 * License:              MIT
 * License URI:          https://choosealicense.com/licenses/mit/
 * Text Domain:          diller-loyalty
 * Domain Path:          /languages
 * Stable tag:           2.3.0
 * Requires at least:    4.7
 * Tested up to:         5.8.2
 * Requires PHP:         7.3
 * WC requires at least: 3.8.0
 * WC tested up to:      6.0.0
 *
 */


// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// Start at version 2.0.0 and use SemVer - https://semver.org
if ( ! defined( 'DILLER_LOYALTY_VERSION' ) ) {
	define('DILLER_LOYALTY_VERSION', '2.3.0');
}

if ( ! defined( 'DILLER_LOYALTY_PLUGIN_NAME' ) ) {
	define( 'DILLER_LOYALTY_PLUGIN_NAME', 'diller-loyalty' );
}

if ( ! defined( 'DILLER_LOYALTY_PLUGIN_FILE' ) ) {
	define( 'DILLER_LOYALTY_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'DILLER_LOYALTY_PATH' ) ) {
	define( 'DILLER_LOYALTY_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'DILLER_LOYALTY_URL' ) ) {
	define( 'DILLER_LOYALTY_URL', plugins_url( '', __FILE__ ) );
}

if ( ! defined( 'DILLER_LOYALTY_ENROLLMENT_PAGE_SLUG' ) ) {
	define('DILLER_LOYALTY_ENROLLMENT_PAGE_SLUG', 'innmelding-kundeklubb');
}

if ( ! defined( 'DILLER_LOYALTY_ENVIRONMENT' ) ) {
	define('DILLER_LOYALTY_ENVIRONMENT', 'production');
}

if ( ! defined( 'DILLER_LOYALTY_API_PROTOCOL' ) ) {
	define('DILLER_LOYALTY_API_PROTOCOL', 'https://');
}

if ( ! defined( 'DILLER_LOYALTY_API_BASE' ) ) {
	
	define('DILLER_LOYALTY_API_BASE', 'diller.app');
}

// If test mode is enabled, under Diller Loyalty -> Settings, then this test server is used instead of the live api.
if ( ! defined( 'DILLER_LOYALTY_TEST_API_BASE' ) ) {
	define('DILLER_LOYALTY_TEST_API_BASE', 'prerelease.dillerapp.no');
}

if ( ! defined( 'DILLER_LOYALTY_MODE' ) ) {
	define('DILLER_LOYALTY_MODE', 'production');
}



if ( ! defined( 'DILLER_LOYALTY_BRAND_COLOR' ) ) {
	define('DILLER_LOYALTY_BRAND_COLOR', '#000000');
}

if ( ! defined( 'DILLER_LOYALTY_JS_VENDORS_BUNDLE_HANDLE' ) ) {
	define( 'DILLER_LOYALTY_JS_VENDORS_BUNDLE_HANDLE', 'diller-loyalty-vendors-bundle');
}


// Load plugin Activation/Deactivation code only for admin
if ( is_admin() && !( defined( 'DOING_AJAX' ) && DOING_AJAX ) ):

	/**
	 * The code that runs during plugin activation.
	 * This action is documented in includes/class-diller-loyalty-activator.php
	 */
	function activate_diller_loyalty($network_wide = false) {
		require_once DILLER_LOYALTY_PATH . 'includes/class-diller-loyalty-utils.php';
		require_once DILLER_LOYALTY_PATH . 'includes/class-diller-loyalty-activator.php';

		if ( is_multisite() && $network_wide) {
			foreach (get_sites(array("fields" => "ids" )) as $blog_id) {
				switch_to_blog($blog_id);
				Diller_Loyalty_Activator::activate();
				restore_current_blog();
			}
		}else{
			Diller_Loyalty_Activator::activate();
		}
	}
	register_activation_hook( __FILE__, 'activate_diller_loyalty' );

	/**
	 * The code that runs during plugin deactivation.
	 * This action is documented in includes/class-diller-loyalty-deactivator.php
	 */
	function deactivate_diller_loyalty($network_wide = false) {
		require_once DILLER_LOYALTY_PATH . 'includes/class-diller-loyalty-deactivator.php';
		Diller_Loyalty_Deactivator::deactivate();
	}
	register_deactivation_hook( __FILE__, 'deactivate_diller_loyalty' );

	/**
	 * Add a Settings link right next to the plugin actions (eg. Activate, Deactivate, Delete)
	 * @param $links
	 * @param $file
	 *
	 * @return mixed
	 */
	function diller_plugin_action_links( $links, $file ) {
		if ( plugin_basename(__FILE__ ) === $file ) {
			$settings_link = '<a href="options-general.php?page='. DILLER_LOYALTY_PLUGIN_NAME .'">' . esc_html__( 'Settings', 'diller-loyalty' ) . '</a>';
			array_unshift( $links, $settings_link );
		}
		return $links;
	}
	add_filter( 'plugin_action_links', 'diller_plugin_action_links', 10, 2 );
endif;


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once DILLER_LOYALTY_PATH . 'includes/class-diller-loyalty.php';

/**
 * Begins execution of the plugin and load all it's dependencies
 */
if ( !function_exists( 'DillerLoyalty' ) ) {

	/**
	 * The function responsible for returning the one true Diller_Loyalty Singletone Instance.
	 * Usage Example: <code><?php $diller_loyalty = Diller_Loyalty(); ?></code>
	 *
	 * @uses Diller_Loyalty::get_instance() Retrieve Diller_Loyalty instance.
	 *
	 * @return Diller_Loyalty Diller_Loyalty singleton instance.
	 */
	function DillerLoyalty() {
		return Diller_Loyalty::get_instance();
	}
	add_action( 'plugins_loaded', 'DillerLoyalty', 999 );
}
