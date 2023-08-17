<?php

class Diller_Member_Enrollment_Form extends Diller_Form {
	use Diller_Loyalty_Form_Scripts;

	public function __construct($id){
		parent::__construct($id);
	}

	/**
	 * Returns a list of default fields that are common for this form and other that may inherit from this.
	 * This function can be overridden if needed.
	 * @return array
	 */
	public function get_default_fields(){
		$fields = array(
			//Add default fields to the form
			10 =>  new Diller_Hidden_Field("phone_country_code","", array()),
			20 =>  new Diller_Phone_Field("phone_number", __('Mobile number', 'diller-loyalty'), array(), array(
				'depends_on_field' => "phone_country_code",
				'validation_rules' => array(
					'required' => true,
					'minlength' => 8,
					'maxlength' => 10,
					'phonenumber' => 255
				)
			)),

			21 => new Diller_Email_Field("email", __('Email', 'diller-loyalty'), array(), array(
				'validation_rules' => array(
					'required' => true,
					'email' => true
				)
			)),
			22 => new Diller_Text_Field("first_name", __('First Name', 'diller-loyalty'), array(), array(
				'validation_rules' => array(
					'required' => true,
					'minlength' => 2,
					'maxlength' => 255
				)
			)),
			23 => new Diller_Text_Field("last_name", __('Last Name', 'diller-loyalty'), array(), array(
				'validation_rules' => array(
					'required' => true,
					'minlength' => 2,
					'maxlength' => 255
				)
			)),
			25 => new Diller_Select_Field("gender", __('Gender', 'diller-loyalty'), array(), array(
				'allow_none' => false,
				'default' => Diller_Loyalty_Helpers::gender_name_from_value(3),
				'options' => array(
					Diller_Loyalty_Helpers::gender_name_from_value(1) => __('Male', 'diller-loyalty'),
					Diller_Loyalty_Helpers::gender_name_from_value(2) => __('Female', 'diller-loyalty'),
					Diller_Loyalty_Helpers::gender_name_from_value(3) => __('Prefer not to answer', 'diller-loyalty'),
					Diller_Loyalty_Helpers::gender_name_from_value(4) => __('Non-binary', 'diller-loyalty')
				)
			)),
			30 => new Diller_Text_Field("address", __('Address', 'diller-loyalty'), array(), array()),
			40 => new Diller_Text_Field("postal_code", __('Postal code', 'diller-loyalty'), array(), array(
				
				//'validation_rules' => array(
				//	'minlength' => 4,
				//	'maxlength' => 4,
				//	'number' => true
				//)
			)),
			50 => new Diller_Text_Field("postal_city", __('Postal city', 'diller-loyalty'), array(), array(
				'validation_rules' => array(
					'minlength' => 2,
					'maxlength' => 255
				)
			)),

		    
			60 => new Diller_Select_Field("country", __('Country', 'diller-loyalty'), array(), array(
				'default' => 'NO',
				'data_delegate' => array(WC()->countries, 'get_countries')
			)),

			70 => new Diller_Date_Field("birth_date", __('Birth date', 'diller-loyalty'), array(), array(
				'validation_rules' => array(
					'validdate' => true,
					'lessthandate' => true,
					'agehigherthan' => true
				)
			))
		);

		//Dynamic segments
		$segments = DillerLoyalty()->get_store()->get_store_segments();
		foreach ($segments as $key_seg => $segment_details):

			if(!$segment_details->get_is_visible()){
				continue;
			}

			$segment_field_id_attr = $segment_details->get_field_id_attr();
			$segment_field_name = $segment_details->get_name();
			$segment_field_type = $segment_details->get_field_type();
			$segment_field_required = $segment_details->get_is_required();
			$segment_field_values = $segment_details->get_values();
			$options = array();
			$base_priority = array_key_last($fields) + 10;

			if(sizeof($segment_field_values) > 1):
				foreach ($segment_field_values as $key => $value):
					$options[$value["value"]] = $value["value"];
				endforeach;
			endif;

			switch ($segment_field_type):
				case Diller_Segments_Field_Types::Text:
					$fields[++$base_priority] = new Diller_Text_Field($segment_field_id_attr, $segment_field_name, array(), array(
						'validation_rules' => array(
							"required" => $segment_field_required,
							"maxlength" => 255,
							'minlength' => 2
						)
					));
					break;

				case Diller_Segments_Field_Types::Date:
					$fields[++$base_priority] = new Diller_Date_Field($segment_field_id_attr, $segment_field_name, array(), array(
						"validation_rules" => array(
							"required" => $segment_field_required,
							"validdate" => $segment_field_required
						)
					));
					break;

				case Diller_Segments_Field_Types::RadioList:
					$fields[++$base_priority] = new Diller_Radio_Field($segment_field_id_attr, $segment_field_name, array(), array(
						"options" => $options,
						"validation_rules" => array(
							"required" => $segment_field_required
						)
					));
					break;

				case Diller_Segments_Field_Types::CheckboxList:
					$fields[++$base_priority] = new Diller_Checkbox_Multi($segment_field_id_attr, $segment_field_name, array(), array(
						"options" => $options,
						"validation_rules" => array(
							"required" => $segment_field_required
						)
					));
					break;

				case Diller_Segments_Field_Types::DropdownList:
					$fields[++$base_priority] = new Diller_Select_Field($segment_field_id_attr, $segment_field_name, array(), array(
						"allow_none" => true,
						"options" => $options,
						"validation_rules" => array(
							"required" => $segment_field_required
						)
					));
					break;
			endswitch;
		endforeach;

		// Store departments
		$departments = DillerLoyalty()->get_store()->get_store_departments();
		$base_priority = array_key_last($fields) + 10;
		if($departments && $departments->get_is_visible()):

			if ($departments->get_field_type() == Diller_Segments_Field_Types::CheckboxList):

				$options = array();
				if(sizeof($departments->get_values()) > 1):
					foreach ($departments->get_values() as $key => $depart):
						$options[$depart["id"]] = $depart["value"];
					endforeach;
				endif;

				$fields[++$base_priority] = new Diller_Checkbox_Multi("department_ids", $departments->get_name(), array(), array("options" => $options));
			endif;

		endif;

		$base_priority = array_key_last($fields) + 10;

		//Consent / T&C fields
		$purchase_history_consent_text = __('I want to get offers and benefits that suit me based on my preferences and purchase history.', 'diller-loyalty');
		$diller_join_text = sprintf(
			/* translators: 1: Store Name, 2: link to Terms & Conditions URL, 3: closing url */
			esc_html__( 'I want to join %1$s\'s loyalty club and receive benefits, offers and other marketing communications electronically, including email, SMS and the like. Read our %2$sprivacy policy here%3$s', 'diller-loyalty' ),
			DillerLoyalty()->get_store()->get_store_name(),
			'<a href="' . esc_url( DillerLoyalty()->get_store()->get_privacy_policy_url()) . '" target="_blank">',
			'</a>'
		);

		$fields[++$base_priority] = new Diller_Checkbox_Field("membership_consent_accepted", $diller_join_text, array(), array(
			"outer_class" => "diller-form-group diller-mt-5",
			"default" => "Yes",
			'validation_rules' => array(
				'required' => true
			)
		));
		$fields[++$base_priority] = new Diller_Checkbox_Field("purchase_history_consent_accepted", $purchase_history_consent_text, array(), array(
			"default" => "Yes"
		));

		//Note 2 self: marketing_sms_consent_accepted and marketing_email_consent_accepted fields are only displayed under My Account area

		return $fields;
	}

	public function build_fields()
	{
		$fields = array();
		$fields[998] = new Diller_Hidden_Field("referral","", array());

		//Note 2 self: array_merge() resets the key values to start from index 0
		$merged_fields = $fields + $this->get_default_fields();

		// Sort fields by priority
		ksort($merged_fields, SORT_NUMERIC);

		// Add fields to the form
		array_map([$this, 'add_field'], $merged_fields);

		$this->add_element(new Diller_Submit_Button("subscribe", __('Subscribe', 'diller-loyalty'), array(
			"class" => "diller-button diller-button--primary diller-button--round"
		)));

		$this->load_form_data();

		$this->register_inline_scripts();
	}

	/**
	 * Loads values for the fields from an array of supplied values or automatically from $_POST object if the form was submitted.
	 */
	function load_form_data($values = array()){

		//If we have a postback, use just the submitted values.
		//This will be used if validation fails, to prevent filling the data twice
		if((isset($_POST) && !empty($_POST))) {
			foreach ( $_POST as $key => $value) {
				if ( is_array($value) ) {
					$values[$key] = array_map("sanitize_text_field", $value);
					continue;
				}
				$values[$key] = sanitize_text_field($value);
			}
		}else{
			$values = $this->load_values_from_query_string_data($values);
		}

		// Fill in the fields values.
		foreach ($this->fields as $key => $field) {
			$value = array_key_exists($field->get_name(), $values) ? $values[$field->get_name()] : '';
			is_array($value) ? $field->set_values($value) : $field->set_value($value);
		}
	}

	private function load_values_from_query_string_data($values = array()){

		// Check if we have an invited friend link to handle
		// Eg. http://localhost:8080/follower-club-enrollment/?refer_id=MDlKRjQ5b05MTUtCclpvTjU2MlNYUT09
		if(isset($_GET['refer_id']) && !empty($referral_id = sanitize_text_field($_GET['refer_id']))) {
			$referral = DillerLoyalty()->get_api()->get_invited_friend_details($referral_id);
			if ( !is_wp_error($referral) ) {
				$values["first_name"] = $referral->get_first_name();
				$values["last_name"] = $referral->get_last_name();
				$values["email"] = $referral->get_email();
				$values["referral"] = $referral_id;
			}
		}

		return $values;
	}

	public function validate_request(){
		$request_data = parent::validate_request();
		if(is_wp_error($request_data) || $request_data === false){
			return false;
		}

		// Check dynamic segments validations
		$segments_request_data = $this->get_segments_from_request_data();
		if(is_wp_error($segments_request_data) || $segments_request_data === false){
			return false;
		}

		
		$this->set_request_data($request_data + array('segments' => $segments_request_data));

		return true;
	}

	/**
	 * Aggregates the submitted form data with other fields data that are more complex (eg. multidimensional values from segments, etc)
	 *
	 * @return array
	 */
	protected function get_submitted_data(){
		// $test = $this->get_request_data();

		$request_data = parent::get_submitted_data();
		$request_data['segments'] = $this->get_segments_from_request_data();
		return $request_data;
	}

	public function save(){
		$request_data = $this->get_submitted_data();
		// $request_data = $this->get_request_data();

		$follower = (new Diller_Loyalty_Follower())
				 ->set_first_name($request_data["first_name"])
		         ->set_last_name($request_data["last_name"])
				 ->set_full_phone_number($request_data["phone_country_code"], $request_data["phone_number"])
		         ->set_email($request_data["email"])
		         ->set_gender($request_data["gender"])
		         ->set_address($request_data["address"])
		         ->set_country($request_data["country"])
		         ->set_postal_city($request_data["postal_city"])
		         ->set_postal_code($request_data["postal_code"])
		         ->set_birth_date($request_data["birth_date"])
		         ->set_membership_consent_accepted($request_data["membership_consent_accepted"] ?? 'No')
		         ->set_purchase_history_consent_accepted( $request_data["purchase_history_consent_accepted"] ?? 'No')
		         ->set_marketing_email_consent_accepted( $request_data["marketing_email_consent_accepted"] ?? 'No')
		         ->set_marketing_sms_consent_accepted( $request_data["marketing_sms_consent_accepted"] ?? 'No')
				 ->set_department_ids($request_data["department_ids"] ?? array())
				 ->set_segments($request_data["segments"] ?? array())
				 ->set_diller_referral_id($request_data["referral"] ?? '');

		$result = DillerLoyalty()->get_api()->get_follower($follower->get_phone_country_code(), $follower->get_phone_number());
		if(is_a($result, 'Diller_Loyalty_Follower')){
			$error_message = __('There is already a member with this phone number associated.', 'diller-loyalty');
			DillerLoyalty()->add_notice($this->id,'error', $error_message);
			return false;
		}

		

		// Checks whether a WP user already exists in WP or not.
		$user = get_user_by('email', $follower->get_email());
		$send_welcome_sms = $user && is_a($user, 'WP_User');
		$send_password_sms = $send_welcome_sms === false;

		$result = DillerLoyalty()->get_api()->create_new_follower($follower, $send_welcome_sms, $send_password_sms);
		if(is_wp_error($result)){
			$error_message = !empty($result->get_error_message())
				? $result->get_error_message()
				: __('Ooops! An error has occurred while we tried to enroll you in the loyalty program. Please try again later. If the problem persists please contact us.', 'diller-loyalty');

			DillerLoyalty()->add_notice($this->id,'error', $error_message);
			return false;
		}

		// All is good...
		$success_message = esc_html__('Congratulations! You are now registered successfully in our loyalty program.', 'diller-loyalty');
		if(!is_user_logged_in()){
			$success_message .= ' ' . sprintf(
				/* translators: 1: link to Webshop page. 2: closing link */
				esc_html__('You can access your loyalty program status and benefits by %1$sclicking here%2$s', 'diller-loyalty'),
               '<a href="' . esc_url(wc_get_page_permalink('myaccount')) . '">',
               '</a>'
			);
		}

		DillerLoyalty()->add_notice( $this->id,'success', $success_message);

		return true;
	}

	public function get_segments_from_request_data(){
		$values = array();
		$has_errors = false;
		$segments = DillerLoyalty()->get_store()->get_store_segments();

		foreach ($segments as $key => $segment):
			// Expecting eg. 4#345
			$segment_field_uid = $segment->get_field_id_attr();
			if(isset($_POST[$segment_field_uid])):

				
				if($segment->get_field_type() == (string)Diller_Segments_Field_Types::Date){
					// We use flatpickr in the frontend to help pick dates.
					// When instantiated, flatpickr swaps the original date field with another and creates an hidden field,
					// to keep track of the value chosen, in the right server side date format (Y-m-d)
					// Unlike native birth_date field, Diller retailer saves the dynamic date field for segments, as yyyy-mm-dd
					// As such, we make use of the hidden_2$XXX field instead, to capture the right date value for it.
					if(!isset($_POST['hidden_'.$segment_field_uid]) || empty($_POST['hidden_'.$segment_field_uid])) continue;

					$date_value = sanitize_text_field($_POST['hidden_'.$segment_field_uid]);
					if (!DateTime::createFromFormat('Y-m-d', $date_value)){
						/* translators: %s: Field name. */
						DillerLoyalty()->add_notice($this->id, 'error', sprintf(__('Field: %s has an invalid date value.', 'diller-loyalty'), $segment->get_name()));
						$has_errors = true;
						continue;
					}

					$segment_field_values = $date_value;
				}else {
					//Check for multiple values or single value
					$segment_field_values = ( is_array( $_POST[ $segment_field_uid ] ) )
						? filter_var_array( $_POST[ $segment_field_uid ], FILTER_SANITIZE_STRING )
						: sanitize_text_field( $_POST[ $segment_field_uid ]);
				}

				// This is the actual format API v1 is expecting
				$values[] = array(
					"segment_id"    => $segment->get_id(),
					"segment_type"  => $segment->get_field_type(),
					"segment_value" => $segment_field_values
				);

			elseif($segment->get_is_required() && empty($_POST[$segment_field_uid])):
				/* translators: %s: Field name. */
				DillerLoyalty()->add_notice($this->id, 'error', sprintf(__('Field: %s is required.', 'diller-loyalty'), $segment->get_name()));
				$has_errors = true;
			endif;
		endforeach;

		return $has_errors ? false : $values;
	}

	/**
	 * Load JS scripts this form will use.
	 *
	 * @uses enqueue_styles
	 */
	function enqueue_styles() {
	}

	/**
	 * Load specific stylesheets this form will use
	 *
	 * @uses enqueue_scripts
	 */
	function enqueue_scripts() {
		// Scripts are bundled using Gulp script and served in the following files:
		// ./assets/js/diller-loyalty-admin-bundle.js
		// ./assets/js/diller-loyalty-public-bundle.js
		// ./assets/js/vendors-bundle.js

		// Localize Scripts
		$iso2_lang = get_bloginfo('language');
		if(substr($iso2_lang,0,2) !== 'en'){
			// JQuery Validate
			$lang = Diller_Loyalty_Helpers::get_language_file_name('jquery-validate');
		    $language_file_found = file_exists( DILLER_LOYALTY_PATH."assets/js/jquery-validate/localization/messages_${lang}.min.js" );
			if($language_file_found){
				wp_enqueue_script( 'jquery-validate-i18n', trailingslashit( DILLER_LOYALTY_URL ) . "assets/js/jquery-validate/localization/messages_${lang}.min.js", array( DILLER_LOYALTY_JS_VENDORS_BUNDLE_HANDLE ), '1', true );
			}

			// Flatpickr
			$lang = Diller_Loyalty_Helpers::get_language_file_name('flatpickr');
			$language_file_found = file_exists( DILLER_LOYALTY_PATH."assets/js/flatpickr/l10n/${lang}.js" );
			if($language_file_found){
				wp_enqueue_script( 'flatpickr-l10n', trailingslashit( DILLER_LOYALTY_URL ) . "assets/js/flatpickr/l10n/${lang}.js", array( DILLER_LOYALTY_JS_VENDORS_BUNDLE_HANDLE ), '4.6.9', true );
			}
		}
	}


	// Implements the abstract function from the trait, by calling
	public function get_inline_javascript_params() {
		return parent::get_inline_javascript_params();
	}

}


