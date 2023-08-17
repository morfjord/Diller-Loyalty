<?php

final class Diller_Loyalty_Migration_161_20 extends Diller_Loyalty_Migration_Base {

	private $current_user = false;
	private $log_message = "";

	public function set_current_user($user){
		$this->current_user = $user;
	}

	public function __construct($from_version, $to_version){
		parent::__construct($from_version, $to_version);
	}

	public function migrate_shortcodes(){
		global $wpdb;
		$results = $wpdb->get_results($wpdb->prepare("
				SELECT ID, post_content 
				FROM $wpdb->posts
				WHERE post_type = 'page'
				AND post_content
				LIKE %s 
				",
			"%[short_subscription]%"
		));
		if ( empty( $results ) ) {

			// CLI Support
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				WP_CLI::log("No shortcodes to migrate. Exiting...");
			}

			return $results;
		}

		// Find all the shortcodes in use
		foreach ( $results as $enrollment_page ) {
			$enrollment_page->post_content = preg_replace(
				'/\[short_subscription\]/m',
				'['. Diller_Loyalty_Configs::LOYALTY_ENROLLMENT_FORM_SHORTCODE .']',
				$enrollment_page->post_content
			);

			
			$result = wp_update_post($enrollment_page, false, false);

			if(is_wp_error($result)){
				$this->log_message = sprintf("Error occurred while upgrading v%s shortcode [short_subscription] to v%s [diller_loyalty_enrollment_form], on page %s", $this->version_from, $this->version_to, $enrollment_page->ID);

				// CLI Support
				if ( defined( 'WP_CLI' ) && WP_CLI ) {
					WP_CLI::error($this->log_message);
				}

				DillerLoyalty()->get_logger()->error($this->log_message, $result);
			}
			else{
				$this->log_message = sprintf("Upgraded v%s shortcode [short_subscription] to v%s [diller_loyalty_enrollment_form] successfully, on page %s", $this->version_from, $this->version_to, $enrollment_page->ID);

				// CLI Support
				if ( defined( 'WP_CLI' ) && WP_CLI ) {
					WP_CLI::success($this->log_message);
				}

				DillerLoyalty()->get_logger()->info($this->log_message);
			}
		}
		return array_column($results, 'ID');
	}

	public function maybe_migrate(){
		if(!$this->current_user) {
			_doing_it_wrong( 'maybe_migrate', 'Call <code>set_current_user()</code> before calling maybe_migrate()', '2.0' );
			return;
		}

		// Skip migration
		if(!$this->current_user->exists() || metadata_exists( 'user', $this->current_user->ID, DillerLoyalty()->get_follower_meta_key())) return false;

		if($this->migrate_user_meta_data()){
			$this->delete_user_meta_data();
		}
		else{
			$this->migrate_from_wc_billing_details();
		}
    }

	public function migrate_options() : bool{
		$old_settings = array();
		if(($old_settings = get_option('dillerapp_settings', false)) === false) {
			if ( ($store_pin = get_option( '_store_pin_id', false )) !== false && ($api_key = get_option( 'x_api_key', false )) !== false ) {
				$old_settings["_store_pin_id"] = $store_pin;
				$old_settings["x_api_key"]     = $api_key;
			}
		}

		if(empty($old_settings)) return false;


		$store_pin = (!empty($old_settings["_store_pin_id"]) && is_numeric($old_settings["_store_pin_id"])) ? $old_settings["_store_pin_id"] : '';
		$api_key = $this->maybe_decrypt_api_key($old_settings["x_api_key"]);

		if(!empty($api_key) && !empty($store_pin)) {
			$result = DillerLoyalty()->get_auth()->authenticate( $api_key, $store_pin );
			if ( !is_wp_error($result) ) {
				$this->log_message = "Successfully migrated old `dillerapp_settings` to v{$this->version_to} format";

				// CLI Support
				if ( defined( 'WP_CLI' ) && WP_CLI ) {
					WP_CLI::success($this->log_message);
				}

				DillerLoyalty()->get_logger()->info($this->log_message);

				$this->delete_options();
			}
			else{
				$this->log_message = "Couldn't migrate `dillerapp_settings` to v{$this->version_to} format.\r\nReason: couldn't authenticate store with the old store pin and api key. Store Pin: " . $store_pin;

				// CLI Support
				if ( defined( 'WP_CLI' ) && WP_CLI ) {
					WP_CLI::error($this->log_message);
				}

				DillerLoyalty()->get_logger()->error($this->log_message);

				return false;
			}
		}

		// CLI Support
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::log("No settings to migrate. Exiting...");
		}

		return true;
	}

	private function migrate_user_meta_data() : bool{
		global $wpdb;
		$curr_env_prefix = DillerLoyalty()->get_site_prefix();
		$curr_env_prefix = ($curr_env_prefix !== $wpdb->base_prefix) ? $curr_env_prefix : '';
		$phone_number = get_user_meta($this->current_user->ID, "{$curr_env_prefix}phone", true);
		if(empty($phone_number)) return false;

		$phone_country_code = get_user_meta($this->current_user->ID, "{$curr_env_prefix}country_code", true);
		$phone_country_code = !empty($phone_country_code) ? $phone_country_code : DillerLoyalty()->get_store()->get_phone_default_country_code();
		$follower = (new Diller_Loyalty_Follower())
			->set_wp_user_id($this->current_user->ID)
			->set_first_name(get_user_meta( $this->current_user->ID, 'first_name', true ))
			->set_last_name(get_user_meta( $this->current_user->ID, 'last_name', true ))
			->set_full_phone_number($phone_country_code, $phone_number);

		$joined_loyalty_program = get_user_meta($this->current_user->ID, "{$curr_env_prefix}loyalty_program", true);
		if((int)$joined_loyalty_program === 1){
			$follower->set_membership_consent_accepted('Yes');
		}
		$follower->save();

		$this->log_message = "Successfully migrated follower";

		// CLI Support
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::success($this->log_message);
		}

		DillerLoyalty()->get_logger()->info($this->log_message, $follower);

		return true;
	}

	private function migrate_from_wc_billing_details(){
		try {
			$wc_customer = new WC_Customer($this->current_user->ID);
			$billing_phone_number = $wc_customer->get_billing_phone();
			$country = $wc_customer->get_billing_country(); // Expecting 2 letters ISO Code
			$phone_country_code = Diller_Loyalty_Helpers::get_phone_country_code( $billing_phone_number, $country );
			$phone_number = Diller_Loyalty_Helpers::get_phone_number( $billing_phone_number, $country );
			$phone_country_code = !is_wp_error( $phone_country_code ) ? $phone_country_code : DillerLoyalty()->get_store()->get_phone_default_country_code();

			if(!is_wp_error($phone_number)) {
				(new Diller_Loyalty_Follower())
					->set_wp_user_id( $this->current_user->ID )
					->set_first_name(get_user_meta( $this->current_user->ID, 'first_name', true ))
					->set_last_name(get_user_meta( $this->current_user->ID, 'last_name', true ))
					->set_full_phone_number( $phone_country_code, $phone_number )
					->save();

				$this->log_message = sprintf("Created follower metadata from WC billing details. WP ID: %s | Country Code: %s | Phone Number: %s", $this->current_user->ID, $phone_country_code, $phone_number);

				// CLI Support
				if ( defined( 'WP_CLI' ) && WP_CLI ) {
					WP_CLI::success($this->log_message);
				}

				DillerLoyalty()->get_logger()->info($this->log_message);
			}
			else{
				$this->log_message = sprintf("Error parsing phone number from WC billing details. Cannot check if Follower exists already in Diller. WP ID: %s | Phone Number: %s", $this->current_user->ID, $billing_phone_number);

				// CLI Support
				if ( defined( 'WP_CLI' ) && WP_CLI ) {
					WP_CLI::warning($this->log_message);
				}

				DillerLoyalty()->get_logger()->error($this->log_message);
			}
		} catch ( Exception $e ) {
			// Customer not found
		}
	}

	private function delete_user_meta_data(){
		global $wpdb;
		$curr_env_prefix = DillerLoyalty()->get_site_prefix();
		$curr_env_prefix = ($curr_env_prefix !== $wpdb->base_prefix) ? $curr_env_prefix : '';
		$meta_fields = array(
			"country_code",
			"phone",
			"mob_no",
			"loyalty_program",
			"sms_check",
			"email_check",
			"is_purchase_history",
			"store_id",
			"user_id"
		);

		array_map(function($meta_key) use ($curr_env_prefix) {
			delete_user_meta($this->current_user->ID, "{$curr_env_prefix}{$meta_key}");
		}, $meta_fields);


		$this->log_message = sprintf("Removed old meta fields %s for Follower WP ID: %s", join(", {$curr_env_prefix}", $meta_fields), $this->current_user->ID);

		// CLI Support
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::success($this->log_message);
		}

		DillerLoyalty()->get_logger()->info($this->log_message);
	}

	private function delete_options(){
		$meta_fields = array(
			"dillerapp_settings",
			"_store_pin_id",
			"x_api_key",
			"_choose_language_id",
			"_disable_skeleton_css",
			"_disable_multisite",
		);

		array_map(function($meta_key){
			delete_option($meta_key);
		}, $meta_fields);

		$this->log_message = sprintf("Removed old options: %s", join(", ", $meta_fields));

		// CLI Support
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::success($this->log_message);
		}

		DillerLoyalty()->get_logger()->info($this->log_message);
	}


	/**
	 * Decrypts a value using the old plugin v1.6.x specific encryption key and IV.
	 * @param $api_key
	 *
	 * @return string
	 */
	private function maybe_decrypt_api_key($api_key){
		$v160_encryption_iv = '2334587974566325';
		$v160_encryption_key = "DillerLoyalty";

		// Look for unencrypted value first
		if(strlen($api_key) === 32) return $api_key;

		$result = openssl_decrypt($api_key, "AES-128-CTR", $v160_encryption_key, 0, $v160_encryption_iv);
		return ($result !== false) ? $result : '';
	}
}



abstract class Diller_Loyalty_Migration_Base {

	protected $version_from = '';
	protected $version_to = '';

	public function __construct($from, $to) {
		$this->version_from = $from;
		$this->version_to = $to;
	}

	protected abstract function maybe_migrate();
}