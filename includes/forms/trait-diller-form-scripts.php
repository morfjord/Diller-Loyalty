<?php


trait Diller_Loyalty_Form_Scripts {

	function add_inline_scripts() {

		$js_params = $this->get_inline_javascript_params();
		$js_params->sendOtpLoginCodeEndpoint = Diller_Loyalty_Configs::SEND_OTP_LOGIN_CODE_REST_ENDPOINT;
		$js_params->validateOtpLoginCodeEndpoint = Diller_Loyalty_Configs::VALIDATE_OTP_LOGIN_CODE_REST_ENDPOINT;
		$js_params->checkPhoneNumberEndpoint = Diller_Loyalty_Configs::CHECK_PHONE_NUMBER_REST_ENDPOINT;
		$js_params->checkEmailEndpoint = Diller_Loyalty_Configs::CHECK_EMAIL_REST_ENDPOINT;
		$js_params->checkPhoneNumberDetailsEndpoint = Diller_Loyalty_Configs::PHONE_NUMBER_DETAILS_REST_ENDPOINT;

		// Address fields - configurations
		//$js_params->address = new stdClass();
		//$js_params->address->postalCodeFormat = DillerLoyalty()->get_store()->get_default_postal_code_format();

		// Date fields - configurations
		$js_params->calendar = new stdClass();
		$js_params->calendar->placeholder = DillerLoyalty()->get_store()->get_default_date_placeholder();
		$js_params->calendar->locale = Diller_Loyalty_Helpers::get_language_file_name('flatpickr');

		// Phone - configurations
		$allowed_countries_option = DillerLoyalty()->get_store()->get_phone_country_option();
		$js_params->phone = new stdClass();
		$js_params->phone->preferredCountries = DillerLoyalty()->get_store()->get_phone_preferred_countries();
		$js_params->phone->defaultCountryCode = DillerLoyalty()->get_store()->get_phone_default_country_code();
		$js_params->phone->intlInputPluginEnabled = DillerLoyalty()->get_store()->get_phone_intl_plugin_input_enabled();
		$js_params->phone->allowedCountriesOption = $allowed_countries_option;
		$js_params->phone->selectedCountries = array();

		if($allowed_countries_option != "all"){
			$selected_countries = DillerLoyalty()->get_store()->get_phone_countries();
			$all_countries = array_map('strtolower', array_keys(WC()->countries->get_countries()));
			$js_params->phone->selectedCountries = ($allowed_countries_option === "specific")
				? $selected_countries // specific
				: array_values(array_filter($all_countries, function ($cc) use($selected_countries) {
					return !in_array($cc, $selected_countries); // all_except
				}));
		}

		$scripts = "window.Diller_Loyalty = " . json_encode($js_params) .";";

		foreach ( $this->fields as $field ) {
			$scripts .= $field->inline_scripts().PHP_EOL;
		}

		if(isset($js_params->form->inline_scripts) && is_array($js_params->form->inline_scripts)) {
			$scripts .= implode( PHP_EOL, $js_params->form->inline_scripts );
			unset($js_params->form->inline_scripts);
		}

		$this->inline_scripts[] = $scripts;
	}

	abstract public function get_inline_javascript_params();

	// To implement overridding trait function, see: https://freek.dev/1764-how-to-call-an-overridden-trait-function
}
