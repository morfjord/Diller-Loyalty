<?php


class Diller_WC_Update_Phone_Form extends Diller_Form {
	use Diller_Loyalty_Form_Scripts;

	public static function phone_number_has_changed(): bool {
		if(false === DillerLoyalty()->user_has_joined()) return false;

		if(DillerLoyalty()->get_current_follower()->get_membership_consent_accepted() !== 'Yes') return false;

		if(!isset($_POST["phone_number"]) || empty(sanitize_text_field($_POST["phone_number"]))) return false;

		if(isset($_POST["phone_number"]) && sanitize_text_field($_POST["phone_number"]) === DillerLoyalty()->get_current_follower()->get_phone_number()
		   && isset($_POST["phone_country_code"]) && sanitize_text_field($_POST["phone_country_code"]) === DillerLoyalty()->get_current_follower()->get_phone_country_code()) return false;

		return true;
	}

	private $skip_form_render = false;
	private $current_phone_data = array();

	public function __construct($id){
		parent::__construct($id);

		$this->current_phone_data = array(
			"previous_phone_country_code" => DillerLoyalty()->get_current_follower()->get_phone_country_code(),
			"previous_phone_number" => DillerLoyalty()->get_current_follower()->get_phone_number(),
			"phone_verification_code" => ''
		);
	}

	public function build_fields(){
		$this->add_field(new Diller_Hidden_Field("previous_phone_number","", array(), array()));
		$this->add_field(new Diller_Hidden_Field("previous_phone_country_code","", array(), array()));
		$this->add_field(new Diller_Hidden_Field("phone_country_code","", array(), array()));
		$this->add_field(new Diller_Phone_Field("phone_number", __('New mobile number', 'diller-loyalty'), array(), array(
				'depends_on_field' => "phone_country_code",
				'readonly' => true,
				'validation_rules' => array(
					'required' => true
					// Since it's read-only we don't need all the validation rules for client side (jQuery Validate)
				)
			))
		);
		$this->add_field(new Diller_Text_Field( "phone_verification_code", __( 'Verification code (sent by SMS)', 'diller-loyalty' ), array(), array(
				'validation_rules' => array(
					'required' => true,
					'minlength' => 4,
					'maxlength' => 4,
					'digits' => true
				))
            )
		);

		$this->add_element(new Diller_Submit_Button("subscribe", __('Verify phone number', 'diller-loyalty'), array(
			"class" => "diller-button diller-button--primary diller-button--round"
		)));

		$this->register_inline_scripts();
	}

	public function render() {
		if($this->skip_form_render){
			// Just render title and notifications
			if ( !empty( $this->title ) ) {
				echo '<h2 class="diller-heading__title"">' . esc_html( $this->title ) . '</h2>';
			}
			$this->display_notices();
		}
		else {
			// Normal form render with fields (default)
			$this->load_form_data( array_merge( $this->current_phone_data, array(
					"phone_country_code" => ( isset( $_POST["phone_country_code"] ) ) ? sanitize_text_field( $_POST["phone_country_code"] ) : '',
					"phone_number"       => ( isset( $_POST["phone_number"] ) ) ? sanitize_text_field( $_POST["phone_number"] ) : ''
				) )
			);
			parent::render();
		}
	}

	/**
	 * Loads values for the fields from an array of supplied values or automatically from $_POST object if the form was submitted.
	 */
	public function load_form_data($values = array()) {
		//If we have a postback, use just the submitted values.
		//This will be used if validation fails, to prevent filling the data twice
		$values = (isset($_POST) && !empty($_POST)) ? array_merge(array_map("sanitize_text_field", $_POST), $values) : $values;

		// Fill in the fields values.
		foreach ($this->fields as $key => $field) {
			$value = array_key_exists($field->get_name(), $values) ? $values[$field->get_name()] : '';
			is_array($value) ? $field->set_values($value) : $field->set_value($value);
		}
	}

    public function save(){

	    unset($GLOBALS['diller_hide_profile_form']);

        // Phone number changes are only possible for joined follower
	    if(false === DillerLoyalty()->user_has_joined()) return false;

	    //$request_data = $this->get_request_data();
	    $message = '';
	    $request_data = array_merge($this->current_phone_data, $this->get_submitted_data());
		$new_phone_number = $request_data["phone_number"];
	    $new_phone_country_code = $request_data["phone_country_code"];
	    $phone_verification_code = $request_data["phone_verification_code"];

        // If we have a verification code, verify it
        if(!empty($phone_verification_code)){
            $result = DillerLoyalty()->get_api()->verify_phone_number_change($request_data);
            $message = $result ? __('Your phone number has been successfully changed!', 'diller-loyalty') :  __('The verification code you entered is incorrect.', 'diller-loyalty');

	        if($result === true){
		        DillerLoyalty()->get_current_follower()
		                       ->set_full_phone_number($new_phone_country_code, $new_phone_number)
		                       ->save();

				$this->skip_form_render = true;
	        }
        }
        else {
            //Send verification code to the new phone number
            $result = DillerLoyalty()->get_api()->reset_phone_number($request_data);
            $message = $result
                ?  sprintf(
                    /* translators: %s: Phone number. */
                    __('We just sent an SMS to <b>%s</b> with your verification code. It may take some seconds to arrive.', 'diller-loyalty'),
		            ($new_phone_country_code !== DillerLoyalty()->get_store()->get_phone_default_country_code() ? $new_phone_country_code .' '. $new_phone_number : $new_phone_number)
                )
                : sprintf(
                    /* translators: %s: Phone number. */
                    __('We could not send the verification code to <b>%s</b>.', 'diller-loyalty'),
		            ($new_phone_country_code !== DillerLoyalty()->get_store()->get_phone_default_country_code() ? $new_phone_country_code .' '. $new_phone_number : $new_phone_number)
                );
        }

        DillerLoyalty()->add_notice($this->get_id(), ($result ? 'success' : 'error'), $message);

		$GLOBALS['diller_hide_profile_form'] = true;

        return $result;
	}

	/**
	 * Load JS scripts this form will use.
	 *
	 * @uses enqueue_scripts
	 */
	function enqueue_scripts() {
		parent::enqueue_scripts();
	}

	/**
	 * Load specific stylesheets this form will use
	 *
	 * @uses enqueue_styles
	 */
	function enqueue_styles() {
		parent::enqueue_styles();
	}

	// Implements the abstract function from the trait, by calling
	public function get_inline_javascript_params() {
		$js_params = parent::get_inline_javascript_params();

		// Form fields
		$js_params->form->enableEmailLookup = false;
		$js_params->form->enablePhoneLookup = false;

		return $js_params;
	}
}
