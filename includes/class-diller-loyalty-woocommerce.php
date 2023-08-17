<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Diller_Loyalty_Woocommerce {


	public function __construct() {
	}

	/**
	 * This will create custom endpoints that can be used in the menu for the My Account public page or just to handle requests.
     * The name of the endpoints will be hooked into the action: woocommerce_account_(endpoint-name)_endpoint, where (endpoint-name) is the endpoint name.
     * This action is what allow us to define a function that will be invoked when the given endpoints get called.
	 */
	function my_account_page_custom_endpoints() {
		add_rewrite_endpoint(Diller_Loyalty_Configs::LOYALTY_PROFILE_ENDPOINT, EP_PAGES);
		add_rewrite_endpoint(Diller_Loyalty_Configs::LOYALTY_COUPONS_ENDPOINT, EP_PAGES);
		add_rewrite_endpoint(Diller_Loyalty_Configs::LOYALTY_FRIEND_REFERRAL_ENDPOINT, EP_PAGES);
		add_rewrite_endpoint(Diller_Loyalty_Configs::LOYALTY_SINGLE_COUPON_ENDPOINT, EP_PAGES);
        add_rewrite_endpoint(Diller_Loyalty_Configs::LOYALTY_STAMP_CARDS_ENDPOINT, EP_PAGES);
        add_rewrite_endpoint(Diller_Loyalty_Configs::LOYALTY_SINGLE_STAMPCARD_ENDPOINT, EP_PAGES);

        
        // An idea could be to calc the hashes of all the endpoints and compare with previously saved one.

		// We create a transient to control how we call flush_rewrite_rules(), as this is a costly operation.
		// flush_rewrite_rules() will run only once per each time the store is authenticated.
		if(get_transient( 'diller_flush_rewrite_rules' )) {
			delete_transient( 'diller_flush_rewrite_rules' );
			flush_rewrite_rules();
		}
	}

	function my_account_add_menu_items($menu_items)	{

		$diller_menu_items = array(
			Diller_Loyalty_Configs::LOYALTY_COUPONS_ENDPOINT => __('My coupons', 'diller-loyalty'),
            Diller_Loyalty_Configs::LOYALTY_STAMP_CARDS_ENDPOINT => __('My stamp cards', 'diller-loyalty'),
			Diller_Loyalty_Configs::LOYALTY_FRIEND_REFERRAL_ENDPOINT => __('Refer a friend', 'diller-loyalty'),
			Diller_Loyalty_Configs::LOYALTY_PROFILE_ENDPOINT => __('Loyalty Program', 'diller-loyalty')
		);

        // Some stores do not have stamp cards
		if(!DillerLoyalty()->get_store()->get_stamps_enabled()){
            unset($diller_menu_items[Diller_Loyalty_Configs::LOYALTY_STAMP_CARDS_ENDPOINT]);
        }

        // Some stores do not have refer a friend enabled
		if(!DillerLoyalty()->get_store()->get_refer_friend_enabled()){
            unset($diller_menu_items[Diller_Loyalty_Configs::LOYALTY_FRIEND_REFERRAL_ENDPOINT]);
        }

		return array_slice( $menu_items, 0, 3, true) // Dashboard, Order, Downloads
		        + $diller_menu_items
		        + array_slice($menu_items, count($diller_menu_items) - 1, null, true);
	}

	function my_account_customize_dashboard(){

        if(DillerLoyalty()->user_has_joined()):
	        $follower = DillerLoyalty()->get_current_follower();
	        $follower = DillerLoyalty()->get_api()->get_membership_details_for($follower);
	        if(is_wp_error($follower)){
                DillerLoyalty()->get_logger()->error(sprintf("Could not get get_membership_details for Follower. Function: %s()", __FUNCTION__), $follower);
                return;
            }

            $membership_levels = DillerLoyalty()->get_store()->get_store_membership_levels();
            ?>
            <div class="diller-box">
                <div>
                    <h4 class="diller-heading__title"><?php echo esc_html__('Loyalty Program Status','diller-loyalty'); ?></h4>
                    <?php if(sizeof($membership_levels) > 1): ?>
                        <span class=""><?php echo esc_html__('My level','diller-loyalty'); ?>: <span><b><?php echo $follower->get_current_membership_level(); ?></b></span></span>
                        <br/>
                    <?php endif; ?>
                    <span>
                        <?php echo esc_html__('Points for use','diller-loyalty'); ?>:
                        <span><b><?php echo esc_html($follower->get_points()); ?> <?php echo esc_html__('points','diller-loyalty') ?></b>
                            <small>
                                <?php echo $follower->get_points_expire_details() ? '(' . $follower->get_points_expire_details() . ')': ''; ?>
                            </small>
                        </span>
                    </span>
                    <br/>
                    <?php if(sizeof($membership_levels) > 1): ?>
                        <span>
                            <?php echo esc_html__('Points till next level','diller-loyalty'); ?> (<?php echo esc_html($follower->get_next_membership_level()); ?>):
                            <span><b><?php echo esc_html__($follower->get_next_membership_level_required_points()); ?> <?php echo esc_html__('points','diller-loyalty') ?></b></span>
                        </span>
                        <br/>
                    <?php endif; ?>
                    <span class=""><?php echo esc_html__('Points earned for the period','diller-loyalty'); ?>: <b><?php echo esc_html($follower->get_total_earned_points()); ?> <?php echo esc_html__('points','diller-loyalty') ?></b></span>
                </div>

                <?php if(sizeof($membership_levels) > 1): ?>
                    <div class="diller-membership-progress" style="margin: 25px 0;">
                        <?php
                            usort($membership_levels, function($a, $b) {
                                return $a->get_points() <=> $b->get_points(); // Note 2 self: <=> Spaceship Operator (php7)
                            });

                            if (sizeof($membership_levels) > 0):
                                $progress_bar_points_html = $progress_bar_labels_html = '';
                                $max_membership_level =  end($membership_levels);
                                $follower_current_percent =  $follower->get_total_earned_points() * 100 / $max_membership_level->get_points();
                                $follower_current_percent =  $follower_current_percent > 100 ? 100 : $follower_current_percent;
                                reset($membership_levels);

                                foreach ($membership_levels as $key => $membership_level):
                                    $level_percent = $membership_level->get_points() * 100 / $max_membership_level->get_points();
                                    $level_percent = $level_percent > 100 ? 100 : $level_percent;
                                    $style = (sizeof($membership_levels) > 1) ? "left:{$level_percent}%; " : "transform: translate(10px, 10px); left:{$level_percent}%; ";

                                    $progress_bar_points_html .=  '<div style="' . $style . '">' . esc_html($membership_level->get_points()) . '</div>';
                                    $progress_bar_labels_html .=  '<div style="' . $style . '">' . esc_html(trim($membership_level->get_name())) . '</div>';
                                endforeach;

                                ?>
                                <div class="diller-membership-progress-points"><?php echo $progress_bar_points_html; ?></div>
                                <div class="diller-membership-progress-bar">
                                    <div class="diller-membership-progress-bar-indicator" style="width:<?php echo esc_attr($follower_current_percent); ?>%;"></div>
                                </div>
                                <div class="diller-membership-progress-points"><?php echo $progress_bar_labels_html; ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="diller-w-100 <?php echo esc_attr(sizeof($membership_levels) > 1 ? " diller-mb-2 diller-mt-4 " : ""); ?>">
                <?php
                    $membership_level_created_date = $follower->get_membership_level_created_date();
                    $membership_level_expire_date = $follower->get_membership_level_expire_details();
                    echo esc_html(sizeof($membership_levels) > 1
                        ? __('Member level qualification period','diller-loyalty')
                        : __('Qualification period','diller-loyalty'));
                    ?>: <b><?php echo esc_html(empty($level_expire_date) ? "$membership_level_created_date - $membership_level_expire_date" : __('Never expire','diller-loyalty')); ?></b>
                </div>
            </div>
        <?php else: ?>
        <div class="diller-box diller-mt-3">
            <h4 class="diller-heading__title"><?php echo esc_html__('Loyalty Program','diller-loyalty'); ?></h4>
            <span>
                <?php printf(
                        /* translators: 1: is a line break <br>. 2: link to my account page. 3: closing link */
                        esc_html__( 'Become a member of our Loyalty Program to get exclusive benefits and offers from us.%1$s%2$sClick here%3$s to enroll now', 'diller-loyalty' ),
                '</br>',
                       '<a href="' . esc_url( trailingslashit(wc_get_page_permalink( 'myaccount' )) . Diller_Loyalty_Configs::LOYALTY_PROFILE_ENDPOINT ) . '">',
                       '</a>'
                    );
                ?>
            </span>
        </div>
        <?php endif;
	}

	function my_account_page_loyalty_profile_endpoint_content() {

		echo '<div class="diller-container">';

        $this->display_my_account_unsubscribe_content();

        $this->display_my_account_update_phone_content();

        $this->display_my_account_profile_content();

		echo '</div>';
	}

	function my_account_page_friend_referral_endpoint_content() {
        ?>
        <div class="diller-container">
        <?php
            if(DillerLoyalty()->user_has_joined()):
                $form = new Diller_Refer_Friend_Form("dillerReferFriendForm");
                $form->set_title(__('Refer a friend', 'diller-loyalty'));
                $form->build_fields();

                if ($form->was_submitted() && $form->validate_request()):
                    $form->save();
                endif;

                $form->render();
            else:
            ?>
            <h2 class="diller-heading__title"><?php echo esc_html('Refer a friend', 'diller-loyalty'); ?></h2>
            <div class="diller-alert diller-alert--info diller-w-100">
                <?php printf(
                        /* translators: 1: is a line break <br>. 2: link to Loyalty Program enrollment form URL. 3: closing link */
                        esc_html__( 'You need to enroll on the Loyalty Program first, before you can invite your friends.%1$sTo enroll, please %2$sclick here%3$s', 'diller-loyalty' ),
                '</br>',
                       '<a href="' . esc_url( trailingslashit(wc_get_page_permalink( 'myaccount' )) . Diller_Loyalty_Configs::LOYALTY_PROFILE_ENDPOINT ) . '">',
                       '</a>'
                    );
                ?>
            </div>
		<?php endif; ?>
        </div>
        <?php
	}

	/**
     * Checks the validity of the applied coupon code against Diller api.
     * If not valid a message will be displayed.
	 * @param $coupon_code
	 */
	function applied_coupon($coupon_code){
		$error_message = "";
		$coupon = new WC_Coupon($coupon_code);
        $is_diller_coupon = (int)$coupon->get_meta('store_id', true) > 0;
        $is_public_coupon = (int)$coupon->get_meta('is_public', true) === 1;

        // Internal WC coupon or Diller public coupon. OK
        if(!$is_diller_coupon || $is_public_coupon) return true;

        // Coupon is not public. Validate it against the API for the current customer
        if(DillerLoyalty()->user_has_joined() && !DillerLoyalty()->user_has_unsubscribed() ){
	        $follower = DillerLoyalty()->get_current_follower();
	        $result   = DillerLoyalty()->get_api()->validate_coupon_for( $follower, $coupon_code );

	        if (is_wp_error( $result ) ){
		        $error_message = join( "<br/>", $result->get_error_messages( 'validation-error' ) );
            }else{
                return true;
	        }
        }

        // Not valid
		$error_message = !empty($error_message)
            ? $error_message
            : esc_html__( 'You need to login and become a member of our Loyalty Program before you can use this coupon code.', 'diller-loyalty' );

		WC()->cart->remove_coupon( $coupon_code );
		wc_clear_notices();
		wc_add_notice( $error_message, 'error' );
	}

	function my_cart_show_available_coupons() {
        if(!DillerLoyalty()->user_has_joined() || DillerLoyalty()->user_has_unsubscribed()) return;

        // Output some json params that the javascript file will use
		$js_params = new stdClass();
		$js_params->texts = new stdClass();
		$js_params->texts->applyCoupon  = esc_html__('Apply', 'diller-loyalty' );
		$js_params->texts->removeCoupon = esc_html__('Remove', 'diller-loyalty' );

        wp_add_inline_script(DILLER_LOYALTY_JS_VENDORS_BUNDLE_HANDLE, "window.Diller_Loyalty = " . json_encode($js_params) .";", 'before' );

		?>
        <div class="diller-container">
            <h2 class="diller-heading__title"><?php echo esc_html__('My Coupons', 'diller-loyalty'); ?></h2>

		<?php
        $follower = DillerLoyalty()->get_current_follower();
        $coupons = DillerLoyalty()->get_api()->get_coupons_for($follower);

        // Filter by those with a valid woocommerce id
        $coupons = array_filter($coupons, function($coupon, $key) {
            return $coupon->get_woocommerce_id() > 0;
        }, ARRAY_FILTER_USE_BOTH);

        if(DillerLoyalty()->get_store()->get_point_system_enabled()): ?>
            <div class="diller-heading__subtitle">
                <?php echo esc_html__('My points', 'diller-loyalty'); ?>: <b><?php echo esc_html($follower->get_points()); ?> <?php echo esc_html__('points', 'diller-loyalty'); ?></b>
            </div>
        <?php endif; ?>

        <?php if(is_array($coupons) && sizeof($coupons) > 0): ?>
            <div class="diller-coupon-container">
                <?php foreach ($coupons as $key => $coupon): ?>
                    <div class="diller-coupon">
                        <div class="diller-coupon-inner">
                            <div class="diller-coupon-img" style="background-image: url(<?php echo esc_url($coupon->get_icon()); ?>)"></div>

                            <h3 class="diller-coupon-name"><?php echo esc_html($coupon->get_name()); ?></h3>

                            <div class="diller-coupon-discount diller-flex-col">
		                        <span><?php echo esc_html__('Discount', 'diller-loyalty'); ?></span>
                                <span>
                                    <b>
                                    <?php
                                        if($coupon->get_discount_type() == Diller_Coupon_Discount_Types::Fixed){
                                            echo sprintf("%s %s", $coupon->get_discount(), get_woocommerce_currency());
                                        }else if($coupon->get_discount_type() == Diller_Coupon_Discount_Types::Percentage){
                                            echo $coupon->get_discount()."%";
                                        }else if($coupon->get_discount_type() == Diller_Coupon_Discount_Types::FreeShipping){
                                            echo Diller_Coupon_Discount_Types::get_discount_name($coupon->get_discount_type());
                                        }
                                    ?>
                                    </b>
                                </span>
                            </div>

                            <div class="diller-coupon-usage">
	                            <?php
	                                if($coupon->get_usages() < 1000):
		                                $usage_text = sprintf(
		                                    /* translators: %s: Remaining coupons usages. */
			                                _n( 'You have <b>%s</b> usage left', 'You have <b>%s</b> usages left', $coupon->get_remaining_redemptions(), 'diller-loyalty' ),
                                            $coupon->get_remaining_redemptions()
		                                ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

		                                $usage_text .= $coupon->get_total_redemptions() == 0 ? '' : sprintf(' (%1$s/%2$s)', $coupon->get_total_redemptions(), $coupon->get_usages() );
		                                echo $usage_text;
                                    else:
                                        echo esc_html__( 'Can be used unlimited times', 'diller-loyalty' );
                                    endif;
                                ?>
                            </div>
                        </div>
                        <div class="diller-coupon-promo-code diller-flex-col">
                            <span><?php echo esc_html__('Promo code', 'diller-loyalty'); ?></span>
                            <span><b><?php echo esc_html($coupon->get_promo_code()); ?></b></span>
                        </div>
                        <div class="diller-coupon-inner-bottom">
                            <button class="diller-button diller-button--primary diller-button--round diller-w-100" value="<?php echo $coupon->get_promo_code(); ?>" data-diller-action="apply-coupon" data-diller-coupon="<?php echo $coupon->get_promo_code(); ?>">
                                <?php echo esc_html__('Apply', 'diller-loyalty'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else : ?>
            <div class="diller-alert diller-alert--info diller-w-100">
                <?php echo esc_html__('You have no coupons available at the time', 'diller-loyalty'); ?>
            </div>
        <?php endif; ?>

        </div>

        <?php
	}

	function my_account_page_coupons_endpoint_content() {
	    ?>
        <div class="diller-container">
            <h2 class="diller-heading__title"><?php echo esc_html__('My coupons', 'diller-loyalty'); ?></h2>
		<?php

		if(DillerLoyalty()->user_has_joined()):
			$follower = DillerLoyalty()->get_current_follower();
			$coupons = DillerLoyalty()->get_api()->get_coupons_for($follower);

            if(DillerLoyalty()->get_store()->get_point_system_enabled()): ?>
                <div class="diller-heading__subtitle">
                    <?php echo esc_html__('My points', 'diller-loyalty'); ?>: <b><?php echo esc_html($follower->get_points()); ?> <?php echo esc_html__('points', 'diller-loyalty'); ?></b>
                </div>
            <?php endif; ?>

            <?php if(is_array($coupons) && sizeof($coupons) > 0): ?>
                <div class="diller-coupon-container">
                    <?php foreach ($coupons as $key => $coupon): ?>
                        <div class="diller-coupon">
                            <div class="diller-coupon-inner">
                                <div class="diller-coupon-img" style="background-image: url(<?php echo esc_url($coupon->get_icon()); ?>)"></div>

                                <h3 class="diller-coupon-name"><?php echo esc_html($coupon->get_name()); ?></h3>

                                <div class="diller-coupon-discount diller-flex-col">
                                    <span><?php echo esc_html__('Discount', 'diller-loyalty'); ?></span>
                                    <span>
                                        <b>
                                        <?php
                                        if($coupon->get_discount_type() == Diller_Coupon_Discount_Types::Fixed){
                                            echo sprintf("%s %s", $coupon->get_discount(), get_woocommerce_currency());
                                        }else if($coupon->get_discount_type() == Diller_Coupon_Discount_Types::Percentage){
                                            echo $coupon->get_discount()."%";
                                        }else if($coupon->get_discount_type() == Diller_Coupon_Discount_Types::FreeShipping){
                                            echo Diller_Coupon_Discount_Types::get_discount_name($coupon->get_discount_type());
                                        }
                                        ?>
                                        </b>
                                    </span>
                                </div>

                                <div class="diller-coupon-usage">
				                    <?php
				                    if($coupon->get_usages() < 1000):
					                    $usage_text = sprintf(
					                    /* translators: %s: Remaining coupons usages. */
						                    _n( 'You have <b>%s</b> usage left', 'You have <b>%s</b> usages left', $coupon->get_remaining_redemptions(), 'diller-loyalty' ),
						                    $coupon->get_remaining_redemptions()
					                    ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

					                    $usage_text .= $coupon->get_total_redemptions() == 0 ? '' : sprintf(' (%1$s/%2$s)', $coupon->get_total_redemptions(), $coupon->get_usages() );
					                    echo $usage_text;
				                    else:
					                    echo esc_html__( 'Can be used unlimited times', 'diller-loyalty' );
				                    endif;
				                    ?>
                                </div>
                            </div>
                            <div class="diller-coupon-promo-code diller-flex-col">
                                <span><?php echo esc_html__('Promo code', 'diller-loyalty'); ?></span>
                                <span><b><?php echo esc_html($coupon->get_promo_code()); ?></b></span>
                            </div>
                            <div class="diller-coupon-inner-bottom">
                                <a class="diller-button diller-button--primary diller-button--round" href="<?php echo esc_url(wc_get_page_permalink('myaccount') . Diller_Loyalty_Configs::LOYALTY_SINGLE_COUPON_ENDPOINT . '/' . $coupon->get_id()); ?>">
			                        <?php echo esc_html__('Go to coupon', 'diller-loyalty'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="diller-alert diller-alert--info diller-w-100">
                    <?php echo esc_html__('You have no coupons available at the time', 'diller-loyalty'); ?>
                </div>
            <?php endif; ?>

		<?php else: ?>
            <div class="diller-alert diller-alert--info diller-w-100">
                <?php printf(
                        /* translators: 1: is a line break <br>. 2: link to Loyalty Program enrollment form URL. 3: closing link */
                        esc_html__( 'You need to enroll on the Loyalty Program first, before you can access your coupons.%1$sTo enroll, please %2$sclick here%3$s', 'diller-loyalty' ),
                '</br>',
                       '<a href="' . esc_url( trailingslashit(wc_get_page_permalink( 'myaccount' )) . Diller_Loyalty_Configs::LOYALTY_PROFILE_ENDPOINT ) . '">',
                       '</a>'
                    );
                ?>
            </div>
		<?php endif; ?>

        </div>

		<?php
	}

	function my_account_page_single_coupon_endpoint_content($id) {
	    ?>
        <div class="diller-container">
            <h2 class="diller-heading__title"><?php echo esc_html__('My Coupons', 'diller-loyalty'); ?></h2>
		<?php

            if(DillerLoyalty()->user_has_joined()):
                $follower = DillerLoyalty()->get_current_follower(); 
                $coupon = DillerLoyalty()->get_api()->get_coupon_details_for($follower, $id);
                if(is_wp_error($coupon)){
                    DillerLoyalty()->get_logger()->error(sprintf("Could not get get_coupon_details. Coupon ID: %s. Function: %s()", $id, __FUNCTION__), $follower, $coupon);
                }

                if(DillerLoyalty()->get_store()->get_point_system_enabled()): ?>
                    <div class="diller-heading__subtitle">
                        <?php echo esc_html__('My points', 'diller-loyalty'); ?>: <b><?php echo esc_html($follower->get_points()); ?> <?php echo esc_html__('points', 'diller-loyalty'); ?></b>
                    </div>
                <?php endif; ?>

                <div class="diller-coupon-container">
                    <div class="diller-coupon diller-coupon--single" data-coupon-used="<?php echo esc_attr($coupon->get_total_redemptions()); ?>" data-total-coupon="<?php echo esc_attr($coupon->get_usages()); ?>">
                        <div class="diller-coupon-img" style="background-image: url(<?php echo esc_url($coupon->get_icon()); ?>)"></div>
                        <div class="diller-coupon-inner">
                            <h3 class="diller-coupon-name"><?php echo esc_html($coupon->get_name()); ?></h3>
                            <div class="diller-coupon-discount diller-flex-col">
                                <span><?php echo esc_html__('Discount', 'diller-loyalty'); ?></span>
                                <span>
                                        <b>
                                        <?php
                                        if($coupon->get_discount_type() == Diller_Coupon_Discount_Types::Fixed){
	                                        echo sprintf("%s %s", $coupon->get_discount(), get_woocommerce_currency());
                                        }else if($coupon->get_discount_type() == Diller_Coupon_Discount_Types::Percentage){
	                                        echo $coupon->get_discount()."%";
                                        }else if($coupon->get_discount_type() == Diller_Coupon_Discount_Types::FreeShipping){
	                                        echo Diller_Coupon_Discount_Types::get_discount_name($coupon->get_discount_type());
                                        }
                                        ?>
                                        </b>
                                    </span>
                            </div>

                            <div class="diller-coupon-usage">
		                        <?php
		                        if($coupon->get_usages() < 1000):
			                        $usage_text = sprintf(
			                            /* translators: %s: Remaining coupons usages. */
				                        _n( 'You have <b>%s</b> usage left', 'You have <b>%s</b> usages left', $coupon->get_remaining_redemptions(), 'diller-loyalty' ),
				                        $coupon->get_remaining_redemptions()
			                        ); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped

			                        $usage_text .= $coupon->get_total_redemptions() == 0 ? '' : sprintf(' (%1$s/%2$s)', $coupon->get_total_redemptions(), $coupon->get_usages() );
			                        echo $usage_text;
		                        else:
			                        echo esc_html__( 'Can be used unlimited times', 'diller-loyalty' );
		                        endif;
		                        ?>
                            </div>

                            <div class="diller-coupon-points-expiration">
                                <?php echo esc_html__('Expires:', 'diller-loyalty'); ?> <?php echo esc_html($coupon->get_valid_until() == '0000-00-00' ? __('expired', 'diller-loyalty') : date('d.m.Y', strtotime($coupon->get_valid_until()))); ?>
                            </div>
                        </div>

                        <div class="diller-coupon-bottom">
                            <div class="diller-coupon-code">
                                <div class="diller-coupon-code__label"><?php echo esc_html__('Promo code:', 'diller-loyalty'); ?></div>
                                <div class="diller-coupon-code__text"><?php echo esc_html($coupon->get_promo_code()); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else : ?>
                <div class="diller-alert diller-alert--info">
                    <?php printf(
                            /* translators: 1: is a line break <br>. 2: link to Loyalty Program enrollment form URL. 3: closing link */
                            esc_html__( 'You need to enroll on the Loyalty Program first, before you can access your coupons.%1$sTo enroll, please %2$sclick here%3$s', 'diller-loyalty' ),
                    '</br>',
                           '<a href="' . esc_url( trailingslashit(wc_get_page_permalink( 'myaccount' )) . Diller_Loyalty_Configs::LOYALTY_PROFILE_ENDPOINT ) . '">',
                           '</a>'
                        );
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
	}

	function my_account_page_stamp_cards_endpoint_content() {
	    ?>
        <div class="diller-container">
            <h2 class="diller-heading__title"><?php echo esc_html__('My stamp cards', 'diller-loyalty'); ?></h2>
		<?php

        if(DillerLoyalty()->user_has_joined()):
            $follower = DillerLoyalty()->get_current_follower();
            $stamps = DillerLoyalty()->get_api()->get_stamps_for($follower);
            if(is_wp_error($stamps)){
                DillerLoyalty()->get_logger()->error(sprintf("Could not get get_stamps for Follower. Function: %s()", __FUNCTION__), $follower, $stamps);
            }

            if(DillerLoyalty()->get_store()->get_point_system_enabled()): ?>
                <div class="diller-heading__subtitle">
                    <?php echo esc_html__('My points', 'diller-loyalty'); ?>: <b><?php echo esc_html($follower->get_points()); ?> <?php echo esc_html__('points', 'diller-loyalty'); ?></b>
                </div>
            <?php endif; ?>

            <?php if(is_array($stamps) && sizeof($stamps) > 0): ?>

                <div class="diller-coupon-container">
                    <?php foreach ($stamps as $key => $stamp): ?>
                        <div class="diller-coupon">
                            <div class="diller-coupon-img" style="background-image: url(<?php echo $stamp->get_icon(); ?>)"></div>
                            <h3 class="diller-coupon-name"><?php echo $stamp->get_name(); ?></h3>
                            <div class="diller-coupon-usage">
			                    <?php echo esc_html__('Stamp can be used', 'diller-loyalty'); ?>
			                    <?php echo esc_html($stamp->get_usages() == 1000 ? __('unlimited', 'diller-loyalty') : $stamp->get_usages()); ?>
			                    <?php echo esc_html($stamp->get_usages() <= 1 ? __('time', 'diller-loyalty') : __('times', 'diller-loyalty')); ?>
                            </div>

		                    <?php if(DillerLoyalty()->get_store()->get_point_system_enabled()): ?>
                                <div class="diller-coupon-points">
				                    <?php // echo $stamp->get_points_required() ?> <?php // echo __('points', 'diller-loyalty'); ?>
                                </div>
		                    <?php endif; ?>
                            <div class="diller-coupon-bottom">
                                <a class="diller-button diller-button--primary diller-button--round" href="<?php echo esc_url(wc_get_page_permalink('myaccount') . Diller_Loyalty_Configs::LOYALTY_SINGLE_STAMPCARD_ENDPOINT  . '/' . $stamp->get_id()); ?>">
				                    <?php echo esc_html__('Go to stamp', 'diller-loyalty'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else : ?>
                <div class="diller-alert diller-alert--info">
                    <?php echo esc_html__('You have no stamps available at the time', 'diller-loyalty'); ?>
                </div>
            <?php endif; ?>

        <?php else : ?>
            <div class="diller-alert diller-alert--info">
                <?php printf(
                        /* translators: 1: is a line break <br>. 2: link to Loyalty Program enrollment form URL. 3: closing link */
                        esc_html__( 'You need to enroll on the Loyalty Program first, before you can access your stamp cards.%1$sTo enroll, please %2$sclick here%3$s', 'diller-loyalty' ),
                '</br>',
                       '<a href="' . esc_url( trailingslashit(wc_get_page_permalink( 'myaccount' )) . Diller_Loyalty_Configs::LOYALTY_PROFILE_ENDPOINT ) . '">',
                       '</a>'
                    );
                ?>
            </div>
        <?php endif; ?>

        </div>
        <?php
	}

	function my_account_page_single_stampcard_endpoint_content($id) {
	    ?>
        <div class="diller-container">
            <h2 class="diller-heading__title"><?php echo esc_html__('My stamps', 'diller-loyalty'); ?></h2>
		<?php
            if(DillerLoyalty()->user_has_joined()):
                $follower = DillerLoyalty()->get_current_follower();
                $stamp = DillerLoyalty()->get_api()->get_stamp_details_for($follower, $id);
                if(is_wp_error($stamp)){
                    DillerLoyalty()->get_logger()->error(sprintf("Could not get get_stamp_details. Stamp ID: %s. Function: %s()", $id, __FUNCTION__), $follower, $stamp);
                }

                if(DillerLoyalty()->get_store()->get_point_system_enabled()): ?>
                    <div class="diller-heading__subtitle">
                        <?php echo esc_html__('My points', 'diller-loyalty'); ?>: <b><?php echo esc_html($follower->get_points()) ?> <?php echo esc_html__('points', 'diller-loyalty'); ?></b>
                    </div>
                <?php endif; ?>

                <div class="diller-coupon-container">
                    <div class="diller-coupon diller-coupon--single" data-stamp-used="<?php echo esc_attr($stamp->get_total_redemptions()); ?>" data-total-stamp="<?php echo esc_attr($stamp->get_usages()); ?>">
                        <div class="diller-coupon-img" style="background-image: url(<?php echo esc_url($stamp->get_icon()); ?>)"></div>
                        <h3 class="diller-coupon-name"><?php echo esc_html($stamp->get_name()); ?></h3>

                        <?php if($stamp->get_description()): ?>
                        <div class="diller-coupon-description">
                            <?php echo esc_html($stamp->get_description()); ?>
                        </div>
                        <?php endif; ?>

                        <?php if($stamp->get_remaining_redemptions() > 0): ?>
                            <div class="diller-coupon-usage">
                                <?php echo esc_html__('Stamp can be used:', 'diller-loyalty'); ?>
                                <?php echo esc_html($stamp->get_usages() == 1000 ? __('unlimited', 'diller-loyalty') : $stamp->get_usages()); ?>
                                <?php echo esc_html($stamp->get_usages() <= 1 ? __('time', 'diller-loyalty') : __('times', 'diller-loyalty')); ?>
                            </div>

                            <?php if(DillerLoyalty()->get_store()->get_point_system_enabled()): ?>
                                <div class="diller-coupon-points">
                                    <?php //echo $stamp->get_points_required() ?> <?php // echo __('points', 'diller-loyalty'); ?>
                                </div>
                            <?php endif; ?>

                            <div class="diller-coupon-points-expiration">
                                <?php echo esc_html__('Expires:', 'diller-loyalty'); ?> <?php echo esc_html__($stamp->get_valid_until() == '0000-00-00' ? __('expired', 'diller-loyalty') : date('d.m.Y', strtotime($stamp->get_valid_until()))); ?>
                            </div>

                            <?php if($stamp->get_total_redemptions() > 0): ?>
                                <div class="diller-coupon-bottom">
                                    <div class="diller-coupon-redemptions">
                                        <?php echo esc_html__('Stamp has been used', 'diller-loyalty'); ?> <?php echo esc_html($stamp->get_total_redemptions()); ?> <?php echo esc_html($stamp->get_total_redemptions() <= 1 ? __('time', 'diller-loyalty') : __('times', 'diller-loyalty')); ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                        <?php else: ?>

                            <?php // CONGRATZ - Get your nth item for free :) ?>
                            <div class="diller-coupon-bottom">
                                <div class="diller-coupon-win-text">
                                    <?php echo esc_html($stamp->get_last_stamp_text()); ?>
                                </div>
                                <?php if(DillerLoyalty()->get_store()->get_point_system_enabled()): ?>
                                    <div class="diller-coupon-points-expiration">
                                        <?php echo esc_html__('Expires:', 'diller-loyalty'); ?> <?php echo esc_html($stamp->get_valid_until() == '0000-00-00' ? __('expired', 'diller-loyalty') : date('d.m.Y', strtotime($stamp->get_valid_until()))); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            <?php else : ?>
                <div class="diller-alert diller-alert--info">
                    <?php printf(
                            /* translators: 1: link to Loyalty Program enrollment form URL. 2: closing link */
                            esc_html__( 'You need to enroll on the Loyalty Program first, before you can access your stamp cards. To enroll, please %1$sclick here%2$s', 'diller-loyalty' ),
                           '<a href="' . esc_url( trailingslashit(wc_get_page_permalink( 'myaccount' )) . Diller_Loyalty_Configs::LOYALTY_PROFILE_ENDPOINT ) . '">',
                           '</a>'
                        );
                    ?>
                </div>
            <?php endif; ?>

        </div>
    <?php
	}

    /**
     * Add/edit fields for WC checkout form to customize the phone number input field
     * @param $fields
     *
     * @return array
     */
    function checkout_form_add_custom_fields($fields){
        if(!DillerLoyalty()->get_store()->get_phone_intl_plugin_input_enabled()) return $fields;

        // Custom css class for easier styling
        array_push($fields['billing']['billing_phone']['class'], "diller-checkout-billing-phone");

        // Add hidden custom field to hold the country code for the phone number
		$fields['billing']['diller_billing_phone_country_code'] = array(
			'type'    => 'hidden',
			'class'   => array( 'diller-checkout-phone-country-code' ),
			'default' => DillerLoyalty()->user_has_joined()
			    ? DillerLoyalty()->get_current_follower()->get_phone_country_code()
			    : DillerLoyalty()->get_store()->get_phone_default_country_code()
		);

        // Output some json params that the javascript file will use
		$js_params = new stdClass();
		$js_params->pluginUrl = DILLER_LOYALTY_URL;

		// Phone - configurations
        $allowed_countries_option = DillerLoyalty()->get_store()->get_phone_country_option();
		$js_params->phone = new stdClass();
		$js_params->phone->preferredCountries = DillerLoyalty()->get_store()->get_phone_preferred_countries();
		$js_params->phone->defaultCountryCode = DillerLoyalty()->get_store()->get_phone_default_country_code();
		$js_params->phone->intlInputPluginEnabled = DillerLoyalty()->get_store()->get_phone_intl_plugin_input_enabled();
		$js_params->phone->allowedCountriesOption = $allowed_countries_option;
		$js_params->phone->selectedCountries = array();

		if($allowed_countries_option != "all"){
			$selected_countries = DillerLoyalty()->get_store()->get_phone_countries();
			$all_countries = array_map('strtolower', array_keys(WC()->countries->get_countries()));
			$js_params->phone->selectedCountries = ($allowed_countries_option === "specific")
				? $selected_countries // specific
				: array_values(array_filter($all_countries, function ($cc) use($selected_countries) {
					return !in_array($cc, $selected_countries); // all_except
				}));
		}

		$js_params->form = new stdClass();
		$js_params->form->enablePhoneLookup = false;
		$js_params->form->enableEmailLookup = false;
		$js_params->form->fields = new stdClass();
        $js_params->form->fields->phone_country_code = new stdClass();
        $js_params->form->fields->phone_country_code->id = 'diller_billing_phone_country_code';
        $js_params->form->fields->phone_number = new stdClass();
        $js_params->form->fields->phone_number->id = 'billing_phone';

        // Texts for custom validation rules defined in diller-loyalty-public.js
		$js_params->form->validationRulesTexts = new stdClass();
		$js_params->form->validationRulesTexts->phonenumber = esc_html__("You must enter a valid mobile number", "diller-loyalty");

        wp_add_inline_script(DILLER_LOYALTY_JS_VENDORS_BUNDLE_HANDLE, "window.Diller_Loyalty = " . json_encode($js_params) .";", 'before' );

        return $fields;
    }

	/**
     * Displays consent fields for joining LP and allowing purchases to be saved, in terms and billing pages.
     *
	 * @return void
	 */
	function checkout_page_display_consent_fields() {
	    

		if(DillerLoyalty()->user_has_joined() == false):
            $purchase_history_consent_text = esc_html__('I want to get offers and benefits that suit me based on my preferences and purchase history.', 'diller-loyalty');
            $diller_join_text = sprintf(
                /* translators: 1: Store Name, 2: link to Terms & Conditions URL, 3: closing url */
                esc_html__( 'I want to join %1$s\'s loyalty club and receive benefits, offers and other marketing communications electronically, including email, SMS and the like. Read our %2$sprivacy policy here%3$s', 'diller-loyalty' ),
                DillerLoyalty()->get_store()->get_store_name(),
                '<a href="' . esc_url( DillerLoyalty()->get_store()->get_privacy_policy_url()) . '" target="_blank">',
                '</a>'
            );

            // Note 2 self: We only output these 2 fields when on checkout page. The full 4 detailed consent fields are only display in the other contexts
            ?>
            <div id="diller-consent-section" class="diller-woocommerce-section diller-consent-section">
                <h3 class="diller-consent-section__title" ><?php echo esc_html__('Loyalty Program', 'diller-loyalty'); ?></h3>
                <div class="diller-form-group">
                    <label class="diller-checkbox">
                        <input type="checkbox" class="diller-form-control" name="diller_membership_consent_accepted" id="diller_membership_consent_accepted" value="Yes">
                        <span class="diller-label-text"><?php echo $diller_join_text; ?></span>
                    </label>
                    <label class="diller-checkbox">
                        <input type="checkbox" class="diller-form-control" name="diller_purchase_history_consent_accepted" id="diller_purchase_history_consent_accepted" value="Yes">
                        <span class="diller-label-text"><?php echo $purchase_history_consent_text; ?></span>
                    </label>
                </div>
            </div>
            <?php
		endif;
	}

	/**
     * Displays points earned for a given order, if customer is logged in and enrolled LP. Otherwise, displays a CTA element to encourage joining the LP.
	 * This function is called for my account view order details and checkout complete order details pages
	 *
	 * @param $order_id
	 *
	 * @return void
	 */
	function order_details_display_loyalty_program_info($order_id) {
		$order = wc_get_order($order_id);
		if(!$order || !DillerLoyalty()->get_store()->get_point_system_enabled()){
			return;
		}

		$follower = DillerLoyalty()->get_follower_by_order($order);

        // Check if consent was given at checkout.
		$checkout_consent_accepted = (get_post_meta($order->get_id(), "_diller_checkout_membership_consent", true) === 'Yes');

        // Non logged in customers
        // "woocommerce_after_order_details" action always fires.
		if( did_action("woocommerce_after_order_details") && !is_user_logged_in() ){
			if($checkout_consent_accepted || $follower->get_membership_consent_accepted() === "Yes") {
				$this->display_points_summary( $order_id );
			}else{
				$this->display_join_loyalty_program_cta($order_id);
			}
		}

		// Logged in customers
		// "woocommerce_order_details_after_customer_details" only fires id customer is logged in
		if( did_action("woocommerce_order_details_after_customer_details") && is_user_logged_in()){
			if( ($checkout_consent_accepted || $follower->get_membership_consent_accepted() === "Yes") && $order->get_status() != "cancelled") {
				$this->display_points_summary( $order_id );
			}
			else if( $order->get_status() != "cancelled" ){
				$this->display_join_loyalty_program_cta($order_id);
			}
		}
	}

	/**
     * Adds new column header to orders table under https://store.com/my-account/orders/
	 * @param $columns
	 *
	 * @return array|void
	 */
	function my_account_my_orders_customize_columns($columns) {
		$new_columns = array();
        foreach ($columns as $key => $name) {
            $new_columns[$key] = $name;
            if ('order-status' === $key) {
                // Append points column after, order status column
                $new_columns[Diller_Loyalty_Configs::POINTS_EARNED_COLUMN_NAME] = __('Points earned','diller-loyalty');;
            }
        }
        return $new_columns;
	}

	/**
     * Adds the total points per order (cell value) for the column defined with alias 'diller-points-earned' in https://store.com/my-account/orders/
	 * @param $order
	 */
	function my_account_orders_display_earned_points_column($order) {
		if(DillerLoyalty()->get_store()->get_point_system_enabled() && DillerLoyalty()->user_has_joined()):
			if($order && is_a($order, 'WC_Order')):
				$follower = DillerLoyalty()->get_current_follower();
				$result_points = DillerLoyalty()->get_api()->get_earned_points_for_order($follower, $order->get_id());
                if(is_wp_error($result_points)){
                    DillerLoyalty()->get_logger()->error(sprintf("Could not get points for order# %s. Function: %s()", $order->get_id(), __FUNCTION__), $follower, $result_points);
                }
				echo (!is_wp_error($result_points) ? $result_points : '0') . ' ' . esc_html__('points','diller-loyalty');
			endif;
		endif;
	}

	function update_order_received_text($thank_you_text, $order) {
		// We only display points for customers that joined LP and gave consent to save transactions
		if(! ($follower = DillerLoyalty()->get_follower_by_order($order)) || !DillerLoyalty()->get_store()->get_point_system_enabled()){
			return;
		}

		if($follower->get_membership_consent_accepted() !== 'Yes' || $follower->get_purchase_history_consent_accepted() !== 'Yes') return $thank_you_text;


		$total_points = $this->get_order_points($order);
        $thank_you_text .= '<ul class="diller-woocommerce-order-overview diller-woocommerce-order-points">';
        $thank_you_text .= '    <li>';
        $thank_you_text .=  esc_html__('Points earned', 'diller-loyalty') . ': <b>' . $total_points . '</b>';
        $thank_you_text .= '    </li>';
        $thank_you_text .= '</ul>';

		return $thank_you_text;
	}

    /**
     * Cancels Follower's order transaction in Diller Api. This will deduct the earned points
     *
     * @param $order WC_Order|int Order object or order ID
     *
     * @return bool|WP_Error
     */
	function cancel_order_transaction($order) {
		if( !($order = ($order && $order instanceof WC_Order )? $order : new WC_Order($order)) ) return false;


        $current_admin_user = (is_admin() && is_user_logged_in()) ? wp_get_current_user() : false;
        $follower = DillerLoyalty()->get_follower_by_order($order);
        if($follower && $follower->get_is_diller_member()){
            $result = DillerLoyalty()->get_api()->cancel_order_transaction_for($follower, $order->get_id());
            if(!is_wp_error($result)){
                // Add a note to the order, to inform points were removed
                $order->add_order_note(sprintf(
                    /* translators: This is the note text to add to the current order when cancelled. 1: Amount of points earned in this purchase */
                    esc_html__( 'Loyalty Program - Order cancelled. %1$s points removed.', 'diller-loyalty' ),
                    get_post_meta($order->get_id(), '_diller_points', true)
                ));
                $order->save();

                $log_message = sprintf("Order# %s successfully cancelled", $order->get_id());
                if($current_admin_user){
                    $log_message .= sprintf("by user: %s (ID: %d)", $current_admin_user->user_login, $current_admin_user->ID);
                }
                DillerLoyalty()->get_logger()->info($log_message, $follower);

                delete_post_meta($order->get_id(), '_diller_points');

                return true;
            }else{
                DillerLoyalty()->get_logger()->error(sprintf("Error while cancelling order# %s", $order->get_id()), $follower, $result);
                return $result;
            }
        }
        else{
            $log_message = sprintf("Cannot cancel order# %s. ", $order->get_id());
            if(!empty($order->get_billing_phone())){
                $log_message .= sprintf("Follower with phone number %s was not found.", $order->get_billing_phone());
            }else{
                $log_message .= "Phone number was empty.";
            }

            if($current_admin_user) {
                $log_message .=sprintf( "by user: %s (ID: %d)", $current_admin_user->user_login, $current_admin_user->ID );
            }

            global $Vipps;
            if(class_exists( 'Vipps' ) && $order->get_payment_method() === "vipps" && $Vipps != null && $current_admin_user === false) {
                $log_message .= "This order was cancelled by Vipps payment gateway. Check order notes, to find the reason.";
            }

            DillerLoyalty()->get_logger()->error($log_message);
        }

        // it's a cancellation, so whether it fails or succeeds, make sure to wipe the temporary consent meta fields.
        delete_post_meta($order->get_id(), "_diller_checkout_membership_consent");
        delete_post_meta($order->get_id(), "_diller_checkout_purchase_history_consent");
	}

	function order_created_handle_membership_consent($order) {
		if(DillerLoyalty()->user_has_joined() || DillerLoyalty()->user_has_unsubscribed()) return;

		// Load order
		$order = ($order && $order instanceof WC_Order ) ? $order : new WC_Order($order);

		// Handle scenario where Customer is new and wants to join the LP or has already joined LP, but never used the webshop
		if(filter_var($_POST['diller_membership_consent_accepted'] ?? 'No', FILTER_SANITIZE_STRING) === 'Yes'){
			update_post_meta($order->get_id(), '_diller_checkout_membership_consent', 'Yes');
		}

		if(filter_var($_POST['diller_purchase_history_consent_accepted'] ?? 'No', FILTER_SANITIZE_STRING) === 'Yes'){
			update_post_meta($order->get_id(), '_diller_checkout_purchase_history_consent', 'Yes');
        }
	}

	/**
     * Creates or retrieves a Follower object for a given order.
     * It will try to find the Follower in Diller by using the Order details (e.g. email, WP id, phone number).
     * If the Follower is not found/exists, and gave consent to join the Loyalty Program,
     * then new Follower will be created in Diller and also matching user in Wordpress.
     *
     * If a Follower doesn't exist in Diller but exists in WP, then it will be created in Diller.
     *
	 * @param int|WC_Order The order id or object
	 *
	 * @return Diller_Loyalty_Follower|mixed|void|WP_Error
	 */
	function resolve_follower_from_order($order) {

		// Load order
		if( !($order = ($order && $order instanceof WC_Order ) ? $order : new WC_Order($order)) ) return null;

		if($order->get_status() === "cancelled") return null;

		$user_email = $order->get_billing_email();
		$first_name = $order->get_billing_first_name();
		$last_name = $order->get_billing_last_name();
		$full_phone_number = trim($order->get_billing_phone());
		$address =  $order->get_billing_address_1();
		$postal_city =  $order->get_billing_city();
		$postal_code =  $order->get_billing_postcode();
		$country =  $order->get_billing_country();

		// Handle normal scenario, for registered and logged-in Customer that has already enrolled LP before
		if(DillerLoyalty()->user_has_joined()) return DillerLoyalty()->get_current_follower();

		// Handle scenario for registered Customer that has already enrolled LP from before, but has made this purchase in Guest mode
        if( ($follower = DillerLoyalty()->get_follower_by_order($order)) && $follower->get_is_diller_member()) return $follower;

		// Handle scenario where Customer is new and wants to join the LP or has already joined LP, but never used the webshop before
		$checkout_membership_consent_accepted = (get_post_meta($order->get_id(), "_diller_checkout_membership_consent", true) === 'Yes') ? 'Yes' : 'No';
		$purchase_history_consent_accepted = (get_post_meta($order->get_id(), "_diller_checkout_purchase_history_consent", true) === 'Yes') ? 'Yes' : 'No';
		$marketing_sms_consent_accepted = $marketing_email_consent_accepted = $checkout_membership_consent_accepted;
		$follower_force_send_password = false;

		$phone_number = Diller_Loyalty_Helpers::get_phone_number($full_phone_number, $country);
		$phone_number = !is_wp_error($phone_number) ? $phone_number : $full_phone_number;
		$phone_country_code = Diller_Loyalty_Helpers::get_phone_country_code($phone_number, $country);
		if(is_wp_error($phone_country_code)){
			// Get the country code from the custom hidden field we added before, that will be filled in by our javascript
			$billing_phone_cc = !empty($_POST['diller_billing_phone_country_code'])
				? filter_var($_POST['diller_billing_phone_country_code'], FILTER_SANITIZE_STRING)
				: '';

			$phone_country_code = Diller_Loyalty_Helpers::get_phone_country_code($billing_phone_cc.$phone_number);
			if(is_wp_error($phone_country_code)){
				$phone_country_code = DillerLoyalty()->get_store()->get_phone_default_country_code();
			}
		}

		// Check if the phone number is already registered in Diller
		$follower = DillerLoyalty()->get_api()->get_follower($phone_country_code, $phone_number);
		$follower_exists = (!is_wp_error($follower) && is_a($follower, 'Diller_Loyalty_Follower'));
		if($follower_exists):
			// Quit because Follower hasn't accepted the GDPR before and now at checkout
			if($follower->get_membership_consent_accepted() !== 'Yes' && $checkout_membership_consent_accepted !== 'Yes') return $follower;

			// Handle case for when a Follower was added via POS system, but didn't accept GDPR yet. Meanwhile, the Follower came to the webshop and checked to enroll the LP.
			// In that case, we need to force the consent accepted by passing the param flag "q_hidden_val" in the API request.
			// The following property keeps track of that.
			if(empty($follower->get_membership_consent_accepted_date())){
				$follower->set_force_membership_consent_acceptance(true);
				//This also means the Follower didn't get any password yet, by SMS
				$follower_force_send_password = true;
			}

			// Check if Follower haven't accepted purchase_history, sms and email consents before, but accepted it now, at checkout
			$checkout_membership_consent_accepted = ($follower->get_membership_consent_accepted() === 'Yes') ? 'Yes' : $checkout_membership_consent_accepted;
			$purchase_history_consent_accepted = ($follower->get_purchase_history_consent_accepted() === 'Yes') ? 'Yes' : $purchase_history_consent_accepted;
			$marketing_sms_consent_accepted = ($follower->get_marketing_sms_consent_accepted() === 'Yes') ? 'Yes' : $marketing_sms_consent_accepted;
			$marketing_email_consent_accepted = ($follower->get_marketing_email_consent_accepted() === 'Yes') ? 'Yes' : $marketing_email_consent_accepted;
		endif;


		// Quit is customer hasn't accepted the GDPR at checkout
		if(!$follower_exists && $checkout_membership_consent_accepted !== 'Yes') return;

		$follower = ($follower_exists) ? $follower : new Diller_Loyalty_Follower();
		$follower->set_first_name($first_name)
		         ->set_last_name($last_name)
		         ->set_full_phone_number($phone_country_code, $phone_number)
		         ->set_email($user_email)
		         ->set_address($address)
		         ->set_postal_city($postal_city)
		         ->set_postal_code($postal_code)
		         ->set_country($country)
		         ->set_membership_consent_accepted($checkout_membership_consent_accepted)
		         ->set_purchase_history_consent_accepted($purchase_history_consent_accepted)
		         ->set_marketing_email_consent_accepted($marketing_email_consent_accepted)
		         ->set_marketing_sms_consent_accepted($marketing_sms_consent_accepted);


        // Create / Update Follower
        if($follower_exists){
	        $result = DillerLoyalty()->get_api()->update_follower($follower);
        }
        else{
            // If Follower doesn't exist in WP from before, create it and set the password.
	        $order_user = ( ($order_user = $order->get_user()) && is_a($order_user, 'WP_User'))? $order_user : get_user_by('email', $order->get_billing_email());
	        $send_password = (!$order_user || !$order_user->exists());

            // NB: On success, this function "create_new_follower()" will implicitly call wp action "diller_api_follower_registered" that will create the WP User
	        $result = DillerLoyalty()->get_api()->create_new_follower($follower, true, $send_password);
        }

        if(!is_wp_error($result)){
			$follower = $result;
			if($follower_force_send_password){
				if(DillerLoyalty()->get_api()->send_follower_password($follower)){
					DillerLoyalty()->get_logger()->info(sprintf("Checkout: SMS password sent to follower (%s)", $follower->get_full_phone_number()));
				}
			}
            return $follower;
		}
		else {
			DillerLoyalty()->get_logger()->error(sprintf("Checkout: Order# %d | Error creating / updating Follower (%s)", $order->get_id(), $follower->get_full_phone_number() ?? ""), $result);
		}

        return null;
	}

	/**
     * When order status is set to complete, we finally communicate the transaction details to Diller.
     * At this moment points will be calculated.
     *
	 * @param int|WC_Order $order The order id or object
	 *
	 * @return void
	 */
    function handle_order_completed($order) {
        if( ($follower = $this->resolve_follower_from_order($order)) ) {
	        $this->save_transaction( $follower, $order );
        }
	}


    /**
     * Saves Follower's order transaction details to Diller Api.
     *
     * @param $follower Diller_Loyalty_Follower instance
     * @param $order WC_Order|int Order object or order ID
     *
     * @return bool|WP_Error
     */
    public function save_transaction($follower, $order) {
	    $log_message = '';

	    // Load order
	    if( !($order = ($order && $order instanceof WC_Order ) ? $order : new WC_Order($order)) ) return;

        if(get_post_meta($order->get_id(), "_diller_checkout_membership_consent", true) === 'Yes'){
	        $log_message = 'Customer joined LP via checkout';
	        $log_message .= (get_post_meta($order->get_id(), "_diller_checkout_purchase_history_consent", true) === 'Yes') ? ' and gave consent to save purchase history.' : '.';
        }

        $result = DillerLoyalty()->get_api()->save_follower_transactions($follower, $order);
        if(!is_wp_error($result)){

            // Transaction was successfully communicated, so we can safely save here the amount of points earned and remove aux. meta fields
            // This is mainly to cover the scenario where customer enrolled the LP at checkout.
            $order_total = get_post_meta( $order->get_id(), '_order_total', true );
            $currency_to_points_ratio = DillerLoyalty()->get_store()->get_currency_to_points_ratio();
            $points_earned = $order_total * $currency_to_points_ratio;
            update_post_meta($order->get_id(), '_diller_points', $points_earned);
	        delete_post_meta($order->get_id(), "_diller_checkout_membership_consent");
	        delete_post_meta($order->get_id(), "_diller_checkout_purchase_history_consent");

            // Relate order with current follower, while respecting original WC filter "woocommerce_checkout_customer_id"
	        $order->set_customer_id(apply_filters( 'woocommerce_checkout_customer_id', $follower->get_wp_user_id() ) );

            // Add a note to the order, to inform how many points were earned
	        $order_note = sprintf(
	            /* translators: This is the note text to add to the current order. 1: Amount of points earned in this purchase */
		        esc_html__( 'Loyalty Program - Customer earned %1$s points with this purchase.', 'diller-loyalty' ),
		        $points_earned
	        );
            if(sizeof($order->get_coupon_codes()) > 0){
	            $order_note .= sprintf(
	                /* translators: This is the text to append to the current order note, if coupons were applied. 1: This is the coupon codes for this order */
		            esc_html__( 'The following coupons were used: %1$s', 'diller-loyalty' ),
		            join(', ', $order->get_coupon_codes())
	            );
            }

	        $order->add_order_note($order_note);
	        $order->save();

            $log_message = sprintf("Transaction %s via %s for order# %d with status %s was saved.", $order->get_transaction_id(), $order->get_payment_method_title(), $order->get_id(), $order->get_status()) . $log_message;
            DillerLoyalty()->get_logger()->info($log_message, $follower);
            return true;
        }
        else{
            $log_message .= sprintf("Error while saving transaction %s via %s for order #%d with status %s. Reason: %s", $order->get_transaction_id(), $order->get_payment_method_title(), $order->get_id(), $order->get_status(), $result->get_error_message());
	        DillerLoyalty()->get_logger()->error($log_message, $follower, $result);
            return $result;
        }
	}

    private function display_my_account_profile_content() {

        // Some scenarios, like changing the phone number, after unsuscribing, etc we want to skip rendering the main form.
        if(isset($GLOBALS['diller_hide_profile_form']) && $GLOBALS['diller_hide_profile_form'] === true) return;

        $follower_data = array();
        $force_refresh = true;

        //Initialize form
        $form = new Diller_WC_Enrollment_Form('dillerLoyaltyProfileForm');
        $form->set_title(__('Loyalty Program', 'diller-loyalty'));
        $form->build_fields();

        if ($form->was_submitted() && $form->validate_request()){
            $form->save();
            $force_refresh = !$force_refresh;
        }

        if(DillerLoyalty()->user_has_joined()):
            $follower = DillerLoyalty()->get_current_follower($force_refresh);

            $follower_segments_data = Diller_Loyalty_Helpers::generate_form_data_for_segments($follower->get_segments());

            $follower_data = array_merge($follower_segments_data, array(
                "phone_country_code" => $follower->get_phone_country_code(),
                "phone_number" => $follower->get_phone_number(),
                "email" => $follower->get_email(),
                "first_name" => $follower->get_first_name(),
                "last_name" => $follower->get_last_name(),
                "gender" => $follower->get_gender(),
                "address" => $follower->get_address(),
                "postal_code" => $follower->get_postal_code(),
                "postal_city" => $follower->get_postal_city(),
                "country" => $follower->get_country(),
                "birth_date" => $follower->get_birth_date(),
                "purchase_history_consent_accepted" => $follower->get_purchase_history_consent_accepted(),
                "marketing_sms_consent_accepted" => $follower->get_marketing_sms_consent_accepted(),
                "marketing_email_consent_accepted" => $follower->get_marketing_email_consent_accepted(),
                "membership_consent_accepted" => $follower->get_membership_consent_accepted(),
                "department_ids" => $follower->get_department_ids()
            ));
        else:
            // Get data from Wordpress

            //Note 2 self: Even if the WP User hasn't bought anything yet and/or doesn't have the role "Customer", the line below will work.
            $wc_customer = new WC_Customer(wp_get_current_user()->ID);
            $follower_data = array(
                "email" => $wc_customer->get_email(),
                "first_name" => $wc_customer->get_first_name(),
                "last_name" => $wc_customer->get_last_name(),
                "address" => $wc_customer->get_billing_address(),
                "postal_code" => $wc_customer->get_billing_postcode(),
                "postal_city" => $wc_customer->get_billing_city(),
                "country" => $wc_customer->get_billing_country()
            );

            $full_phone_number = $wc_customer->get_billing_phone();
            if(!empty($full_phone_number)){
                $phone_number = Diller_Loyalty_Helpers::get_phone_number($full_phone_number);
                $phone_country_code = Diller_Loyalty_Helpers::get_phone_country_code($full_phone_number);
                $follower_data["phone_number"] = !is_wp_error($phone_number)? $phone_number : trim($full_phone_number);
                $follower_data["phone_country_code"] = !is_wp_error($phone_country_code)? $phone_country_code : DillerLoyalty()->get_store()->get_phone_default_country_code();
            }

        endif;

        // Pre-fill form fields
        $form->load_form_data($follower_data);
        $form->render();
	}

    private function display_my_account_unsubscribe_content() : bool {
        if(DillerLoyalty()->user_has_unsubscribed()):
            $profile_url = trailingslashit(wc_get_page_permalink( 'myaccount' )) . Diller_Loyalty_Configs::LOYALTY_PROFILE_ENDPOINT;
            $userid = DillerLoyalty()->get_current_follower()->get_wp_user_id();
            $profile_url = $profile_url . '/' . wp_nonce_url( "?action=enroll&user=$userid", 'enroll-user-' . $userid );

            // Handle re-subscribe
            if(isset($_GET["action"]) && $_GET["action"] === "enroll"){
                //Re-subscribe
                $action_userid = filter_var($_GET["user"], FILTER_SANITIZE_NUMBER_INT);
                if($action_userid !== false && (int)$action_userid == (int)$userid && wp_verify_nonce($_GET["_wpnonce"], 'enroll-user-' . $action_userid)){
                    return $GLOBALS['diller_hide_profile_form'] = false;
                }
            }

            // Show message, that the user has unsubscribed
            ?>
                <h4 class="diller-heading__title"><?php echo __('Loyalty Program','diller-loyalty'); ?></h4>
                <div class="diller-alert diller-alert--info diller-w-100">
                    <?php echo sprintf(
                        /* translators: 1: link to Terms & Conditions URL, 2: closing url */
                        esc_html__( 'You have unsubscribed the Loyalty Program. To enroll again and enjoy the benefits, please %1$sclick here%2$s', 'diller-loyalty' ),
                        '<a href="' . esc_url($profile_url) . '">',
                        '</a>'
                    ); ?>
                </div>
            <?php
            return $GLOBALS['diller_hide_profile_form'] = true;
	    endif;
        return false;
    }

    private function display_my_account_update_phone_content() {
        if(Diller_WC_Update_Phone_Form::phone_number_has_changed()){
            $form = new Diller_WC_Update_Phone_Form('dillerLoyaltyUpdatePhoneForm');
            $form->set_title(__('Loyalty Program', 'diller-loyalty'));
            $form->build_fields();

            if ($form->was_submitted() && $form->validate_request()){
               $form->save();
            }
            $form->render();
        }
	}

    private function get_order_points($order) {
        if (is_int( $order )) {
            $order = new WC_Order($order);
        }

        if(false === is_a($order, 'WC_Order') || !DillerLoyalty()->get_store()->get_point_system_enabled()) return 0;

        if(!($total_points = get_post_meta($order->get_id(), '_diller_points', true))){
            // Fallback
            $order_total = get_post_meta($order->get_id(), '_order_total', true);
            $currency_to_points_ratio = DillerLoyalty()->get_store()->get_currency_to_points_ratio();
            $total_points = ($order_total * $currency_to_points_ratio);
        }
        return $total_points;
	}


	/**
	 * Displays an element with the points earned for a given order.
	 *
	 * @param $order_id
	 *
	 */
	private function display_points_summary($order_id) {
		$total_points = $this->get_order_points($order_id);
		?>
        <div class="diller-woocommerce-section">
            <h2 class="diller-woocommerce-section__title"><?php echo esc_html__('Loyalty Program','diller-loyalty'); ?></h2>
            <div class="diller-woocommerce-section__body diller-flex-row">
                <span class="diller-w-50"><b><?php echo esc_html__('Points earned','diller-loyalty'); ?>: </b></span>
                <span class="diller-w-50"><b><?php echo $total_points; ?></b></span>
            </div>
        </div>
        <?php
	}

	/**
	 * Displays a CTA element, encouraging the Customer to join LP and displays the points it could have earned.
	 *
	 * @param $order_id
	 *
	 */
	private function display_join_loyalty_program_cta($order_id) {
        
        $total_points = $this->get_order_points($order_id);
        $join_url = (is_user_logged_in())
            ? esc_url(trailingslashit(wc_get_page_permalink( 'myaccount' )) . Diller_Loyalty_Configs::LOYALTY_PROFILE_ENDPOINT)
            : get_the_permalink(DillerLoyalty()->get_store()->get_enrollment_form_page_id());
        ?>
            <div class="diller-woocommerce-section">
                <h3 class="diller-woocommerce-section__title"><?php echo esc_html__('Join our Loyalty Program for exclusive benefits','diller-loyalty'); ?></h3>
                <div class="diller-woocommerce-section__body">
	                <?php printf(
	                    /* translators: 1: is a line break <br>. 2: is the points earned with this purchase */
		                esc_html__( 'Become a member of our loyalty program and enjoy benefits and offers that is only available for our members. With this purchase you could have earned %1$s points', 'diller-loyalty' ),
                        '<b>' . $total_points . '</b>'
	                );
	                ?>
                </div>
                <div class="diller-woocommerce-section__footer diller-flex-col">
                    <a href="<?php echo $join_url; ?>" class="diller-button diller-button--primary diller-button--round">
						<?php echo esc_html__('Subscribe','diller-loyalty'); ?>
                    </a>
                </div>
            </div>
		<?php
	}
}
