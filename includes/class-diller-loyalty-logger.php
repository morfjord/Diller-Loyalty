<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


interface IDiller_Loyalty_Logger {

	public function clear_log();

	public function info( $message, ...$args);

	public function error( $message, ...$args);
}


/**
 * Class responsible for creating logs. Logs reside by default under plugin_dir/logs
 * This class has no dependencies and should be used when Woocommerce is not installed/active.
 */
class Diller_Loyalty_Logger implements IDiller_Loyalty_Logger {

	private $header_log_str;
	private $log_file_name;

	public function __construct() {
		$this->header_log_str = "Diller Loyalty debug log file" . PHP_EOL;
		$this->log_file_name = 'logs' . DIRECTORY_SEPARATOR . '.diller_log_' .gmdate("d-m-Y") . '.log';

		// init log file
		if ( !file_exists( trailingslashit(DILLER_LOYALTY_PATH) . $this->log_file_name ) ) {
			file_put_contents( trailingslashit(DILLER_LOYALTY_PATH) . $this->log_file_name, $this->header_log_str );
		}
	}

	public function clear_log() {
		
	}

	private function log( $category = 'INFO', $message = '', $args ) {
		try {
			$overwrite = false;
			if ($overwrite) {
				if ( !empty( $this->log_file_name) && file_exists( trailingslashit(DILLER_LOYALTY_PATH) . $this->log_file_name ) ) {
					unlink( trailingslashit(DILLER_LOYALTY_PATH) . $this->log_file_name );
				}
				file_put_contents( trailingslashit(DILLER_LOYALTY_PATH) . $this->log_file_name, $this->reset_log_str );
			}

			$log_message = '[' . date( 'd-m-Y H:i:s' ) . '] [' . strtoupper($category) . '] ' . $this->format_message($message, $args);

			//Write to the log file
			return file_put_contents( trailingslashit(DILLER_LOYALTY_PATH) . $this->log_file_name, $log_message . PHP_EOL . PHP_EOL, (!$overwrite ? FILE_APPEND : 0) );
		}
		catch ( \Exception $e ) {
			return false;
		}
	}

	protected function format_message($message = "", $args) {
		global $wp_version;

		$log_message  = $message . PHP_EOL;
		$log_message .= sprintf("WP Version: %s | PHP Version: %s | Diller Version: %s ", $wp_version, phpversion(), DILLER_LOYALTY_VERSION);
		$log_message .= is_multisite() ? PHP_EOL . "Multisite: true | URL: " . get_site_url(): "Single site: true";

		// Check if a specific Follower instance was provided, otherwise default to current follower
		$follower = current(array_filter($args, function($arg) { return is_a($arg, "Diller_Loyalty_Follower"); }));
		if($follower === false){
			$follower = DillerLoyalty()->get_current_follower();
		}

		// Log Follower details. Defaults to current follower
		if($follower->get_wp_user_id() > 0 && !empty($follower->get_full_phone_number())) {
			$log_message .= PHP_EOL . 'Follower: ' . $follower->get_full_name() . ' | Phone: ' . $follower->get_full_phone_number() . ' | WP ID: ' . $follower->get_wp_user_id();
		}

		foreach ( $args as $arg ) {
			switch ($arg){
				case is_a($arg, "Diller_Loyalty_Follower"):
					break;
				case is_a($arg, "WP_Error"):
					$log_message .= PHP_EOL . 'ERROR DETAILS: ' . PHP_EOL . print_r($arg, true);
					break;
				case is_a($arg, "WC_Order"):
					$log_message .= PHP_EOL . 'ORDER DETAILS: ' . PHP_EOL . print_r($arg, true);
					break;
				case is_array($arg):
					$log_message .= PHP_EOL . 'DATA: ' . print_r($arg, true);
					break;
				case is_string($arg):
					$log_message .= $arg;
					break;
				default:
					$log_message .= PHP_EOL . 'DATA: ' . var_export($arg, true);
					break;
			}
		}
		$log_message .= PHP_EOL . PHP_EOL;

		return $log_message;
	}

	/**
	 * Logs an info message to the log.
	 *
	 * @param $message  The message to log
	 * @param mixed $args   Optional multiple argument list relevant for logging purposes
	 *
	 * @return false|int
	 */
	public function info( $message, ...$args) {
		return $this->log('INFO', $message, $args);
	}

	/**
	 * Logs an error to the error log.
	 *
	 * @param $message  The message to log
	 * @param mixed $args   Optional multiple argument list relevant for logging purposes
	 *
	 * @return false|int
	 */
	public function error( $message, ...$args) {
		return $this->log('ERROR', $message, $args);
	}

}

/**
 * Class responsible for creating logs using WC internal logging mechanism, by override parent's default behavior.
 * Logs will be available at https://store_url/wp-admin/admin.php?page=wc-status&tab=logs
 * This class has dependency on Woocommerce being installed/active.
 */
final class Diller_Loyalty_WC_Logger extends Diller_Loyalty_Logger{
	private $wc_logger;

	public function __construct() {
		if(!class_exists( 'woocommerce' )){
			_doing_it_wrong( 'Diller_Loyalty_WC_Logger::__construct', 'You can only Diller_Loyalty_WC_Logger if Woocommerce is installed. Use Diller_Loyalty_Logger class instead', DILLER_LOYALTY_VERSION);
		}

		$this->wc_logger = wc_get_logger();
	}

	/**
	 * Logs an info message to the log.
	 *
	 * @param $message  The message to log
	 * @param mixed $args   Optional multiple argument list relevant for logging purposes
	 *
	 */
	public function info( $message, ...$args) {
		$message = $this->format_message($message, $args);
		$this->wc_logger->info( $message, array( 'source' => 'diller-loyalty-info' ) );
	}

	/**
	 * Logs an error to the error log.
	 *
	 * @param $message  The message to log
	 * @param mixed $args   Optional multiple argument list relevant for logging purposes
	 *
	 */
	public function error( $message, ...$args) {
		$message = $this->format_message($message, $args);
		$this->wc_logger->error( $message, array( 'source' => 'diller-loyalty-error' ) );
	}
}