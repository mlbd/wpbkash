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
		flush_rewrite_rules();
	}

}
