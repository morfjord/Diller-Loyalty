<?php

/**
 * Because current theme's functions.php loads way after Diller plugin does, we will look for a special file called "diller-loyalty-overrides.php" in the theme's directory instead.
 * If we find it we load it and execute the code inside. The goal behind it, is that a developer can declare some hooks, that can customize some of the plugin behavior.
 *
 * Example: we could define some hooks like <code>diller_woocommerce_actions</code> or <code>diller_woocommerce_filters</code> that allow us to manipulate Diller's implementation
 * of Woocommerce actions and filters.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) || ! function_exists( 'DillerLoyalty' )) {
	exit;
}

// Filters Diller's defined actions and hooks for backend
if ( is_admin() ):

	add_filter( 'diller_admin_woocommerce_actions', 'customize_diller_admin_woocommerce_actions', 10, 1);
	function customize_diller_admin_woocommerce_actions($actions) {
		// Remove an existing hook
		if($found_index = array_search('hook_name_here', array_column($actions, 'hook'))) {
			unset( $actions[ $found_index ] );
		}

		// Add a new hook (this most likely won't be necessary, but you can be done)
		$my_component = new My_Component_Class();
		$actions[] = array( 'hook' => 'some_other_admin_hook_here', 'component' => $my_component, 'callback' => 'your_custom_admin_callback', 'priority' => 10, 'accepted_args' => 1 );

		return $actions;
	}

	add_filter( 'diller_admin_woocommerce_filters', 'customize_diller_admin_woocommerce_filters', 10, 1);
	function customize_diller_admin_woocommerce_filters($filters) {
		// same as above: customize_diller_woocommerce_actions()
	}

else:
	// Filters Diller's defined actions and hooks for frontend

	add_filter( 'diller_woocommerce_actions', 'customize_diller_woocommerce_actions', 10, 1);
	function customize_diller_woocommerce_actions($actions) {
		// Remove an existing hook
		if($found_index = array_search('hook_name_here', array_column($actions, 'hook'))) {
			unset( $actions[ $found_index ] );
		}

		// Add a new hook (this most likely won't be necessary, but you can be done)
		$my_component = new My_Component_Class();
		$actions[] = array( 'hook' => 'some_other_hook_here', 'component' => $my_component, 'callback' => 'your_custom_callback', 'priority' => 10, 'accepted_args' => 1 );

		return $actions;
	}

	add_filter( 'diller_woocommerce_filters', 'customize_diller_woocommerce_filters', 10, 1);
	function customize_diller_woocommerce_filters($filters) {
		// same as above: customize_diller_woocommerce_actions()
	}

endif;