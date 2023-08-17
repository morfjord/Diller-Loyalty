<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://diller.no/contact-us
 * @since      2.0.0
 *
 * @package    Diller_Loyalty
 * @subpackage Diller_Loyalty/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      2.0.0
 * @package    Diller_Loyalty
 * @subpackage Diller_Loyalty/includes
 * @author     Diller AS <dev@diller.no>
 */
class Diller_Loyalty_Deactivator {

	public static function deactivate() {
		DillerLoyalty()->get_logger()->info("Plugin deactivated.");
	}

}
