<?php


class Diller_Loyalty_Store {

	protected $store_pin;
	protected $store_id;
	protected $store_dimensions;
	protected $store_data = array();
	protected $store_language;
	protected $store_styles = array();
	protected $departments = array();
	protected $segments = array();
	protected $membership_levels = array();
	protected $point_system_enabled = false;
	protected $refer_friend_enabled = false;
	protected $refer_friend_points = 0;
	protected $currency_to_points_ratio = 0;
	protected $stamps_enabled = false;
	protected $phone_intl_plugin_input_enabled = true;
	protected $phone_preferred_countries = array();
	protected $phone_countries = array();
	protected $enable_number_lookup = true;
	protected $default_date_placeholder = 'DD.MM.YYYY';
	protected $phone_country_option = 'all';
	protected $phone_default_country_code = '+47';
	protected $default_postal_code_format = '';
	protected $min_enrollment_age = 15;
	protected $join_checkboxes_placement = '';
	protected $test_mode_enabled = false;

	/**
	 * @return string
	 */
	public function get_store_name(){
		return $this->store_data['store_name'];
	}

	/**
	 * @return int
	 */
	public function get_enrollment_form_page_id(){
		return (int)$this->store_data['enrollment_form_page_id'] ?? 0;
	}

	public function set_enrollment_form_page_id(int $value){
		$this->store_data['enrollment_form_page_id'] = $value;
	}

	/**
	 * @return bool
	 */
	public function get_enable_phone_number_lookup(){
		return $this->enable_number_lookup ?? true;
	}

	/**
	 * Language value comes originally as int, but this function returns the matching ISO code for the language (eg. en, no, sv, etc)
	 *
	 * @return string
	 */
	public function get_store_language(){
		return Diller_Languages::get_letter_iso_code((int)$this->store_language);
	}

	public function get_privacy_policy_url(){
		return $this->store_data["privacy_policy_url"] ?? '';
	}

	/**
	 * @return string
	 */
	public function get_store_pin() {
		return $this->store_pin;
	}

	/**
	 * @return string
	 */
	public function get_store_id(){
		return $this->store_id;
	}

	/**
	 * Returns an array with the segments/dimensions setup for the store.
	 * This represents the dynamic fields that can be created and added to the enrollment form
	 *
	 * @return Diller_Loyalty_Store_Segment[]
	 */
	public function get_store_segments() {
		return $this->segments;
	}

	/**
	 * Returns an array with the membership levels setup for the store
	 *
	 * @return Diller_Loyalty_Membership_Level[]
	 */
	public function get_store_membership_levels() {
		return $this->membership_levels;
	}

	/**
	 * Returns an array of departments for the store
	 *
	 * @return Diller_Loyalty_Store_Department[]
	 */
	public function get_store_departments() {
		return $this->departments;
	}

	/**
	 * @return bool
	 */
	public function get_point_system_enabled() {
		return $this->point_system_enabled;
	}

	/**
	 * @return bool
	 */
	public function get_refer_friend_enabled() {
		return $this->refer_friend_enabled;
	}

	/**
	 * @return bool
	 */
	public function get_phone_intl_plugin_input_enabled() {
		return $this->phone_intl_plugin_input_enabled;
	}

	/**
	 * @return array
	 */
	public function get_phone_preferred_countries() {
		return $this->phone_preferred_countries;
	}

	/**
	 * @return array
	 */
	public function get_phone_countries() {
		return $this->phone_countries;
	}

	/**
	 * Returns all|all_except|specific
	 * @return string
	 */
	public function get_phone_country_option() : string {
		return $this->phone_country_option;
	}

	/**
	 * @return int
	 */
	public function get_refer_friend_points() {
		return $this->refer_friend_points;
	}

	public function get_currency_to_points_ratio(): int {
		return $this->currency_to_points_ratio;
	}

	public function get_stamps_enabled(): bool {
		return $this->stamps_enabled;
	}

	public function get_default_date_placeholder(): string {
		return $this->default_date_placeholder;
	}

	public function get_phone_default_country_code(): string {
		return $this->phone_default_country_code;
	}

	public function get_default_postal_code_format(): string {
		return $this->default_postal_code_format;
	}

	public function get_min_enrollment_age(): int {
		return $this->min_enrollment_age;
	}

	public function get_join_checkboxes_placement(): string {
		return $this->join_checkboxes_placement;
	}

	public function get_test_mode_enabled(): bool {
		return $this->test_mode_enabled;
	}

	/**
	 * Returns all the store configs, merged with the default configs array. If a config is missing, it will use the value from the default array.
	 *
	 * @param bool $force If true, it will fetch a fresh version the the DB, otherwise uses the data in memory
	 *
	 * @return array Array with all the store configs
	 */
	public function get_configs( $force = false ) {
		if ( !empty( $this->store_data ) && !$force ) {
			return array_merge($this->get_default_configs(), $this->store_data);
		}
		else {
			$this->store_data = get_option( '_diller_store_configs', array() );
			return array_merge($this->get_default_configs(), $this->store_data);
		}
	}


	/**
	 * Save store configs/preferences fetched from Diller Api, into wp_options, with meta_key "_diller_store_configs"
	 *
	 * @param array $data
	 */
	public function save_configs( $data = array() ) {
		update_option( '_diller_store_configs', $data );
		$this->store_data = $data;

		$this->load_configs();
	}

	public function get_default_configs() {
		return array(
			"privacy_policy_url" => '',
			"enrollment_form_page_id" => 0,
			"test_mode_enabled" => false,
			"enable_recaptcha" => true,
			"stamps_enabled" => false,
			"default_date_format" => "DD.MM.YYYY",
			"default_date_placeholder" => "dd.mm.åååå",
			"default_postal_code_format" => "",
			"min_enrollment_age" => 15,
			"join_checkboxes_placement" => "billing",
			"phone" => array(
				"enable_number_lookup" => true,
				"country_option" => "all", // Values can be all|all_except|specific
				"countries" => array("no", "se", "dk", "fi", "de", "uk"),
				"default_country_code" => $this->get_phone_default_country_code(),
				"intl_tel_input_plugin_enabled" => true,
				"preferred_countries" => array("no", "se", "dk", "fi"), // Will make these to show up first in the phone input field
			)
		);
	}

	public function delete_configs() {
		$this->store_data = array();
		delete_option( '_diller_store_configs' );
		return true;
	}

	/**
	 * Returns a list of :root CSS variables fetched from Diller, that will be used to override certain styles. Eg. colors
	 *
	 * @return string
	 */
	public function get_store_css_styles() {
		$selector = ':root';
		$lines = array();

		// Eg. array( "membership_progress_bar" => array(), "buttons" => array() )
		foreach ($this->store_styles as $style_group_name => $style_group) {

			foreach ($style_group as $style => $color) {
				//Eg: $color => array( "background_color" => '#fff', "background_color_hover" => '#fff' )
				$var_name = str_replace('_', '-', $style);
				$lines[] = sprintf('--diller-%1$s-%2$s: %3$s;', $style_group_name, $var_name, $color); //Eg. --diller-point-bar-color: #90b3c7;
			}
		}

		return sprintf('%1$s{%2$s}', $selector, implode(' ', $lines));
	}


	/**
	 * Diller_Loyalty_Store constructor.
	 */
	public function __construct() {
		$this->load_configs();
	}

	private function load_configs() {
		$this->store_data = $this->get_configs();
		$default_data = $this->get_default_configs();

		

		if(isset($this->store_data)):
			$this->store_id = $this->store_data["store_id"] ?? '';
			$this->store_pin = $this->store_data["store_pin"] ?? '';
			$this->store_language = $this->store_data["wordpress_language"] ?? Diller_Languages::English;
			$this->point_system_enabled = ($this->store_data["point_system"] ?? 0) == 1;
			$this->stamps_enabled = $this->store_data["stamps_enabled"] ?? false;
			$this->refer_friend_enabled = (bool)($this->store_data["plugin_refer_friend"] ?? 0);
			$this->refer_friend_points = (int)($this->store_data["refer_a_friend_point"] ?? 0);
			$this->currency_to_points_ratio = (int)($this->store_data["dollar_is_x_point"] ?? 0);
			$this->store_styles = $this->store_data["store_styles"] ?? array();

			$this->store_data["phone"]  = $this->store_data["phone"] ?? $default_data["phone"];
			$this->phone_country_option = $this->store_data["phone"]["country_option"] ;
			$this->phone_countries      = $this->store_data["phone"]["countries"];
			$this->phone_default_country_code = $this->store_data["phone"]["default_country_code"];
			$this->phone_preferred_countries = $this->store_data["phone"]["preferred_countries"];
			$this->phone_intl_plugin_input_enabled = $this->store_data["phone"]["intl_tel_input_plugin_enabled"];
			$this->enable_number_lookup = $this->store_data["phone"]["enable_number_lookup"];

			$this->default_date_placeholder = $this->store_data["default_date_placeholder"] ?? $default_data["default_date_placeholder"] ?? '';
			$this->default_postal_code_format = $this->store_data["default_postal_code_format"] ?? $default_data["default_postal_code_format"] ?? '';
			$this->min_enrollment_age = $this->store_data["min_enrollment_age"] ?? $default_data["min_enrollment_age"] ?? '';
			$this->join_checkboxes_placement = $this->store_data["join_checkboxes_placement"] ?? $default_data["join_checkboxes_placement"] ?? '';
			$this->test_mode_enabled = $this->store_data["test_mode_enabled"] ?? $default_data["test_mode_enabled"] ?? false;

			

			

			// Store departments
			$department_details = $this->store_data['department_details'] ?? null;
			if($department_details){
				$department_id = $department_details["department_id"];
				$department_name = $department_details["department_name"];
				$department_type = $department_details["department_type"];
				$department_visible = $department_details["is_visible_follower"] == 1;
				$department_values = $department_details["department_values"];

				if($department_values) {
					$depart_values = array();

					//Ensure department values are sorted
					$sort_order = array_column( $department_values, 'order_number' );
					array_multisort( $department_values, SORT_ASC, $sort_order );

					foreach ( $department_values as $value_key => $values) {
						$depart_values[] = array(
							
							"id"         => $values["department_value_id2"],
							"value"      => $values["department_values"],
							"sort_order" => $values["order_number"]
						);
					}

					$this->departments = new Diller_Loyalty_Store_Department( $department_id, $department_name, $department_type, $department_visible, $depart_values );
				}
			}

			// Store segments
			$segment_details = $this->store_data['segment_details'] ?? array();
			if(is_array($segment_details) && sizeof($segment_details) > 0) {
				foreach ( $segment_details as $seg_key => $segment ) {
					$segment_id       = $segment["segment_id"];
					$segment_name     = $segment["segment_name"];
					$segment_type     = $segment["segment_type"];
					$segment_visible  = $segment["is_visible_follower"] == 1;
					$segment_required = $segment["is_required"] == 1;
					$segment_values   = $segment["segment_values"] ?? array();
					$seg_values = array();

					if ( sizeof( $segment_values ) > 0 ) {
						foreach ( $segment_values as $value_key => $values ) {
							$seg_values[] = array(
								"id"    => $values["segment_value_id"],
								"value" => $values["segment_values"]
							);
						}
					}

					$this->segments[] = new Diller_Loyalty_Store_Segment( $segment_id, $segment_name, $segment_type, $segment_visible, $segment_required, $seg_values );
				}
			}

			// Membership levels
			$membership_levels = $this->store_data['membership_level_details'] ?? array();
			if(sizeof($membership_levels) > 0) {
				foreach ( $membership_levels as $key => $membership_level ) {
					$this->membership_levels[] = new Diller_Loyalty_Membership_Level($membership_level["membership_level_id"], $membership_level["membership_level_title"], $membership_level["membership_level_points"]);
				}
			}
		endif;
	}
}

class Diller_Loyalty_Store_Department {

	protected $id;
	protected $name;
	protected $field_type;
	protected $sort_order;
	protected $values;
	protected $visible = false;

	/**
	 * @return string
	 */
	public function get_id(){
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function get_name(){
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function get_field_type(){
		return $this->field_type;
	}

	/**
	 * @return string
	 */
	public function get_is_visible(){
		return $this->visible;
	}

	/**
	 * @return array
	 */
	public function get_values(){
		return $this->values;
	}

	/**
	 * Unique id to use as id attribute in input fields.
	 * The returned value is the concatenation of field type and field id. Eg. 4#520
	 *
	 * @return string
	 */
	public function get_field_id_attr(){
		return sprintf("%d#%s", $this->field_type, $this->id); //eg. 4#520
	}

	public function __construct($id, $name, $field_type, $visible, $values) {
		$this->name = $name;
		$this->id = $id;
		$this->field_type = $field_type;
		$this->visible = $visible;
		$this->values = $values;
	}
}


class Diller_Loyalty_Store_Segment extends Diller_Loyalty_Store_Department {

	protected $is_required = false;

	/**
	 * @return bool
	 */
	public function get_is_required(){
		return $this->is_required;
	}

	public function __construct($id, $name, $field_type, $visible, $required, $values) {
		parent::__construct($id, $name, $field_type, $visible, $values);

		$this->values = $values;
		$this->is_required = $required;
	}
}



class Diller_Loyalty_Membership_Level {

	protected $id;
	protected $name;
	protected $points;

	/**
	 * @return string
	 */
	public function get_id() : int{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function get_name() : string{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function get_points() : int{
		return $this->points;
	}

	public function __construct(int $id, string $name, int $points) {
		$this->name = $name;
		$this->id = $id;
		$this->points = $points;
	}
}
