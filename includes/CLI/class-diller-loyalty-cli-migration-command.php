<?php

/**
 * The file that defines the CLI migration command code to upgrade for v1.6x and v1.7.x data
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
 * class-diller-loyalty-cli-migration-command.php is a class ...
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
 * Called from Diller_Loyalty_CLI_Migration_Command::migrate
 */
final class Diller_Loyalty_CLI_Migration_Command {

	private static $migration = null;
	private static $dry_run = true;
	private static $dry_run_str = '';

    public static function run($args, $opts){
	    self::$dry_run = isset($opts["dry-run"]);
	    self::$dry_run_str = isset($opts["dry-run"]) ? "**DRY RUN** " : "";

	    require_once( DILLER_LOYALTY_PATH . 'includes/migrations/class-diller-migration-161-20.php' );
	    self::$migration = new Diller_Loyalty_Migration_161_20('1.6.1', '2.0');

		if($args[0] === "shortcodes"){
			self::migrate_shortcodes($opts);
		}
	    if($args[0] === "settings"){
		    self::migrate_site_settings($opts);
	    }
	    if($args[0] === "users"){
		    self::migrate_users($opts);
	    }
    }

	private static function migrate_users($opts){
		$curr_env_prefix = DillerLoyalty()->get_site_prefix();
		$users = get_users( array(
			'role__in ' => array('subscriber', 'customer'),
			'order' => 'ASC',
			'orderby' => 'display_name',
			'meta_query' => array(
				'relation' => 'AND',
				array(
					'relation' => 'OR',
					array(
						'key' => "phone",
						'compare' => 'EXISTS'
					),
					array(
						'key' => "{$curr_env_prefix}phone",
						'compare' => 'EXISTS'
					)
				)
			)
		) );

		if(count($users) == 0){
			WP_CLI::line( sprintf('%sNothing to migrate.', self::$dry_run_str) );
			return;
		}

		WP_CLI::line( sprintf('%sStarting users migrations.', self::$dry_run_str) );

		$progress = \WP_CLI\Utils\make_progress_bar( 'Progress Bar', count($users) );
		$updated = 0;
		foreach( $users as $user){
			WP_CLI::Success( sprintf('%sProcessing WP user "%s" ID: %s', self::$dry_run_str, $user->display_name, $user->ID) );

			if(!self::$dry_run) {
				self::$migration->set_current_user( $user );
				self::$migration->maybe_migrate();

				WP_CLI::Success( sprintf('%sUser "%s" (%s) migrated.', self::$dry_run_str, $user->display_name, $user->ID) );
			}
			$progress->tick();
			$updated++;
		}

		$progress->finish();

		WP_CLI::Success( sprintf('%sMigration done! %d users updated.', self::$dry_run_str, $updated) );
	}

	private static function migrate_site_settings($opts){

		WP_CLI::line( sprintf('%sStarting settings migration.', self::$dry_run_str) );

		if(!self::$dry_run) {
			self::$migration->migrate_options();
		}

		WP_CLI::line( sprintf('%sFinished settings migration.', self::$dry_run_str) );
	}

	private static function migrate_shortcodes($opts){

		WP_CLI::line( sprintf('%sFinished shortcodes migration.', self::$dry_run_str) );

		if(!self::$dry_run) {
			self::$migration->migrate_shortcodes();
		}

		WP_CLI::line( sprintf('%sFinished shortcodes migration.', self::$dry_run_str) );
	}
}