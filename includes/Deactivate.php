<?php
/**
 * @package WPbKash
 */
namespace Themepaw\bKash;

class Deactivate {

    /**
	 * Call this method to get the singleton
	 *
	 * @return Deactivate|null
	 */
	public static function instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new Deactivate();
		}

		return $instance;
	}

	/**
	 * Deactivate initialize
	 *
	 * @return void
	 */
	public function deactivate() {
		delete_transient( 'wpbkash_flush' );
        delete_transient( 'wpbkash_token_key' );
        update_option( '_wpbkash_refresh_token', '' );
        update_option( 'bkash_api_request', array() );
		flush_rewrite_rules();
	}

}
