<?php
/**
 * @package WPbKash
 */
namespace Themepaw\bKash\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Themepaw\bKash\Invoice;

/**
 * Query class file.
 *
 * @package WpbKash
 */
class Query {

	/**
	 * Properties
	 */
	protected $token;
	protected $createURL;
	protected $executeURL;
	protected $tokenURL;
	protected $app_key;
	protected $app_secret;
	protected $username;
	protected $password;
	public $type;

	/**
	 * Initialize
	 */
	function __construct( $option, $type = 'wc' ) {

		$mode             = ( isset( $option['testmode'] ) && ! empty( $option['testmode'] ) ) ? 'sandbox' : 'pay';
		$this->createURL  = 'https://checkout.' . $mode . '.bka.sh/v' . WPBKASH()->bkash_api_version . '/checkout/payment/create';
		$this->executeURL = 'https://checkout.' . $mode . '.bka.sh/v' . WPBKASH()->bkash_api_version . '/checkout/payment/execute/';
        $this->queryURL   = 'https://checkout.' . $mode . '.bka.sh/v' . WPBKASH()->bkash_api_version . '/checkout/payment/query/';
        $this->searchURL  = 'https://checkout.' . $mode . '.bka.sh/v' . WPBKASH()->bkash_api_version . '/checkout/payment/search/';
		$this->tokenURL   = 'https://checkout.' . $mode . '.bka.sh/v' . WPBKASH()->bkash_api_version . '/checkout/token/grant';
		$this->refreshURL = 'https://checkout.' . $mode . '.bka.sh/v' . WPBKASH()->bkash_api_version . '/checkout/token/refresh';

		$this->app_key    = sanitize_text_field( $option['app_key'] );
		$this->app_secret = sanitize_text_field( $option['app_secret'] );
		$this->username   = sanitize_text_field( $option['username'] );
		$this->password   = sanitize_text_field( $option['password'] );
		$this->type       = $type;

	}

	/**
	 * Check Token creation for accessing bKash payment APIs.
	 *
	 * @return string
	 */
	public function check_bkash_token() {

		$data = [
			'app_key'    => $this->app_key,
			'app_secret' => $this->app_secret,
		];

		$username = $this->username;
		$password = $this->password;

		$headers = [
			'username'     => $username,
			'password'     => $password,
			'Content-Type' => 'application/json',
		];

        $api_response = $this->create_requrest( $this->tokenURL, $data, $headers );

		if ( empty( $api_response ) ) {
			return false;
        }

		$response = json_decode( $api_response, true );

		if ( isset( $response['id_token'] ) && isset( $response['token_type'] ) ) {
			$token = $response['id_token'];
			return $token;
		}

		return false;
	}

    public function refresh_token() {
        
    }

	/**
	 * Token creation for accessing bKash payment APIs.
	 *
	 * @return string
	 */
	public function get_bkash_token() {
		if ( $token = get_transient( "wpbkash_token_key" ) ) {
			return $token;
		}

		$data = [
			'app_key'    => $this->app_key,
			'app_secret' => $this->app_secret,
		];

		$username = $this->username;
		$password = $this->password;

		$headers = [
			'username'     => $username,
			'password'     => $password,
			'Content-Type' => 'application/json',
		];

		$api_response = $this->create_requrest( $this->tokenURL, $data, $headers );
		if ( empty( $api_response ) ) {
			return false;
        }
        
        $response = json_decode( $api_response, true );        
        $this->api_request_docs(0, 'Grant Token', $this->tokenURL, $headers, $data, $response);

		if ( isset( $response['id_token'] ) && isset( $response['token_type'] ) ) {
			$token = $response['id_token'];
			set_transient( "wpbkash_token_key", $token, $response['expires_in'] );
			return $token;
		}

		return false;
	}


	/**
	 * This API will receive a payment creation request with necessary information.
	 *
	 * @param int    $amount
	 *
	 * @return mixed|string
	 */
	public function createPayment( $amount) {

		$token = $this->get_bkash_token();
		$invoice = Invoice::instance()->get_invoice();

		$app_key = $this->app_key;
		$intent  = 'sale';
		$data    = [
			'amount'                => $amount,
			'currency'              => 'BDT',
			'merchantInvoiceNumber' => $invoice,
			'intent'                => $intent,
		];

		$headers = [
			'Content-Type'  => 'application/json',
			'authorization' => $token,
			'x-app-key'     => $app_key,
		];

        $api_response = $this->create_requrest( $this->createURL, $data, $headers );
        $response = json_decode( $api_response, true );
        $this->api_request_docs(1, 'Create Payment', $this->createURL, $headers, $data, $response);

		return $api_response;
    }

	/**
	 * This API will finalize a payment request.
	 *
	 * @param string $paymentid
	 * @param int    $order_id
	 *
	 * @return mixed|string
	 */
	public function executePayment( $paymentid ) {

		$paymentID  = $paymentid;
		$token      = $this->get_bkash_token();
		$app_key    = $this->app_key;
		$executeURL = $this->executeURL . $paymentID;

		$headers = [
			'Content-Type'  => 'application/json',
			'authorization' => $token,
			'x-app-key'     => $app_key,
		];

        $api_response = $this->create_requrest( $executeURL, false, $headers );
        $response = json_decode( $api_response, true );
        $this->api_request_docs(2, 'Execute Payment', $executeURL, $headers, [], $response);
        if( empty( $api_response ) ) {
            // wait 10 second before query payment request.
            sleep(10);
            $check_response = $this->queryPayment( $paymentID );
            return $check_response;
        }
		return $api_response;
    }
    
    /**
     * Check or verify the payment with id
     * 
     * @param string $paymentid
     * 
     * @return mixed|string
     */
     public function queryPayment( $paymentid ) {

        $paymentID  = $paymentid;
        $token      = $this->get_bkash_token();
        $app_key    = $this->app_key;
		$queryURL   = $this->queryURL . $paymentID;
        
        $headers = [
            "Authorization" => "Bearer {$token}",
            'x-app-key'     => $app_key,
            "Content-Type"  => 'application/json',
        ];

        $args = [ 
            'headers' => $headers,
            'timeout' => apply_filters( 'wpbkash_remote_get_timeout', 30 ),
         ];

        $response = wp_remote_get(
            esc_url_raw( $queryURL ),
            $args
        );

        $api_response = wp_remote_retrieve_body( $response );
        $response = json_decode( $api_response, true );
        $this->api_request_docs(3, 'Query Payment', $queryURL, $headers, [], $response);
        return $api_response;
     }
    
     /**
     * Search transaction
     * 
     * @param string $trx_id
     * 
     * @return mixed|string
     */
     public function searchTransaction( $trx_id ) {

        $token      = $this->get_bkash_token();
        $app_key    = $this->app_key;
		$searchURL  = $this->searchURL . $trx_id;
        
        $headers = [
            "Authorization" => "Bearer {$token}",
            'x-app-key'     => $app_key,
            "Content-Type"  => 'application/json',
        ];

        $args = [ 
            'headers' => $headers,
            'timeout' => apply_filters( 'wpbkash_remote_get_timeout', 30 ),
         ];

        $response = wp_remote_get(
            esc_url_raw( $searchURL ),
            $args
        );

        $api_response = wp_remote_retrieve_body( $response );
        $response = json_decode( $api_response, true );
        $this->api_request_docs(4, 'Search Transaction', $searchURL, $headers, [], $response);
        return $api_response;
     }

	/**
	 * Create request with WordPress core functions
	 *
	 * @param $url
	 * @param array|boolean $data
	 * @param array         $headers
	 */
	public function create_requrest( $url, $data = false, $headers = [], $timeout = 30 ) {

		$args = [
			'method'      => 'POST',
			'timeout'     => $timeout,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => $headers,
		];

		if ( false !== $data ) {
			$args['body'] = json_encode( $data );
		}

		$response = wp_remote_post( esc_url_raw( $url ), $args );

		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
			/*
			 Will result in $api_response being an array of data,
			parsed from the JSON response of the API listed above */
			$api_response = wp_remote_retrieve_body( $response );
			return $api_response;
		}

		return false;
    }
    
    /**
     * Saving for display each api request to get bKash merchant account
     * according to bKash official rules.
     * 
     * @param string $name
     * @param string $title
     * @param array $url
     * @param array $body
     * @param mixed|string $response
     * 
     * @return void
     */
    public function api_request_docs($name, $title, $url, $headers, $body, $response) {
        if ( current_user_can( 'manage_options' ) ) {
            $get_data = get_option('bkash_api_request', []);
            $get_data[$name]['title']     = $title;
            $get_data[$name]['url']       = $url;
            $get_data[$name]['headers']   = $headers;
            $get_data[$name]['body']      = $body;
            $get_data[$name]['response']  = $response;
            update_option( 'bkash_api_request', $get_data );
        }
    }

}


