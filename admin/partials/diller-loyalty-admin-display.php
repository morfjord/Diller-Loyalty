<?php
    /**
     * Provide a admin area view for the plugin
     *
     * This file is used to markup the admin-facing aspects of the plugin.
     *
     * @link       https://diller.no/contact-us
     * @since      2.0.0
     *
     * @package    Diller_Loyalty
     * @subpackage Diller_Loyalty/admin/partials
     */

    // Exit if accessed directly
    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }

    // check if current user can authenticate
    if ( ! current_user_can( 'manage_options' ) ) {
        return new WP_Error( 'validation-error', __( "You don't have permission to authenticate Diller Loyalty Plugin.", 'diller-loyalty' ) );
    }

    //Get the active tab
    $default_tab = 'connect';
    $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : $default_tab;

    $store_settings = array();
    $store_configs = array(
        "external_form_signup_url" => get_the_permalink(DillerLoyalty()->get_store()->get_enrollment_form_page_id()),
        "external_my_account_url" => get_permalink( get_option('woocommerce_myaccount_page_id') ),
        "external_authorization_token" => "" 
    );

    if(DillerLoyalty()->get_auth()->is_authenticated()){
	    $store_settings['store_pin'] = DillerLoyalty()->get_auth()->get_store_pin();
	    $store_settings['api_key'] = DillerLoyalty()->get_auth()->get_api_key();

	    $store_configs = DillerLoyalty()->get_store()->get_configs();
    }
?>

    <div class="diller-settings-container">

        <div class="diller-settings-header" style="display: flex;justify-content: space-between;">
            <div>
                <img src="<?php echo esc_url(DILLER_LOYALTY_URL . '/assets/images/logodiller.svg'); ?>" alt="Logo"/>
                <span class="diller-header-text" style="margin-left: 25px;"><?php echo esc_html__("Thanks for installing Diller Loyalty", "diller-loyalty"); ?></span>
            </div>
            <div style="display: flex; flex-direction: column;">
	            <?php if(DillerLoyalty()->get_auth()->is_authenticated()): ?>
                    <span class="diller--success" style="color:#155724; font-weight: bold;">
                        <?php echo esc_html__("Store connected successfully", "diller-loyalty" ); ?> (<?php echo DillerLoyalty()->get_store()->get_store_name(); ?>)
                    </span>
	            <?php endif; ?>
                <span class="diller-header-text">
                    <?php echo esc_html__("Environment:", "diller-loyalty" ); ?> <?php echo DillerLoyalty()->get_environment(); ?>
                    <?php echo (DillerLoyalty()->get_store()->get_test_mode_enabled() ? '<span style="color:#b94a48; font-weight: bold;">('.__( "Test-mode", "diller-loyalty" ).'</span>)' : ''); ?>
                </span>
                <span class="diller-header-text"><?php echo esc_html__("Version:", "diller-loyalty" ); ?> <?php echo DillerLoyalty()->get_version("admin"); ?></span>
            </div>
        </div>

        <div class="diller-admin-notices diller-mb-5 diller-mt-5">
	        <?php Diller_Loyalty_Admin::get_instance()->show_wp_admin_notices(); ?>
        </div>

        <div class="diller-settings-main">
            <!-- TABS -->
            <nav class="nav-tab-wrapper">
                <a href="?page=<?php echo esc_attr(DILLER_LOYALTY_PLUGIN_NAME) ?>&tab=connect" class="nav-tab <?php echo ($tab === 'connect' ? 'nav-tab-active' : ''); ?>">Connect</a>
                <a href="?page=<?php echo esc_attr(DILLER_LOYALTY_PLUGIN_NAME) ?>&tab=settings" class="nav-tab <?php echo ($tab === 'settings' ? 'nav-tab-active' : ''); ?>">Settings</a>
            </nav>

            <!-- TAB CONTENT -->
            <div class="diller-settings-tab-content">
                <?php switch($tab) :
	                case 'settings':
	                default:
		                get_partial_view( 'diller-loyalty-admin', 'settings', $store_configs );
		                break;
	                case 'connect':
		                get_partial_view( 'diller-loyalty-admin', 'connect', $store_settings );
		                break;
                endswitch; ?>
            </div>
        </div>

        <div class="diller-settings-footer">

        </div>
    </div>
