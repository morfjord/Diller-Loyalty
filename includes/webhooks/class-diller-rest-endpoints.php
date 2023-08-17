<?php
/**
 * Diller Webhooks helper class.
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'Diller_Loyalty_Webhook_Request', false ) ) {
	include_once __DIR__  . '/class-diller-webhook-request.php';
}


final class Diller_Loyalty_Rest_Endpoints {

	private $route_namespace = '';

	public function __construct() {
		$this->route_namespace = Diller_Loyalty_Configs::REST_ENDPOINT_BASE_URL;
	}

	public function register_diller_wordpress_rest_endpoints() {

		// Eg. /wp-json/diller-loyalty/v2/check-phone-number
		register_rest_route( $this->route_namespace, '/' . Diller_Loyalty_Configs::CHECK_PHONE_NUMBER_REST_ENDPOINT, array(
			'methods'  => array('GET'),
			'callback' => array($this, 'handle_check_phone_number_endpoint_request'),
			'permission_callback' => '__return_true',
			'args'     => array(
				'dial_code' => array(
					'required' => true,
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => function($param, $request, $key){ return preg_match("/^(\+|00)\d{2,3}/i", $param); },
				),
				'phone_number' => array(
					'required' => true,
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => function($param, $request, $key){ return Diller_Loyalty_Helpers::is_valid_phone_number($param, strtoupper($request["country_iso2_code"])); },
				)
			)
		));

		// Eg. /wp-json/diller-loyalty/v2/get-phone-number-details
		register_rest_route( $this->route_namespace, '/' . Diller_Loyalty_Configs::PHONE_NUMBER_DETAILS_REST_ENDPOINT, array(
			'methods'  => array('GET'),
			'callback' => array($this, 'handle_phone_number_details_endpoint_request'),
			'permission_callback' => '__return_true',
			'args'     => array(
				'phone_number' => array(
					'required' => true,
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => function($param, $request, $key) { return Diller_Loyalty_Helpers::is_valid_phone_number($param); },
				)
			)
		));


		// Eg. /wp-json/diller-loyalty/v2/send-otp-login-code
		register_rest_route( $this->route_namespace, '/' . Diller_Loyalty_Configs::SEND_OTP_LOGIN_CODE_REST_ENDPOINT, array(
			'methods'  => array('POST'),
			'callback' => array($this, 'handle_send_otp_code_endpoint_request'),
			'permission_callback' => '__return_true',
			'args'     => array(
				'phone_country_code' => array(
					'required' => true,
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => function($param, $request, $key){ return preg_match("/^(\+|00)\d{2,3}/i", $param); },
				),
				'phone_number' => array(
					'required' => true,
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => function($param, $request, $key){ return Diller_Loyalty_Helpers::is_valid_phone_number($param, strtoupper($request["country_iso2_code"])); },
				)
			)
		));

		// Eg. /wp-json/diller-loyalty/v2/validate-otp-login-code
		register_rest_route( $this->route_namespace, '/' . Diller_Loyalty_Configs::VALIDATE_OTP_LOGIN_CODE_REST_ENDPOINT, array(
			'methods'  => array('POST'),
			'callback' => array($this, 'handle_validate_sms_login_code_endpoint_request'),
			'permission_callback' => '__return_true',
			'args' => array(
				'phone_country_code' => array(
					'required' => true,
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => function($param, $request, $key){ return preg_match("/^(\+|00)\d{2,3}/i", $param); },
				),
				'phone_number' => array(
					'required' => true,
					'type' => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => function($param, $request, $key){ return Diller_Loyalty_Helpers::is_valid_phone_number($param, strtoupper($request["country_iso2_code"])); },
				),
				'otp_code' => array(
					'required' => true,
					'type' => 'integer',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => function($param, $request, $key){ return is_numeric($param); },
				)
			)
		));

		// Eg. /wp-json/diller-loyalty/v2/check-email
		register_rest_route( $this->route_namespace, '/' . Diller_Loyalty_Configs::CHECK_EMAIL_REST_ENDPOINT, array(
			'methods'  => array('GET'),
			'callback' => array($this, 'handle_check_email_endpoint_request'),
			'permission_callback' => '__return_true',
			'args'     => array(
				'email' => array(
					'required' => true,
					'type' => 'string',
					'sanitize_callback' => 'sanitize_email',
					'validate_callback' => function($param, $request, $key) { return is_email($param); },
				)
			)
		));

		// Implement: Handle callbacks from Diller API
	}

	public function handle_phone_number_details_endpoint_request($request) {
		// Note: This just works with NO numbers, therefore no country code needed
		$phone_number = $request->get_param("phone_number");
		$result = DillerLoyalty()->get_api()->get_follower_details_by_phone($phone_number);

		return new WP_REST_Response( array( "success" => !empty($result), "result" => $result ), 200 );
	}

	public function handle_check_phone_number_endpoint_request($request) {
		$dial_code = $request->get_param("dial_code");
		$phone_number = $request->get_param("phone_number");
		$data = array( "consentAccepted" => false, "validEmail" => false, "token" => "" );
		$message = esc_html__( "Phone number is available.", 'diller-loyalty' );

		// Ignore phone number check for current logged-in user, if it's the same
		if(DillerLoyalty()->get_current_follower()->get_full_phone_number() === $dial_code.$phone_number){
			return new WP_REST_Response( array( "success" => true ), 200 );
		}

		// Perform API check
		if(!($result = DillerLoyalty()->get_api()->check_phone_number_is_available($dial_code, $phone_number))) {

			$message = esc_html__( "We see that you are already a member, but you didn\'t fully complete the registration process. Please continue by requesting a validation code", 'diller-loyalty' );

			// Phone number is taken. Check if follower has email address and already accepted GDPR
			$follower = DillerLoyalty()->get_api()->get_follower( $dial_code, $phone_number );
			if ( !is_wp_error( $follower ) ) {
				$data["consentAccepted"] =  $follower->get_membership_consent_accepted() === 'Yes';
				$data["validEmail"]      = !empty( $follower->get_email() );
				$data["token"]      = wp_hash($dial_code . $phone_number);

				if($data["consentAccepted"] && $data["validEmail"]){
					$message = esc_html__( "Congratulations! We see that you are already a member. Request an SMS with a verification code to continue.", 'diller-loyalty' );
				}
			}
		}

		return new WP_REST_Response( array( "success" => $result, "data" => $data, "message" => $message ), 200 );
	}

	public function handle_send_otp_code_endpoint_request($request) {
		$phone_country_code = $request->get_param("phone_country_code");
		$phone_number = $request->get_param("phone_number");
		$token = $request->get_param("token");

		if($token != wp_hash($phone_country_code . $phone_number)){
			return new WP_REST_Response( array( "success" => false, "message" => esc_html__('Request token is invalid or expired. Try to reload the page.','diller-loyalty') ), 400 );
		}

		// Implement a delay of 2mins before a new code can be requested
		$transient_key = DillerLoyalty()->get_site_prefix() . md5($phone_country_code.$phone_number);
		if(($initial_date = get_transient( $transient_key ))){
			$diff = date_diff( date_create(), $initial_date );
			$min_elapsed = (($diff->y * 365.25 + $diff->m * 30 + $diff->d) * 24 + $diff->h) * 60 + $diff->i;
			if ( $min_elapsed < 2 ) {
				return new WP_REST_Response( array( "success" => false, "message" => esc_html__('Please wait 2 minutes before requesting another code', 'diller-loyalty' ) ), 200 );
			}

			delete_transient( $transient_key );
		}

		// Perform API call
		$message = esc_html__('We\'ve sent a 5-digit verification code to your phone. Add it below to continue logging in.','diller-loyalty');
		if(($result = DillerLoyalty()->get_api()->send_sms_login_code($phone_country_code, $phone_number))){
			set_transient( $transient_key, date_create(), 3600 );
		}
		else{
			delete_transient( $transient_key );
			$message = esc_html__('We were unable to send you the verification code. Please make sure the phone number is valid.','diller-loyalty');
		}

		return new WP_REST_Response( array( "success" => $result, "message" => $message ), 200 );
	}

	public function handle_validate_sms_login_code_endpoint_request($request) {
		$result = false;
		$update_follower = false;
		$phone_country_code = $request->get_param("phone_country_code");
		$phone_number = $request->get_param("phone_number");
		$otp_code = $request->get_param("otp_code");
		$email = $request->get_param("email");
		$membership_consent_accepted = $request->get_param("membership_consent_accepted");
		$purchase_history_consent_accepted = $request->get_param("purchase_history_consent_accepted");
		$token = $request->get_param("token");

		if($token != wp_hash($phone_country_code . $phone_number)){
			return new WP_REST_Response( array( "success" => false, "message" => esc_html__('Request token is invalid or expired. Please try again. Try to reload the page.','diller-loyalty') ), 400 );
		}

		// Perform API call
		if(!DillerLoyalty()->get_api()->validate_sms_login_code($phone_country_code, $phone_number, $otp_code)){
			return new WP_REST_Response( array( "success" => false, "message" => __('Invalid verification code.', 'diller-loyalty') ), 200 );
		}

		// Force the consent and email for the Follower, if it was not filled in before.
		$follower = DillerLoyalty()->get_api()->get_follower($phone_country_code, $phone_number);
		if($follower->get_membership_consent_accepted() !== 'Yes' && $membership_consent_accepted === 'Yes'){
			$follower->set_force_membership_consent_acceptance(true);
			$follower->set_purchase_history_consent_accepted($purchase_history_consent_accepted === 'Yes');
			$update_follower = true;
		}

		// If empty email, Follower most likely was added through POS
		if(empty($follower->get_email())){
			if(!is_email($email)){
				return new WP_REST_Response(array( "success" => false, "message" => esc_html__('The email provided in invalid.','diller-loyalty') ), 400 );
			}

			$follower->set_email($email);
			$update_follower = true;
		}

		// Create / Update the user in WP
		$follower = DillerLoyalty()->create_or_update_wp_user_account($follower);
		if( !is_wp_error($follower) && ( $wp_user = get_user_by_email($follower->get_email()) ) && is_a($wp_user, 'WP_User') ){

			// If password is empty means the user exists in WP from before, and we have to rest its password
			// to be able to send it by SMS further down the code
			if(empty($follower->get_password())){
				$follower->set_password( wp_generate_password( 12, true, false ) );
				wp_set_password( $follower->get_password(), $wp_user->ID);
			}

			// Try to get first and last names from WP (if existing user)
			if ( empty( $follower->get_first_name() ) ) {
				$follower->set_first_name( ! empty( $wp_user->first_name ) ? $wp_user->first_name : strtoupper(substr($follower->get_email(), 0, 1)));
			}

			if ( empty( $follower->get_last_name() ) ) {
				$follower->set_last_name( ! empty( $wp_user->last_name ) ? $wp_user->last_name : ' ' ); // NOTE: White space is intentional
			}

			// Try to get the name from 1881.no (Only valid for NO numbers)
			if( $follower->get_phone_country_code() === "+47" && (empty($follower->get_first_name()) || empty($follower->get_last_name())) ) {
				if( ($contact_details = DillerLoyalty()->get_api()->get_follower_details_by_phone($phone_number)) ){
					$follower->set_first_name(! empty($contact_details["first_name"]) ? $contact_details["first_name"] : strtoupper(substr($follower->get_email(), 0, 1)));
					$follower->set_last_name(! empty($contact_details["last_name"]) ? $contact_details["last_name"] : ' '); // NOTE: White space is intentional
				}
			}
		}

		// Update follower profile info
		$result = DillerLoyalty()->get_api()->update_follower($follower);
		if(is_wp_error($result)) {
			$data = array();
			$message = esc_html__( 'We were unable to get your details from our system', 'diller-loyalty' );
			if(preg_match("/e-?mail|e-?post/i", $result->get_error_message(''))) { 
				$data["email"] = $message = esc_html__( "This email is already in use.", 'diller-loyalty' );
			}

			return new WP_REST_Response( array( "success" => false, "data" => $data, "message" => $message, 200));
		}

		// Reset and send password.
		if(!($result = DillerLoyalty()->get_api()->send_follower_password($follower)) ) {
			return new WP_REST_Response( array( "success" => $result, "message" => esc_html__('We were unable to get your details from our system','diller-loyalty') ), 200);
		}

		// Clear the transient set on "handle_send_otp_code_endpoint_request"
		delete_transient( DillerLoyalty()->get_site_prefix() . md5($phone_country_code.$phone_number));

		return new WP_REST_Response( array(
			"success" => $result,
			"message" => sprintf(
				/* translators: 1: link to My Account / Loyalty Program page. 2: closing link */
				esc_html__('Congratulations! You will get an SMS shortly with your new password to login to your account %1$shere%2$s.', 'diller-loyalty'),
				'<a href="' . trailingslashit(wc_get_page_permalink( 'myaccount' )) . Diller_Loyalty_Configs::LOYALTY_PROFILE_ENDPOINT . '">',
				'</a>'
			)
		), 200 );
	}

	public function handle_check_email_endpoint_request($request) {
		$email = $request->get_param("email") ?? '';

		// Ignore email check for current logged-in user, if it's the same
		if(strcasecmp(DillerLoyalty()->get_current_follower()->get_email(), $email) === 0){
			return new WP_REST_Response( array( "success" => true ), 200 );
		}

		// Perform API check
		$result = DillerLoyalty()->get_api()->check_email_is_available($email);
		return new WP_REST_Response( array( "success" => $result ), 200 );
	}

}
