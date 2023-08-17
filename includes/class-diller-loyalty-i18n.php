<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://diller.no/contact-us
 * @since      2.0.0
 *
 * @package    Diller_Loyalty
 * @subpackage Diller_Loyalty/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      2.0.0
 * @package    Diller_Loyalty
 * @subpackage Diller_Loyalty/includes
 * @author     Diller AS <dev@diller.no>
 */
class Diller_Loyalty_i18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    2.0.0
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( DILLER_LOYALTY_PLUGIN_NAME, false, trailingslashit(plugin_basename( dirname( DILLER_LOYALTY_PLUGIN_FILE ) )) . 'languages/' );
	}

	/* Since WordPress 4.6 translations now take translate.wordpress.org as priority and so
	 * plugins that are translated via translate.wordpress.org do not necessary require load_plugin_textdomain() anymore.
	 * If you don’t want to add a load_plugin_textdomain() call to your plugin you have to set the Requires at least: field in your readme.txt to 4.6 or more.
	 * More info: https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/#plugins-on-wordpress-org */

	// If you still want to load your own translations and not the ones from translate, you will have to use a hook filter named load_textdomain_mofile.
	// Example with a .mo file in the /languages/ directory of your plugin, with this code inserted in the main plugin file:
	// function my_plugin_load_my_own_textdomain( $mofile, $domain ) {
	// 	if ( 'my-domain' === $domain && false !== strpos( $mofile, WP_LANG_DIR . '/plugins/' ) ) {
	// 		$locale = apply_filters( 'plugin_locale', determine_locale(), $domain );
	// 		$mofile = WP_PLUGIN_DIR . '/' . dirname( plugin_basename( __FILE__ ) ) . '/languages/' . $domain . '-' . $locale . '.mo';
	// 	}
	// 	return $mofile;
	// }
	// add_filter( 'load_textdomain_mofile', 'my_plugin_load_my_own_textdomain', 10, 2 );
}
