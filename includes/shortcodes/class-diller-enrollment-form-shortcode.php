<?php

class Diller_Enrollment_Form_Shortcode {

	public $short_code_name = '';
	public $form = null;

	public function __construct() {
		$this->short_code_name = Diller_Loyalty_Configs::LOYALTY_ENROLLMENT_FORM_SHORTCODE;
		$this->form = new Diller_Member_Enrollment_Form("dillerEnrollmentForm");
	}

	public function render($atts) {
		$params = shortcode_atts( array(
			'title' => '',
		), $atts );

		if(DillerLoyalty()->user_has_joined()):

			// Display message to go to my account, if user is logged in and enrolled
			echo '<p>';
			printf(
				/* translators: 1: Line break 2: Loyalty Program, Enrollment Form URL (Inside My Account), 3: closing url */
				esc_html__('You have already joined our Loyalty Program.%1$sGo to %2$sMy Account%3$s page, if you wish to change your preferences and view your benefits.', 'diller-loyalty'),
				'<br/>',
				'<a href="' . esc_url(trailingslashit(wc_get_page_permalink( 'myaccount' )) . Diller_Loyalty_Configs::LOYALTY_PROFILE_ENDPOINT) . '">',
				'</a>'
			);
			echo '</p>';

		else:

			// Setup enrollment form
			$this->form->set_title($params["title"]);
			$this->form->build_fields();

			if ($this->form->was_submitted() && $this->form->validate_request()):
				$this->form->save()
					? DillerLoyalty()->display_notices($this->form->get_id(), false)
					: $this->form->render();
			else:
				$this->form->render();
			endif;

		endif;
    }

	public function enqueue_scripts() {
		$this->form->enqueue_scripts();
	}

	public function enqueue_styles() {
		$this->form->enqueue_styles();
	}
}