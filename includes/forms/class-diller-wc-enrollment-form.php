<?php

class Diller_WC_Enrollment_Form extends Diller_Member_Enrollment_Form {
    
	public function __construct($id){
		parent::__construct($id);
	}

	public function build_fields(){
		$marketing_sms_consent_text = __('SMS: I want to receive benefits, offers and other marketing electronically in connection with the customer club via SMS.', 'diller-loyalty');
		$marketing_email_consent_text = __('E-mail: I want to receive benefits, offers and other marketing electronically in connection with the customer club by E-mail.', 'diller-loyalty');

		// Add extra fields here as needed in the following format: priority => Field_Object
		$fields = array(
			9998 => new Diller_Checkbox_Field("marketing_sms_consent_accepted", $marketing_sms_consent_text, array(), array("default" => "Yes")),
			9999 => new Diller_Checkbox_Field("marketing_email_consent_accepted", $marketing_email_consent_text, array(), array("default" => "Yes"))
		);

		//Note 2 self: array_merge() resets the key values to start from index 0
		$merged_fields = $fields + $this->get_default_fields();

		// Sort field by priority
		ksort($merged_fields, SORT_NUMERIC);

		// Add fields to the form
		array_map([$this, 'add_field'], $merged_fields);

        $submit_btn_text = DillerLoyalty()->user_has_joined()
            ? esc_html__('Save my preferences', 'diller-loyalty')
            : esc_html__('Subscribe', 'diller-loyalty');

		$this->add_element(new Diller_Submit_Button("subscribe", $submit_btn_text, array(
			"class" => "diller-button diller-button--primary diller-button--round"
		)));

		$this->register_inline_scripts();
	}

	public function render() {
		parent::render();

        // If current user has enrolled, we add this modal to ask for confirmation in case the user wants to unsubscribe
        if(DillerLoyalty()->user_has_joined()):
            $modal_id = "{$this->get_id()}-modal";
            ?>
            <div id="<?php echo esc_attr($modal_id); ?>" class="diller-modal">
                <div class="diller-modal-window diller-modal-window--small">
                    <div class="diller-modal-header">
                        <h4 class="diller-modal-title"><?php echo esc_html__('Loyalty Program','diller-loyalty'); ?></h4>
                        <button type="button" class="diller-modal-close" data-dismiss="<?php echo esc_attr($modal_id); ?>" aria-label="<?php echo esc_html__('Close','diller-loyalty'); ?>">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="diller-modal-body"></div>
                    <div class="diller-modal-footer"></div>
                </div>
            </div>
            <?php
        endif;
	}

	public function save(){

		//$request_data = $this->get_request_data();
		$request_data = $this->get_submitted_data();
		$follower = (DillerLoyalty()->user_has_joined()) ? DillerLoyalty()->get_current_follower() : new Diller_Loyalty_Follower();

		// Handle unsubscribes from My Account => Loyalty Program => Unchecked "I want to join Store XYZ follower club"
		if( DillerLoyalty()->user_has_joined() && !DillerLoyalty()->user_has_unsubscribed()
		    && $follower->get_membership_consent_accepted() === 'Yes'
		    && ( !isset($request_data["membership_consent_accepted"]) || sanitize_text_field($request_data["membership_consent_accepted"]) != 'Yes')){

			$result = DillerLoyalty()->get_api()->unsubscribe_follower($follower);
			if(!is_wp_error($result)){
				// Success
				$follower->set_membership_consent_accepted('No')
				         ->set_purchase_history_consent_accepted( 'No')
				         ->set_marketing_email_consent_accepted( 'No')
				         ->set_marketing_sms_consent_accepted( 'No')
				         ->save();

				DillerLoyalty()->add_notice($this->id,'success', __('You are unsubscribed from the Loyalty Program. Please allow 10 days for us to come to terms with this change and update our system.', 'diller-loyalty'));

                return $GLOBALS['diller_hide_profile_form'] = true;
			}

			//..else something went wrong
			DillerLoyalty()->add_notice($this->id,'error', __('Ooops! An error has occurred while trying to unsubscribe you from the Loyalty Program. Please try again later. If the problem persists please contact us.', 'diller-loyalty'));
			return false;
		}

		// Handle normal updates
		$follower->set_first_name($request_data["first_name"])
		         ->set_last_name($request_data["last_name"])
		         ->set_full_phone_number($request_data["phone_country_code"], $request_data["phone_number"])
		         ->set_email($request_data["email"])
		         ->set_address($request_data["address"])
		         ->set_country($request_data["country"])
		         ->set_gender($request_data["gender"])
		         ->set_birth_date($request_data["birth_date"])
		         ->set_postal_city($request_data["postal_city"])
		         ->set_postal_code($request_data["postal_code"])
		         ->set_membership_consent_accepted($request_data["membership_consent_accepted"] ?? 'No')
		         ->set_purchase_history_consent_accepted( $request_data["purchase_history_consent_accepted"] ?? 'No')
		         ->set_marketing_email_consent_accepted( $request_data["marketing_email_consent_accepted"] ?? 'No')
		         ->set_marketing_sms_consent_accepted( $request_data["marketing_sms_consent_accepted"] ?? 'No')
		         ->set_department_ids($request_data["department_ids"] ?? array())
		         ->set_segments($request_data["segments"] ?? array());

		if(!DillerLoyalty()->user_has_joined() && !DillerLoyalty()->user_has_unsubscribed()){
			// New enrollment
			return parent::save();
		}

        // Set the flag here, as update_follower will reset it
		$was_unsubscribed = DillerLoyalty()->user_has_unsubscribed();

		//...else Update
		$result = DillerLoyalty()->get_api()->update_follower($follower);
		if(!is_wp_error($result)){
			// All is good...
			$message = __('Your loyalty program preferences were successfully updated', 'diller-loyalty');

			// If unsubscribed from before, greet the user
			$message = $was_unsubscribed ?  __('Welcome back!', 'diller-loyalty') . ' ' . $message : $message;

			DillerLoyalty()->add_notice( $this->id,'success', $message);

			return true;
		}
		else{
			$error_message = !empty($result->get_error_message())
				? $result->get_error_message()
				: __('Ooops! An error has occurred while we tried to update your loyalty program preferences. Please try again later. If the problem persists please contact us.', 'diller-loyalty');

			DillerLoyalty()->add_notice($this->id,'error', $error_message);
			return false;
		}
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

	/**
	 * Add JS inline scripts for this form.
	 *
	 * @uses add_inline_scripts
	 */
	function add_inline_scripts() {

		parent::add_inline_scripts();

        // Unsubscribe Modal Configs
        $modal_params = new stdClass();
		$modal_params->id = "{$this->get_id()}-modal";
		$modal_params->title = __('Loyalty Program - Unsubscribe','diller-loyalty');
		$modal_params->body = __('Are you sure you want to leave the Loyalty Program ? You will miss out on great our special offers and discounts.','diller-loyalty');
		$modal_params->trigger = $this->get_field("membership_consent_accepted")->get_id();
        $modal_params->buttons = array();

        // Cancel Button
		$cancel_button = new stdClass();
		$cancel_button->text = __('Stay','diller-loyalty');
		$cancel_button->class = 'diller-button diller-button--secondary ';
		$cancel_button->click_callback = 'window.Diller_Loyalty.handleUnsubscribeCancelBtnClick(event);'; //This function needs to exist in the public js file.
		$modal_params->buttons[] = $cancel_button;

		// Confirm Button
		$confirm_button = new stdClass();
		$confirm_button->text = __('Leave','diller-loyalty');
		$confirm_button->class = 'diller-button diller-button--primary';
		$confirm_button->click_callback = 'window.Diller_Loyalty.handleUnsubscribeBtnClick(event);'; //This function needs to exist in the public js file.
		$modal_params->buttons[] = $confirm_button;

		$this->inline_scripts[] = "window.Diller_Loyalty.modal = " . json_encode($modal_params) .";";
	}


	// Overrides the parents function
	public function get_inline_javascript_params() {
		return parent::get_inline_javascript_params();
	}
}
