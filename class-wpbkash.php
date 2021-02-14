<?php
/**
 * Plugin Name:       bKash WordPress Payment
 * Plugin URI:        https://wordpress.org/plugins/wpbkash/
 * Description:       bKash payment gateway integration for WordPress
 * Version:           0.1.8
 * Author:            themepaw
 * Author URI:        https://themepaw.com
 * Text Domain:       wpbkash
 * Domain Path:       /languages
 * License: GPLv2 Or Later
 */

defined( 'ABSPATH' ) || die( 'No script kiddies please!' );  // prevent direct access.

require_once __DIR__ . '/vendor/autoload.php';

use Themepaw\bKash\Activate;
use Themepaw\bKash\Deactivate;
use Themepaw\bKash\Admin\Settings;

/**
 * WPbKash main class
 */
final class WPbKash {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '0.1.8';

	/**
	 * Plugin Database Table.
	 *
	 * @var string
	 */
	private $db_table = 'wpbkash';

	/**
	 * BKash Payment Gateway Mode.
	 *
	 * @var string
	 */
	public $mode = '';

	/**
	 * BKash API version.
	 *
	 * @var string
	 */
	public $bkash_api_version = '';

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;


	/**
	 * Initialize the plugin.
	 */
	private function __construct() {

		$this->define_constanst();

		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'setup' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_gateway' ) );
	}

	/**
	 * Private clone method to prevent cloning of the instance of the
	 * *Singleton* instance.
	 *
	 * @return void
	 */
	private function __clone() {
		wc_doing_it_wrong( __FUNCTION__, esc_html__( 'Nope', 'wpbkash' ), '1.0' );
	}
	/**
	 * Private unserialize method to prevent unserializing of the *Singleton*
	 * instance.
	 *
	 * @return void
	 */
	private function __wakeup() {
		wc_doing_it_wrong( __FUNCTION__, esc_html__( 'Nope', 'wpbkash' ), '1.0' );
	}

	/**
	 * Setup file and notice callback
	 *
	 * @return void
	 */
	public function setup() {

		$this->bkash_api_version = apply_filters( 'wpbkash_bkash_version', '1.2.0-beta' );

		Settings::get_instance();

		if ( $this->is_woocommerce_exists() ) {
			new Themepaw\bKash\WooCommerce\Init();
		}
		if ( $this->is_wpcf7_exists() ) {
			Themepaw\bKash\WPCF7\Init::init();
		}

		load_plugin_textdomain( 'wpbkash', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		add_filter( 'plugin_action_links', array( $this, 'settings_link' ), 10, 2 );

	}

	/**
	 * Add settings link to plugin actions links
	 *
	 * @param array  $links_array
	 * @param string $plugin_file_name
	 *
	 * @return array
	 */
	public function settings_link( $links_array, $plugin_file_name ) {

		if ( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
			array_unshift( $links_array, '<a href="' . esc_url( admin_url( 'admin.php?page=wpbkash_settings' ) ) . '">' . __( 'Settings', 'wpbkash' ) . '</a>' );
		}

		return $links_array;
	}


	/**
	 * Check if WooCommerce exsits
	 *
	 * @return void
	 */
	public function is_woocommerce_exists() {
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if WPCF7 exsits
	 *
	 * @return void
	 */
	public function is_wpcf7_exists() {
		if ( in_array( 'contact-form-7/wp-contact-form-7.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Define require constansts
	 *
	 * @return void
	 */
	public function define_constanst() {
		define( 'WPBKASH_VERSION', self::VERSION );
		define( 'WPBKASH_URL', plugins_url( '/', __FILE__ ) );
		define( 'WPBKASH_PATH', plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Register WooCommerce Gateway
	 *
	 * @param  array $gateways
	 *
	 * @return array
	 */
	function register_gateway( $gateways ) {
		$gateways[] = new Themepaw\bKash\WooCommerce\WCBkashGateway();
		return $gateways;
	}

	/**
	 * Create the transaction table
	 *
	 * @return void
	 */
	function activate() {
		Activate::instance()->activate();
	}

	/**
	 * WooCommerce fallback notice.
	 *
	 * @return string
	 */
	public function deactivate() {
		Deactivate::instance()->deactivate();
	}


}//end class

WPbKash::get_instance();


/**
 * Main instance WPbKash.
 *
 * Returns the main instance of WPbKash.
 *
 * @return WPbKash
 */
function WPBKASH() { // phpcs:ignore
	return WPbKash::get_instance();
}
