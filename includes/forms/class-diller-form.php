<?php

/**
 * Abstract class for all Diller forms.
 *
 * @abstract
 *
 */


abstract class Diller_Form {

	protected $id;

	protected $title;

	protected $nonce_field;

	protected $method = 'POST';

	protected $fields = array();

	protected $request_data = array();

	protected $elements;

    /*
     * Array of strings containing inline scripts that will be added to the footer
     * Use this to add dynamic variables, or on-liner JS dynamic statements
     */
	protected $inline_scripts = array();

	public function __construct( $id, $title = '' ) {
		$this->elements    = array();
		$this->fields      = array();
		$this->id          = $id;
		$this->title       = $title;
		$this->nonce_field = 'diller_subscribe_nonce';

		//Adding the nonce field directly here
		$this->add_field( new Diller_Hidden_Field( $this->nonce_field, "", array(), array(
			'default' => wp_create_nonce( $this->nonce_field ),
		) ) );
	}

	public function add_field( Diller_Base_Field $field ) {
		$this->fields[] = $field;
	}

	public function get_field(string $field_name) {
		$found_index = array_search($field_name, array_column($this->fields, 'name'), true);
		return $this->fields[$found_index];
	}

	public function get_id() {
		return $this->id;
	}

	public function set_title( string $title ) {
		$this->title = $title;
	}

	public function set_request_data( array $request_data ) {
		$this->request_data = $request_data;
	}

	public function get_request_data() {
		return $this->request_data;
	}

	public function add_element( Diller_Form_Element $element ) {
		$this->elements[] = $element;
	}

	public function build_fields() {
	}

	public function render() {
		if ( !empty( $this->title ) ) {
			echo '<h2 class="diller-heading__title">' . esc_html( $this->title ) . '</h2>';
		}

		$this->display_notices();

		?>
        <form action="" class="diller-form" id="<?php echo esc_attr($this->id); ?>" method="post">
			<?php

			foreach ( $this->fields as $field ) :
				$field->display();
			endforeach;

			foreach ( $this->elements as $element ) :
				$element->display();
			endforeach;

			?>
        </form>
		<?php
	}

	/**
	 * Calls add_inline_scripts() internally to ensure all the scripts are added to the internal script array
     * and enqueue all those scripts, to be rendered in the footer.
     *
     * @uses Diller_Form::add_inline_scripts()
	 */
	public function register_inline_scripts() {

        // Ensure all the scripts are added
        $this->add_inline_scripts();

        if(sizeof($this->inline_scripts) > 0){
	        // Handle arg. defines where will the inline scripts be placed
	        //wp_register_script( 'diller-loyalty-scripts', false );
	        //wp_enqueue_script( 'diller-loyalty-scripts' );
	        wp_add_inline_script(DILLER_LOYALTY_JS_VENDORS_BUNDLE_HANDLE, join(PHP_EOL, $this->inline_scripts), 'before' );
        }
	}

	/**
	 * Shortcut function to display specific notices for this form.
	 * Uses DillerLoyalty()->display_notices() under the hood.
	 */
	public function display_notices() {
		if ( DillerLoyalty()->has_notice( $this->id ) ) {
			DillerLoyalty()->display_notices( $this->id, false );
		}
	}

	/**
	 * This function will perform the default validation on form submit, such as nonce validation and field required, input in right format etc.
	 *
	 * @return array|false|void
	 */
	public function validate_request() {
		$has_errors   = false;
		$request_data = array();
		foreach ( $this->fields as $field ) {
			if ( isset( $_POST[ $field->name ] ) ) {
				$request_data[ $field->name ] = sanitize_text_field( $_POST[ $field->name ] );

				// Call the specific validation rule for the current field
				$validation_result = $field->validate( $request_data[ $field->name ] );

				if ( is_wp_error( $validation_result ) ) {
					//On validation error, join all the errors together in one WP_Error object.
					//$this->errors->merge_from($validation_result);
					DillerLoyalty()->add_notice( $this->id, 'error', $validation_result );
					$has_errors = true;
				}
			}
		}
		return $has_errors ? false : $request_data;
	}

	/**
	 * Returns an array with all the data submitted by the form.
	 * Override this method in child classes, in order to aggregate other data value that are more complex, such as Segments.
	 *
	 * @return array
	 */
	protected function get_submitted_data() {
		$request_data = array();
		foreach ( $this->fields as $field ) {
			if ( isset( $_POST[ $field->name ] ) ) {
				$request_data[ $field->name ] = is_array( $_POST[ $field->name ] )
					? filter_var_array( $_POST[ $field->name ] )
					: sanitize_text_field( $_POST[ $field->name ] );
			}
		}

		return $request_data;
	}

	/**
	 * Checks wether the form was submitted or not by checking the existing and validation of the form nonce field
	 * @return bool
	 */
	public function was_submitted() {
		return ( isset( $_POST[ $this->nonce_field ] ) && ! empty( $_POST[ $this->nonce_field ] ) && wp_verify_nonce( sanitize_text_field( $_POST[ $this->nonce_field ]), $this->nonce_field ) );
	}

	public function save() {
	}

	/**
	 * Load JS scripts for this form use.
	 *
	 * @uses enqueue_scripts
	 */
	function enqueue_scripts() {
	}

	/**
	 * Load stylesheets for this form use.
	 *
	 * @uses enqueue_styles
	 */
	function enqueue_styles() {
	}

	/**
	 * Add JS inline scripts for this form.
	 *
	 */
	protected function add_inline_scripts() {
    }


	/**
	 * Returns an array with all validation rules for the forms fields
	 *
	 */
	function get_validation_rules() {
		$rules = array();
		foreach ( $this->fields as $field ) {
			if($field_rules = $field->get_validation_rules()){
				$field_name = ($field instanceof Diller_Checkbox_Multi) ? $field->get_name() . "[]" : $field->get_name();
				$rules[$field_name] = $field_rules;
			}
		}
        return $rules;
	}

	/**
     * Builds and returns a stdClass representing dynamic parameters, that will be used in JS on the frontend
     *
	 * @return stdClass
	 */
	function get_inline_javascript_params() {

		// Create stdClass that represents dynamic data needed for frontend interaction with javascript and this form
		$js_params = new stdClass();
		$js_params->version = DILLER_LOYALTY_VERSION;
		$js_params->restUrl = rest_url( Diller_Loyalty_Configs::REST_ENDPOINT_BASE_URL);
		$js_params->restNonce = wp_create_nonce('wp_rest');
		$js_params->pluginUrl = DILLER_LOYALTY_URL;

        // Date fields - configurations
		$js_params->calendar = new stdClass();
		$js_params->calendar->placeholder = DillerLoyalty()->get_store()->get_default_date_placeholder();
		$js_params->calendar->locale = Diller_Loyalty_Helpers::get_language_file_name('flatpickr');

		// Form fields
		$js_params->form = new stdClass();
		$js_params->form->minEnrollmentAge = DillerLoyalty()->get_store()->get_min_enrollment_age();
		$js_params->form->enableEmailLookup = true;
		$js_params->form->enablePhoneLookup = DillerLoyalty()->get_store()->get_enable_phone_number_lookup();
		$js_params->form->id = $this->get_id();
		$js_params->form->validationRules = new stdClass();

        // Texts for custom validation rules defined in diller-loyalty-public.js
		$js_params->form->validationRulesTexts = new stdClass();
        $js_params->form->validationRulesTexts->lessthandate = esc_html__("Date value cannot be higher than today's date", "diller-loyalty");
        $js_params->form->validationRulesTexts->phonenumber = esc_html__("You must enter a valid mobile number", "diller-loyalty");
        $js_params->form->validationRulesTexts->validdate = esc_html__( "You must enter a valid date value", 'diller-loyalty' );
		$js_params->form->validationRulesTexts->agehigherthan = sprintf(
		    /* translators: 1: Minimum age for enrolling the Loyalty Program */
			esc_html__( 'You must be at least %1$s years old to join the Loyalty Program', 'diller-loyalty' ),
			DillerLoyalty()->get_store()->get_min_enrollment_age()
		);


		$js_params->texts = new stdClass();
        $js_params->texts->emailAlreadyExists = esc_html__( "This email is already in use.", 'diller-loyalty' );
		$js_params->texts->loginToMyAccount = is_user_logged_in() ? '' : sprintf(
		    /* translators: 1: Loyalty Program, Enrollment Form URL (Inside My Account), 2: closing url */
            esc_html__('Go to %1$sMy Account%2$s page instead to join the Loyalty Program', 'diller-loyalty'),
            '<a href="' . esc_url(trailingslashit(wc_get_page_permalink( 'myaccount' )) . Diller_Loyalty_Configs::LOYALTY_PROFILE_ENDPOINT) . '">',
			'</a>'
        );

        // OTP texts
		$js_params->texts->verifyOtpCode = esc_html__('Validate code and continue', 'diller-loyalty' );
		$js_params->texts->otpCode = esc_html__('SMS Code', 'diller-loyalty' );
		$js_params->texts->sendVerificationCode = esc_html__('Send verification code', 'diller-loyalty' );
		$js_params->texts->resendVerificationCode = esc_html__('Didn\'t get the code? Resend it >', 'diller-loyalty' );

        // Fields
		$js_params->form->fields = new stdClass();
		$js_params->form->inline_scripts = array();

		foreach ($this->fields as $field ) {
            // Creates a dynamic property in stdClass(), named after the field name.
            // This dynamic prop will hold the id of the field, which is random and uniquely generated
			$js_params->form->fields->{$field->get_name()} = new stdClass();
			$js_params->form->fields->{$field->get_name()}->id = $field->get_id();
		}

		// jQuery Validate - Rules generator.
        // Output format example: { "field_name": { required: true, minlength: 2, maxlength: 255 } }
		foreach ( $this->get_validation_rules() as $field_name => $field_rules ) {
			if(!empty($field_rules)){
				$js_params->form->validationRules->{$field_name} = $field_rules;
			}
		}

		// Add form re-submission prevention script
		if($this->was_submitted() && $this->validate_request()){
			$js_params->form->inline_scripts[] = $this->add_prevent_form_resubmission_script();
		}

		return $js_params;
	}

	/**
	 * Prevents form re-submission by replacing state via JS.
     * This only works if JS is enabled. For more complex scenario consider implementing PRG pattern (Post->Redirect->Get)
     *
     * @link https://www.phptutorial.net/php-tutorial/php-prg/<url>
	 */
    protected function add_prevent_form_resubmission_script(){
        return 'if(window.history.replaceState){ window.history.replaceState( null, null, window.location.href ); }'.PHP_EOL;
    }
}
