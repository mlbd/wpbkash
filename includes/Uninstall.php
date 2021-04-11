<?php
/**
 * @package WPbKash
 */
namespace Themepaw\bKash;

class Uninstall {

    /**
	 * Call this method to get the singleton
	 *
	 * @return Uninstall|null
	 */
	public static function instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new Uninstall();
		}

		return $instance;
	}

	/**
	 * Uninstall initialize
	 *
	 * @return void
	 */
	public function uninstall() {

        // Delete Transient
		delete_transient( 'wpbkash_flush' );
        delete_transient( 'wpbkash_token_key' );

        // Delete Option
        delete_option( '_wpbkash_refresh_token' );
        delete_option( 'wpbkash__connection' );
        delete_option( 'wpbkash_general_fields' );
        delete_option( 'bkash_api_request' );
	}

}
