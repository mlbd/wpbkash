<?php
/**
 * @package WPbKash
 */

namespace Themepaw\bKash\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/*
 * Extra Settings Class
 * @since 1.0
 */

class Coming {

	protected $option_name = 'wpbkash_coming';
	protected $options;

    /**
	 * Call this method to get the singleton
	 *
	 * @return Coming|null
	 */
	public static function instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new Coming();
		}

		return $instance;
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
		if ( false == get_option( $this->option_name . '_fields' ) ) {
			update_option( $this->option_name, '' );
		}

		register_setting(
			$this->option_name . '_group', // Option group
			$this->option_name . '_fields', // Option name
			array( $this, 'validate_and_save' ) // Sanitize
		);

		add_settings_section(
			$this->option_name . '_section',
			esc_html__( 'Coming Soon', 'wpbkash' ),
			array( $this, 'print_section_info' ),
			$this->option_name . '_settings'
		);
	}

	/**
	 * generate the page using the standard Settings API function
	 *
	 * @since 1.0
	 */
	public function add_settings_page() {
		settings_fields( $this->option_name . '_group' );
		do_settings_sections( $this->option_name . '_settings' );
	}

	/**
	 * Print the Section text
	 */
	public function print_section_info() {}



}
