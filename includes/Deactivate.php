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
	public function deactivate() {
		delete_transient( 'wpbkash_flush' );
        delete_transient( 'wpbkash_token_key' );
        update_option( '_wpbkash_refresh_token', '' );
        update_option( 'bkash_api_request', array() );
		flush_rewrite_rules();
	}

}
