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

class Debug {

	protected $option_name = 'wpbkash_debug';
	protected $options;

    /**
	 * Call this method to get the singleton
	 *
	 * @return Debug|null
	 */
	public static function instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new Debug();
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
		$this->options = $this->default_settings();
	}

	/**
	 * Set default value for settings
	 */
	function default_settings() {
		$saved    = (array) get_option( $this->option_name . '_fields' );
		$defaults = array(
			'enable' => '',
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
			esc_html__( 'Debug Settings', 'wpbkash' ),
			array( $this, 'print_section_info' ),
			$this->option_name . '_settings'
		);

		add_settings_field(
			'enable',
			esc_html__( 'Enable', 'wpbkash' ),
			array( $this, 'enable' ),
			$this->option_name . '_settings',
			$this->option_name . '_section'
		);

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
	 * generate the page using the standard Settings API function
	 *
	 * @since 1.0
	 */
	public function add_settings_page() {
		settings_fields( $this->option_name . '_group' );
		do_settings_sections( $this->option_name . '_settings' );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function enable() {
		?>
		<label for="enable">
			<input type="checkbox" id="enable" name="wpbkash_debug_fields[enable]" value="1" 
			<?php
			if ( isset( $this->options['enable'] ) && 1 == $this->options['enable'] ) {
				echo 'checked="checked"';
			}
			?>
			 />
			<?php esc_html_e( 'Enable WPbkash Debugging', 'wpbkash' ); ?>
		</label>
		<?php
	}

	/**
	 * Print the Section text
	 */
	public function print_section_info() {
        printf(
            esc_html__( '%1$s %2$s', 'text-domain' ),
            esc_html__( 'Before enable WPBkash debug. Enable', 'wpbkash' ),
            sprintf(
                '<a href="%s" target="_blank">%s</a>',
                esc_url( 'https://wordpress.org/support/article/debugging-in-wordpress/' ),
                esc_html__( 'WordPress Debug', 'text-domain' )
            )
        );
    }

}
