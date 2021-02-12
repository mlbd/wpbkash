<?php
/**
 * @package WPbKash
 */
namespace Themepaw\bKash\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Base class file.
 *
 * @package WpbKash
 */
class Base {

	protected $api_url;

	/**
	 * Call this method to get the singleton
	 *
	 * @return Base|null
	 */
	public static function instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new Base();
		}

		return $instance;
	}

	/**
	 * Get api url
	 *
	 * @param string $end_point
	 *
	 * @return string
	 */
	protected function get_api_url( $end_point = '' ) {
		switch ( $end_point ) {
			case 'create':
				$this->api_url = 'https://checkout.' . $this->get_mode() . '.bka.sh/v' . WPBKASH()->bkash_api_version . '/checkout/payment/create';
				break;
			case 'execute':
				$this->api_url = 'https://checkout.' . $this->get_mode() . '.bka.sh/v' . WPBKASH()->bkash_api_version . '/checkout/payment/execute/';
				break;
			case 'query':
				$this->api_url = 'https://checkout.' . $this->get_mode() . '.bka.sh/v' . WPBKASH()->bkash_api_version . '/checkout/payment/query/';
				break;
			case 'search':
				$this->api_url = 'https://checkout.' . $this->get_mode() . '.bka.sh/v' . WPBKASH()->bkash_api_version . '/checkout/payment/search/';
				break;
			case 'refresh':
				$this->api_url = 'https://checkout.' . $this->get_mode() . '.bka.sh/v' . WPBKASH()->bkash_api_version . '/checkout/token/refresh';
				break;
			default:
				$this->api_url = 'https://checkout.' . $this->get_mode() . '.bka.sh/v' . WPBKASH()->bkash_api_version . '/checkout/token/grant';
		}

		return apply_filters( 'wpbkash_api_url', $this->api_url, $end_point );
	}

	/**
	 * Get wpbkash settings options
	 *
	 * @return array $options
	 */
	protected function get_general_options() {
		$options = get_option( 'wpbkash_general_fields', array() );
		return (array) $options;
	}

	/**
	 * Get single option field data
	 *
	 * @param $key
	 *
	 * @return $field
	 */
	public function get_option( $key ) {
		$fields = $this->get_general_options();
		$field  = ( isset( $fields[ $key ] ) && ! empty( $fields[ $key ] ) ) ? sanitize_text_field( $fields[ $key ] ) : '';
		return $field;
	}

	/**
	 * Get bkash mode
	 *
	 * @return string $mode
	 */
	public function get_mode() {
		$mode = ! empty( $this->get_option( 'testmode' ) ) ? 'sandbox' : 'pay';
		return $mode;
	}

	/**
	 * Check settings field are emtpy or not
	 */
	public function is_settings_ok() {
		if ( empty( $this->get_general_options() ) ||
			empty( $this->get_option( 'app_key' ) ) ||
			empty( $this->get_option( 'app_secret' ) ) ||
			empty( $this->get_option( 'username' ) ) ||
			empty( $this->get_option( 'password' ) )
		) {
			return false;
		}
		return true;
	}

	/**
	 * Saving for display each api request to get bKash merchant account
	 * according to bKash official rules.
	 *
	 * @param int          $key
	 * @param string       $title
	 * @param array        $url
	 * @param array        $body
	 * @param mixed|string $response
	 *
	 * @return void
	 */
	public function api_request_docs( $key, $title, $url, $headers, $body, $response ) {
		if ( current_user_can( 'manage_options' ) ) {
			$get_data                     = get_option( 'bkash_api_request', array() );
			$get_data[ $key ]['title']    = $title;
			$get_data[ $key ]['url']      = $url;
			$get_data[ $key ]['headers']  = $headers;
			$get_data[ $key ]['body']     = $body;
			$get_data[ $key ]['response'] = $response;
			update_option( 'bkash_api_request', $get_data );
		}
	}


}
