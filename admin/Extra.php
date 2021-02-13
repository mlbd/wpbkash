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

class Extra {

	protected $option_name = 'wpbkash_extra';
	protected $options;

    /**
	 * Call this method to get the singleton
	 *
	 * @return Extra|null
	 */
	public static function instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new Extra();
		}

		return $instance;
	}

	/**
	 * Set things up.
	 *
	 * @since 1.0
	 */
	public function init() {

        Invoice::instance()->init();

		add_action( 'admin_init', array( $this, 'init_settings' ) );
		$this->options = $this->default_settings();
	}

	/**
	 * Set default value for settings
	 */
	function default_settings() {
		$saved    = (array) get_option( $this->option_name . '_fields' );
		$defaults = array(
			'enable'    => '',
			'type'      => 'parcentage',
			'amount'    => 2,
			'label'     => __( 'bKash Fee', 'wpbkash' ),
			'shipping'  => '',
			'minimum'   => '',
			'maximum'   => '',
			'tax'       => '',
			'tax_class' => 'standard',
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
			__( 'Extra Settings', 'wpbkash' ),
			array( $this, 'print_section_info' ),
			$this->option_name . '_settings'
		);
		add_settings_field(
			'enable',
			__( 'Charge Enable', 'wpbkash' ),
			array( $this, 'enable' ),
			$this->option_name . '_settings',
			$this->option_name . '_section'
		);
		add_settings_field(
			'label',
			__( 'Label', 'wpbkash' ),
			array( $this, 'label' ),
			$this->option_name . '_settings',
			$this->option_name . '_section'
		);
		add_settings_field(
			'type',
			__( 'Charge Type', 'wpbkash' ),
			array( $this, 'type' ),
			$this->option_name . '_settings',
			$this->option_name . '_section'
		);
		add_settings_field(
			'amount',
			__( 'Charge Amount', 'wpbkash' ),
			array( $this, 'amount' ),
			$this->option_name . '_settings',
			$this->option_name . '_section'
		);
		add_settings_field(
			'shipping',
			__( 'Include shipping costs', 'wpbkash' ),
			array( $this, 'shipping' ),
			$this->option_name . '_settings',
			$this->option_name . '_section'
		);
		add_settings_field(
			'minimum',
			__( 'Minimum cart amount', 'wpbkash' ),
			array( $this, 'minimum' ),
			$this->option_name . '_settings',
			$this->option_name . '_section'
		);
		add_settings_field(
			'maximum',
			__( 'Maximum cart amount', 'wpbkash' ),
			array( $this, 'maximum' ),
			$this->option_name . '_settings',
			$this->option_name . '_section'
		);
		add_settings_field(
			'tax',
			__( 'Apply Tax', 'wpbkash' ),
			array( $this, 'tax' ),
			$this->option_name . '_settings',
			$this->option_name . '_section'
		);
		add_settings_field(
			'tax_class',
			__( 'Tax Class', 'wpbkash' ),
			array( $this, 'tax_class' ),
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

	public function show_settings() {
		$sections           = $this->get_sections();
		$active_section     = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] )
			: 'fee';
		$number_of_sections = count( $sections );
		$number             = 0;
		?>
		<div class="wp-clearfix">
			<ul class="subsubsub">
				<?php
				foreach ( $sections as $section_key => $section_title ) {
					$number ++;
					$class = '';
					if ( $active_section == $section_key ) {
						$class = 'current';
					}
					?>
					<li>
						<a class="<?php echo $class; ?>"
						href="
						<?php
						echo admin_url( 'admin.php?page=wpbkash_settings&tab=extra-settings' ) .
											'&section='
											. $section_key;
						?>
											"><?php echo $section_title; ?></a>
						<?php
						if ( $number != $number_of_sections ) {
							echo ' | ';
						}
						?>
					</li>
					<?php
				}
				?>
			</ul>
		</div>
		<div id="tab_container" class="general-tab">
			<?php
			if ( $active_section === 'fee' ) {
				$this->add_settings_page();
				submit_button( __( 'Submit', 'wpbkash' ) );
			}

			if ( $active_section === 'invoice' ) {
				Invoice::instance()->add_settings_page();
				submit_button( __( 'Submit', 'wpbkash' ) );
			}

			?>
		</div>
		<?php
	}

	public function get_sections() {
		$sections = array(
			'fee'     => __( 'bKash Fee', 'content-workflow' ),
			'invoice' => __( 'Invoice Generator', 'content-workflow' ),
		);

		return $sections;
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
		print __( 'bKash Fee settings for bKash Payment.', 'wpbkash' );
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function enable() {
		?>
		<label for="enable">
			<input type="checkbox" id="enable" name="wpbkash_extra_fields[enable]" value="1" 
			<?php
			if ( isset( $this->options['enable'] ) && 1 == $this->options['enable'] ) {
				echo 'checked="checked"';
			}
			?>
			 />
			<?php esc_html_e( 'Enable', 'wpbkash' ); ?>
		</label>
		<?php
	}

	/**
	 * Get the bKash charge
	 */
	public function type() {
		?>
		<select name="wpbkash_extra_fields[type]" id="type">
			<option value="percentage" 
			<?php
			if ( isset( $this->options['type'] ) && $this->options['type'] == 'parcentage' ) {
				echo 'selected="selected"';}
			?>
			><?php esc_html_e( 'Percentage', 'wpbkash' ); ?></option>
			<option value="fixed" 
			<?php
			if ( isset( $this->options['type'] ) && $this->options['type'] == 'fixed' ) {
				echo 'selected="selected"';}
			?>
			><?php esc_html_e( 'Fixed', 'wpbkash' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Get the bKash charge amount
	 */
	public function amount() {
		printf(
			'<input type="number" size="50" id="amount" name="wpbkash_extra_fields[amount]" value="%s" />',
			isset( $this->options['amount'] ) ? esc_attr( $this->options['amount'] ) : ''
		);
	}

	/**
	 * Label for fee field
	 */
	public function label() {
		printf(
			'<input type="text" id="label" name="wpbkash_extra_fields[label]" value="%s" />',
			isset( $this->options['label'] ) ? esc_attr( $this->options['label'] ) : ''
		);
	}

	/**
	 * Enable shipping cost include with sub total.
	 */
	public function shipping() {
		?>
		<label for="shipping">
			<input type="checkbox" id="shipping" name="wpbkash_extra_fields[shipping]" value="1" 
			<?php
			if ( isset( $this->options['shipping'] ) && 1 == $this->options['shipping'] ) {
				echo 'checked="checked"';
			}
			?>
			 />
			 <i><?php esc_html_e( 'Calculate the cart total based on the combined cost of products and shipping', 'wpbkash' ); ?></i>
		</label>
		<?php
	}

	/**
	 * Minimum cart amount
	 */
	public function minimum() {
		printf(
			'<input type="number" size="50" id="minimum" name="wpbkash_extra_fields[minimum]" value="%s" />',
			isset( $this->options['minimum'] ) ? esc_attr( $this->options['minimum'] ) : ''
		);
	}

	/**
	 * Maximum cart amount
	 */
	public function maximum() {
		printf(
			'<input type="number" size="50" id="maximum" name="wpbkash_extra_fields[maximum]" value="%s" />',
			isset( $this->options['maximum'] ) ? esc_attr( $this->options['maximum'] ) : ''
		);
	}

	/**
	 * Enable shipping cost include with sub total.
	 */
	public function tax() {
		?>
		<label for="tax">
			<input type="checkbox" id="tax" name="wpbkash_extra_fields[tax]" value="1" 
			<?php
			if ( isset( $this->options['tax'] ) && 1 == $this->options['tax'] ) {
				echo 'checked="checked"';
			}
			?>
			 />
			 <?php esc_html_e( 'Enable', 'wpbkash' ); ?>
		</label>
		<?php
	}

	/**
	 * Get the bKash charge
	 */
	public function tax_class() {
		?>
		<select name="wpbkash_extra_fields[tax_class]" id="type">
			<option value="standard" 
			<?php
			if ( isset( $this->options['tax_class'] ) && $this->options['tax_class'] == 'parcentage' ) {
				echo 'selected="selected"';}
			?>
			><?php esc_html_e( 'Standard', 'wpbkash' ); ?></option>
			<?php
			$get_tax_classes = \WC_Tax::get_tax_classes();
			if ( ! empty( $get_tax_classes ) ) {
				foreach ( (array) $get_tax_classes as $tax_class ) {
					$selected = ( isset( $this->options['tax_class'] ) && $this->options['tax_class'] == $tax_class ) ? 'selected="selected"' : '';
					echo '<option value="' . esc_attr( $tax_class ) . '" ' . $selected . '>' . esc_html( $tax_class ) . '</option>';
				}
			}
			?>
						
		</select>
		<?php
	}

}
