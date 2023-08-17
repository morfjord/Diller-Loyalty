<?php

class Diller_Refer_Friend_Form extends Diller_Form {
    use Diller_Loyalty_Form_Scripts;
    
	public function __construct($id)
	{
		parent::__construct($id);

		$this->set_title(__('Refer a friend', 'diller-loyalty'));
	}

	public function build_fields()
	{
		//Add default fields to the form
		$this->add_field(new Diller_Text_Field("first_name", __('Your friend\'s first name', 'diller-loyalty'), array(), array(
			'validation_rules' => array(
				'required' => true,
				'minlength' => 2,
				'maxlength' => 255
			)
		)));
		$this->add_field(new Diller_Text_Field("last_name", __('Your friend\'s last name', 'diller-loyalty'), array(), array(
			'validation_rules' => array(
				'required' => true,
				'minlength' => 2,
				'maxlength' => 255
			)
		)));
        $this->add_field(new Diller_Email_Field("email", __('Your friend\'s email', 'diller-loyalty'), array(), array(
			'validation_rules' => array(
				'required' => true,
				'email' => true
			)
		)));
		$this->add_element(new Diller_Submit_Button("send_invite", __('Send invitation', 'diller-loyalty'), array(
            "class" => "diller-button diller-button--primary diller-button--round"
        )));

		$this->register_inline_scripts();
	}

	public function render(){
		if ( !empty( $this->title ) ) {
			echo '<h2 class="diller-heading__title">' . esc_html( $this->title ) . '</h2>';
		}

		echo '<p class="diller-heading__text">';
        echo sprintf(
                /*translators: %s is the number of points.*/
                esc_html__("Earn %s points for each friend you refer to sign up for our Loyalty Program", "diller-loyalty"),
                DillerLoyalty()->get_store()->get_refer_friend_points()
            );
        echo '</p>';

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

		echo '<div class="diller-invitation-list">';
		$this->display_invite_list();
		echo '</div>';
	}

	public function save(){
        $form_values = $this->get_submitted_data();
		// $form_values = $this->get_request_data();
		$follower = DillerLoyalty()->get_current_follower();

		$result = DillerLoyalty()->get_api()->invite_friend($follower, $form_values["first_name"], $form_values["last_name"], $form_values["email"]);
		if(!$result){
			DillerLoyalty()->add_notice( $this->id,'error', __('There is already a member in our loyalty club with this email', 'diller-loyalty'));
			return false;
		}

		DillerLoyalty()->add_notice( $this->id,'success', __('Thank you! You friend was successfully invited to join the loyalty program!', 'diller-loyalty'));

		return true;
	}

	function display_invite_list() {
		$current_follower = DillerLoyalty()->get_current_follower();
		$result = DillerLoyalty()->get_api()->get_invited_friends_list($current_follower);
		if(is_wp_error($result)){
			DillerLoyalty()->get_logger()->error("Could not get invited_friends_list for follower.", $result);
			return false;
		}

		if(sizeof($result) > 0): ?>
			<table class="diller-table">
				<thead>
					<tr>
						<td><?php echo esc_html__('Your friend\'s name', 'diller-loyalty'); ?></td>
						<td><?php echo esc_html__('Your friend\'s email', 'diller-loyalty'); ?></td>
						<td><?php echo esc_html__('Invite status', 'diller-loyalty'); ?></td>
						<?php if(DillerLoyalty()->get_store()->get_point_system_enabled()): ?>
							<td><?php echo esc_html__('Points earned', 'diller-loyalty'); ?></td>
						<?php endif; ?>
					</tr>
				</thead>
			<?php foreach ($result as $key => $friend) : ?>
				<tr>
					<td><?php echo esc_html($friend->get_full_name()); ?></td>
					<td><?php echo esc_html($friend->get_email()); ?></td>
					<td>
						<?php if($friend->get_status() == '1'): ?>
							<span class="diller--success"><?php echo esc_html__('Registration completed', 'diller-loyalty'); ?></span>
						<?php else: ?>
							<span class="diller--warning"><?php echo esc_html__('Awaiting registration', 'diller-loyalty'); ?></span>
						<?php endif; ?>
					</td>
					<?php if(DillerLoyalty()->get_store()->get_point_system_enabled()): ?>
						<td>
							<?php echo esc_html($friend->get_status() == '1' ? DillerLoyalty()->get_store()->get_refer_friend_points() : '0'); ?>
							<?php echo esc_html__('points earned', 'diller-loyalty'); ?>
						</td>
					<?php endif; ?>
				</tr>
			<?php endforeach; ?>
			</table>
		<?php endif;
	}
}
