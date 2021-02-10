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
 * Extra Settings Class
 * @since 1.0
 */

class Invoice {

    protected $option_name = 'wpbkash_invoice';
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
        $saved = (array) get_option( $this->option_name . '_fields' );
        $defaults = array(
            'namespace'   => '',
            'type'        => 'random'
        );
    
        $defaults = apply_filters( "{$this->option_name}_default_settings", $defaults );
        $options = wp_parse_args( $saved, $defaults );
        $options = array_intersect_key( $options, $defaults );
    
        return $options;
    }

	public function init_settings() {
		if ( false == get_option( $this->option_name . '_fields' ) ) {
			update_option( $this->option_name, '' );
		}

		register_setting(
			$this->option_name .'_group', // Option group
			$this->option_name . '_fields', // Option name
			[ $this, 'validate_and_save' ] // Sanitize
		);

		add_settings_section(
			$this->option_name . '_section',
			__( 'Invoice Settings', 'wpbkash' ),
			[ $this, 'print_section_info' ],
			$this->option_name . '_settings'
		);

        add_settings_field(
			'namespace',
			__( 'Invoice Namespace', 'wpbkash' ),
			[ $this, 'namespace' ],
			$this->option_name . '_settings',
			$this->option_name . '_section'
        );
        add_settings_field(
			'type',
			__( 'Invoice Type', 'wpbkash' ),
			[ $this, 'type' ],
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
        foreach( $input as $key => $value ) {
            
            // Check to see if the current option has a value. If so, process it.
            if( isset( $input[$key] ) ) {
            
                // Strip all HTML and PHP tags and properly handle quoted strings
                $output[$key] = strip_tags( stripslashes( $input[ $key ] ) );
                
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
	 * Get the bKash charge amount
	 */
	public function namespace() {
        printf(
			'<input type="text" size="50" id="namespace" name="wpbkash_invoice_fields[namespace]" value="%s" />',
			isset( $this->options['namespace'] ) ? esc_attr( $this->options['namespace'] ) : ''
		);
	}

     /**
	 * Get the bKash charge
	 */
	public function type() {
        ?>
        <select name="wpbkash_invoice_fields[type]" id="type">
            <option value="random" <?php if ( isset( $this->options['type'] ) && $this->options['type'] == 'random' ) echo 'selected="selected"'; ?>><?php esc_html_e( 'Random', 'wpbkash' ); ?></option>
            <option value="post_id" <?php if ( isset( $this->options['type'] ) && $this->options['type'] == 'post_id' ) echo 'selected="selected"'; ?>><?php esc_html_e( 'Post ID', 'wpbkash' ); ?></option>
            <option value="number" <?php if ( isset( $this->options['type'] ) && $this->options['type'] == 'number' ) echo 'selected="selected"'; ?>><?php esc_html_e( 'Custom ID Number', 'wpbkash' ); ?></option>
        </select>
        <?php
	}
    
    /**
     * Print the Section text
     */
    public function print_section_info() {}

   

}