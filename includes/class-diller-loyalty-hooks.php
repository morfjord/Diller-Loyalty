<?php

class Diller_Loyalty_Hooks {

	/**
	 * Redirects a user to the LP section inside dashboard if is logged in.
	 * The advantage is if the user is not enrolled yet in the LP we can prefill some information from Wordpress.
	 */
	//function enrollment_page_redirect_logged_in_user() {
	//	if (!is_admin() && is_user_logged_in() && is_page(DillerLoyalty()->get_store()->get_enrollment_form_page_id()) && DillerLoyalty()->user_has_joined()) {
	//		wp_redirect( trailingslashit(wc_get_page_permalink( 'myaccount' )) . Diller_Loyalty_Configs::LOYALTY_PROFILE_ENDPOINT);
	//		exit;
	//	}
	//}

	public function handle_follower_updated(Diller_Loyalty_Follower $follower) {

		// Create or update WP User account
		$result = DillerLoyalty()->create_or_update_wp_user_account($follower);
		if(!is_wp_error($result)) {
			

			
			$wc_customer = new WC_Customer($follower->get_wp_user_id());
			$wc_customer->set_first_name($follower->get_first_name());
			$wc_customer->set_last_name($follower->get_last_name());
			$wc_customer->set_billing_first_name($follower->get_first_name());
			$wc_customer->set_billing_last_name($follower->get_last_name());
			$wc_customer->set_billing_phone($follower->get_phone_number());
			$wc_customer->set_billing_email($follower->get_email());

			// Added to complete more the user profile
			$wc_customer->set_billing_address_1($follower->get_address());
			$wc_customer->set_billing_postcode($follower->get_postal_code());
			$wc_customer->set_billing_city($follower->get_postal_city());
			$wc_customer->set_billing_country($follower->get_country());
			$wc_customer->save();
		}
		else{
			DillerLoyalty()->get_logger()->error("Error while creating/updating user in WP. Function: create_or_update_wp_user_account()", $follower, $result);
		}
	}

	public function handle_follower_unsubscribed(Diller_Loyalty_Follower $follower) {
		$curr_env_prefix = DillerLoyalty()->get_site_prefix();
		update_user_meta($follower->get_wp_user_id(), "_diller_{$curr_env_prefix}unsubscribed_datetime", gmdate( 'd-m-Y H:i:s', time() ));
	}

	/**
	 * When Customer changes its email from "My Account" page or an admin or store owner changes a Customer email directly in WP
	 * we sync those changes to Diller Api. This will only work if a customer has joined the Loyalty Program before.
	 *
	 * @param $user_id
	 * @param $old_user_data
	 */
	public function handle_wp_user_profile_updated($user_id, $old_user_data) {
		$userdata = get_userdata( $user_id );

		// Check for changes
		if($userdata->user_email !== $old_user_data->user_email && DillerLoyalty()->user_has_joined($user_id)) {
			$updated_follower = DillerLoyalty()->get_follower($user_id, true);
			if(!is_wp_error($updated_follower)) {
				$updated_follower->set_email( $userdata->user_email );

				// Remove action. Not needed in this context (even if next line fails)
				remove_action('diller_api_follower_updated', array($this, 'handle_follower_updated'), 10);

				$result = DillerLoyalty()->get_api()->update_follower($updated_follower);
				if (is_wp_error( $result ) ) {
					DillerLoyalty()->get_logger()->error(sprintf("Error while changing Follower's email from %s to %s", $old_user_data->user_email, $userdata->user_email), $updated_follower, $result);
				}
				else{
					DillerLoyalty()->get_logger()->info(sprintf("Follower's email changed from %s to %s", $old_user_data->user_email, $userdata->user_email), $updated_follower);

					// Update follower local meta data
					$updated_follower->save();

					// Update WC billing address as well
					$wc_customer = new WC_Customer($updated_follower->get_wp_user_id());
					$wc_customer->set_billing_email($updated_follower->get_email());
					$wc_customer->save();

					global $wpdb;

					// If login name is the same as the old email, then make sure the login is also updated
					if($old_user_data->user_login === $old_user_data->user_email){
						$wpdb->update( $wpdb->users, array( 'user_login' => $updated_follower->get_email() ), array( 'ID' => $updated_follower->get_wp_user_id() ) );
					}

					// Update user_nicename, if it was based on old email.
					if($old_user_data->user_nicename === sanitize_title($old_user_data->user_email, true )) {
						$newname = sanitize_title( $updated_follower->get_email() );
						$wpdb->update( $wpdb->users, array( 'user_nicename' => $newname ), array( 'ID' => $updated_follower->get_wp_user_id() ) );
					}

					// Update display_name. If it was the same as the email address
					if($old_user_data->display_name === $old_user_data->user_email) {
						$wpdb->update( $wpdb->users, array( 'display_name' => $updated_follower->get_email() ), array( 'ID' => $updated_follower->get_wp_user_id() ) );
					}

					// Update nickname.
					$nickname = get_user_meta( $updated_follower->get_wp_user_id(), 'nickname', true );
					if ( $nickname === $old_user_data->user_email ) {
						update_user_meta( $user_id, 'nickname', $updated_follower->get_email() );
					}

					// Make sure the updated data is refreshed
					clean_user_cache($user_id);
				}
			}
		}
	}


	/**
	 * Retrieves a fresh copy of the Follower profile data from Diller API after the Follower has logged in into Wordpress via (My Account page)
	 *
	 * @param $user_login
	 * @param $user
	 */
	public function my_account_user_logged_in($user_login, $user){

		if(array_intersect(array("customer", "subscriber"), $user->roles) && !DillerLoyalty()->user_has_joined() ){
			require_once( DILLER_LOYALTY_PATH . 'includes/migrations/class-diller-migration-161-20.php' );
			$migration = new Diller_Loyalty_Migration_161_20('1.6.1', '2.0');
			$migration->set_current_user($user);
			$migration->maybe_migrate();
		}

		DillerLoyalty()->get_follower($user->ID, true);
    }

	/**
	 * Keeps track of changes in the permalink/url for the enrollment form page and sync them to Diller.
	 * This calls @see DillerLoyalty()->get_api()->update_store_details() internally with the parameters: 'external_my_account_url', 'external_form_signup_url'
	 *
	 * @param $post_id Id of the post being saved
	 * @param $post the post object being saved
	 * @param $update if it's an update or not
	 *
	 * @return void
	 */
	public function handle_enrollment_form_page_changes( $post_id, $post, $update) {
		//return early if not a page
		if ( 'page' !== $post->post_type ) {
			return;
		}

		//return early if autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// return early if this is not the enrollment page id
		if($post_id !== DillerLoyalty()->get_store()->get_enrollment_form_page_id()) {
			return;
		}

		// If we have the enrollment form shortcode present, then
		if(preg_match('/\['. Diller_Loyalty_Configs::LOYALTY_ENROLLMENT_FORM_SHORTCODE .'\]/m', $post->post_content)){
			$result = DillerLoyalty()->get_api()->update_store_details(array(
				'external_my_account_url' => '/' . get_post_field( 'post_name', get_option( 'woocommerce_myaccount_page_id' ) ),
				'external_form_signup_url' => '/' . $post->post_name
			));

			if (is_wp_error( $result ) ) {
				DillerLoyalty()->get_logger()->error("Page with form shortcode changed. Couldn't update 'external_form_signup_url' in Diller", $result);
			}
		}
	}
}
