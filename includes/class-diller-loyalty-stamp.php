<?php

class Diller_Loyalty_Stamp {

	protected $usages;
	protected $id;
	protected $valid_until;
	protected $discount;
	protected $discount_type;
	protected $name;
	protected $description;
	protected $points_required;
	protected $icon;
	protected $last_stamp_text;
	protected $product_ids = array();
	protected $product_category_ids = array();
	protected $product_names = array();
	protected $auto_start_stamp;
	protected $is_applicable;
	protected $total_redemptions;
	protected $remaining_redemptions;

	/**
	 * @return int
	 */
	public function get_usages(): int {
		return $this->usages;
	}

	/**
	 * @param int $usages
	 *
	 * @return Diller_Loyalty_Stamp
	 */
	public function set_usages( int $usages ): Diller_Loyalty_Stamp {
		$this->usages = $usages;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_id(): int {
		return $this->id;
	}

	/**
	 * @param int $id
	 *
	 * @return Diller_Loyalty_Stamp
	 */
	public function set_id( int $id ): Diller_Loyalty_Stamp {
		$this->id = $id;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_valid_until(): string {
		return $this->valid_until;
	}

	/**
	 * @param string $valid_until
	 *
	 * @return Diller_Loyalty_Stamp
	 */
	public function set_valid_until( string $valid_until ): Diller_Loyalty_Stamp {
		$this->valid_until = $valid_until;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_discount(): int {
		return $this->discount;
	}

	/**
	 * @param int $discount
	 *
	 * @return Diller_Loyalty_Stamp
	 */
	public function set_discount( int $discount ): Diller_Loyalty_Stamp {
		$this->discount = $discount;

		
		return $this;
	}

	/**
	 * @return int
	 */
	public function get_discount_type(): int {
		return $this->discount_type;
	}

	/**
	 * @param int $discount_type
	 *
	 * @return Diller_Loyalty_Stamp
	 */
	public function set_discount_type( int $discount_type ): Diller_Loyalty_Stamp {
		$this->discount_type = $discount_type;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * @param string $name
	 *
	 * @return Diller_Loyalty_Stamp
	 */
	public function set_title( string $name ): Diller_Loyalty_Stamp {
		$this->name = $name;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * @param string $description
	 *
	 * @return Diller_Loyalty_Stamp
	 */
	public function set_description( string $description ): Diller_Loyalty_Stamp {
		$this->description = $description;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_points_required(): int {
		return $this->points_required;
	}

	/**
	 * @param int $points_required
	 *
	 * @return Diller_Loyalty_Stamp
	 */
	public function set_points_required( int $points_required ): Diller_Loyalty_Stamp {
		$this->points_required = $points_required;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_icon(): string {
		return $this->icon;
	}

	/**
	 * @param string $icon
	 *
	 * @return Diller_Loyalty_Stamp
	 */
	public function set_icon( string $icon ): Diller_Loyalty_Stamp {
		$this->icon = $icon;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_last_stamp_text(): string {
		return $this->last_stamp_text;
	}

	/**
	 * @param string $last_stamp_text
	 *
	 * @return Diller_Loyalty_Stamp
	 */
	public function set_last_stamp_text( string $last_stamp_text ): Diller_Loyalty_Stamp {
		$this->last_stamp_text = $last_stamp_text;

		return $this;
	}

	/**
	 * @return array
	 */
	public function get_product_ids(): array {
		return $this->product_ids;
	}

	/**
	 * @param array $product_ids
	 *
	 * @return Diller_Loyalty_Stamp
	 */
	public function set_product_ids( array $product_ids ): Diller_Loyalty_Stamp {
		$this->product_ids = $product_ids;

		return $this;
	}

	/**
	 * @return array
	 */
	public function get_product_category_ids(): array {
		return $this->product_category_ids;
	}

	/**
	 * @param array $product_category_ids
	 *
	 * @return Diller_Loyalty_Stamp
	 */
	public function set_product_category_ids( array $product_category_ids ): Diller_Loyalty_Stamp {
		$this->product_category_ids = $product_category_ids;

		return $this;
	}

	/**
	 * @return array
	 */
	public function get_product_names(): array {
		return $this->product_names;
	}

	/**
	 * @param array $product_names
	 *
	 * @return Diller_Loyalty_Stamp
	 */
	public function set_product_names( array $product_names ): Diller_Loyalty_Stamp {
		$this->product_names = $product_names;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function is_auto_start_stamp(): bool {
		return $this->auto_start_stamp;
	}

	/**
	 * @param bool $auto_start_stamp
	 *
	 * @return Diller_Loyalty_Stamp
	 */
	public function set_auto_start_stamp( bool $auto_start_stamp ): Diller_Loyalty_Stamp {
		$this->auto_start_stamp = $auto_start_stamp;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_is_applicable(): int {
		return $this->is_applicable;
	}

	/**
	 * @param int $is_applicable
	 *
	 * @return Diller_Loyalty_Stamp
	 */
	public function set_is_applicable( int $is_applicable ): Diller_Loyalty_Stamp {
		$this->is_applicable = $is_applicable;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_total_redemptions(): int {
		return $this->total_redemptions;
	}

	/**
	 * @param int $total_redemptions
	 *
	 * @return Diller_Loyalty_Stamp
	 */
	public function set_total_redemptions( int $total_redemptions ): Diller_Loyalty_Stamp {
		$this->total_redemptions = $total_redemptions;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_remaining_redemptions(): int {
		return $this->remaining_redemptions;
	}

	/**
	 * @param int $remaining_redemptions
	 *
	 * @return Diller_Loyalty_Stamp
	 */
	public function set_remaining_redemptions( int $remaining_redemptions ): Diller_Loyalty_Stamp {
		$this->remaining_redemptions = $remaining_redemptions;

		return $this;
	}

	public function __construct(string $id, string $name) {
		$this->id = $id;
		$this->name = $name;
	}
}
