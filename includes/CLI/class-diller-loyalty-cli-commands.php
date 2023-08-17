<?php

/**
 * The file that defines Diller's CLI commands.
 *
 *
 * @link       https://diller.no/contact-us
 * @since      2.3.0
 *
 * @package    Diller_Loyalty
 * @subpackage Diller_Loyalty/includes/CLI
 * @author Tiago Teixeira
 */

// Exit if accessed directly.


/**
 *
 * class-diller-loyalty-cli-commands.php is a class ...
 *
 *
 * @since 2.0.0
 *
 * @package DillerLoyalty
 * @author Tiago Teixeira
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Performs some operations on behalf of Diller Loyalty Plugin.
 */
final class Diller_Loyalty_CLI_Commands {

	public function __construct() {

	}

    /**
     * Migrates old data from Diller plugin v1.6.x and 1.7.x to the new 2.x format.
     * 
     * ## OPTIONS
     *
     * [<filter>]
     * : Restrict to a type of objects / entities to migrate (users|settings|shortcodes)
     *
     * [--dry-run]
     * : Specify dry run. Makes no changes on the database.
     * 
     * ## EXAMPLES
     * 
     * wp diller migrate <users|settings|shortcodes> --dry-run
     * wp diller migrate --url=http://your.multisite.com/store-xyz/
     * 
     * 
     * @param string[]
     * @param string[]
     */
    public function migrate( $args, $opts ){
	    require_once( DILLER_LOYALTY_PATH . 'includes/CLI/class-diller-loyalty-cli-migration-command.php');

		if(!isset($args[0]) || !in_array($args[0], array("users","settings","shortcodes"))){
			WP_CLI::error("Please pass the migration you wish to perform. Example: wp diller migrate <users|settings|shortcodes>");
			exit;
		}

        try {
	        Diller_Loyalty_CLI_Migration_Command::run($args, $opts);
        }
        catch(Exception $e ){
            WP_CLI::error( $e->getMessage() );
        }
    }

}
    