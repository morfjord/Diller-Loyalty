<?php

class Diller_Refer_Friend_Form_Shortcode {

	public $short_code_name = '';
	public $form = null;

	public function __construct() {
		$this->short_code_name = Diller_Loyalty_Configs::LOYALTY_FRIEND_REFERRAL_FORM_SHORTCODE;
		$this->form = new Diller_Refer_Friend_Form("dillerReferFriendForm");
	}

	public function render($atts) {
		$params = shortcode_atts( array(
			'title' => __('Refer a friend', 'diller-loyalty'),
		), $atts );

		$this->form->set_title($params["title"]);
		$this->form->build_fields();

		if ($this->form->was_submitted() && $this->form->validate_request()):
			$this->form->save()
				? DillerLoyalty()->display_notices($this->form->get_id(), false)
				: $this->form->render();
		else:
			$this->form->render();
		endif;
	}

	public function enqueue_scripts() {
		$this->form->enqueue_scripts();
	}

	public function enqueue_styles() {
		$this->form->enqueue_styles();
	}
}