<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://diller.no/contact-us
 * @since      2.0.0
 *
 * @package    Diller_Loyalty
 * @subpackage Diller_Loyalty/admin
 */

/**
 * The admin-specific functionality of the plugin, like settings, configs and enabling/disabling of features
 *
 * @package    Diller_Loyalty
 * @subpackage Diller_Loyalty/admin
 * @author     Diller AS <dev@diller.no>
 */
class Diller_Loyalty_Admin {

	/**
	 * Holds the class object.
	 *
	 * @access public
	 * @var object Instance of instantiated Diller_Loyalty_Admin class.
	 */
	public static $instance;

	private $notices;

	public function add_notice($message, $category){
		$this->notices[] = array(
			"message" => $message,
			"category" => $category
		);
	}

	public function __construct() {
		$this->notices = array();

		$this->maybe_handle_settings_form_submission();
	}


	/**
	 * Returns the singleton instance of the class.
	 *
	 * @access public
	 *
	 * @return object The Diller_Loyalty_Admin object.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Diller_Loyalty_Admin ) ) {
			self::$instance = new Diller_Loyalty_Admin();
		}
		return self::$instance;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    2.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( DILLER_LOYALTY_PLUGIN_NAME.'-admin', trailingslashit( DILLER_LOYALTY_URL )    . 'assets/css/diller-loyalty-admin.css', array(), DillerLoyalty()->get_version("assets"), 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    2.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( DILLER_LOYALTY_JS_VENDORS_BUNDLE_HANDLE, trailingslashit( DILLER_LOYALTY_URL )   . 'assets/js/vendors-bundle.js', array( 'jquery' ), DillerLoyalty()->get_version("assets"), false );
		wp_enqueue_script( 'diller-loyalty-admin-vendors-bundle', trailingslashit( DILLER_LOYALTY_URL )   . 'assets/js/diller-loyalty-admin-bundle.js', array( 'jquery', DILLER_LOYALTY_JS_VENDORS_BUNDLE_HANDLE ), DillerLoyalty()->get_version("assets"), false );

		// Create stdClass that represents dynamic data needed for frontend interaction with javascript and this form
		$js_params = new stdClass();
		$js_params->version = DillerLoyalty()->get_version();
		$js_params->restNonce = wp_create_nonce('wp_rest');
		$js_params->pluginUrl = DILLER_LOYALTY_URL;
		$js_params->phone = new stdClass();
		$js_params->phone->preferredCountries = DillerLoyalty()->get_store()->get_phone_preferred_countries();
		$js_params->phone->allowedCountries = DillerLoyalty()->get_store()->get_phone_countries();

		wp_add_inline_script('diller-loyalty-admin-vendors-bundle', "window.Diller_Loyalty_Admin = " . json_encode($js_params) .";", 'before' );
	}

	/**
	 * Custom hook, that appends a Diller class to the body and make it blue
	 * @param $classes
	 *
	 * @return mixed|string
	 */
	public function diller_add_admin_body_class( $classes ) {
		return (!empty($_GET["page"]) && $_GET["page"] == DILLER_LOYALTY_PLUGIN_NAME)
			? ($classes .= ' diller-settings ')
			: $classes;
	}

	public function diller_loyalty_admin_menu() {

		function diller_loyals_admin_settings_page() {
			require_once DILLER_LOYALTY_PATH . 'admin/diller-loyalty-admin-utils.php';
			require_once DILLER_LOYALTY_PATH . 'admin/partials/diller-loyalty-admin-display.php';
		}

		add_menu_page(__('Diller Loyalty', 'diller-loyalty'), __('Diller Loyalty', 'diller-loyalty'), 'manage_options', 'diller-loyalty', 'diller_loyals_admin_settings_page', trailingslashit(DILLER_LOYALTY_URL). 'assets/images/diller_white.svg', 68);

		//add_submenu_page( 'diller-loyalty', __('Settings', 'diller-loyalty'), __('Diller Loyalty Settings', 'diller-loyalty'), 'manage_options', 'diller-loyalty-settings', 'diller_loyals_admin_settings_page' );
	}

	/**
	 * Display WP-style notifications in WP admin
	 */
	public function show_wp_admin_notices() {
		if(sizeof($this->notices) == 0) return;

		$html = '
        <div class="notice notice-%s is-dismissible">
            <p><strong>%s</strong></p>
            <button type="button" class="notice-dismiss">
                <span class="screen-reader-text">' . esc_html__('Dismiss this notice.', 'diller-loyalty') . '</span>
            </button>
        </div>
        ';

		foreach ($this->notices as $index => $notice) {
			echo sprintf($html, $notice["category"], esc_html__($notice["message"]));
		}
	}


	public function maybe_handle_settings_form_submission() {
		if(empty($_POST["diller_admin_settings_nonce"]) || !wp_verify_nonce(sanitize_text_field($_POST["diller_admin_settings_nonce"]), "diller_save_admin_settings")) return;

		$this->handle_connect_store_form_submission();
		$this->handle_settings_form_submission();
	}

	private function handle_connect_store_form_submission() {

		if (isset($_POST["diller_connect_store_submit"]) && sanitize_text_field($_POST["diller_connect_store_submit"]) ?? false):
			$store_pin = sanitize_text_field( $_POST["diller_store_pin"] );
			$api_key = sanitize_text_field( $_POST["diller_x_api_key"] );

			// Check if we've any firewall of anything else blocking our call to the API
			$blocked = Diller_Loyalty_Helpers::is_request_blocked(DILLER_LOYALTY_API_PROTOCOL . trailingslashit(DillerLoyalty()->get_api_base_url()));
			if (is_wp_error( $blocked )  ) {
				$this->add_notice( $blocked->get_error_message(), "error" );
				return;
			}

			// Authenticate
			$result = DillerLoyalty()->get_auth()->authenticate($api_key, $store_pin);
			if (!is_wp_error($result)) {
				$store_server_configs = DillerLoyalty()->get_store()->get_configs( true );

				//Get store details, such as language, preferences, etc
				$result = DillerLoyalty()->get_api()->get_store_details();
				if ( ! is_wp_error( $result ) ) {
					DillerLoyalty()->get_store()->save_configs( array_merge( $store_server_configs, $result ) );

					// Update store details, with the params from WP
					$result = DillerLoyalty()->get_api()->update_store_details( array(
							'external_my_account_url'      => '/' . get_post_field( 'post_name', get_option( 'woocommerce_myaccount_page_id' ) ),
							'external_form_signup_url'     => '/' . get_post_field( 'post_name', DillerLoyalty()->get_store()->get_enrollment_form_page_id() ), // Get only post slug
							'external_authorization_token' => '', 
						)
					);

					if ( !is_wp_error( $result ) ) {
						$this->add_notice( __( "Store connected successfully", "diller-loyalty" ), "success" );

						// On activation, set this temporary flag to true, so that we flush rewrite rules for our custom endpoints just once
						set_transient( 'diller_flush_rewrite_rules', 'Yes', 60 );

						DillerLoyalty()->maybe_create_or_update_enrollment_page();
					}
				}
			}

			if( is_wp_error( $result )){
				$this->add_notice( sprintf(
					/* translators: %s: Error description provided by the API. */
					__( "Could not connect store. Details: %s", "diller-loyalty" ),
					$result->get_error_message()
				), "error" );
			}
		endif;
	}

	private function handle_settings_form_submission() {

		if (isset($_POST["diller_admin_settings_submit"]) && sanitize_text_field($_POST["diller_admin_settings_submit"]) ?? false):

			$new_configs = array();
			$new_configs["test_mode_enabled"] = filter_var($_POST["test_mode_enabled"] ?? false, FILTER_VALIDATE_BOOLEAN);
			$new_configs["stamps_enabled"] = filter_var($_POST["stamps_enabled"] ?? false, FILTER_VALIDATE_BOOLEAN);
			$new_configs["enable_recaptcha"] = filter_var($_POST["enable_recaptcha"] ?? false, FILTER_VALIDATE_BOOLEAN);

			$new_configs["phone"] = array();
			$new_configs["phone"]["enable_number_lookup"] = filter_var($_POST["phone"]["enable_number_lookup"], FILTER_VALIDATE_BOOLEAN);
			$new_configs["phone"]["intl_tel_input_plugin_enabled"] = filter_var($_POST["phone"]["intl_tel_input_plugin_enabled"], FILTER_VALIDATE_BOOLEAN);
			$new_configs["phone"]["country_option"] = sanitize_text_field($_POST["phone"]["country_option"]); // Values can be all|all_except|specific
			$new_configs["phone"]["countries"] = array_map( 'sanitize_text_field', isset( $_POST["phone"]["countries"] ) ? (array) $_POST["phone"]["countries"] : array() );
			$new_configs["phone"]["default_country_code"] = sanitize_text_field($_POST["phone"]["default_country_code"]);
			$new_configs["phone"]["preferred_countries"] = array_map( 'sanitize_text_field', isset( $_POST["phone"]["preferred_countries"] ) ? (array) $_POST["phone"]["preferred_countries"] : array() ); // Will make these to show up first in the phone input field

			$new_configs["default_postal_code_format"] = sanitize_text_field($_POST["default_postal_code_format"] ?? '');
			$new_configs["default_date_placeholder"] = sanitize_text_field($_POST["default_date_placeholder"] ?? '');
			$new_configs["min_enrollment_age"] = sanitize_text_field($_POST["min_enrollment_age"] ?? 15);
			$new_configs["join_checkboxes_placement"] = sanitize_text_field($_POST["join_checkboxes_placement"] ?? '');

			$store_configs = wp_parse_args($new_configs, DillerLoyalty()->get_store()->get_configs(true));

			DillerLoyalty()->get_store()->save_configs($store_configs);
			
			$this->add_notice( __( "Settings saved", "diller-loyalty" ), "success" );

		endif;
	}
}
