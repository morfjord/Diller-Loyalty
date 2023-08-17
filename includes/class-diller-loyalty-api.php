<?php
/**
 * API class.
 *
 * @package    Diller_Loyalty
 * @subpackage Diller_Loyalty/includes
 * @author     Diller AS <dev@diller.no>
 */
final class Diller_Loyalty_Api {

	public function authenticate($api_key, $store_pin){
		$api = new Diller_Loyalty_API_Request( '/storeIDAuth', array( "x_api_key" => $api_key, "store_pin" =>  $store_pin ), 'POST');
		return $api->request();
	}

	public function get_store_details(){
		$api = new Diller_Loyalty_API_Request( '/getStoreDetails', array(), 'POST', 'PosV3');
		$response = $api->request();

		if(is_wp_error($response)){
			return $response;
		}

		
		$defaults = array(
			"buttons" => array("color" => "#ffff", "color_hover" => "#000"),
			"membership_progress_bar" => array("color" => "#ffff")
		);

		$response["result"]["store_styles"] = wp_parse_args( array(
			"membership_progress_bar" => array(
				"background_color" => $response["result"]["point_bar_color"] ?? DILLER_LOYALTY_BRAND_COLOR
			),
			"buttons" => array(
				"background_color" => $response["result"]["wp_submit_button_color"] ?? DILLER_LOYALTY_BRAND_COLOR,
				"background_color_hover" => !empty($response["result"]["wp_submit_button_color_hover"])
					? $response["result"]["wp_submit_button_color_hover"]
					: Diller_Loyalty_Helpers::css_lighten_color($response["result"]["wp_submit_button_color"] ?? DILLER_LOYALTY_BRAND_COLOR, 0.2)	 // If no hover color provided, apply the default: lighten main color by 20%
			)
		), $defaults);

		
		$response["result"]["stamps_enabled"] = false;

		
		// Exclude unnecessary data from being stored in WP, to avoid confusion and misuse
		unset($response["result"]["external_id"]);
		unset($response["result"]["woocommerce_store_url"]);
		unset($response["result"]["language"]); // This is the retailer page language setting
		unset($response["result"]["store_language"]); // This is the retailer page language setting
		unset($response["result"]["partner_id"]);
		unset($response["result"]["organisation_no"]);
		unset($response["result"]["store_domain"]);
		unset($response["result"]["sms_sender_id"]);
		unset($response["result"]["store_pin"]);
		unset($response["result"]["x_api_key"]);
		unset($response["result"]["point_bar_color"]);
		unset($response["result"]["wp_submit_button_color"]);
		unset($response["result"]["wp_submit_button_color_hover"]);
		unset($response["result"]["point_bar_level_width"]);
		unset($response["result"]["department_details"]["store_id"]);
		unset($response["result"]["department_details"]["mailchimp_tag"]);
		unset($response["result"]["department_details"]["mailchimp_merge_id"]);
		unset($response["result"]["paymentplan"]);
		unset($response["result"]["store_status"]);
		unset($response["result"]["paymentplan_cap_amount"]);
		unset($response["result"]["paymentsystem"]);

		return $response["result"];
	}

	/**
	 * Update store details, with the new values passed in
	 *
	 * @param array $values
	 *
	 * @return mixed|WP_Error
	 */
	public function update_store_details($values = array()){
		$request_data = array_merge($values, array(
			// Add default values here
		));

		$api = new Diller_Loyalty_API_Request( '/updateStore', $request_data, 'POST', 'PosV2');
		$response = $api->request();

		return $response;
	}


	
	/**
	 * Retuns the full details for a giving follower, including membership details and segments
	 *
	 * @param Diller_Loyalty_Follower $follower
	 *
	 * @return Diller_Loyalty_Follower|mixed|WP_Error
	 */
	public function get_follower_details(Diller_Loyalty_Follower $follower) {

		//Note 2 self: Difference between PosV2 and PosV1 here is that Posv2 return an object with the "membershipDetails"
		$api    = new Diller_Loyalty_API_Request( "/getFollower", array( "country_code" => $follower->get_phone_country_code(), "phone" => $follower->get_phone_number()), 'POST', 'PosV2');
		$result = $api->request();

		if(is_wp_error($result)){
			return $result;
		}

		$follower_data = $result["result"];

		// Follower details
		$follower->set_first_name($follower_data["first_name"])
		         ->set_last_name($follower_data["last_name"])
		         ->set_email($follower_data["email"])
		         ->set_address($follower_data["address"] ?? '')
		         ->set_gender($follower_data["gender"] ?? 3)
		         ->set_postal_city($follower_data["city"] ?? '')
		         ->set_postal_code($follower_data["zip_code"] ?? '')
		         ->set_country($follower_data["country"] ?? '')
		         ->set_birth_date($follower_data["date_of_birth"] ?? '')
		         ->set_membership_consent_accepted(strtotime($follower_data["GDPR_date"]) > 0)
		         ->set_membership_consent_accepted_date($follower_data["GDPR_date"])
		         ->set_purchase_history_consent_accepted($follower_data["is_purchase_history"])
		         ->set_marketing_email_consent_accepted($follower_data["receive_email"])
		         ->set_marketing_sms_consent_accepted($follower_data["receive_sms"])
		         ->set_diller_id($follower_data["user_follower_id"])
		         ->set_remaining_points($follower_data['remaining_points'] ?? 0);

		// Store departments
		//Expecting: ["department_id": 477, "department_values": "Arendal" }, { "department_id": 479, "department_values": "Tromsø" }]
		$follower->set_department_ids(array_column($follower_data['DepartmentDetails'] ?? array(), 'department_id'));

		// Membership details
		$membership_details = $follower_data['membershipDetails'];
		if(is_array($membership_details) && sizeof($membership_details) > 0){
			$follower->set_points($membership_details['points'] ?? 0)
			         ->set_total_earned_points($membership_details['total_earn_point'] ?? 0)
			         ->set_points_expire_details($membership_details['point_expiry_details'])
			         ->set_membership_level_created_date($membership_details['level_created_date'])
			         ->set_membership_level_expire_details($membership_details['member_level_expiry_details'])
			         ->set_current_membership_level($membership_details['current_membership_level_title'])
			         ->set_next_membership_level($membership_details['next_membership_level_title'])
			         ->set_next_membership_level_required_points($membership_details['next_membership_level_require_points']);
					//->set_next_membership_level_points($memberships['next_membership_level_points'])
					
		}

		// Segments
		$follower_segments_data = $follower_data['SegmentDetails'] ?? array();
		if(is_array($follower_segments_data) && sizeof($follower_segments_data) > 0) {
			$follower_segments = array();
			foreach ( $follower_segments_data as $seg_key => $segment ) {
				$segment_values   = $segment["follower_segment_values"];

				// Skip empty values
				if(is_string($segment_values) && ($segment_values == '0000-00-00 00:00:00' || empty($segment_values ?? ''))){
					continue;
				}

				$follower_segments[] = array(
					"segment_id" => $segment["segment_id"],
					"segment_type" => $segment["segment_type"],
					"segment_value" => $segment_values,
				);
			}

			$follower->set_segments($follower_segments);
		}
		return $follower;
	}


	/**
	 * Fetches persons details based on his/her phone number from 1881.no. Only available for norwegian numbers
	 *
	 * @param string $phone_number
	 *
	 * @return array
	 */
	public function get_follower_details_by_phone(string $phone_number) {
		$api    = new Diller_Loyalty_API_Request( "/getFollowerDetailsByPhone/$phone_number", array(), 'GET');
		$result = $api->request();

		$contact_details = array();
		if(!is_wp_error($result) && (int)$result["result"]["count"] >= 1) {
			$contact = $result["result"]["contacts"][0];
			if(!empty($contact)){
				$contact_details["first_name"]  = $contact["firstName"] ?? '';
				$contact_details["last_name"]   = $contact["lastName"] ?? '';

				$geography = $contact["geography"];
				if(!empty($geography) && !empty($geography["address"])) {
					$contact_details["street"] = $geography["address"]["street"] ?? '';
					$contact_details["house_number"] = $geography["address"]["houseNumber"] ?? '';
					$contact_details["entrance"] = $geography["address"]["entrance"] ?? '';
					$contact_details["postal_code"] = $geography["address"]["postCode"] ?? '';
					$contact_details["postal_city"] = $geography["address"]["postArea"] ?? '';
				}
			}
		}
		return $contact_details;
	}

	/**
	 * Checks whether a given phone number is already registered for this store or not
	 *
	 * @param string $phone_country_code
	 * @param string $phone_number
	 *
	 * @return bool
	 */
	public function check_phone_number_is_available(string $phone_country_code, string $phone_number){
		$api    = new Diller_Loyalty_API_Request( "/checkFollowerPhone", array(
			"country_code" => $phone_country_code,
			"phone" => $phone_number
		));

		$result = $api->request();
		return !is_wp_error($result) && $result["success"] !== false;
	}

	/**
	 * Checks whether a given email address is already registered for this store or not
	 *
	 * @param string $email
	 *
	 * @return bool
	 */
	public function check_email_is_available(string $email){
		$api    = new Diller_Loyalty_API_Request( "/checkFollowerEmail", array(
			"country_code" => "", // Required by API v1 even if empty
			"phone" => "", // Required by API v1 even if empty
			"email" => $email
		));
		$result = $api->request();
		return !is_wp_error($result) && $result["success"] !== false;
	}


	/**
	 * Returns the membership details for a given follower
	 *
	 * @param Diller_Loyalty_Follower $follower
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function get_membership_details_for(Diller_Loyalty_Follower $follower) {
		$api    = new Diller_Loyalty_API_Request( '/getFollower', array( "country_code" => $follower->get_phone_country_code(), "phone" => $follower->get_phone_number()), 'POST', 'PosV2');
		$result = $api->request();

		if(is_wp_error($result)){
			return $result;
		}

		$memberships = $result["result"]['membershipDetails'];
		if(is_array($memberships)){
				$follower->set_points($memberships['points'] ?? 0)
		                 ->set_total_earned_points($memberships['total_earn_point'] ?? 0)
		                 ->set_points_expire_details($memberships['point_expiry_details'])
		                 ->set_membership_level_created_date($memberships['level_created_date'])
		                 ->set_membership_level_expire_details($memberships['member_level_expiry_details'])
		                 ->set_current_membership_level($memberships['current_membership_level_title'])
		                 ->set_next_membership_level($memberships['next_membership_level_title'])
		                 ->set_next_membership_level_required_points($memberships['next_membership_level_require_points']);
						//->set_next_membership_level_points($memberships['next_membership_level_points'])
						
		}
		return $follower;
	}

	/**
	 * Fetches the Follower data object by its phone number. This is a lighter version of the function <code>get_follower_details()</code> as it only returns personal data and not segments, departments data
	 * Use this function for cases like, checking if follower exists or not, if consent was accepted, etc..
	 * Returns WP_Error is no follower is found.
	 *
	 * @param string $country_code
	 * @param string $phone_number
	 *
	 * @return WP_Error|Diller_Loyalty_Follower
	 */
	public function get_follower(string $country_code, string $phone_number) {
		$api = new Diller_Loyalty_API_Request( '/getFollower', array( "country_code" => $country_code, "phone" => $phone_number), 'POST');
		$result = $api->request();

		if(is_wp_error($result)){
			return $result;
		}

		$follower_data = $result["result"];

		// Follower main properties
		$follower = (new Diller_Loyalty_Follower())
				 ->set_full_phone_number($country_code, $phone_number)
				 ->set_first_name($follower_data["first_name"])
		         ->set_last_name($follower_data["last_name"])
		         ->set_email($follower_data["email"])
		         ->set_address($follower_data["address"] ?? '')
		         ->set_gender($follower_data["gender"] ?? 3)
		         ->set_postal_city($follower_data["city"] ?? '')
		         ->set_postal_code($follower_data["zip_code"] ?? '')
		         ->set_country($follower_data["country"] ?? '')
		         ->set_birth_date($follower_data["date_of_birth"] ?? '')
		         ->set_membership_consent_accepted(strtotime($follower_data["GDPR_date"]) > 0)
		         ->set_membership_consent_accepted_date(strtotime($follower_data["GDPR_date"]))
			     ->set_purchase_history_consent_accepted($follower_data["is_purchase_history"])
		         ->set_marketing_email_consent_accepted($follower_data["receive_email"])
		         ->set_marketing_sms_consent_accepted($follower_data["receive_sms"])
		         ->set_diller_id($follower_data["user_follower_id"])
		         ->set_remaining_points($follower_data['remaining_points'] ?? 0);

		// Store departments
		//Expecting: ["department_id": 477, "department_values": "Arendal" }, { "department_id": 479, "department_values": "Tromsø" }]
		$follower->set_department_ids(array_column($follower_data['DepartmentDetails'] ?? array(), 'department_id'));

		return $follower;
	}

	/**
	 *
	 *
	 * @param Diller_Loyalty_Follower $follower
	 *
	 * @return bool
	 */
	public function invite_friend(Diller_Loyalty_Follower $follower, $friends_first_name, $friends_last_name, $friends_email) {
		$api = new Diller_Loyalty_API_Request( '/referAFriend', array(
			"country_code" => $follower->get_phone_country_code(),
			"phone" => $follower->get_phone_number(),
			"first_name" => $friends_first_name,
			"last_name" => $friends_last_name,
			"email" => $friends_email
		), 'POST');

		$result = $api->request();
		return !is_wp_error($result);
	}


	/**
	 * Returns a list of invited friends as well as it's status
	 *
	 * @param Diller_Loyalty_Follower $follower
	 *
	 * @return array
	 */
	public function get_invited_friends_list(Diller_Loyalty_Follower $follower) {
		$api = new Diller_Loyalty_API_Request( '/getFollower', array("country_code" => $follower->get_phone_country_code(), "phone" => $follower->get_phone_number() ), 'POST', 'PosV2');
		$result = $api->request();

		if(is_wp_error($result)){
			return $result;
		}

		$data = array();
		$invitations = $result["result"]['referFriendDetails'];
		if(is_array($invitations) && sizeof($invitations) > 0){
			foreach ($invitations as $key => $invitation):

				$data[] = (new Diller_Loyalty_Follower())
						 ->set_first_name($invitation['first_name'])
				         ->set_last_name($invitation['last_name'])
				         ->set_email($invitation['email'])
				         ->set_status($invitation['status']);

			endforeach;
		}
		return $data;
	}


	/**
	 * Returns the details for the person that was referred to join the Loyalty Program
	 *
	 * @param Diller_Loyalty_Follower $referral
	 *
	 * @return mixed
	 */
	public function get_invited_friend_details($referral_id) {
		$api = new Diller_Loyalty_API_Request( '/getReferFriendDetails', array( "refer_id" => $referral_id ), 'POST');
		$result = $api->request();

		if(!is_wp_error($result)){
			return (new Diller_Loyalty_Follower())
				->set_first_name($result["result"]['first_name'])
				->set_last_name($result["result"]['last_name'])
				->set_email($result["result"]['email'])
				->set_diller_id($result["result"]['user_follower_id']);
		}
		return $result;
	}


	/**
	 * Returns all the stamps for a given follower
	 *
	 * @param Diller_Loyalty_Follower $follower
	 *
	 * @return mixed
	 */
	public function get_stamps_for(Diller_Loyalty_Follower $follower) {

		$api = new Diller_Loyalty_API_Request( '/GetFollowerStamps', array(
			"country_code" => $follower->get_phone_country_code(),
			"phone" => $follower->get_phone_number(),
			"type"  => "all"
		), 'POST' );

		$result = $api->request();
		if(!is_wp_error($result)){
			$stamps = array();
			foreach($result["result"] as $key => $data){
				$stamp = new Diller_Loyalty_Stamp($data["Id"], $data["Title"]);
				$stamps[] = $stamp->set_usages($data["Usages"])
				      ->set_valid_until($data["ValidTo"])
				      ->set_discount($data["Discount"])
				      ->set_discount_type($data["DiscountType"])
				      ->set_description($data["Description"])
				      ->set_points_required($data["PointsRequired"])
				      ->set_icon($data["MediaContent"])
				      ->set_last_stamp_text($data["LastStampText"])
				      ->set_product_ids($data["product_id"])
				      ->set_product_category_ids($data["product_category_id"])
				      ->set_product_names($data["product_name"])
				      ->set_auto_start_stamp($data["autoStampStart"])
				      ->set_is_applicable($data["IsApplicable"])
				      ->set_total_redemptions($data["TotalRedemptions"])
				      ->set_remaining_redemptions($data["RemainingRedemptions"]);
			}
			return $stamps;
		}

		return $result;
	}

	/**
	 * Returns the details for a specific stamps
	 *
	 * @param Diller_Loyalty_Follower $follower
	 * @param int $stamp_id
	 *
	 * @return mixed
	 */
	public function get_stamp_details_for(Diller_Loyalty_Follower $follower, string $stamp_id) {

		$api = new Diller_Loyalty_API_Request( '/getStampDetails', array(
			"country_code" => $follower->get_phone_country_code(),
			"phone" => $follower->get_phone_number(),
			"stamp_id" => $stamp_id
		), 'POST' );

		$result = $api->request();
		if(!is_wp_error($result)){
			$data = $result["result"];
			$stamp = new Diller_Loyalty_Stamp($data["Id"], $data["Title"]);
			return $stamp->set_usages($data["Usages"])
		                ->set_valid_until($data["ValidTo"])
						->set_discount($data["Discount"])
						->set_discount_type($data["DiscountType"])
						->set_description($data["Description"])
						->set_points_required($data["PointsRequired"] ?? 0)
						->set_icon($data["MediaContent"])
						->set_last_stamp_text($data["LastStampText"])
						->set_product_ids($data["product_id"])
						->set_product_category_ids($data["product_category_id"])
						->set_product_names($data["product_name"])
						->set_auto_start_stamp($data["autoStampStart"])
						->set_is_applicable($data["IsApplicable"])
						->set_total_redemptions($data["TotalRedemptions"])
						->set_remaining_redemptions($data["RemainingRedemptions"]);
		}

		return $result;
	}


	/**
	 * Returns the details for a specific coupon
	 *
	 * @param Diller_Loyalty_Follower $follower
	 * @param int $coupon_id
	 *
	 * @return mixed
	 */
	public function get_coupon_details_for(Diller_Loyalty_Follower $follower, string $coupon_id) {

		$api = new Diller_Loyalty_API_Request( '/getCouponDetails', array(
			"country_code" => $follower->get_phone_country_code(),
			"phone" => $follower->get_phone_number(),
			"coupon_id" => $coupon_id
		), 'POST' );

		$result = $api->request();
		if(!is_wp_error($result)){
			$data = $result["result"];
			$coupon = new Diller_Loyalty_Coupon($data["Id"], $data["Title"]);
			return $coupon->set_usages($data["Usages"])
			             ->set_valid_until($data["ValidTo"])
			             ->set_discount($data["Discount"])
			             ->set_discount_type($data["DiscountType"])
			             ->set_description($data["Description"])
			             ->set_points_required($data["PointsRequired"] ?? 0)
			             ->set_icon($data["MediaContent"])
			             ->set_product_ids($data["product_id"])
			             ->set_product_category_ids($data["product_category_id"])
			             ->set_product_names($data["product_name"])
			             ->set_is_applicable($data["IsApplicable"])
			             ->set_total_redemptions($data["TotalRedemptions"])
			             ->set_remaining_redemptions($data["RemainingRedemptions"])
			             ->set_promo_code($data["PromoCode"])
			             ->set_membership_level_title($data["MembershipLevelTitle"] ?? "")
			             ->set_woocommerce_id($data["WooCommerceId"] ?? 0)
			             ->set_is_campaign($data["IsCampaign"])
			             ->set_coupon_type($data["CouponType"]);
		}

		return $result;
	}

	public function get_earned_points_for_order(Diller_Loyalty_Follower $follower, string $order_id) {
		$api = new Diller_Loyalty_API_Request( '/getOrderHistoryData', array(
			"country_code" => $follower->get_phone_country_code(),
			"phone" => $follower->get_phone_number(),
			"order_id" => $order_id
		), 'POST' );

		$result = $api->request();
		return !is_wp_error($result) ? $result["result"]["purchase_points"] : $result;
	}

	/**
	 * Cancels the order transaction.
	 *
	 * @param Diller_Loyalty_Follower $follower
	 * @param string $transaction_id
	 *
	 * @return mixed|WP_Error
	 */
	public function cancel_order_transaction_for(Diller_Loyalty_Follower $follower, string $transaction_id) {

		$api = new Diller_Loyalty_API_Request( '/cancelTransaction', array(
			"country_code" => $follower->get_phone_country_code(),
			"phone" => $follower->get_phone_number(),
			"transaction_id" => $transaction_id
		), 'POST' );

		return $api->request();
	}

	/**
	 * Returns all the coupons for a given follower
	 *
	 * @param Diller_Loyalty_Follower $follower
	 *
	 * @return mixed
	 */
	public function get_coupons_for(Diller_Loyalty_Follower $follower) {

		$api = new Diller_Loyalty_API_Request( '/GetFollowerCoupons', array(
			"country_code" => $follower->get_phone_country_code(),
			"phone" => $follower->get_phone_number(),
			"type"  => "active"
		), 'POST', "PosV2" );

		$result = $api->request();
		if(!is_wp_error($result)){
			$coupons = array();
			foreach($result["result"] as $key => $data){
				$coupon = new Diller_Loyalty_Coupon($data["Id"], $data["Title"]);
				$coupons[] = $coupon->set_usages($data["Usages"])
				                   ->set_valid_until($data["ValidTo"])
				                   ->set_discount($data["Discount"])
				                   ->set_discount_type($data["DiscountType"])
				                   ->set_description($data["Description"])
				                   ->set_points_required($data["PointsRequired"] ?? 0)
				                   ->set_icon($data["MediaContent"])
				                   ->set_is_applicable($data["IsApplicable"])
				                   ->set_total_redemptions($data["TotalRedemptions"])
				                   ->set_remaining_redemptions($data["RemainingRedemptions"])
				                   ->set_promo_code($data["PromoCode"])
				                   ->set_membership_level_title($data["MembershipLevelTitle"] ?? "")
				                   ->set_woocommerce_id($data["WooCommerceId"] ?? 0)
				                   ->set_is_campaign($data["IsCampaign"])
				                   ->set_coupon_type($data["CouponType"]);
			}
			return $coupons;
		}

		// API return a 400 if there are no coupons available for this follower. It should have been just an empty array() and return a 200 - OK
		// Therefore we catch it here until it's fixed server side.
		return array();
	}

	/**
	 * Checks whether a given coupon code is valid or not for the follower.
	 * Returns true if valid otherwise returns WP_Error object with a message of why isn't valid.
	 *
	 * @param Diller_Loyalty_Follower $follower
	 * @param string $coupon_code
	 *
	 * @return bool|WP_Error
	 */
	public function validate_coupon_for(Diller_Loyalty_Follower $follower, string $coupon_code) {
		
		$api = new Diller_Loyalty_API_Request( '/validateCouponPromoCode', array(
			"country_code" => $follower->get_phone_country_code(),
			"phone" => $follower->get_phone_number(),
			"promo_code"  => $coupon_code
		), 'POST');

		$result = $api->request();

		return !is_wp_error($result) ? true : $result;
	}

	/**
	 * Adds a phone number to Diller API. Follower is created only with phone number and will get sent a link to conclude the registration and accept the GDPR.
	 * This function is used for a POS/Diller enrollment type of scenario.
	 * To find the right values for $department_ids parameter, call the function <code>DillerLoyalty()->get_store()->get_store_departments()</code>
	 *
	 * @param $country_code Phone Country Code
	 * @param $phone_number Phone Number without spaces
	 * @param $department_id Optional. Store department ID. See <code>DillerLoyalty()->get_store()->get_store_departments()</code>
	 *
	 * @return bool|WP_Error
	 */
	public function add_new_follower($country_code, $phone_number, $department_id) {
		$request_data = array(
			"country_code" => $country_code,
			"phone" => $phone_number,
			"department_id" => $department_id,
			'subscribe_type' => 'WOOCOMMERCE',
			'subscribe_by' => 'POS'
		);

		$api = new Diller_Loyalty_API_Request( '/addFollower', $request_data, 'POST');
		$result = $api->request();

		return !is_wp_error($result) ? true : $result;
	}

	/**
	 * Creates a new User/Follower in Diller and fires the action <code>diller_api_follower_registered</code> after the new Follower was successfully created in Diller.
	 *
	 * @return Diller_Loyalty_Follower|mixed|WP_Error
	 */
	public function create_new_follower(Diller_Loyalty_Follower $follower, bool $send_welcome_msg = false, bool $send_password_msg = false) {

		$follower->set_password( wp_generate_password( 12, true, false ) );

		//Map Follower object to the API field
		$request_data = array(
			"country_code" => $follower->get_phone_country_code(),
			"phone" => $follower->get_phone_number(),
			"first_name" => $follower->get_first_name(),
			"last_name" => $follower->get_last_name(),
			"email" => $follower->get_email(),
			"address" => $follower->get_address(),
			"country" => $follower->get_country(),
			"gender" => Diller_Loyalty_Helpers::value_from_gender_name($follower->get_gender()),
			"city" => $follower->get_postal_city(),
			"postcode" => $follower->get_postal_code(),
			"zip_code" => $follower->get_postal_code(),
			"date_of_birth" => $follower->get_birth_date(),
			"privacy_policy" => $follower->get_membership_consent_accepted(), // Diller API Expects Yes/No value
			"is_purchase_history" => $follower->get_purchase_history_consent_accepted(), // Diller API Expects Yes/No value
			"receive_sms" => $follower->get_marketing_sms_consent_accepted(), // Diller API Expects Yes/No value
			"receive_email" => $follower->get_marketing_email_consent_accepted(), // Diller API Expects Yes/No value
			"password" => $follower->get_password(),
			"refer_id" => $follower->get_diller_referral_id(),
			"is_sendsms" => 0, // Diller API Expects 1/0 value. This field will trigger sending the link to confirm the registration. Eg. https://diller.app/webshop/subscription/U2lRWlVqMExpd3BVSFNJRVlmdGtWZz09
			"is_welcome_sms"  => $send_welcome_msg ? 1 : 0, // Diller API Expects 1/0 value.
			"department_id" => $follower->get_department_ids(), // Diller API Expects array of ints // Mandatory, otherwise departments get reset. Diller API Expects array of ints
			"has_external_account" => 'Yes' // This flag tells Diller api, to redirect the user to this store my account rather than the retailer panel
		);

		// This field will trigger sending the welcome text + password for logging into Diller
		if($send_password_msg){
			$request_data["is_password_sms"] = 1; // Diller API Expects 1/0 value.
		}

		// Diller API Expects array of objects, with multi-values concatenated (if applicable). Eg:
		// [
		//      { "segment_id": 318, "segment_type": 1, "segment_value" : "Lorem ipsum" },
		//      { "segment_id": 319, "segment_type": 4, "segment_value" : "Value1,Value2,Value3" } // for arrays
		// ]
		$request_data["segment_details"] =  $follower->get_segments();
		Diller_Loyalty_Helpers::join_multi_value_array_field($request_data["segment_details"], "segment_value");

		$api    = new Diller_Loyalty_API_Request( '/addRegistration', $request_data, 'POST');
		$result = $api->request();

		if(is_wp_error($result)){
			//Add extra data for logging the error
			$result->add_data($request_data, "request_data");
			return $result;
		}

		$follower->set_diller_id($result["response"]["user_id"]);

		/**
		 * Fires after the new Follower was successfully created in Diller.
		 *
		 * @since 2.0
		 *
		 * @param Diller_Loyalty_Follower $follower Follower object.
		 */
		do_action( 'diller_api_follower_registered', $follower);

		return $follower;
	}

	public function reset_phone_number($values = array()) {
		$request_data = array(
			"country_code" => $values["previous_phone_country_code"],
			"phone" => $values["previous_phone_number"],
			"change_country_code" => $values["phone_country_code"],
			"change_phone" => $values["phone_number"]
		);

		$api    = new Diller_Loyalty_API_Request( '/resetPhoneNumber', $request_data, 'POST');
		$result = $api->request();

		return !is_wp_error($result);
	}

	public function verify_phone_number_change($values = array()) {
		$request_data = array(
			"country_code" => $values["previous_phone_country_code"],
			"phone" => $values["previous_phone_number"],
			"otp" => $values["phone_verification_code"],
			"change_country_code" => $values["phone_country_code"],
			"change_phone" => $values["phone_number"]
		);

		$api    = new Diller_Loyalty_API_Request( '/VerifyChangePhoneNumber', $request_data, 'POST');
		$result = $api->request();

		return !is_wp_error($result);
	}

	/**
	 * Sends an SMS with an OTP code for the Follower to be able to login into My Account.
	 * @param $dial_code
	 * @param $phone
	 *
	 * @return bool
	 */
	public function send_sms_login_code($dial_code, $phone) {
		$api    = new Diller_Loyalty_API_Request( '/sendOTP', array( "country_code" => $dial_code, "phone" => $phone ), 'POST');
		$result = $api->request();

		return !is_wp_error($result);
	}

	/**
	 * Validates the OTP code for the Follower to be able to login into My Account.
	 *
	 * @param $dial_code
	 * @param $phone
	 * @param $otp_code
	 *
	 * @return bool
	 */
	public function validate_sms_login_code($dial_code, $phone, $otp_code) {
		$api    = new Diller_Loyalty_API_Request( '/validateOTP', array( "country_code" => $dial_code, "phone" => $phone,  "otp_code" =>  $otp_code), 'POST');
		$result = $api->request();

		return !is_wp_error($result);
	}

	/**
	 * Updates an existing Follower in Diller and fires the action <code>diller_api_follower_updated</code> if Follower was successfully updated in Diller.
	 *
	 * @param Diller_Loyalty_Follower $follower
	 *
	 * @return Diller_Loyalty_Follower|mixed|WP_Error
	 */
	public function update_follower(Diller_Loyalty_Follower $follower) {

		//Map Follower object to the API field
		$request_data = array(
			"country_code" => $follower->get_phone_country_code(),
			"phone" => $follower->get_phone_number(),
			"first_name" => $follower->get_first_name(),
			"last_name" => $follower->get_last_name(),
			"email" => $follower->get_email(),
			"gender" => Diller_Loyalty_Helpers::value_from_gender_name($follower->get_gender()),
			"address" => $follower->get_address(),
			"country" => $follower->get_country(),
			"city" => $follower->get_postal_city(),
			"postcode" => $follower->get_postal_code(),
			"zip_code" => $follower->get_postal_code(),
			"date_of_birth" => $follower->get_birth_date(),
			"privacy_policy" => $follower->get_membership_consent_accepted(), // Diller API Expects Yes/No value
			"is_purchase_history" => $follower->get_purchase_history_consent_accepted(), // Diller API Expects Yes/No value
			"receive_sms" => $follower->get_marketing_sms_consent_accepted(), // Diller API Expects Yes/No value
			"receive_email" => $follower->get_marketing_email_consent_accepted(), // Diller API Expects Yes/No value
			"is_sendsms" => 0, // Diller API Expects 1/0 value
			"is_welcome_sms" => 0, // Diller API Expects 1/0 value
			"is_password_sms" => 0, // Diller API Expects 1/0 value
			"department_id" => $follower->get_department_ids(), // Mandatory, otherwise departments get reset. Diller API Expects array of ints
			"has_external_account" => 'Yes' // This flag tells Diller api, to redirect the user to this store my account rather than the retailer panel
		);

		
		// then we need to send in param q_hidden_val so that Diller API accepts GDPR
		if($follower->get_force_membership_consent_acceptance()){
			$request_data["q_hidden_val"] = true;
		}

		// Diller API Expects array of objects, with multivalues concatenated (if applicable). Eg:
		// [
		//      { "segment_id": 318, "segment_type": 1, "segment_value" : "Lorem ipsum" },
		//      { "segment_id": 319, "segment_type": 4, "segment_value" : "Value1,Value2,Value3" } // for arrays
		// ]
		$request_data["segment_details"] =  $follower->get_segments();
		Diller_Loyalty_Helpers::join_multi_value_array_field($request_data["segment_details"], "segment_value");

		$api    = new Diller_Loyalty_API_Request( '/updateFollower', $request_data, 'POST', "PosV2");
		$result = $api->request();

		if(is_wp_error($result)){
			//Add extra data for logging the error
			$result->add_data($request_data, "request_data");

			DillerLoyalty()->get_logger()->error(sprintf("API: Error while calling `%s`", __FUNCTION__), $follower, $result);

			return $result;
		}

		/**
		 * Fires after the Follower was successfully updated in Diller.
		 *
		 * @since 2.0
		 *
		 * @param Diller_Loyalty_Follower $follower Follower object.
		 */
		do_action( 'diller_api_follower_updated', $follower);

		return $follower;
	}

	/**
	 * Sends the wordpress password to a given follower.
	 * It assumes, password was previously assigned by calling <code>$follower->set_password('xxxx')<code>.
	 * This method calls the API endpoint `updateFollower` under the hood.
	 *
	 * @param Diller_Loyalty_Follower $follower
	 *
	 * @return bool
	 */
	public function send_follower_password(Diller_Loyalty_Follower $follower) {
		if(empty($follower->get_password())) {
			_doing_it_wrong( __FUNCTION__, "You have to call $follower->set_password('xxxxx') and set a password before calling this function", '2.0' );
		}

		if(empty($follower->get_first_name()) || empty($follower->get_last_name())) {
			_doing_it_wrong( __FUNCTION__, "You have to provide a value for first_name and last_name ( eg. \$follower->set_first_name('john') \$follower->set_last_name('doe') ) before calling this function.", '2.0' );
		}

		//Map Follower object to the API field
		$request_data = array(
			// NB: If we do not send first and last name, API will return 400 - "You are not allowed to update Country Code, Phone"
			"first_name" => $follower->get_first_name(),
			"last_name" => $follower->get_last_name(),
			"country_code" => $follower->get_phone_country_code(),
			"phone" => $follower->get_phone_number(),
			"is_sendsms" => 0, // Diller API Expects 1/0 value
			"is_welcome_sms" => 0, // Diller API Expects 1/0 value
			"is_password_sms" => 1, // Diller API Expects 1/0 value
			"password" => $follower->get_password(),
			"department_id" => $follower->get_department_ids(), // Mandatory, otherwise departments get reset. Diller API Expects array of ints
			"has_external_account" => 'Yes'
		);

		$api = new Diller_Loyalty_API_Request( '/updateFollower', $request_data, 'POST', "PosV2");
		$result = $api->request();

		if(is_wp_error($result)){
			//Add extra data for logging the error
			$result->add_data($request_data, "request_data");

			DillerLoyalty()->get_logger()->error(sprintf("API: Error while calling `%s`", __FUNCTION__), $follower, $result);
		}

		return !is_wp_error($result);
	}

	public function unsubscribe_follower(Diller_Loyalty_Follower $follower) {

		//Map Follower object to the API field
		$request_data = array(
			"country_code" => $follower->get_phone_country_code(),
			"phone" => $follower->get_phone_number(),
			"first_name" => $follower->get_first_name(),
			"last_name" => $follower->get_last_name(),
			"email" => $follower->get_email(),
			"privacy_policy" => 'No',
			"is_purchase_history" => 'No',
			"receive_sms" => 'No',
			"receive_email" => 'No',
			"is_sendsms" => 0,
			"is_welcome_sms" => 0,
			"is_password_sms" => 0,
			"department_id" => $follower->get_department_ids(),
			"has_external_account" => 'No' // This will make Diller API to redirect user to Diller Retailer form, rather than webshop URL.
		);

		$api    = new Diller_Loyalty_API_Request( '/updateFollower', $request_data, 'POST', "PosV2");
		$result = $api->request();

		if(!is_wp_error($result)){
			do_action( 'diller_api_follower_unsubscribed', $follower);
		}

		return $result;
	}

	public function save_follower_transactions(Diller_Loyalty_Follower $follower, WC_Order $order){
		$coupon_codes = $order->get_coupon_codes();
		$transaction_total_amount = $order->get_total();
		$order_items = $order->get_items();
		$created_date = get_gmt_from_date($order->get_date_created(),'Y-m-d');
		$created_time_utc = get_gmt_from_date($order->get_date_created(), 'H:i:s');

		$product_details =  array();

		foreach ( $order_items as $item_id => $item_data ) {
			$product = $item_data->get_product();
			$active_price   = $product->get_price();
			$product_id = $item_data['product_id'];
			$product_categories = get_the_terms ( $product_id, 'product_cat' );
			$product_details[] = array(
				'product_id' => $product_id,
				'product_name' => $product->get_name(),
				'product_category_id' => sizeof($product_categories) > 0 ? $product_categories[0]->term_taxonomy_id : '',
				'product_qty' => wc_get_order_item_meta($item_id, '_qty', true),
				'product_price' => $active_price
			);
		}
		$transaction_details = array(
			"transaction_date" => $created_date,
			"transaction_time" => $created_time_utc,
			"transaction_id" => $order->get_id(),
			"transaction_total_amount" => $transaction_total_amount,
			"employee_name" => "",
			"transaction_total_discount_amount" => ""
		);

		$request_data = array(
			
			'country_code' => $follower->get_phone_country_code(),
			'phone' => $follower->get_phone_number(),
			"is_purchase_history" => $follower->get_purchase_history_consent_accepted(), // Diller API Expects Yes/No value
			"receive_sms" => $follower->get_marketing_sms_consent_accepted(), // Diller API Expects Yes/No value
			"receive_email" => $follower->get_marketing_email_consent_accepted(), // Diller API Expects Yes/No value
			'promo_code' => $coupon_codes,
			'transaction_details' => $transaction_details,
			'product_details' => $product_details,
			'department_id' => $follower->get_department_ids(), // Mandatory, otherwise departments get reset
			'has_external_account' => $follower->get_is_wordpress_user()
		);

		$api    = new Diller_Loyalty_API_Request( '/updateFollower', $request_data, 'POST', 'PosV2');
		$result = $api->request();

		if(!is_wp_error($result)){
			do_action( 'diller_api_follower_transaction_saved', $order->get_id());
		}
		else{
			//Add extra data for logging the error
			$result->add_data($request_data, "request_data");

			DillerLoyalty()->get_logger()->error(sprintf("API: Error while calling `%s`", __FUNCTION__), $follower, $result);
		}

		return $result;
	}
}
