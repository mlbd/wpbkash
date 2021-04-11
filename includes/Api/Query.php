<?php
/**
 * @package WPbKash
 */
namespace Themepaw\bKash\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Themepaw\bKash\Invoice;
use Themepaw\bKash\Utility;

/**
 * Query class file.
 *
 * @package WpbKash
 */
class Query extends Base {

	/**
	 * Call this method to get the singleton
	 *
	 * @return Query|null
	 */
	public static function instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new Query();
		}

		return $instance;
	}

	/**
	 * Check Token creation for accessing bKash payment APIs.
	 *
	 * @return string
	 */
	public function check_bkash_token() {

		$data = array(
			'app_key'    => $this->get_option( 'app_key' ),
			'app_secret' => $this->get_option( 'app_secret' ),
		);

		$username = $this->get_option( 'username' );
		$password = $this->get_option( 'password' );

		$headers = array(
			'username'     => $username,
			'password'     => $password,
			'Content-Type' => 'application/json',
		);

		$api_response = $this->create_requrest( $this->get_api_url(), $data, $headers );

		if ( empty( $api_response ) ) {
			return false;
		}

		$response = json_decode( $api_response, true );

		if ( isset( $response['id_token'] ) && isset( $response['token_type'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Token creation for accessing bKash payment APIs.
	 *
	 * @return string
	 */
	public function get_bkash_token() {
		if ( $token = get_transient( 'wpbkash_token_key' ) ) {
			return $token;
		}

		$data = array(
			'app_key'    => $this->get_option( 'app_key' ),
			'app_secret' => $this->get_option( 'app_secret' ),
		);

		$username = $this->get_option( 'username' );
		$password = $this->get_option( 'password' );

		$headers = array(
			'username'     => $username,
			'password'     => $password,
			'Content-Type' => 'application/json',
		);

        Utility::instance()->logger( $headers );

        $token_url = $this->get_api_url();

        $old_token = get_option( '_wpbkash_refresh_token' );
        if( ! empty( $old_token ) ) {
            $data['refresh_token'] = $old_token;
            $token_url = $this->get_api_url('refresh');
        }

        Utility::instance()->logger( $token_url );

		$api_response = $this->create_requrest( $token_url, $data, $headers );
		if ( empty( $api_response ) ) {
			return false;
		}

		$response = json_decode( $api_response, true );
		$this->api_request_docs( 0, 'Grant Token', $this->get_api_url(), $headers, $data, $response );
		if ( isset( $response['id_token'] ) && isset( $response['token_type'] ) ) {
			$token = $response['id_token'];
            $refresh_token = $response['refresh_token'];
            $this->refresh_token_update( $refresh_token );
			set_transient( 'wpbkash_token_key', $token, $this->token_expiration() );
			return $token;
		}

		return false;
	}

    /**
     * Add/Update bKash refresh token.
     * 
     * @param string $new_token
     * 
     * @return void
     */
    public function refresh_token_update( $new_token ) {
        $old_token = get_option( '_wpbkash_refresh_token' );
        if( ! empty( $old_token ) && $old_token !== $new_token ) {
            update_option( '_wpbkash_refresh_token', sanitize_text_field( $new_token ) );
        } elseif( empty( $old_token ) ) {
            update_option( '_wpbkash_refresh_token', sanitize_text_field( $new_token ) );
        }
    }

	/**
	 * This API will receive a payment creation request with necessary information.
	 *
	 * @param int $amount
	 *
	 * @return mixed|string
	 */
	public function createPayment( $amount ) {

		$token   = $this->get_bkash_token();
		$invoice = Invoice::instance()->get_invoice();

		$app_key = $this->get_option( 'app_key' );
		$intent  = 'sale';
		$data    = array(
			'amount'                => $amount,
			'currency'              => 'BDT',
			'merchantInvoiceNumber' => $invoice,
			'intent'                => $intent,
		);

		$headers = array(
			'Content-Type'  => 'application/json',
			'authorization' => $token,
			'x-app-key'     => $app_key,
		);

		$api_response = $this->create_requrest( $this->get_api_url( 'create' ), $data, $headers );
		$response     = json_decode( $api_response, true );
		$this->api_request_docs( 1, 'Create Payment', $this->get_api_url( 'create' ), $headers, $data, $response );

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
		$app_key    = $this->get_option( 'app_key' );
		$executeURL = $this->get_api_url( 'execute' ) . $paymentID;

		$headers = array(
			'Content-Type'  => 'application/json',
			'authorization' => $token,
			'x-app-key'     => $app_key,
		);

		$api_response = $this->create_requrest( $executeURL, false, $headers );
		$response     = json_decode( $api_response, true );
		$this->api_request_docs( 2, 'Execute Payment', $executeURL, $headers, array(), $response );
		if ( empty( $api_response ) ) {
			// wait 10 second before query payment request.
			sleep( 10 );
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
	public function queryPayment( $paymentid, $type = '' ) {

		$paymentID = $paymentid;
		$token     = $this->get_bkash_token();
		$app_key   = $this->get_option( 'app_key' );
		$queryURL  = $this->get_api_url( 'query' ) . $paymentID;

		$headers = array(
			'Authorization' => "Bearer {$token}",
			'x-app-key'     => $app_key,
			'Content-Type'  => 'application/json',
		);

		$args = array(
			'headers' => $headers,
			'timeout' => apply_filters( 'wpbkash_remote_get_timeout', 30 ),
		);

		$response = wp_remote_get(
			esc_url_raw( $queryURL ),
			$args
		);

		$api_response = wp_remote_retrieve_body( $response );
        Utility::instance()->logger( $api_response );
		$response     = json_decode( $api_response, true );
        if( 'ajax' !== $type ) {
            $this->api_request_docs( 3, 'Query Payment', $queryURL, $headers, array(), $response );
        }
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

		$token     = $this->get_bkash_token();
		$app_key   = $this->get_option( 'app_key' );
		$searchURL = $this->get_api_url( 'search' ) . $trx_id;

		$headers = array(
			'Authorization' => "Bearer {$token}",
			'x-app-key'     => $app_key,
			'Content-Type'  => 'application/json',
		);

		$args = array(
			'headers' => $headers,
			'timeout' => apply_filters( 'wpbkash_remote_get_timeout', 30 ),
		);

		$response = wp_remote_get(
			esc_url_raw( $searchURL ),
			$args
		);

		$api_response = wp_remote_retrieve_body( $response );
        Utility::instance()->logger( $api_response );
		$response     = json_decode( $api_response, true );
		$this->api_request_docs( 4, 'Search Transaction', $searchURL, $headers, array(), $response );
		return $api_response;
	}

    /**
	 * Get the final amount after apply bkash fee/charge
	 *
	 * @since 1.3.0
	 *
	 * @param $amount
	 *
	 * @return float|int
	 */
	public function get_amount( $cart_total ) {

        $extra_fields = get_option( 'wpbkash_extra_fields' );
        if ( ! isset( $extra_fields['enable'] ) || '1' != $extra_fields['enable'] ) {
            return $cart_total;
        }

        $type   = $extra_fields['type'];
        $fee_amount = (float) $extra_fields['amount'];

        $minimum   = $extra_fields['minimum'] ? $extra_fields['minimum'] : '';
        $maximum   = $extra_fields['maximum'] ? $extra_fields['maximum'] : '';

        if ( ! empty( $minimum ) && $cart_total < $minimum ) {
            return $cart_total;
        }

        if ( ! empty( $maximum ) && $cart_total > $maximum ) {
            return $cart_total;
        }

		if ( $type == 'percentage' ) {
            $cart_total = $cart_total + $cart_total * ( $fee_amount / 100 );
        } else {
            $cart_total = $cart_total + $fee_amount;
        }

		$cart_total = number_format($cart_total, 2, '.', '');

		return $cart_total;
	}
	 
    /**
	  * Refund transaction
	  *
	  * @param array $data
	  *
	  * @return mixed|string
	  */
	public function refundTransaction( $data ) {

		$token     = $this->get_bkash_token();
		$app_key   = $this->get_option( 'app_key' );

		$headers = array(
			'Content-Type'  => 'application/json',
			'authorization' => $token,
			'x-app-key'     => $app_key,
		);

		$api_response = $this->create_requrest( $this->get_api_url( 'refund' ), $data, $headers );
		return $api_response;
	}

	/**
	 * Create request with WordPress core functions
	 *
	 * @param $url
	 * @param array|boolean $data
	 * @param array         $headers
	 */
	public function create_requrest( $url, $data = false, $headers = array(), $timeout = 30 ) {

		$args = array(
			'method'      => 'POST',
			'timeout'     => $timeout,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking'    => true,
			'headers'     => $headers,
		);

		if ( false !== $data ) {
			$args['body'] = json_encode( $data );
		}

		$response = wp_remote_post( esc_url_raw( $url ), $args );

		if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) == 200 ) {
			/*
			 Will result in $api_response being an array of data,
			parsed from the JSON response of the API listed above */
			$api_response = wp_remote_retrieve_body( $response );
            Utility::instance()->logger( $api_response );
			return $api_response;
		}

		return false;
	}

}


