<?php
/**
 * API Request class.
 *
 * @package    Diller_Loyalty
 * @subpackage Diller_Loyalty/includes
 * @author     Diller AS <dev@diller.no>
 */
final class Diller_Loyalty_API_Request {

	/**
	 * Base API route.
	 *
	 * @var string
	 */
	public $base = '';

	/**
	 * Current API route.
	 *
	 * @var bool|string
	 */
	public $route = false;

	/**
	 * Full API URL endpoint.
	 *
	 * @var bool|string
	 */
	public $url = false;

	/**
	 * Current API method.
	 *
	 * @var bool|string
	 */
	public $method = false;

	/**
	 * API Key.
	 *
	 * @var bool|string
	 */
	public $api_key = false;

	/**
	 * API Key.
	 *
	 * @var bool|string
	 */
	public $api_version = false;

	/**
	 * API StoreID.
	 *
	 * @var int|string
	 */
	public $store_pin = 0;

	/**
	 * API return.
	 *
	 * @var bool|string
	 */
	public $return = false;

	/**
	 * Additional data to add to request body
	 *
	 * @var array
	 */
	protected $additional_data = array();


	/**
	 * Primary class constructor.
	 *
	 * @param string $route  The API route to target.
	 * @param array  $args   Array of API credentials.
	 * @param string $method The API method.
	 * @param string $method The API version (eg. PosV1, PosV2, V3).
	 */
	public function __construct( $route, $data = array(), $method = 'POST', $api_version = 'PosV1' ) {

		// Set class properties.
		$this->base      = trailingslashit(DillerLoyalty()->get_api_base_url() . '/api');
		$this->route     = $route;
		$this->protocol  = DILLER_LOYALTY_API_PROTOCOL;
		$this->method    = !empty( $method )? $method  : 'GET';
		$this->api_version = $api_version;
		$this->url = trailingslashit( $this->protocol . $this->base . $this->api_version . $this->route );

		// Get API creds from Diller settings
		$this->api_key   = DillerLoyalty()->get_auth()->get_api_key();
		$this->store_pin = DillerLoyalty()->get_auth()->get_store_pin();

		// Check if overriding the default creds. Eg. when connecting the store
		if(!empty( $data['x_api_key'] )){
			$this->api_key = $data['x_api_key'];
			unset($data['x_api_key']);
		}
		if(!empty( $data['store_pin'] )){
			$this->store_pin = $data['store_pin'];
			unset($data['store_pin']);
		}

		$this->additional_data = $data;
	}

	/**
	 * Processes the API request.
	 *
	 * @return mixed $value The response to the API call.
	 */
	public function request() {

		// Build the body of the request.
		$body = array_merge($this->additional_data, array(
			'store_id' => $this->store_pin, // Diller API v1 expects the param store_id but the value is actually store_pin
			'subscribe_type' => 'WOOCOMMERCE',
			'subscribe_by' => 'WEBSHOP'
			//'timezone' => date('e'),
			//'ip'] => !empty( $_SERVER['SERVER_ADDR'] ) ? $_SERVER['SERVER_ADDR'] : ''
		));

		// cache buster for GETs.
		if ( 'GET' == $this->method ) {
			$body['time']   = time();
		}

		// Build the headers of the request.
		$headers = array(
			'Content-Type'          => 'application/json; charset=utf-8',
			'Cache-Control'         => 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0',
			'Pragma'		        => 'no-cache',
			'Expires'		        => 0
		);

		if ( $this->api_key ) {
			$headers['x-api-key'] = $this->api_key;
		}

		// Setup data to be sent to the API.
		$data = array(
			'headers'    => $headers,
			'body'       => ($this->method !== "GET") ? wp_json_encode( $body ) : $body,
			'timeout'    => 5000,
			'user-agent' => 'DILLER/' . DILLER_LOYALTY_VERSION,
		);

		// Perform the query and retrieve the response.
		$query_string = "";
		if($this->method == "GET") {
			$query_string .= "?" . http_build_query( $body, '', '&' );
		}

		

		$response = ($this->method == "GET")
			? wp_remote_get( esc_url_raw( $this->url ) . $query_string, $data )
			: wp_remote_post( esc_url_raw( $this->url ) . $query_string, $data );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$http_response_code = wp_remote_retrieve_response_code( $response );
		$response_body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Bail out early if there are any errors.
		if ( is_wp_error( $response_body ) ) {
			return $response_body;
		}

		// If not a 200 http status header or not a 200 Diller API status, send back error.
		if ( $http_response_code != 200) {
			switch ((int)$http_response_code){
				case 401:
					return new WP_Error( 'api-error', __( 'Invalid authentication. Please make sure you typed the correct API-key and Store Pin.', 'diller-loyalty' ) );
				default:
					return new WP_Error( 'api-error', __( 'The API was unreachable.', 'diller-loyalty' ) );
			}
		}

		if ( !empty($response_body['status']) && $response_body['status'] != 200 ) {

			if (empty($response_body) || empty($response_body['message'])) {
				// Translators: placeholder adds the response code.
				return new WP_Error( 'api-error', sprintf( __( 'The API returned a <strong>%s</strong> response', 'diller-loyalty' ), $http_response_code ) );
			}

			if (!empty($response_body['message'])) {
				return new WP_Error( 'validation-error', stripslashes( $response_body['message'] ) );
			}
		}

		// Return the json decoded content.
		return $response_body;
	}

	/**
	 * Sets a class property.
	 *
	 * @param string $key The property to set.
	 * @param string $val The value to set for the property.
	 * @return mixed $value The response to the API call.
	 */
	public function set( $key, $val ) {
		$this->{$key} = $val;
	}

	/**
	 * Allow additional data to be passed in the request
	 *
	 * @param array $data
	 * return void
	 */
	public function set_additional_data( array $data ) {
		$this->additional_data = array_merge( $this->additional_data, $data );
	}

	/**
	 * Checks for SSL for making API requests.
	 *
	 * return bool True if SSL is enabled, false otherwise.
	 */
	public function is_ssl() {
		// Use the base is_ssl check first.
		if ( is_ssl() ) {
			return true;
		}
		else if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' == $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
			// proxies and load balancers.
			return true;
		}
		else if ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ) {
			return true;
		}

		// Otherwise, return false.
		return false;
	}
}
