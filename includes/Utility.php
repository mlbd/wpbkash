<?php
/**
 * @package WPbKash
 */

namespace Themepaw\bKash;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Utility class - singleton
 *
 * @since 1.0
 */
class Utility {

    /**
	 * Call this method to get the singleton
	 *
	 * @return Utility|null
	 */
	public static function instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new Utility();
		}

		return $instance;
	}

	/**
	 * Prints message (string or array) in the debug.log file
	 *
	 * @param mixed $message
	 */
	public function logger( $message ) {
        $option = get_option( 'wpbkash_debug_fields' );
		if ( ! empty( $option ) && isset( $option['debug'] ) && '1' ==  $option['debug'] && WP_DEBUG === true ) {
			if ( is_array( $message ) || is_object( $message ) ) {
				error_log( print_r( $message, true ) );
			} else {
				error_log( $message );
			}
		}
	}

}