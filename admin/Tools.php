<?php
/*
 * Settings class for Content Types settings
 *
 * @copyright   Copyright (c) 2020, Nugget Solutions, Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 *
 */

namespace Themepaw\bKash\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use Themepaw\bKash\Api\Query;

/*
 * Extra Settings Class
 * @since 1.0
 */

class Tools {

    /**
	 * Store api class
	 */
	public $api;

    protected $option_name = 'wpbkash_tools';
    protected $options;

    /**
	 * Initialize
	 */
	function __construct() {

		$option = get_option( 'wpbkash_settings_fields' );

		if ( ! empty( $option['app_key'] ) && ! empty( $option['app_secret'] ) && ! empty( $option['username'] ) && ! empty( $option['password'] ) ) {
			$this->api = new Query( $option );

		    add_action( 'wp_ajax_wpbkash_search_transaction', [ $this, 'wpbkash_search_transaction' ] );
		}

		
	}

	/**
	 * Set things up.
	 *
	 * @since 1.0
	 */
	public function init() {
        add_action( 'admin_init', array( $this, 'init_settings' ) );
        
    }
    
	public function init_settings() {

        add_settings_section(
			$this->option_name . '_section',
			__( 'Search bKash Transaction', 'wpbkash' ),
			[ $this, 'print_section_info' ],
			$this->option_name . '_settings'
		);

	}

    public function wpbkash_search_transaction() {

        check_ajax_referer( 'wpbkash_nonce', 'nonce' );

		$trx = ! empty( $_POST['trx'] ) ? sanitize_text_field( $_POST['trx'] ) : '';

        if ( empty( $trx ) ) {
			wp_send_json_error([
			    'message'  => __( 'Transaction number can\'t be empty.', 'wpbkash' )
			]);
			wp_die();
		}
        
        $transaction = $this->api->searchTransaction( $trx );
        $transaction = \json_decode( $transaction );
        if (  isset( $transaction ) && ! empty( $transaction ) && isset( $transaction->transactionStatus ) ) {
            wp_send_json_success([
                'transaction' => $transaction
            ]);
            wp_die();
        }

        $msg = isset( $transaction->errorMessage ) ? $transaction->errorMessage : __('Something wen\'t wrong, please try again', 'wpbkash');
        wp_send_json_error([
           'message'   => $msg
        ]);
        wp_die();
    }

	/**
	 * generate the page using the standard Settings API function
	 *
	 * @since 1.0
	 */
	public function add_settings_page() {
        do_settings_sections( $this->option_name . '_settings' );
		?>
        <div class="wpbkash--search-wrapper">
            <form id="wpbkash__search_trx" action="wpbkash_search_transaction">
                <input type="text" name="wpbkash_trx" autocomplete="off" placeholder="<?php esc_html_e( 'bKash Transaction Number', 'wpbkash' ); ?>">
                <button type="submit" class="wpbkash__submit_btn"><?php esc_html_e( 'Submit', 'wpbkash' ); ?></button>
                <?php wp_nonce_field('wpbkash_nonce', '_trx_wpnonce'); ?>
            </form>
            <div class="wpbkash--search-result"></div>
        </div>
        <?php
    }
    
    public function get_tools_section() {
		$sections = array(
			"search" => __( "Search TrxID", "content-workflow" ),
			"refund" => __( "Refund", "content-workflow" ),
			"refund-status" => __( "Refund Status", "content-workflow" ),
		);

		return $sections;
	}

    
    /**
     * Print the Section text
     */
    public function print_section_info() {}


}