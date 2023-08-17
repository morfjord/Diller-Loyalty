<?php


class Diller_Loyalty_Follower {

	protected $phone_country_code = '';
	protected $phone_number = '';
	protected $full_phone_number = '';
	protected $email = '';
	protected $first_name = '';
	protected $last_name = '';
	protected $gender = '';
	protected $address = '';
	protected $postal_code = '';
	protected $postal_city = '';
	protected $country = '';
	protected $birth_date = '';
	protected $purchase_history_consent_accepted = 'No';
	protected $membership_consent_accepted = 'No';
	protected $marketing_email_consent_accepted = 'No';
	protected $marketing_sms_consent_accepted = 'No';
	protected $enrolled_at_checkout = false;
	protected $password = '';
	protected $diller_referral_id = '';
	protected $diller_id = 0;
	protected $wp_user_id = 0;
	protected $department_ids = array();
	protected $segments = array();
	protected $status = '';
	protected $points = 0;
	protected $remaining_points = 0;
	protected $current_membership_level = '';
	protected $next_membership_level = '';
	protected $next_membership_level_required_points = 0;
	protected $total_earned_points = 0;
	protected $points_expire_details  = '';
	protected $membership_level_created_date = '';
	protected $membership_level_expire_details = '';
	protected $force_membership_consent_acceptance = false;
	protected $membership_consent_accepted_date = '';
	protected $exists_diller = false;


	/**
	 * @return int
	 */
	public function get_wp_user_id(): int {
		return $this->wp_user_id;
	}

	/**
	 * @param int $wp_user_id
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_wp_user_id( int $wp_user_id ): Diller_Loyalty_Follower {
		$this->wp_user_id = $wp_user_id;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function get_is_wordpress_user(): bool {
		return (int)$this->wp_user_id > 0;
	}

	/**
	 * @return bool
	 */
	public function get_is_diller_member(): bool {
		return (int)$this->diller_id > 0;
	}

	/**
	/**
	 * @return array
	 */
	public function get_department_ids(): array {
		return $this->department_ids;
	}

	/**
	 * @param string $department_ids
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_department_ids( array $department_ids ): Diller_Loyalty_Follower {
		$this->department_ids = $department_ids;

		return $this;
	}

	/**
	/**
	 * @return array
	 */
	public function get_segments(): array {
		return $this->segments;
	}

	/**
	 * @param array $segments
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_segments( array $segments ): Diller_Loyalty_Follower {
		$this->segments = $segments;

		return $this;
	}

	/**
	/**
	 * @return string
	 */
	public function get_diller_referral_id(): string {
		return $this->diller_referral_id;
	}

	/**
	 * @param string $diller_referral_id
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_diller_referral_id( string $diller_referral_id ): Diller_Loyalty_Follower {
		$this->diller_referral_id = $diller_referral_id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_diller_id(): string {
		return $this->diller_id;
	}

	/**
	 * @param string $diller_id
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_diller_id( string $diller_id ): Diller_Loyalty_Follower {
		$this->diller_id = $diller_id;

		return $this;
	}


	/**
	 * @return string
	 */
	public function get_status(): string {
		return $this->status;
	}


	/**
	 * @param string $status
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_status( string $status ): Diller_Loyalty_Follower {
		$this->status = $status;

		return $this;
	}


	/**
	 * @return string
	 */
	public function get_phone_country_code(): string {
		return $this->phone_country_code;
	}

	/**
	 * @param string $phone_country_code
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_phone_country_code( string $phone_country_code ): Diller_Loyalty_Follower {
		$this->phone_country_code = $phone_country_code;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_phone_number(): string {
		return str_replace($this->phone_country_code, '',  $this->phone_number);
	}

	/**
	 * @param string $phone_number
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_phone_number( string $phone_number ): Diller_Loyalty_Follower {
		$this->phone_number = $phone_number;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_full_phone_number(): string {
		return $this->phone_country_code . $this->phone_number;
	}

	/**
	 * @param string $full_phone_number
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_full_phone_number( string $phone_country_code, string $phone_number ): Diller_Loyalty_Follower {
		$this->phone_country_code = $phone_country_code;
		$this->phone_number = $phone_number;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_email(): string {
		return $this->email;
	}

	/**
	 * @param string $email
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_email( string $email ): Diller_Loyalty_Follower {
		$this->email = $email;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_first_name(): string {
		return $this->first_name;
	}

	/**
	 * @param string $first_name
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_first_name( string $first_name ): Diller_Loyalty_Follower {
		$this->first_name = $first_name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_last_name(): string {
		return $this->last_name;
	}

	/**
	 * @return string
	 */
	public function get_full_name(): string {
		return $this->first_name. ' ' .$this->last_name;
	}

	/**
	 * @param string $last_name
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_last_name( string $last_name ): Diller_Loyalty_Follower {
		$this->last_name = $last_name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_gender(): string {
		return $this->gender;
	}

	/**
	 * @param string $gender
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_gender( string $gender ): Diller_Loyalty_Follower {
		$this->gender = $gender;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_address(): string {
		return $this->address;
	}

	/**
	 * @param string $address
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_address( string $address ): Diller_Loyalty_Follower {
		$this->address = $address;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_postal_code(): string {
		return $this->postal_code;
	}

	/**
	 * @param string $postal_code
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_postal_code( string $postal_code ): Diller_Loyalty_Follower {
		$this->postal_code = $postal_code;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_postal_city(): string {
		return $this->postal_city;
	}

	/**
	 * @param string $postal_city
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_postal_city( string $postal_city ): Diller_Loyalty_Follower {
		$this->postal_city = $postal_city;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_country(): string {
		return $this->country;
	}

	/**
	 * @param string $country
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_country( string $country ): Diller_Loyalty_Follower {
		$this->country = $country;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_birth_date(): string {
		return $this->birth_date;
	}

	/**
	 * @param string $birth_date
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_birth_date( string $birth_date ): Diller_Loyalty_Follower {
		$this->birth_date = $birth_date;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_purchase_history_consent_accepted(): string {
		return $this->purchase_history_consent_accepted;
	}

	/**
	 * @param string $value
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_purchase_history_consent_accepted( string $value ): Diller_Loyalty_Follower {
		$this->purchase_history_consent_accepted = Diller_Loyalty_Helpers::convert_bool_to_yes_no($value);

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_marketing_email_consent_accepted(): string {
		return $this->marketing_email_consent_accepted;
	}

	/**
	 * @param string $value
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_marketing_email_consent_accepted( string $value ): Diller_Loyalty_Follower {
		$this->marketing_email_consent_accepted = Diller_Loyalty_Helpers::convert_bool_to_yes_no($value);

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_marketing_sms_consent_accepted(): string {
		return $this->marketing_sms_consent_accepted;
	}

	/**
	 * @param string $value
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_marketing_sms_consent_accepted( string $value ): Diller_Loyalty_Follower {
		$this->marketing_sms_consent_accepted = Diller_Loyalty_Helpers::convert_bool_to_yes_no($value);

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_membership_consent_accepted(): string {
		return $this->membership_consent_accepted;
	}

	/**
	 * @param string $value
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_membership_consent_accepted( string $value ): Diller_Loyalty_Follower {
		$this->membership_consent_accepted = Diller_Loyalty_Helpers::convert_bool_to_yes_no($value);

		return $this;
	}

	/**
	 * @param bool $value
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_force_membership_consent_acceptance( bool $value ): Diller_Loyalty_Follower {
		$this->force_membership_consent_acceptance = $value;
		return $this;
	}

	/**
	 *
	 * @return bool
	 */
	public function get_force_membership_consent_acceptance() : bool {
		return $this->force_membership_consent_acceptance;
	}

	/**
	 * @return string
	 */
	public function get_membership_consent_accepted_date(): string {
		return $this->membership_consent_accepted_date;
	}

	/**
	 * @param string $value
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_membership_consent_accepted_date( string $value ): Diller_Loyalty_Follower {
		$this->membership_consent_accepted_date = $value;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function get_is_unsubscribed(): bool {
		// Because when fetching a new copy of the data from the server, this gets overwritten, we created the function DillerLoyalty()->user_has_unsubscribed()
		// that reads from a separated user meta key, to provide this value.
		_doing_it_wrong( 'get_is_unsubscribed', 'Use function <code>DillerLoyalty()->user_has_unsubscribed()</code> instead. This value is read from a distinct user meta key', '2.0' );
		return false;
	}

	/**
	 * @return string
	 */
	public function get_password(): string {
		return $this->password;
	}

	/**
	 * @param string $password
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_password( string $password ): Diller_Loyalty_Follower {
		$this->password = $password;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_points(): string {
		return floor($this->points);
	}

	/**
	 * @param int $points
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_points( string $points ): Diller_Loyalty_Follower {
		$this->points = $points;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_remaining_points(): string {
		return  floor($this->remaining_points);
	}

	/**
	 * @param int $remaining_points
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_remaining_points( string $remaining_points ): Diller_Loyalty_Follower {
		$this->remaining_points = $remaining_points;

		return $this;
	}


	/**
	 * @return string
	 */
	public function get_current_membership_level(): string {
		return $this->current_membership_level;
	}

	/**
	 * @param string $current_membership_level
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_current_membership_level( string $current_membership_level ): Diller_Loyalty_Follower {
		$this->current_membership_level = $current_membership_level;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_points_expire_details(): string {
		return str_ireplace('<br>', ', ', $this->points_expire_details);
	}

	/**
	 * @param string $points_expire_details
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_points_expire_details( string $points_expire_details ): Diller_Loyalty_Follower {
		$this->points_expire_details = $points_expire_details;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_next_membership_level(): string {
		return $this->next_membership_level;
	}

	/**
	 * @param string $next_membership_level
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_next_membership_level( string $next_membership_level ): Diller_Loyalty_Follower {
		$this->next_membership_level = $next_membership_level;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_next_membership_level_required_points(): int {
		return  floor($this->next_membership_level_required_points);
	}

	/**
	 * @param int $next_membership_level_required_points
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_next_membership_level_required_points( int $next_membership_level_required_points ): Diller_Loyalty_Follower {
		$this->next_membership_level_required_points = $next_membership_level_required_points;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_total_earned_points(): int {
		return  floor($this->total_earned_points);
	}

	/**
	 * @param int $total_earned_points
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_total_earned_points( int $total_earned_points ): Diller_Loyalty_Follower {
		$this->total_earned_points = $total_earned_points;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_membership_level_created_date(): string {
		return $this->membership_level_created_date;
	}

	/**
	 * @param string $membership_level_created_date
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_membership_level_created_date( string $membership_level_created_date ): Diller_Loyalty_Follower {
		$this->membership_level_created_date = $membership_level_created_date;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_membership_level_expire_details(): string {
		return $this->membership_level_expire_details;
	}

	/**
	 * @param string $membership_level_expire_details
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function set_membership_level_expire_details( string $membership_level_expire_details ): Diller_Loyalty_Follower {
		$this->membership_level_expire_details = $membership_level_expire_details;

		return $this;
	}

	/**
	 * Diller_Loyalty_Follower constructor.
	 */
	public function __construct() {

	}

	public function save(){
		//Note to self: this will implicit call __serialize()
		update_user_meta( $this->wp_user_id, DillerLoyalty()->get_follower_meta_key(),  $this->__serialize());

		//Remove cached Follower object, if it exists
		wp_cache_delete('diller_' . DillerLoyalty()->get_site_prefix() . 'follower_' . $this->wp_user_id, 'diller' );
	}

	/**
	 * If the user is logged in, it will load its data from the DB from metadata "_diller_follower", "_diller_wp2_follower", etc
	 * respecting whether we're on a single site or multisite setup
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function load_data(int $wp_user_id = 0) {
		$this->wp_user_id = ($wp_user_id == 0 && is_user_logged_in()) ? get_current_user_id() : $wp_user_id;
		if($this->wp_user_id > 0) {
			$data = get_user_meta( $this->wp_user_id, DillerLoyalty()->get_follower_meta_key(), true );
			if ( !empty($data)) {
				$this->__unserialize( $data );
			}
		}

		return $this;
	}

	public function __toString(): string {
		return "{$this->first_name} {$this->last_name}";
	}

	public function __serialize(): array
	{
		return [
			'phone_country_code' => $this->phone_country_code,
			'phone_number' => $this->phone_number,
			'email' => $this->email,
			'first_name' => $this->first_name,
			'last_name' => $this->last_name,
			'gender' => $this->gender,
			'address' => $this->address,
			'postal_code' => $this->postal_code,
			'postal_city' => $this->postal_city,
			'country' => $this->country,
			'birth_date' => $this->birth_date,
			'purchase_history_consent_accepted' => $this->purchase_history_consent_accepted,
			'membership_consent_accepted' => $this->membership_consent_accepted,
			'marketing_email_consent_accepted' => $this->marketing_email_consent_accepted,
			'marketing_sms_consent_accepted' => $this->marketing_sms_consent_accepted,
			'enrolled_at_checkout' => $this->enrolled_at_checkout,
			'diller_referral_id' => $this->diller_referral_id,
			'diller_id' => $this->diller_id,
			'department_ids' => $this->department_ids,
			'segments' => $this->segments,
			'wp_user_id' => $this->wp_user_id,
			'status' => $this->status,
			'current_membership_level' => $this->current_membership_level,
			'remaining_points' => $this->remaining_points,
			'points' => $this->points,
			'total_earned_points' => $this->total_earned_points,
			'points_expire_details' => $this->points_expire_details,
			'next_membership_level' => $this->next_membership_level,
			'next_membership_level_required_points' => $this->next_membership_level_required_points,
			'membership_level_created_date' => $this->membership_level_created_date,
			'membership_level_expire_details' => $this->membership_level_expire_details,
			'membership_consent_accepted_date' => $this->membership_consent_accepted_date
		];
	}

	public function __unserialize(array $data): void
	{
		if ( $data ) {
			foreach ( $data as $prop => $value ) {
				$setter = "set_$prop";
				if ( is_callable( array( $this, $setter ) ) ) {
					$this->{$setter}( $value );
				}
			}
		}
	}
}
