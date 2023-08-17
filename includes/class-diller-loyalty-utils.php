<?php

class Diller_Coupon_Discount_Types {
	const Fixed = 1;
	const Percentage = 2;
	const FreeShipping = 3;

	/**
	 * This function ensures that the value passed in is translates to a supported discount type.
	 *
	 * @param int $value
	 *
	 * @return int
	 */
	public static function get_from_value(int $value): int {
		switch($value){
			default:
				return -1;
			case 1:
				return self::Fixed;
			case 2:
				return self::Percentage;
			case 3:
				return self::FreeShipping;
		}
	}

	public static function get_discount_name(int $value): string {
		switch($value){
			default:
				return __('Unknown','diller-loyalty');
			case 1:
				return __('Percentage','diller-loyalty');
			case 2:
				return __('Fixed','diller-loyalty');
			case 3:
				return __('Free shipping','diller-loyalty');
		}
	}
}

class Diller_Segments_Field_Types {
	const Text = 1;
	const Date = 2;
	const RadioList = 3;
	const CheckboxList = 4;
	const DropdownList = 5;

	/**
	 * This function ensures that the value passed in is translates to a supported type.
	 *
	 * @param int $value
	 *
	 * @return int
	 */
	public static function get_from_value(int $value): int {
		switch($value){
			default:
				return -1;
			case 1:
				return self::Text;
			case 2:
				return self::Date;
			case 3:
				return self::RadioList;
			case 4:
				return self::CheckboxList;
			case 5:
				return self::DropdownList;
		}
	}
}

class Diller_Languages {
	const Norwegian = 1;
	const English_AU = 2;
	const English = 3;
	const English_US = 4;
	const Swedish = 5;

	/**
	 * This function ensures that the value passed in is translates to a supported type.
	 *
	 * @param int $value
	 *
	 * @return int
	 */
	public static function get_letter_iso_code(int $value): string {
		switch($value){
			case 1:
				return 'no';
			case 2:
				return 'en_AU';
			default:
			case 3:
				return 'en';
			case 4:
				return 'en_US';
			case 5:
				return 'sv';
		}
	}
}


class Diller_Loyalty_Configs {
	const POINTS_EARNED_COLUMN_NAME = 'diller-points-earned';
	const LOYALTY_PROFILE_ENDPOINT = 'loyalty-profile';
	const LOYALTY_COUPONS_ENDPOINT = 'loyalty-coupons';
	const LOYALTY_STAMP_CARDS_ENDPOINT = 'loyalty-stamp-cards';
	const LOYALTY_FRIEND_REFERRAL_ENDPOINT = 'loyalty-friend-referral';
	const LOYALTY_SINGLE_COUPON_ENDPOINT = 'loyalty-single-coupon';
	const LOYALTY_SINGLE_STAMPCARD_ENDPOINT = 'loyalty-single-stampcard';

	const REST_ENDPOINT_BASE_URL = DILLER_LOYALTY_PLUGIN_NAME . "/v2";
	const CHECK_PHONE_NUMBER_REST_ENDPOINT = 'check-phone-number';
	const SEND_OTP_LOGIN_CODE_REST_ENDPOINT = 'send-otp-login-code';
	const VALIDATE_OTP_LOGIN_CODE_REST_ENDPOINT = 'validate-otp-login-code';
	const CHECK_EMAIL_REST_ENDPOINT = 'check-email';
	const PHONE_NUMBER_DETAILS_REST_ENDPOINT = 'get-phone-number-details';
	const REMOVE_WP_USER_REST_ENDPOINT = 'remove-wp-user';
	const UPDATE_WP_USER_REST_ENDPOINT = 'update-wp-user';
	const CREATE_WP_USER_REST_ENDPOINT = 'create-wp-user';

	// Shortcodes
	const LOYALTY_ENROLLMENT_FORM_SHORTCODE = 'diller_loyalty_enrollment_form';
	const LOYALTY_FRIEND_REFERRAL_FORM_SHORTCODE = 'diller_loyalty_refer_friend_form';
}

class Diller_Loyalty_Helpers {

	public static function gender_name_from_value(int $value): string {
		switch($value){
			case 1:
				return 'Male';
			case 2:
				return 'Female';
			default:
			case 3:
				return 'Don\' t want to share';
			case 4:
				return 'Non-binary';
		}
	}

	public static function value_from_gender_name(string $value): int {
		switch($value){
			case 'Male':
				return 1;
			case 'Female':
				return 2;
			default:
			case 'Don\' t want to share':
				return 3;
			case 'Non-binary':
				return 4;
		}
	}

	public static function convert_bool_to_yes_no($value): string {
		return (!empty($value) && preg_match('/1|true|yes/i', $value)) ? "Yes" : "No";
	}

	/**
	 * Returns the filename used by 3rd party components that match the current wordpress language.
	 * Eg. flatpickr, jquery-validate, etc. The returned filename will match one l10n file under /assets/js/component name
	 * @return string Component name
	 */
	public static function get_language_file_name($component_name = 'default'): string {
		//get_available_languages();
		$iso2_lang = get_bloginfo('language');
		$lang_maps = array();
		$lang_maps['default'] = array(
			"nb-NO" => "no",
			"da-DK" => "da",
			"sv-SE" => "sv",
			"fi-FI" => "fi",
			"nl-NL" => "nl",
			"fr-FR" => "fr",
			"de-DE" => "de",
			"es-ES" => "es",
		);

		// 2 digit lang name for flatpickr l10n
		$lang_maps['flatpickr'] = array_merge($lang_maps['default'] , array(
			"nn-NO" => "no",
		));

		// 2 digit lang name for jquery-validate l10n
		$lang_maps['jquery-validate'] = $lang_maps['default'];

		return $lang_maps[$component_name][$iso2_lang] ?? 'en';
	}

	/*public static function generate_hmac($data, $key) {
		$args = array(
			"http_method" => "",
			"store_id" => "",
			"nonce" => uniqid(),
			"request_uri" => "",
			"request_timestamp" => time(),
			"auth_signature" => ""
		);

		$signature = implode('|', array_values($args));

		return hash_hmac('sha256', $signature, $key);
	}*/

	public static function sanitize_decode_base64_value($raw_value) {
		$result = '';
		$sanitized_value = sanitize_text_field($raw_value);
		if (isset($sanitized_value)) {
			$result = sanitize_text_field(base64_decode($sanitized_value));
		}
		return $result;
	}

	/**
	 * Validates a phone number using libphonenumber-for-php library
	 *
	 * @param $value
	 *
	 * @return bool
	 */
	public static function is_valid_phone_number($value, $default_region = 'NO') {
		try {
			$default_region = (preg_match('/^(\+|00)/i', trim($value))) ? '' : (empty($default_region) ? 'NO' : $default_region);
			$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
			$phone_number_proto = $phoneUtil->parse(trim($value), $default_region);
			return $phoneUtil->isValidNumber($phone_number_proto);
		}
		catch (\libphonenumber\NumberParseException $e) {}

		return false;
	}

	public static function get_phone_country_code($value, $default_region = 'NO') {
		try {
			$default_region = (preg_match('/^(\+|00)/i', $value)) ? '' : (empty($default_region) ? 'NO' : $default_region);
			$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
			$phone_number_proto = $phoneUtil->parse(trim($value), $default_region);
			$is_valid = $phoneUtil->isValidNumber($phone_number_proto);
			if($is_valid){
				$country_code =  (string)$phone_number_proto->getCountryCode();
				return '+' . $country_code;
			}
		}
		catch (\libphonenumber\NumberParseException $e) {}

		return new WP_Error('phone-number', __('Could not extract the country code from the phone number.', 'diller-loyalty'));
	}

	public static function get_phone_number($value, $default_region = 'NO') {
		try {
			$default_region = (preg_match('/^(\+|00)/i', trim($value))) ? '' :  (empty($default_region) ? 'NO' : $default_region);
			$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
			$phone_number_proto = $phoneUtil->parse(trim($value), $default_region);
			$is_valid = $phoneUtil->isValidNumber($phone_number_proto);
			if($is_valid){
				return $phone_number_proto->getNationalNumber();
			}
		}
		catch (\libphonenumber\NumberParseException $e) {}

		return new WP_Error('phone-number', __('Invalid input. Could not parse phone number.', 'diller-loyalty'));
	}

	public static function get_full_phone_number($value, $default_region = 'NO') {
		try {
			$default_region = (preg_match('/^(\+|00)/i', trim($value))) ? '' : (empty($default_region) ? 'NO' : $default_region);
			$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
			$phone_number_proto = $phoneUtil->parse(trim($value), $default_region);
			$is_valid = $phoneUtil->isValidNumber($phone_number_proto);
			if($is_valid){
				return sprintf("%s%s", $phone_number_proto->getCountryCode(), $phone_number_proto->getNationalNumber());
			}
		}
		catch (\libphonenumber\NumberParseException $e) {}

		return new WP_Error('phone-number', __('Invalid input. Could not parse phone number.', 'diller-loyalty'));
	}

	
	public static function get_country_from_phone_number($value, $default_region = 'NO') {
		try {
			$default_region = (preg_match('/^(\+|00)/i', trim($value))) ? '' : (empty($default_region) ? 'NO' : $default_region);
			$phoneUtil = \libphonenumber\PhoneNumberUtil::getInstance();
			$phone_number_proto = $phoneUtil->parse(trim($value), $default_region);
			$is_valid = $phoneUtil->isValidNumber($phone_number_proto);
			if($is_valid){
				$geocoder = \libphonenumber\geocoding\PhoneNumberOfflineGeocoder::getInstance();
				return $geocoder->getDescriptionForNumber($phone_number_proto, "en_GB");
			}
		}
		catch (\libphonenumber\NumberParseException $e) {}
		
		return new WP_Error('phone-number', __('Invalid input. Could not parse phone number.', 'diller-loyalty'));
	}

	/**
	 * Returns an array in the format field_name => value(s), representing the dynamic data for segments, for a given Follower
	 *
	 * @param array $follower_segments
	 *
	 * @return array
	 */
	public static function generate_form_data_for_segments(array $follower_segments) {
		$follower_segments_data = array();
		$store_segments = DillerLoyalty()->get_store()->get_store_segments();
		$segment_ids = array_column($follower_segments, 'segment_id');

		foreach ($store_segments as $key => $segment):
			$segment_field_id = $segment->get_id();
			$found_index = array_search($segment_field_id, $segment_ids); //returns false or index of the found index
			if($found_index === false) continue;

			if(!empty($segment_field_value = $follower_segments[$found_index]['segment_value'])) {
				$follower_segments_data[$segment->get_field_id_attr()] = $segment_field_value; // Eg: 4#457 => "Lorem ipsum"
			}
		endforeach;

		return $follower_segments_data;
	}

	/**
	 * If the input array has a field <code>$field_name</code> that is of type array, it will concatenate all its values into a string.
	 * It uses <code>array_walk()</code> internally to modify the array.
	 *
	 * @param array $input_array Array passed as reference
	 * @param string $field_name The field name to look for inside the array
	 */
	public static function join_multi_value_array_field(array &$input_array, string $field_name = 'segment_value') {
		array_walk($input_array, function (&$item, $key, $field_name) {
			$item[$field_name] = is_array($item[$field_name])
				? implode(",", $item[$field_name])
				: $item[$field_name];
		}, $field_name);
	}


	/**
	 * CSS utility function that increases or decreases the brightness of a color by a percentage.
	 *
	 * @param   string  $hex_code        Supported formats: `#FFF`, `#FFFFFF`, `FFF`, `FFFFFF`
	 * @param   float   $adjust_percent  A number between -1 and 1. E.g. 0.3 = 30% lighter; -0.4 = 40% darker.
	 *
	 * @return  string
	 */
	public static function css_lighten_color($hex_code, $adjust_percent) {
		$hex_code = ltrim($hex_code, '#');
		$hex_code = strlen($hex_code) === 3 ? $hex_code[0] . $hex_code[0] . $hex_code[1] . $hex_code[1] . $hex_code[2] . $hex_code[2] : $hex_code;
		$hex_code = array_map('hexdec', str_split($hex_code, 2));

		foreach ($hex_code as & $color) {
			$adjustable_limit = $adjust_percent < 0 ? $color : 255 - $color;
			$adjust_amount = ceil( $adjustable_limit * $adjust_percent);
			$color = str_pad(dechex($color + $adjust_amount), 2, '0', STR_PAD_LEFT);
		}

		return '#' . implode($hex_code);
	}

	/**
	 * Checks if the current request is a WP REST API request.
	 *
	 * Case #1: After WP_REST_Request initialisation
	 * Case #2: Support "plain" permalink settings and check if `rest_route` starts with `/`
	 * Case #3: It can happen that WP_Rewrite is not yet initialized,
	 *          so do this (wp-settings.php)
	 * Case #4: URL Path begins with wp-json/ (your REST prefix)
	 *          Also supports WP installations in subfolders
	 *
	 * @return bool
	 * @author matzeeable
	 * @link https://wordpress.stackexchange.com/a/317041
	 */
	public static function is_rest_request() {
		if (defined('REST_REQUEST') && REST_REQUEST // (#1)
		    || isset($_GET['rest_route']) // (#2)
		       && strpos(sanitize_text_field($_GET['rest_route']), '/', 0 ) === 0)
			return true;

		// (#3)
		global $wp_rewrite;
		if ($wp_rewrite === null) $wp_rewrite = new WP_Rewrite();

		// (#4)
		$rest_url = wp_parse_url( trailingslashit( rest_url( ) ) );
		$current_url = wp_parse_url( add_query_arg( array( ) ) );
		return strpos( $current_url['path'], $rest_url['path'], 0 ) === 0;
	}

	/**
	 * Checks is a given URL is reachable or possibly blocked by Airplane Mode plugin,
	 * WP_HTTP_BLOCK_EXTERNAL, WP_ACCESSIBLE_HOSTS or any other mechanism such as a firewall.
	 *
	 * @param string $url
	 *
	 * @return array|false|void|WP_Error
	 */
	public static function is_request_blocked( string $url = '' ) {
		global $Airplane_Mode_Core;
		if ( defined( 'AIRMDE_VER' ) && ! empty( $Airplane_Mode_Core ) && $Airplane_Mode_Core->enabled() ) {
			return new WP_Error( 'api-error', __( 'Reason: The API was unreachable because the Airplane Mode plugin is active.', 'diller-loyalty' ) );
		}

		// Quickly test outbound connections.
		if ( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && WP_HTTP_BLOCK_EXTERNAL ) {
			if ( defined( 'WP_ACCESSIBLE_HOSTS' ) ) {
				$wp_http      = new WP_Http();
				$on_blacklist = $wp_http->block_request( $url );
				if ( $on_blacklist ) {
					return new WP_Error( 'api-error', esc_html__(
						sprintf(
						/* translators: %s The API url */
						'Reason: The API (%s) was unreachable because is on the WP HTTP block list. Check WP_ACCESSIBLE_HOSTS within the wp-config.php',
							$url
						),
						'diller-loyalty'
					) );
				}
				return false;
			}
			else {
				return new WP_Error( 'api-error', esc_html__( 'Reason: The API was unreachable because no external hostss are allowed on this site.', 'diller-loyalty' ) );
			}
		}
		else {
			$response = wp_remote_get( $url, array(
				'timeout'       => 2,
				'user-agent'    => 'DILLER/' . DILLER_LOYALTY_VERSION,
				'body'          => '',
				'method'        => 'OPTIONS'
			) );

			if( !is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
				return false;
			}
			else {
				return is_wp_error( $response ) ? $response : new WP_Error(  'api-error', esc_html__(
				/* translators: %s The API base URL */
					sprintf('Reason: The API was unreachable because the call to %s failed.', $url),
					'diller-loyalty'
				) );
			}
		}
	}

	/**
	 * Checks if a given payment gateway is enabled in Woocommerce
	 * @param string $gateway_id The gateway name
	 *
	 * @return bool
	 */
	public static function is_payment_gateway_enabled( string $gateway_id = '' ) {
		

		$payment_gateways = WC()->payment_gateways()->payment_gateways();
		foreach( $payment_gateways as $id => $gateway ){
			if($gateway->enabled == 'yes' && $id == $gateway_id){
				return true;
			}
		}
		return false;
	}

}
