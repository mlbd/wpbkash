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

/*
 * General Class
 * @since 1.0
 */

class General {

	protected $option_name = 'wpbkash_general';
	protected $options;

	/**
	 * Set things up.
	 *
	 * @since 1.0
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'init_settings' ) );
		$this->options = $this->default_settings();
	}

	/**
	 * Set default value for settings
	 */
	function default_settings() {
		$saved    = (array) get_option( $this->option_name . '_fields' );
		$defaults = array(
			'testmode'   => '',
			'app_key'    => '',
			'app_secret' => '',
			'username'   => '',
			'password'   => '',
		);

		$defaults = apply_filters( "{$this->option_name}_default_settings", $defaults );
		$options  = wp_parse_args( $saved, $defaults );
		$options  = array_intersect_key( $options, $defaults );

		return $options;
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
			__( 'General Settings', 'wpbkash' ),
			array( $this, 'print_section_info' ),
			$this->option_name . '_settings'
		);

		add_settings_field(
			'testmode',
			__( 'Test Mode', 'wpbkash' ),
			array( $this, 'testmode' ),
			$this->option_name . '_settings',
			$this->option_name . '_section'
		);

		add_settings_field(
			'app_key',
			__( 'App Key', 'wpbkash' ),
			array( $this, 'app_key' ),
			$this->option_name . '_settings',
			$this->option_name . '_section'
		);
		add_settings_field(
			'app_secret',
			__( 'App Secret', 'wpbkash' ),
			array( $this, 'app_secret' ),
			$this->option_name . '_settings',
			$this->option_name . '_section'
		);
		add_settings_field(
			'username',
			__( 'Username', 'wpbkash' ),
			array( $this, 'username' ),
			$this->option_name . '_settings',
			$this->option_name . '_section'
		);
		add_settings_field(
			'password',
			__( 'Password', 'wpbkash' ),
			array( $this, 'password' ),
			$this->option_name . '_settings',
			$this->option_name . '_section'
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
	 * sanitize and validate user data, called after the user submits the page
	 * add newly selected values
	 * deleted unchecked values
	 * get the updated list
	 *
	 * @param array $input
	 *
	 * @return array
	 */
	public function validate_and_save( $input ) {

		// Create our array for storing the validated options
		$output = array();

		// Loop through each of the incoming options
		foreach ( $input as $key => $value ) {

			// Check to see if the current option has a value. If so, process it.
			if ( isset( $input[ $key ] ) ) {

				// Strip all HTML and PHP tags and properly handle quoted strings
				$output[ $key ] = strip_tags( stripslashes( $input[ $key ] ) );

			} // end if
		} // end foreach

		// Return the array processing any additional functions filtered by this action
		return apply_filters( 'wpbkash_settings_field_validate', $output, $input );
	}

	/**
	 * Print the Section text
	 */
	public function print_section_info() {

			$connection = get_option( 'wpbkash__connection' );

			esc_html_e( 'Setup your bKash app info and credentials.', 'wpbkash' );

			echo '<div class="wpbkash--mode">';
		if ( isset( $this->options['testmode'] ) && 1 == $this->options['testmode'] ) {
			echo '<h4>' . __( 'Testmode is enabled', 'wpbkash' ) . '</h4>';
		}
		if ( isset( $this->options['app_key'] ) && isset( $this->options['app_secret'] ) ) {
			if ( isset( $connection ) && ! empty( $connection ) && 'ok' === $connection ) {
				echo '<div class="wpbkash--connection-signal">' . __( 'Connection Ok', 'wpbkash' ) . ' <span class="dashicons dashicons-yes-alt"></span></div>';
			} else {
				echo '<div class="wpbkash--connection-signal connection-failed">' . __( 'Connection Failed', 'wpbkash' ) . ' <span class="dashicons dashicons-dismiss"></span></div>';
			}
		}
			echo '</div>';
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function testmode() {
		?>
		<label for="testmode">
			<input type="checkbox" id="testmode" name="wpbkash_general_fields[testmode]" value="1" 
			<?php
			if ( isset( $this->options['testmode'] ) && 1 == $this->options['testmode'] ) {
				echo 'checked="checked"';
			}
			?>
			 />
			<?php esc_html_e( 'Enable Test Mode', 'wpbkash' ); ?>
		</label>
		<?php
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function app_key() {
		printf(
			'<input type="text" size="50" id="app_key" name="wpbkash_general_fields[app_key]" value="%s" />',
			isset( $this->options['app_key'] ) ? esc_attr( $this->options['app_key'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function app_secret() {
		printf(
			'<input type="text" size="50" id="app_secret" name="wpbkash_general_fields[app_secret]" value="%s" />',
			isset( $this->options['app_secret'] ) ? esc_attr( $this->options['app_secret'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function username() {
		printf(
			'<input type="text" size="50" id="username" name="wpbkash_general_fields[username]" value="%s" />',
			isset( $this->options['username'] ) ? esc_attr( $this->options['username'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function password() {
		printf(
			'<input type="password" size="50" id="password" name="wpbkash_general_fields[password]" value="%s" />',
			isset( $this->options['password'] ) ? esc_attr( $this->options['password'] ) : ''
		);
	}

}
