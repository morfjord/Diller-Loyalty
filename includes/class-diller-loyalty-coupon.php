<?php

class Diller_Loyalty_Coupon extends Diller_Loyalty_Stamp {

	protected $coupon_type;
	protected $membership_level_title;
	protected $woocommerce_id;
	protected $is_campaign;
	protected $promo_code;


	/**
	 * @return int
	 */
	public function get_coupon_type(): int {
		return $this->coupon_type;
	}

	/**
	 * @param int $coupon_type
	 *
	 * @return Diller_Loyalty_Coupon
	 */
	public function set_coupon_type( int $coupon_type ): Diller_Loyalty_Coupon {
		$this->coupon_type = $coupon_type;
		
		return $this;
	}

	/**
	 * @return string
	 */
	public function get_membership_level_title(): string {
		return $this->membership_level_title;
	}

	/**
	 * @param string $membership_level_title
	 *
	 * @return Diller_Loyalty_Coupon
	 */
	public function set_membership_level_title( string $membership_level_title ): Diller_Loyalty_Coupon {
		$this->membership_level_title = $membership_level_title;

		return $this;
	}

	/**
	 * @return int
	 */
	public function get_woocommerce_id(): int {
		return $this->woocommerce_id;
	}

	/**
	 * @param int $woocommerce_id
	 *
	 * @return Diller_Loyalty_Coupon
	 */
	public function set_woocommerce_id( int $woocommerce_id ): Diller_Loyalty_Coupon {
		$this->woocommerce_id = $woocommerce_id;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function get_is_campaign(): bool {
		return $this->is_campaign;
	}

	/**
	 * @param bool $is_campaign
	 *
	 * @return Diller_Loyalty_Coupon
	 */
	public function set_is_campaign( bool $is_campaign ): Diller_Loyalty_Coupon {
		$this->is_campaign = $is_campaign;

		return $this;
	}

	/**
	 * @return string
	 */
	public function get_promo_code(): string {
		return $this->promo_code;
	}

	/**
	 * @param string $promo_code
	 *
	 * @return Diller_Loyalty_Coupon
	 */
	public function set_promo_code( string $promo_code ): Diller_Loyalty_Coupon {
		$this->promo_code = $promo_code;

		return $this;
	}

	public function __construct(string $id, string $name) {
		parent::__construct($id, $name);
	}
}