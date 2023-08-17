<?php

/**
 * Fired during plugin activation
 *
 * @link       https://diller.no/contact-us
 * @since      2.0.0
 *
 * @package    Diller_Loyalty
 * @subpackage Diller_Loyalty/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      2.0.0
 * @package    Diller_Loyalty
 * @subpackage Diller_Loyalty/includes
 * @author     Diller AS <dev@diller.no>
 */
class Diller_Loyalty_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    2.0.0
	 */
	public static function activate() {

		DillerLoyalty()->get_logger()->info("Plugin activation: starting activation...");

		// Migrate old shortcode [short_subscription] to [diller_loyalty_enrollment_form]
		require_once( DILLER_LOYALTY_PATH . 'includes/migrations/class-diller-migration-161-20.php' );
		$migrator = new Diller_Loyalty_Migration_161_20('1.6.1', '2.0');
		$migrator->migrate_options();
		$migrated_pages = $migrator->migrate_shortcodes();

		$enrollment_page_id = (count($migrated_pages) > 0) ? $migrated_pages[0] : 0;
		if(count($migrated_pages) == 0):
			// If no migration done, then we create a new page or add the shortcode to an existing page
			$enrollment_page_id = DillerLoyalty()->maybe_create_or_update_enrollment_page();

			if(!is_wp_error($enrollment_page_id)) {
				// Create a new item in the navigation menu (Primary one)
				$nav_menus = get_terms( array( 'taxonomy' => 'nav_menu' ) );
				if ( count( $nav_menus ) > 0 ) {
					$pri_nav_menu = array_filter( $nav_menus, function ( $nav_menu ) {
						return preg_match( "/primary|hovedmeny|main-?menu/i", $nav_menu->slug );
					} );

					if( count( $pri_nav_menu ) > 0 ){
						$key = key($pri_nav_menu);
						$pri_nav_menu_id = $pri_nav_menu[$key]->term_id;
					}else{
						$pri_nav_menu_id = $nav_menus[0]->term_id;
					}

					// Get all the sub-items for the menu
					$nav_menu_items = wp_get_nav_menu_items( $pri_nav_menu_id );
					$object_ids     = wp_list_pluck( $nav_menu_items, 'object_id' );

					// Check if it was added from before
					if ( ! in_array( $enrollment_page_id, $object_ids ) ) {
						wp_update_nav_menu_item( $pri_nav_menu_id, 0, array(
							'menu-item-title'     => get_the_title($enrollment_page_id),
							'menu-item-object'    => 'page',
							'menu-item-object-id' => $enrollment_page_id,
							'menu-item-type'      => 'post_type',
							'menu-item-status'    => 'publish'
						) );
					}
				}
			}
			else{
				DillerLoyalty()->get_logger()->error("Plugin activation: could not create or update enrollment form page.", $enrollment_page_id);
			}
		endif;

		// Flush previous store configs and fetch a new copy from the server
		$store_configs = array();
		if(DillerLoyalty()->get_auth()->is_authenticated()) {
			DillerLoyalty()->get_store()->delete_configs();

			//Get a fresh copy of store details, such as language, preferences, etc
			$result = DillerLoyalty()->get_api()->get_store_details();
			if ( !is_wp_error( $result ) ) {
				DillerLoyalty()->get_logger()->info("Plugin Activation: plugin is already authenticated. Refreshing store details...");

				$store_configs = array_merge( DillerLoyalty()->get_store()->get_default_configs(), $result );
				DillerLoyalty()->get_store()->save_configs($store_configs);
			}
		}

		// Save the page id as part of the store settings to be used later
		DillerLoyalty()->get_store()->save_configs(array_merge($store_configs, array("enrollment_form_page_id" => $enrollment_page_id)));
	}
}
