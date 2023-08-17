<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://diller.no/contact-us
 * @since      2.0.0
 *
 * @package    Diller_Loyalty
 * @subpackage Diller_Loyalty/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Diller_Loyalty
 * @subpackage Diller_Loyalty/public
 * @author     Diller AS <dev@diller.no>
 */
class Diller_Loyalty_Public {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.0.0
	 */
	public function __construct() {

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    2.0.0
	 */
	public function enqueue_styles() {

		

		wp_enqueue_style( DILLER_LOYALTY_PLUGIN_NAME, trailingslashit( DILLER_LOYALTY_URL ) . 'assets/css/diller-loyalty-bundle-public.css', array(), DillerLoyalty()->get_version("assets"), 'all' );

		// Inline style overrides (via css variables) using store configuration defined in Diller retailer panel
		wp_register_style( 'diller-store-styles', false );
		wp_enqueue_style( 'diller-store-styles' );
		wp_add_inline_style( 'diller-store-styles', DillerLoyalty()->get_store()->get_store_css_styles() );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    2.0.0
	 */
	public function enqueue_scripts() {

		
		// Enqueues all the necessary 3rd party scripts bundles into a single file. This matches all the dependency files added via npm command
		wp_enqueue_script( DILLER_LOYALTY_JS_VENDORS_BUNDLE_HANDLE, trailingslashit( DILLER_LOYALTY_URL ) . 'assets/js/vendors-bundle.js', array( 'jquery' ), DillerLoyalty()->get_version("assets"), true );
		wp_enqueue_script( 'diller-loyalty-public-bundle', trailingslashit( DILLER_LOYALTY_URL ) . 'assets/js/diller-loyalty-public-bundle.js', array( DILLER_LOYALTY_JS_VENDORS_BUNDLE_HANDLE), DillerLoyalty()->get_version("assets"), true );
	}

}
