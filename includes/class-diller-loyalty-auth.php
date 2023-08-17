<?php
/**
 * Authentication helper class.
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Diller_Loyalty_Auth {

	private $settings = array();

	public function __construct() {
		$this->settings = $this->get_settings();
	}

	public function authenticate($api_key, $store_pin){
		// check if current user can authenticate
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'validation-error', __( "You don't have permission to authenticate Diller Loyalty Plugin.", 'diller-loyalty' ) );
		}

		

		$result = DillerLoyalty()->get_api()->authenticate($api_key, $store_pin);
		if (!is_wp_error($result)) {
			$current_settings = $this->get_settings();
			$current_settings = !empty($current_settings)? $current_settings : array();
			$settings = array_merge($current_settings, array(
				"x_api_key" => $api_key,
				"store_pin" =>  $store_pin
			));
			$this->set_settings($settings);
		}

		return $result;
	}

	public function delete_authentication(){
		return $this->delete_settings();
	}

	public function is_authenticated() {
		$this->get_settings();
		return !empty($this->settings['x_api_key'] ) && !empty($this->settings['store_pin']);
	}

	public function get_settings($force = false) {
		if (!empty($this->settings) && !$force) {
			return $this->settings;
		}
		else {
			$this->settings = get_option( '_diller_settings', array() );
			return $this->settings;
		}
	}

	public function set_settings($data = array()) {
		$this->settings = array_replace_recursive($this->settings, $data);
		update_option( '_diller_settings', $this->settings );
	}

	public function delete_settings() {
		$this->settings = array();
		delete_option( '_diller_settings' );
		return true;
	}

	public function get_store_pin() {
		return !empty( $this->settings['store_pin'] ) ? $this->settings['store_pin'] : '';
	}

	public function get_api_key() {
		return !empty( $this->settings['x_api_key'] ) ? $this->settings['x_api_key'] : '';
	}
}
