<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Diller_Loyalty_Webhook_Request {

	private $store_pin = '';
	private $user_email = '';
	private $phone_number = '';
	private $country_calling_code = '';
	private $action = '';
	private $access_token = '';
	private $params = array();


	/**
	 * @return string
	 */
	public function get_store_pin(): string {
		return $this->store_pin;
	}

	/**
	 * @return string
	 */
	public function get_user_email(): string {
		return $this->user_email;
	}

	/**
	 * @return string
	 */
	public function get_phone_number(): string {
		return $this->phone_number;
	}

	/**
	 * @return string
	 */
	public function get_country_calling_code(): string {
		return $this->country_calling_code;
	}

	/**
	 * @return string
	 */
	public function get_action(): string {
		return $this->action;
	}

	public function __construct($params) {
		$this->params = $params;
	}


	public function validate() {
		if(isset($params['store_pin']) && isset($params['phone_number']) && isset($params['access_token']) && isset($params['country_calling_code']) && isset($params['action']) && isset($params['email'])) {

			$this->store_pin = filter_var( $this->params['store_pin'], FILTER_SANITIZE_STRING );
			$this->user_email = filter_var( $this->params['email'], FILTER_SANITIZE_EMAIL );
			$this->phone_number = filter_var( $this->params['phone_number'], FILTER_SANITIZE_STRING );
			$this->country_calling_code = filter_var( $this->params['country_calling_code'], FILTER_SANITIZE_STRING );
			$this->action = filter_var( $this->params['action'], FILTER_SANITIZE_STRING );
			$this->access_token = filter_var( $params['access_token'], FILTER_SANITIZE_STRING );

			if(!$this->store_pin || !$this->user_email || !$this->phone_number || !$this->country_calling_code || !$this->action || !$this->access_token){
				return new WP_Error('validation', 'Invalid parameters');
			}

			return ( DillerLoyalty()->get_store()->get_store_pin() == $this>store_pin && !empty( $phone_number ) && !empty( $user_email ) && !empty( $country_calling_code ) && !empty( $action ) )
				? true : new WP_Error('validation', 'Invalid parameters');
		}
		return new WP_Error('validation', 'Missing parameters');
	}
}
