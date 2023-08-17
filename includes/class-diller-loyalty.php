<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://diller.no/contact-us
 * @since      2.0.0
 *
 * @package    Diller_Loyalty
 * @subpackage Diller_Loyalty/includes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      2.0.0
 * @package    Diller_Loyalty
 * @subpackage Diller_Loyalty/includes
 * @author     Diller AS <dev@diller.no>
 */
final class Diller_Loyalty {

	/**
	 * Holds the class object.
	 *
	 * @access public
	 * @var object Instance of instantiated Diller_Loyalty class.
	 */
	public static $instance;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    2.0.0
	 * @access   protected
	 * @var      Diller_Loyalty_Loader $loader Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    2.0.0
	 * @access   protected
	 * @var      string $plugin_name The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The Authentication helper class of the plugin.
	 *
	 * @since    2.0.0
	 * @access   protected
	 * @var      Diller_Loyalty_Auth $auth The Authentication helper class.
	 */
	protected $auth;

	/**
	 * The logger class implmenting IDiller_Loyalty_Logger interface.
	 *
	 * @since    2.0.0
	 * @access   protected
	 * @var      IDiller_Loyalty_Logger $logger Logger class implementing interface IDiller_Loyalty_Logger.
	 */
	protected $logger;

	/**
	 * The Diller_Loyalty_Store.
	 *
	 * @since    2.0.0
	 * @access   protected
	 * @var      Diller_Loyalty_Store $store Diller_Loyalty_Store class.
	 */
	protected $store;

	/**
	 * The Diller_Loyalty_Woocommerce.
	 *
	 * @since    2.0.0
	 * @access   protected
	 * @var      Diller_Loyalty_Woocommerce $woocommerce Diller_Loyalty_Woocommerce class.
	 */
	protected $woocommerce;

	/**
	 * The current version of the plugin.
	 *
	 * @since    2.0.0
	 * @access   protected
	 * @var      string $version The current version of the plugin.
	 */
	protected $version;

	/**
	 * The API helper class of the plugin.
	 *
	 * @since    2.0.0
	 * @access   protected
	 * @var      Diller_Loyalty_Api $api The current API helper instance.
	 */
	protected $api;

	protected $environment;

	/**
	 * API base url.
	 *
	 * @since     2.2.5
	 * @access   protected
	 * @var      string    The API base url
	 */
	protected $api_base_url;


	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    2.0.0
	 */
	public function __construct() {
		// Instantiation in the singleton instance below
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, 'Cloning is forbidden.', '2.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 2.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, 'Unserializing instances of this class is forbidden.', '2.0' );
	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @access public
	 *
	 * @return object The Diller_Loyalty object.
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Diller_Loyalty ) ) {
			self::$instance = new Diller_Loyalty();

			global $wp_version;

			// Detect non-supported WordPress version and return early
			if ( version_compare( $wp_version, '5.2.4', '<' ) ) {
				DillerLoyalty()->get_logger()->error("Current WP version ({$wp_version}) is not supported. Minimum supported is 4.7");
				return null;
			}

			self::$instance->version = DILLER_LOYALTY_VERSION;
			self::$instance->environment = DILLER_LOYALTY_ENVIRONMENT;
			self::$instance->plugin_name = DILLER_LOYALTY_PLUGIN_NAME;
			self::$instance->api_base_url = DILLER_LOYALTY_API_BASE;

			self::$instance->load_dependencies();
			self::$instance->set_locale();

			self::$instance->auth = new Diller_Loyalty_Auth();
			self::$instance->logger = class_exists( 'woocommerce' ) ? new Diller_Loyalty_WC_Logger() : new Diller_Loyalty_Logger();
			self::$instance->store = new Diller_Loyalty_Store();
			self::$instance->woocommerce = new Diller_Loyalty_Woocommerce();

			if( self::$instance->get_store()->get_test_mode_enabled() ){
				self::$instance->environment = __( "Test-mode", "diller-loyalty" );
				self::$instance->api_base_url = DILLER_LOYALTY_TEST_API_BASE;
			}

			if(is_admin()) {
				self::$instance->define_admin_hooks();
			}

			if(DillerLoyalty()->get_auth()->is_authenticated()) {
				self::$instance->define_diller_hooks();
				self::$instance->define_public_hooks();

				if(is_admin()) {
					self::$instance->define_woocommerce_admin_hooks();
				}else{
					self::$instance->define_woocommerce_hooks();

					if(!Diller_Loyalty_Helpers::is_rest_request()) {
						self::$instance->define_shortcodes();
					}
				}
			}


			

			// Register the filters, shortcodes and actions with WordPress.
			self::$instance->loader->run();
		}

		return self::$instance;
	}


	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once( DILLER_LOYALTY_PATH . 'includes/class-diller-loyalty-loader.php' );

		/**
		 * The class responsible for logging plugin events and errors
		 */
		require_once( DILLER_LOYALTY_PATH . 'includes/class-diller-loyalty-logger.php' );

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once( DILLER_LOYALTY_PATH . 'includes/class-diller-loyalty-i18n.php' );

		/**
		 * The class responsible for the Diller Follower
		 */
		require_once( DILLER_LOYALTY_PATH . 'includes/class-diller-loyalty-follower.php' );

		/**
		 * The class responsible for the Diller stamps
		 */
		require_once( DILLER_LOYALTY_PATH . 'includes/class-diller-loyalty-stamp.php' );

		/**
		 * The class responsible for the Diller coupon
		 */
		require_once( DILLER_LOYALTY_PATH . 'includes/class-diller-loyalty-coupon.php' );

		/**
		 * The class responsible for the Diller Store
		 */
		require_once( DILLER_LOYALTY_PATH . 'includes/class-diller-loyalty-store.php' );

		/**
		 * The class responsible for the API authentication settings
		 */
		require_once( DILLER_LOYALTY_PATH . 'includes/class-diller-loyalty-auth.php' );

		/**
		 * The class responsible for performing all the API requests
		 */
		require_once( DILLER_LOYALTY_PATH . 'includes/class-diller-loyalty-api-request.php' );

		/**
		 * The class responsible for encapsulating the API method calls
		 */
		require_once( DILLER_LOYALTY_PATH . 'includes/class-diller-loyalty-api.php' );

		/**
		 * The class responsible for defining all Diller hooks.
		 */
		require_once( DILLER_LOYALTY_PATH . 'includes/class-diller-loyalty-hooks.php' );

		/**
		 * The class responsible for defining all hooks related to Woocommerce integration.
		 */
		require_once( DILLER_LOYALTY_PATH . 'includes/class-diller-loyalty-woocommerce.php' );

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once( DILLER_LOYALTY_PATH . 'admin/class-diller-loyalty-admin.php' );

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once( DILLER_LOYALTY_PATH . 'public/class-diller-loyalty-public.php' );

		/**
		 * The class responsible for defining utilities and helpers that can be shared across classes
		 */
		require_once( DILLER_LOYALTY_PATH . 'includes/class-diller-loyalty-utils.php' );

		/**
		 * The class responsible for handling REST requests for the plugin
		 */
		require_once( DILLER_LOYALTY_PATH . 'includes/webhooks/class-diller-rest-endpoints.php' );

		/**
		 * The class responsible for defining all forms and form fields
		 */
		require_once( DILLER_LOYALTY_PATH . 'includes/forms/fields/class-diller-form-element.php' );
		require_once( DILLER_LOYALTY_PATH . 'includes/forms/fields/class-diller-base-field.php' );
		require_once( DILLER_LOYALTY_PATH . 'includes/forms/fields/class-diller-text-field.php' );
		require_once( DILLER_LOYALTY_PATH . 'includes/forms/fields/class-diller-email-field.php' );
		require_once( DILLER_LOYALTY_PATH . 'includes/forms/fields/class-diller-hidden-field.php' );
		require_once( DILLER_LOYALTY_PATH . 'includes/forms/fields/class-diller-checkbox-field.php' );
		require_once( DILLER_LOYALTY_PATH . 'includes/forms/fields/class-diller-phone-field.php' );
		require_once( DILLER_LOYALTY_PATH . 'includes/forms/fields/class-diller-checkbox-multi.php' );
		require_once( DILLER_LOYALTY_PATH . 'includes/forms/fields/class-diller-date-field.php' );
		require_once( DILLER_LOYALTY_PATH . 'includes/forms/fields/class-diller-email-field.php' );
		require_once( DILLER_LOYALTY_PATH . 'includes/forms/fields/class-diller-number-field.php' );
		require_once( DILLER_LOYALTY_PATH . 'includes/forms/fields/class-diller-radio-field.php' );
		require_once( DILLER_LOYALTY_PATH . 'includes/forms/fields/class-diller-select-field.php' );
		require_once( DILLER_LOYALTY_PATH . 'includes/forms/fields/class-diller-submit-button.php' );
		require_once( DILLER_LOYALTY_PATH . 'includes/forms/trait-diller-form-scripts.php' );
		require_once( DILLER_LOYALTY_PATH . 'includes/forms/class-diller-form.php' );
		require_once( DILLER_LOYALTY_PATH . 'includes/forms/class-diller-enrollment-form.php' );

		/**
		 * The class responsible for defining all shortcodes that are used in wordpress
		 */
		require_once( DILLER_LOYALTY_PATH . 'includes/shortcodes/class-diller-enrollment-form-shortcode.php' );

		// Includes that are only required for Follower logged-in scenarios
		if(is_user_logged_in() && !is_admin()) {
			
			require_once( DILLER_LOYALTY_PATH . 'includes/forms/class-diller-wc-update-phone-form.php' );
			require_once( DILLER_LOYALTY_PATH . 'includes/forms/class-diller-wc-enrollment-form.php' );
			require_once( DILLER_LOYALTY_PATH . 'includes/forms/class-diller-refer-friend-form.php' );
			require_once( DILLER_LOYALTY_PATH . 'includes/shortcodes/class-diller-refer-friend-shortcode.php' );
		}

		/**
		 * Composer autoloader class for handling 3rd party dependencies
		 */
		if(file_exists(DILLER_LOYALTY_PATH . 'vendor/autoload.php' )){
			require_once( DILLER_LOYALTY_PATH . 'vendor/autoload.php' );
		}

		/**
		 * Because current theme's functions.php loads way after this plugin does, we will look for a special file called "diller-loyalty-overrides.php" in the theme's directory instead.
		 * If we find it we load it and execute the code inside. The goal behind it, is that a developer can declare some hooks, that can customize some plugin behavior. See README.MD under /sdk folder.
		 * Example: we could define some hooks like <code>diller_woocommerce_actions</code> or <code>diller_woocommerce_filters</code> that allow us to manipulate Diller's implementation
		 * of Woocommerce actions and filters.
		 */
		if( file_exists( get_stylesheet_directory() . '/' . DILLER_LOYALTY_PLUGIN_NAME . "-overrides.php" ) ){
			require_once get_stylesheet_directory() . '/' . DILLER_LOYALTY_PLUGIN_NAME . "-overrides.php";
		}


		// Load WP CLI specific files
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once( DILLER_LOYALTY_PATH . 'includes/CLI/class-diller-loyalty-cli-commands.php');

			WP_CLI::add_command('diller','Diller_Loyalty_CLI_Commands');
		}

		$this->loader = new Diller_Loyalty_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Diller_Loyalty_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    2.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new Diller_Loyalty_i18n();
		$this->loader->add_action( 'init', $plugin_i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = Diller_Loyalty_Admin::get_instance();

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'diller_loyalty_admin_menu' );

		$this->loader->add_filter( 'admin_body_class', $plugin_admin, 'diller_add_admin_body_class' );
	}


	/**
	 * Register all of the hooks related to the Diller the plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 */
	private function define_diller_hooks() {

		$diller_hooks = new Diller_Loyalty_Hooks();
		$this->loader->add_action('diller_api_follower_registered', $diller_hooks, 'handle_follower_updated', 10, 1);
		$this->loader->add_action('diller_api_follower_updated', $diller_hooks, 'handle_follower_updated', 10, 1);
		$this->loader->add_action('diller_api_follower_unsubscribed', $diller_hooks, 'handle_follower_unsubscribed', 10, 1);

		$this->loader->add_action('profile_update', $diller_hooks, 'handle_wp_user_profile_updated', 10, 2);
		$this->loader->add_action('wp_login', $diller_hooks, 'my_account_user_logged_in', 99, 2);
		$this->loader->add_action('save_post', $diller_hooks, 'handle_enrollment_form_page_changes', 99, 3);
		//$this->loader->add_action('template_redirect', $diller_hooks, 'enrollment_page_redirect_logged_in_user', 10, 1);

		$diller_rest_endpoints = new Diller_Loyalty_Rest_Endpoints();
		$this->loader->add_action('rest_api_init', $diller_rest_endpoints, 'register_diller_wordpress_rest_endpoints', 10, 1);
	}

	/**
	 * Register all of the hooks related to the WooCommerce integration functionality
	 * of the plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 */
	private function define_woocommerce_hooks() {
		$actions = array();
		$filters = array();

		// My Account Page
		$actions[] = array( 'hook' => 'init', 'callback' => 'my_account_page_custom_endpoints', 'priority' => 10, 'accepted_args' => 1 );
		$actions[] = array( 'hook' => 'woocommerce_account_dashboard', 'callback' => 'my_account_customize_dashboard', 'priority' => 10, 'accepted_args' => 0 );
		$filters[] = array( 'hook' => 'woocommerce_account_menu_items', 'callback' => 'my_account_add_menu_items', 'priority' => 10, 'accepted_args' => 1 );
		$actions[] = array( 'hook' => 'woocommerce_account_' . Diller_Loyalty_Configs::LOYALTY_PROFILE_ENDPOINT . '_endpoint', 'callback' => 'my_account_page_loyalty_profile_endpoint_content', 'priority' => 10, 'accepted_args' => 1 );
		$actions[] = array( 'hook' => 'woocommerce_account_' . Diller_Loyalty_Configs::LOYALTY_COUPONS_ENDPOINT . '_endpoint', 'callback' => 'my_account_page_coupons_endpoint_content', 'priority' => 10, 'accepted_args' => 1 );
		$actions[] = array( 'hook' => 'woocommerce_account_' . Diller_Loyalty_Configs::LOYALTY_FRIEND_REFERRAL_ENDPOINT . '_endpoint', 'callback' => 'my_account_page_friend_referral_endpoint_content', 'priority' => 10, 'accepted_args' => 1 );
		$actions[] = array( 'hook' => 'woocommerce_account_' . Diller_Loyalty_Configs::LOYALTY_SINGLE_COUPON_ENDPOINT . '_endpoint', 'callback' => 'my_account_page_single_coupon_endpoint_content', 'priority' => 10, 'accepted_args' => 1 );
		$filters[] = array( 'hook' => 'woocommerce_my_account_my_orders_columns', 'callback' => 'my_account_my_orders_customize_columns', 'priority' => 10, 'accepted_args' => 1 );
		$actions[] = array( 'hook' => 'woocommerce_my_account_my_orders_column_' . Diller_Loyalty_Configs::POINTS_EARNED_COLUMN_NAME, 'callback' => 'my_account_orders_display_earned_points_column', 'priority' => 10, 'accepted_args' => 1 );

		// Certain stores do not have stamp cards
		if(DillerLoyalty()->get_store()->get_stamps_enabled()){
			$actions[] = array( 'hook' => 'woocommerce_account_' . Diller_Loyalty_Configs::LOYALTY_STAMP_CARDS_ENDPOINT . '_endpoint', 'callback' => 'my_account_page_stamp_cards_endpoint_content', 'priority' => 10, 'accepted_args' => 1 );
			$actions[] = array( 'hook' => 'woocommerce_account_' . Diller_Loyalty_Configs::LOYALTY_SINGLE_STAMPCARD_ENDPOINT . '_endpoint', 'callback' => 'my_account_page_single_stampcard_endpoint_content', 'priority' => 10, 'accepted_args' => 1 );
		}

		// Cart / Coupons
		$actions[] = array( 'hook' => 'woocommerce_applied_coupon', 'callback' => 'applied_coupon', 'priority' => 10, 'accepted_args' => 1 );
		if(is_user_logged_in()) {
			$actions[] = array( 'hook' => 'woocommerce_before_cart', 'callback' => 'my_cart_show_available_coupons', 'priority' => 10, 'accepted_args' => 1 );
		}

		// Checkout
		$filters[] = array( 'hook' => 'woocommerce_checkout_fields', 'callback' => 'checkout_form_add_custom_fields', 'priority' => 10, 'accepted_args' => 1 );

		// Consent checkboxes placement. Defaults to billing
		$consent_fields_action = "woocommerce_after_checkout_billing_form";
		if(DillerLoyalty()->get_store()->get_join_checkboxes_placement() === "terms"){
			$consent_fields_action = 'woocommerce_checkout_terms_and_conditions';
		}
		$actions[] = array( 'hook' => $consent_fields_action, 'callback' => 'checkout_page_display_consent_fields', 'priority' => 10, 'accepted_args' => 1 );

		// Orders
		$actions[] = array( 'hook' => 'woocommerce_checkout_order_created', 'callback' => 'order_created_handle_membership_consent', 'priority' => 10, 'accepted_args' => 1 );
		$actions[] = array( 'hook' => 'woocommerce_checkout_order_processed', 'callback' => 'resolve_follower_from_order', 'priority' => 10, 'accepted_args' => 1 );
		$actions[] = array( 'hook' => 'woocommerce_order_status_completed', 'callback' => 'handle_order_completed', 'priority' => 10, 'accepted_args' => 1 );
		$actions[] = array( 'hook' => 'woocommerce_order_status_cancelled', 'callback' => 'cancel_order_transaction', 'priority' => 10, 'accepted_args' => 1 );
		$actions[] = array( 'hook' => 'woocommerce_after_order_details', 'callback' => 'order_details_display_loyalty_program_info', 'priority' => 10, 'accepted_args' => 1 );
		$actions[] = array( 'hook' => 'woocommerce_order_details_after_customer_details', 'callback' => 'order_details_display_loyalty_program_info', 'priority' => 10, 'accepted_args' => 1 );
		$filters[] = array( 'hook' => 'woocommerce_thankyou_order_received_text', 'callback' => 'update_order_received_text', 'priority' => 10, 'accepted_args' => 2 );


		// Check for Klarna checkout plugin existence.
		if(class_exists( 'KCO' ) && Diller_Loyalty_Helpers::is_payment_gateway_enabled('kco')) {
			// Ref: https://docs.woocommerce.com/document/klarna-checkout-hooks-actions-filters/
			// Klarna checkout form takes over the native WC checkout billing form. So we add here the custom fields for joining Diller here.
			$actions[] = array( 'hook' => 'kco_wc_after_order_review', 'callback' => 'checkout_page_display_consent_fields', 'priority' => 10, 'accepted_args' => 1 );
		}

		// Check for VIPPS checkout plugin
		// NB: KCO can also use Vipps for payments and expects this Vipps plugin to be activated and enabled.
		if(class_exists( 'Vipps' ) && Diller_Loyalty_Helpers::is_payment_gateway_enabled( 'vipps' ) ) {
			$actions[] = array( 'hook' => 'woo_vipps_express_checkout_orderspec_form', 'callback' => 'checkout_page_display_consent_fields', 'priority' => 10, 'accepted_args' => 1 );

			// This is for Vipps express checkout and will be triggered when returned from Vipps screen
			$actions[] = array( 'hook' => 'woo_vipps_wait_for_payment_page', 'callback' => 'resolve_follower_from_order', 'priority' => 10, 'accepted_args' => 1 );
		}

		/**
		 * Filters Diller actions that will be added to Woocommerce
		 * Because this run very early, to add this filter it's recommended that you create a file called "diller-loyalty-overrides.php" under your theme's folder.
		 * Diller will look for it when it loads the plugin dependencies
		 *
		 * @since 2.0.4
		 *
		 * @param array     $actions The 'redirect_to' URI sent via $_POST.
		 */
		$actions = apply_filters( 'diller_woocommerce_actions', $actions );
		foreach ($actions as $action){
			$this->loader->add_action($action['hook'], $action['component'] ?? $this->woocommerce, $action['callback'], $action['priority'], $action['accepted_args']);
		}

		/**
		 * Filters Diller filters that will be added to Woocommerce
		 * Because this run very early, to add this filter it's recommended that you create a file called "diller-loyalty-overrides.php" under your theme's folder.
		 * Diller will look for it when it loads the plugin dependencies
		 * @since 2.0.4
		 *
		 * @param array     $filters The 'redirect_to' URI sent via $_POST.
		 */
		$filters = apply_filters('diller_woocommerce_filters', $filters );
		foreach ($filters as $filter){
			$this->loader->add_filter($filter['hook'], $filter['component'] ?? $this->woocommerce, $filter['callback'], $filter['priority'], $filter['accepted_args']);
		}
	}

	/**
	 * Register all hooks related to WooCommerce integration functionality for the backoffice (is_admin() == true).
	 *
	 * @since    2.2.2
	 * @access   private
	 */
	private function define_woocommerce_admin_hooks() {
		$actions = array();
		$filters = array();

		// These are duplicated here on purpose, so they also trigger when in admin mode.
		$actions[] = array( 'hook' => 'woocommerce_order_status_completed', 'callback' => 'handle_order_completed', 'priority' => 10, 'accepted_args' => 1 );
		$actions[] = array( 'hook' => 'woocommerce_order_status_cancelled', 'callback' => 'cancel_order_transaction', 'priority' => 10, 'accepted_args' => 1 );

		//$actions[] = array( 'hook' => 'woocommerce_trash_order', 'callback' => 'cancel_order_transaction', 'priority' => 10, 'accepted_args' => 1 );
		//$actions[] = array( 'hook' => 'woocommerce_trash_coupon', 'callback' => 'cancel_coupon', 'priority' => 10, 'accepted_args' => 1 );

		// Check for VIPPS checkout plugin (wp-admin/admin_ajax.php)
		if(class_exists( 'Vipps' ) && Diller_Loyalty_Helpers::is_payment_gateway_enabled( 'vipps' ) ) {
			// This is for Vipps express checkout which is done asynchronously via "wp_ajax_do_express_checkout"
			$actions[] = array( 'hook' => 'woo_vipps_express_checkout_order_created', 'callback' => 'order_created_handle_membership_consent', 'priority' => 10, 'accepted_args' => 1 );
		}


		/**
		 * Filters Diller actions that will be added to Woocommerce, for the backoffice ( is_admin() == true )
		 * Because this run very early, to add this filter it's recommended that you create a file called "diller-loyalty-overrides.php" under your theme's folder.
		 * Diller will look for it when it loads the plugin dependencies
		 *
		 * @since 2.2.2
		 *
		 * @param array     $actions The 'redirect_to' URI sent via $_POST.
		 */
		$actions = apply_filters( 'diller_admin_woocommerce_actions', $actions );
		foreach ($actions as $action){
			$this->loader->add_action($action['hook'], $action['component'] ?? $this->woocommerce, $action['callback'], $action['priority'], $action['accepted_args']);
		}

		/**
		 * Filters Diller filters that will be added to Woocommerce, for the backoffice  ( is_admin() == true )
		 * Because this run very early, to add this filter it's recommended that you create a file called "diller-loyalty-overrides.php" under your theme's folder.
		 * Diller will look for it when it loads the plugin dependencies
		 * @since 2.2.2
		 *
		 * @param array     $filters The 'redirect_to' URI sent via $_POST.
		 */
		$filters = apply_filters('diller_admin_woocommerce_filters', $filters );
		foreach ($filters as $filter){
			$this->loader->add_filter($filter['hook'], $filter['component'] ?? $this->woocommerce, $filter['callback'], $filter['priority'], $filter['accepted_args']);
		}
	}

	/**
	 * Register all of the shortcodes related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 */
	private function define_shortcodes() {
		$short_code_forms = array(
			new Diller_Enrollment_Form_Shortcode()
		);

		// Shortcodes that are only instantiated if the user is logged in
		if(DillerLoyalty()->user_has_joined()) {
			$short_code_forms[] = new Diller_Refer_Friend_Form_Shortcode();
		}

		foreach ($short_code_forms as $index => $sc_form){
			$this->loader->register_shortcode( $sc_form->short_code_name, $sc_form, 'render' );

			// Enqueues scripts and styles.
			$this->loader->add_action( 'wp_enqueue_scripts', $sc_form, 'enqueue_styles');
			$this->loader->add_action( 'wp_enqueue_scripts', $sc_form, 'enqueue_scripts');
		}
	}


	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    2.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Diller_Loyalty_Public();

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles', 999 );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts', 999 );
	}

	/**
	 * Register all of the hooks used for development and debugging purposes
	 *
	 * @since    2.0.0
	 * @access   private
	 */
	private function define_development_hooks() {
		$dev_hooks_file = DILLER_LOYALTY_PATH . 'includes/class-diller-loyalty-development.php';

		if(file_exists( $dev_hooks_file ) ) {
			require_once($dev_hooks_file);

			$plugin_dev = new Diller_Loyalty_Development();

			$this->loader->add_action( 'registered_taxonomy', $plugin_dev, 'buffer_start_relative_url' );
			$this->loader->add_action( 'shutdown', $plugin_dev, 'buffer_end_relative_url' );
		}
	}

	/**
	 * Run the loader to execute all the hooks with WordPress.
	 *
	 * @since    2.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     2.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     2.0.0
	 * @return    Diller_Loyalty_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin. This equivalent of using constant <code>DILLER_LOYALTY_VERSION</code> directly. But,
	 * if in development mode and <code>$context</code> is "assets" returns a unique version made of the actual plugin version + time() to prevent caching when in dev mode.
	 *
	 * @param string $context
	 *
	 * @return    string    The version number of the plugin.
	 * @since     2.0.0
	 */
	public function get_version($context = "") {
		return ($context !== "assets" || DILLER_LOYALTY_MODE !== "development")
			? DILLER_LOYALTY_VERSION
			: DILLER_LOYALTY_VERSION.'.'.time();
	}

	/**
	 * Retrieve the Authentication helper class
	 *
	 * @since     2.0.0
	 * @return    Diller_Loyalty_Auth    The Authentication helper class
	 */
	public function get_auth() {
		return $this->auth;
	}

	/**
	 * Retrieve the Logger class
	 *
	 * @since     2.0.0
	 * @return    Diller_Loyalty_Logger    The logger helper class
	 */
	public function get_logger() {
		return $this->logger;
	}

	/**
	 * Retrieve the API base url. If test mode is enabled, the test api url is returned.
	 *
	 * @since     2.2.5
	 * @return    string    The API base url
	 */
	public function get_api_base_url() {
		return $this->api_base_url;
	}

	/**
	 * Retrieve the current enviroment. eg production, pre-release, development
	 *
	 * @since     2.0.0
	 * @return    string    The current environment
	 */
	public function get_environment() {
		return $this->environment;
	}

	/**
	 * Retrieve the Diller_Loyalty_Store class
	 *
	 * @since     2.0.0
	 * @return    Diller_Loyalty_Store    The Diller_Loyalty_Store class
	 */
	public function get_store() {
		return $this->store;
	}

	/**
	 * Retrieve the Diller_Loyalty_Woocommerce class
	 *
	 * @since     2.0.0
	 * @return    Diller_Loyalty_Woocommerce    The Diller_Loyalty_Woocommerce class
	 */
	public function get_woocommerce() {
		return $this->woocommerce;
	}

	/**
	 * Retrieves the Diller_Loyalty_Follower cached object for a given WP user id.
	 * Optionally allows for forcing the retrieval of a fresh copy of the Follower data from the API server
	 *
	 * @param bool $force_refresh
	 *
	 * @return Diller_Loyalty_Follower|mixed|WP_Error
	 */
	public function get_current_follower(bool $force_refresh = false) {
		return $this->get_follower(get_current_user_id(), $force_refresh);
	}

	/**
	 * Tries to retrieve a Diller_Loyalty_Follower from an order.
	 * First tries via order WP_User id (if not guest), otherwise tries via phone number used in the order.
	 *
	 * @param WC_Order|string $order id or object
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function get_follower_by_order($order) {
		if( !($order = ($order && $order instanceof WC_Order )? $order : new WC_Order($order)) ) return null;

		// By order id or email
		$order_user = $order->get_user();
		$order_user = ($order_user && is_a($order_user, 'WP_User'))? $order_user : get_user_by('email', $order->get_billing_email());
		if($order_user && $order_user->exists()){
			return DillerLoyalty()->get_follower( $order_user->ID, true);
		}

		// By user
		$billing_phone_number = $order->get_billing_phone();
		$country = $order->get_billing_country(); // Expecting 2 letters ISO Code
		$phone_country_code = Diller_Loyalty_Helpers::get_phone_country_code( $billing_phone_number, $country );
		$phone_number = Diller_Loyalty_Helpers::get_phone_number( $billing_phone_number, $country );
		$phone_country_code = !is_wp_error( $phone_country_code ) ? $phone_country_code : $this->get_store()->get_phone_default_country_code();
		if( is_wp_error($phone_number) ) return null;

		$follower = $this->get_api()->get_follower( $phone_country_code, $phone_number);
		if(!is_wp_error($follower) ){
			return $follower;
		}
		else{
			return (new Diller_Loyalty_Follower())->set_full_phone_number($phone_country_code, $phone_number);
		}
	}

	/**
	 * Retrieves the Diller_Loyalty_Follower cached object for a given WP user id.
	 * Optionally allows for forcing the retrieval of a fresh copy of the Follower data from the API server
	 *
	 * @param int $wp_user_id
	 * @param bool $force_refresh
	 *
	 * @return Diller_Loyalty_Follower|mixed|WP_Error
	 */
	public function get_follower(int $wp_user_id, bool $force_refresh = false) {
		$follower_cache_key = 'diller_' . DillerLoyalty()->get_site_prefix() . 'follower_' . $wp_user_id;
		$follower = wp_cache_get( $follower_cache_key, 'diller' );

		// If nothing is found, build the object.
		if( false === $follower){
			$follower = new Diller_Loyalty_Follower();
			$follower->load_data($wp_user_id);

			if(true === $force_refresh ){
				// Remove any stale data saved in WP from before.
				delete_user_meta($wp_user_id, DillerLoyalty()->get_follower_meta_key());

				// Refresh follower's data with a new copy from the server
				$follower = $this->get_api()->get_follower_details( $follower );
				if ( !is_wp_error( $follower ) ) {
					$follower->set_wp_user_id($wp_user_id);
					$follower->save(); // Save data on user_meta
				}
				else{
					$follower = new Diller_Loyalty_Follower();
				}
			}

			// Cache the whole Follower object in the cache and store it for 5 minutes (300 secs).
			wp_cache_set( $follower_cache_key, $follower, 'diller', 5 * MINUTE_IN_SECONDS );
		}

		return $follower;
	}

	public function is_network_admin() {
		return is_multisite() && is_network_admin();
	}

	/**
	 * Returns if the a given WP_User has joined the Loyalty Program or not, by reading the respective meta key value.
	 * If a $wp_user_id is provided it will check for the WP User with the given id other it will check for the current logged-in user.
	 * If $force_verification is set to true, it will check it through the API remotely
	 *
	 * @param int $wp_user_id
	 *
	 * @param bool $force_refresh
	 *
	 * @return bool
	 */
	public function user_has_joined(int $wp_user_id = 0, bool $force_refresh = false){
		$user = ($wp_user_id > 0) ? get_user_by('id', $wp_user_id) : wp_get_current_user();

		if(!$user->exists() || !metadata_exists( 'user', $user->ID, DillerLoyalty()->get_follower_meta_key())) return false;

		$force_refresh |= $user->ID !== get_current_user_id();
		return DillerLoyalty()->get_follower($user->ID, $force_refresh)->get_membership_consent_accepted() == 'Yes';
	}

	/**
	 * Checks if a given user ID or the current logged-in user (default) has unsubscribed the LP
	 * This function reads the user meta key "_diller_{wp_site_prefix}unsubscribed_datetime"
	 *
	 * @param int $wp_user_id
	 *
	 * @return bool
	 */
	public function user_has_unsubscribed(int $wp_user_id = 0){
		$curr_env_prefix = DillerLoyalty()->get_site_prefix();
		$user = ($wp_user_id > 0) ? get_user_by('id', $wp_user_id) : wp_get_current_user();
		return $user && is_a( $user, 'WP_User' ) && get_user_meta($user->ID, "_diller_{$curr_env_prefix}unsubscribed_datetime", true) != '';
	}

	/**
	 * Get the API instance.
	 *
	 * @return Diller_Loyalty_Api
	 */
	public function get_api() {
		if ( ! isset( $this->api ) ) {
			$this->api = new Diller_Loyalty_Api();
		}
		return $this->api;
	}

	/**
	 * Returns the current WP table prefix. It's sensible to whether we're running a single site or multi-site environment.
	 * Eg. for MS wp_2_
	 * Eg. for SS wp_
	 *
	 *
	 * @return string
	 */
	public function get_site_prefix() {
		global $wpdb;
		return $wpdb->get_blog_prefix();
	}

	/**
	 * Returns the meta key name that stores Follower serialized data.
	 * Eg. it will return _diller_wp_follower for SS and _diller_wp_2_follower
	 *
	 * @return string
	 */
	public function get_follower_meta_key() {
		return "_diller_" . $this->get_site_prefix() . "follower";
	}

	/**
	 * Updated the user meta fields values for the Diller_Loyalty_Follower instance passed in
	 *
	 * @return Diller_Loyalty_Follower
	 */
	public function update_wp_follower_metadata(Diller_Loyalty_Follower $follower): void {
		// If applicable, removes the aux. meta key flag, that registers if the follower has ever unsubscribe, via My Account
		if ( $follower->get_membership_consent_accepted() === 'Yes' ) {
			delete_user_meta( $follower->get_wp_user_id(), "_diller_" . $this->get_site_prefix() . "unsubscribed_datetime" );
		}
	}

	/**
	 * Creates or Updates a Wordpress user account based on the passed Follower's object data and returns the same object updated
	 *
	 * @param Diller_Loyalty_Follower $follower
	 *
	 * @return Diller_Loyalty_Follower $follower Updated Follower Object
	 */
	public function create_or_update_wp_user_account(Diller_Loyalty_Follower $follower): Diller_Loyalty_Follower {

		$existing_wp_user = get_user_by('email', $follower->get_email());
		if(!$existing_wp_user || !is_a($existing_wp_user, 'WP_User')) {

			// Ensure password
			if(empty($follower->get_password())){
				$follower->set_password( wp_generate_password( 12, true, false ) );
			}

			// Create New WP User
			$wp_user_id = wp_insert_user(array(
				'first_name'        => $follower->get_first_name(),
				'last_name'         => $follower->get_last_name(),
				'user_login'        => $follower->get_email(),
				'user_pass'         => $follower->get_password(),
				'user_email'        => $follower->get_email(),
				'user_registered'   => date('Y-m-d H:i:s'),
				'role'              => 'customer'
			));

			//Set langugae
			update_user_meta($wp_user_id, 'lang', get_bloginfo("language"));
		}
		else{
			$wp_user_id = $existing_wp_user->ID;
		}

		if (is_multisite() && !is_user_member_of_blog( $wp_user_id, get_current_blog_id() ) ) {
			add_user_to_blog(get_current_blog_id(), $wp_user_id, 'customer');
		}

		$follower->set_wp_user_id($wp_user_id);
		$follower->save();

		// This always get called when a WP user is created or edited
		DillerLoyalty()->update_wp_follower_metadata($follower);

		return $follower;
	}

	/**
	 * Creates or updates the enrollment page, with the shortcode [diller_loyalty_enrollment_form] inside that will use used to render "innmelding-kundeklubb" form
	 * Usually is accessible using https://shop_url/innmelding-kundeklubb
	 *
	 * @return int|WP_Error The updated/created page id or WP_Error object
	 */
	public function maybe_create_or_update_enrollment_page() {
		$args = array();
		$enrollment_page_id = 0;
		$enrollment_page = get_page_by_path( DILLER_LOYALTY_ENROLLMENT_PAGE_SLUG, 'page' );
		if(!$enrollment_page) {
			$enrollment_page_title = __( 'Join Loyalty Program', 'diller-loyalty' );
			$enrollment_page = get_page_by_title( $enrollment_page_title, 'page' );

			// Creates or updates a page, with the shortcode [diller_loyalty_enrollment_form] inside that will use used to render "innmelding-kundeklubb" form
			// Eg. http://localhost:8080/innmelding-kundeklubb
			$args = array(
				'post_title'   => $enrollment_page_title,
				'post_name'    => DILLER_LOYALTY_ENROLLMENT_PAGE_SLUG,
				'post_content' => '[' . Diller_Loyalty_Configs::LOYALTY_ENROLLMENT_FORM_SHORTCODE . ']',
				'post_status'  => 'publish',
				'post_date'    => date( 'Y-m-d H:i:s' ),
				'post_author'  => get_current_user_id(),
				'post_type'    => 'page',
			);
		}

		if ($enrollment_page) {
			$enrollment_page_id = $enrollment_page->ID;
			if ( !preg_match( '/\[' . Diller_Loyalty_Configs::LOYALTY_ENROLLMENT_FORM_SHORTCODE . '\]/m', $enrollment_page->post_content ) ) {
				$args['ID']           = $enrollment_page_id;
				$args['post_content'] = $enrollment_page->post_content . PHP_EOL . '[' . Diller_Loyalty_Configs::LOYALTY_ENROLLMENT_FORM_SHORTCODE . ']';
				wp_update_post( $args );
			}
		} else {
			$enrollment_page_id = wp_insert_post( $args );
		}

		// Save the page id as part of the store settings to be used later
		$store_server_configs = DillerLoyalty()->get_store()->get_configs( true );
		DillerLoyalty()->get_store()->save_configs(array_merge($store_server_configs, array(
				"enrollment_form_page_id" => $enrollment_page_id
			)
		));

		return $enrollment_page_id;
	}

	public function add_notice($group = 'diller', $status = 'success', $error = '') {
		global $diller_view_data;
		$diller_view_data = (isset($diller_view_data))? $diller_view_data : array();
		$diller_view_data = array_merge($diller_view_data, array(
			//Eg. "diller" => array("error" => "Error description goes here")
			$group => array( $status => array( is_wp_error($error) ? $error->get_error_message() : $error ) )
		));
	}


	public function display_notices($group = 'diller', $return = true) {
		global $diller_view_data;
		if(!isset($diller_view_data)){
			_doing_it_wrong( 'display_notice', 'You can only call display_notice() after using add_notice()', DILLER_LOYALTY_VERSION);
		}

		$html = '';

		if(isset($diller_view_data[$group]["error"]) && sizeof($diller_view_data[$group]["error"]) > 0){
			$html = '<div class="diller-alert diller-alert--danger">';

			if(sizeof($diller_view_data[$group]["error"]) > 1){
				$html .= '	<ul>';
				$html .= '		<li>' . implode( '</li><li>', $diller_view_data[ $group ]["error"] ) . '</li>';
				$html .= '	</ul>';
			}
			else{
				$html .= '  <span>' . join("<br/>", $diller_view_data[ $group ]["error"]) .'</span>';
			}

			$html .= '</div>';

			// Once, displayed we can remove them
			unset($diller_view_data[$group]["error"]);
		}

		if(isset($diller_view_data[$group]["success"]) && sizeof($diller_view_data[$group]["success"]) > 0){
			$html = '<div class="diller-alert diller-alert--success">';
			if(sizeof($diller_view_data[$group]["success"]) > 1){
				$html .= '	<ul>';
				$html .= '		<li>' . implode( '</li><li>', $diller_view_data[ $group ]["success"] ) . '</li>';
				$html .= '	</ul>';
			}
			else{
				$html .= '  <span>' . join("<br/>", $diller_view_data[ $group ]["success"]) .'</span>';
			}

			$html .= '</div>';

			// Once, displayed we can remove them
			unset($diller_view_data[ $group ]["success"]);
		}

		if(!$return) {
			echo $html;
			return true;
		}

		return $html;
	}

	public function has_notice($group = 'diller') {
		global $diller_view_data;
		return isset($diller_view_data) && isset($diller_view_data[$group]) && sizeof($diller_view_data[$group]) > 0;
	}
}
