<?php
/**
 * @package WPbKash
 */
namespace Themepaw\bKash\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Themepaw\bKash\Api\Query;
use Themepaw\bKash\Utility;

/**
 * WPbkash Settings class
 */
class Settings {

	/**
	 * The single instance of Settings.
	 *
	 * @var    object
	 * @access private
	 * @since  1.0.0
	 */
	private static $instance = null;

	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;

	/**
	 * WP_List_Table object
	 */
	public $tabe_obj;

	/**
	 * Prefix for plugin settings.
	 *
	 * @var    string
	 * @access public
	 * @since  1.0.0
	 */
	public $base = '';

	public $tabs = array();

	public function __construct() {

		$this->base = 'wpbkash';

		// Register plugin settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'admin_post_update_entry', array( $this, 'form_handler' ) );

		add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );

		add_filter( 'set-screen-option', array( $this, 'set_screen' ), 10, 3 );

		add_action( 'update_option_wpbkash_general_fields', array( $this, 'trigger_on_update' ), 10, 2 );

		$this->tabs['general-settings'] = esc_html__( 'General', 'wpbkash' );
		$this->tabs['extra-settings']   = esc_html__( 'Extra', 'wpbkash' );
		$this->tabs['tools-settings']   = esc_html__( 'Tools', 'wpbkash' );
		$this->tabs['docs-settings']    = esc_html__( 'Docs Generator', 'wpbkash' );

        General::instance()->init();
        Extra::instance()->init();
        Tools::instance()->init();

	}

	/**
	 * Adds one or more classes to the body tag in the dashboard.
	 *
	 * @link https://wordpress.stackexchange.com/a/154951/17187
	 * @param  String $classes Current body classes.
	 * @return String          Altered body classes.
	 */
	public function admin_body_class( $classes ) {
		if ( isset( $_POST ) && ! empty( $_POST ) && isset( $_POST['wpbkash_docs_fields'] ) ) {
			return "$classes wpbkash-fullview";
		}
		return $classes;
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @param string $hook
	 */
	public function enqueue_scripts( $hook ) {

		if ( 'wpbkash_page_api-docs' === $hook ) {
			wp_enqueue_style( 'wpbkash_admin', WPBKASH_URL . 'assets/css/docs-style.css' );
		}

		wp_enqueue_style( 'wpbkash_admin', WPBKASH_URL . 'assets/css/admin-style.css' );
		wp_enqueue_script( 'wpbkash_admin', WPBKASH_URL . 'assets/js/admin.js', array( 'jquery' ), WPBKASH_VERSION, true );

		wp_localize_script(
			'wpbkash_admin',
			'wpbkash_params',
			array(
				'home_url' => esc_url( home_url() ),
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	/**
	 * Add settings page to admin menu
	 *
	 * @return void
	 */
	public function add_menu_item() {

		$hook = add_menu_page(
			esc_html__( 'WPbKash', 'wpbkash' ),
			esc_html__( 'WPbKash', 'wpbkash' ),
			'administrator',
			'wpbkash',
			array( $this, 'wpbkash_all_orders' ),
			WPBKASH_URL . 'assets/images/bkash.gif'
		);

		add_action( "load-$hook", array( $this, 'add_options' ) );

		add_submenu_page(
			'wpbkash',
			esc_html__( 'All Entries', 'wpbkash' ),
			esc_html__( 'All Entries', 'wpbkash' ),
			'administrator',
			'wpbkash'
		);

        /**
         * Not complete yet
         * 
         * Need to work with db base file
         * So, can call entry, refund function seperatly.
         */
        // $refund = add_submenu_page(
        //     'wpbkash',
		// 	esc_html__( 'Refund', 'wpbkash' ),
		// 	esc_html__( 'Refund', 'wpbkash' ),
		// 	'administrator',
		// 	'wpbkash_refunds',
		// 	array( $this, 'wpbkash_all_refunds' )
		// );

		// add_action( "load-$refund", array( $this, 'refund_page' ) );

		add_submenu_page(
			'wpbkash',
			esc_html__( 'WPbKash Settings', 'wpbkash' ),
			esc_html__( 'Settings', 'wpbkash' ),
			'administrator',
			'wpbkash_settings',
			array( $this, 'settings_page' )
		);

		add_submenu_page(
			'wpbkash',
			esc_html__( 'API Reuqest Docs', 'wpbkash' ),
			esc_html__( 'API Reuqest Docs', 'wpbkash' ),
			'administrator',
			'api-docs',
			array( $this, 'doc_build' )
		);

	}

	public function set_screen( $status, $option, $value ) {
		return $value;
	}

	function add_options() {
		$option = 'per_page';
		$args   = array(
			'label'   => 'Entry',
			'default' => 10,
			'option'  => 'entry_per_page',
		);
		add_screen_option( $option, $args );

		$this->tabe_obj = new EntryTable();
	}

    function refund_page() {
        $option = 'per_page';
		$args   = array(
			'label'   => 'Refund',
			'default' => 10,
			'option'  => 'refund_per_page',
		);
		add_screen_option( $option, $args );

		$this->tabe_obj = new RefundTable();
    }

	/**
	 * Display a custom menu page
	 */
	public function wpbkash_all_orders() {
		$entry = ( isset( $_GET['action'] ) && ! empty( $_GET['action'] ) ) ? sanitize_key( $_GET['action'] ) : '';

		switch ( $entry ) {
			case 'view':
				$template = dirname( __FILE__ ) . '/pages/entry/view.php';
				break;

			case 'edit':
				$template = dirname( __FILE__ ) . '/pages/entry/edit.php';
				break;

			default:
				$template = dirname( __FILE__ ) . '/pages/entry/list.php';
				break;
		}

		if ( file_exists( $template ) ) {
			include $template;
		}
	}

    public function wpbkash_all_refunds() {
        $refund = ( isset( $_GET['action'] ) && ! empty( $_GET['action'] ) ) ? sanitize_key( $_GET['action'] ) : '';

		switch ( $refund ) {
			case 'view':
				$template = dirname( __FILE__ ) . '/pages/refund/view.php';
				break;

			case 'edit':
				$template = dirname( __FILE__ ) . '/pages/refund/edit.php';
				break;

			default:
				$template = dirname( __FILE__ ) . '/pages/refund/list.php';
				break;
		}

		if ( file_exists( $template ) ) {
			include $template;
		}
    }

	public function form_handler() {

		if ( ! isset( $_POST['update_entry'] ) && ! isset( $_POST['entry_id'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Are you cheating?' );
		}

		if ( ! isset( $_POST['wpbkash_edit_nonce'] ) || ! wp_verify_nonce( $_POST['wpbkash_edit_nonce'], 'wpbkash_entry_edit' ) ) {
			wp_die( 'Are you cheating?' );
		}

		$entry_id = isset( $_POST['entry_id'] ) ? intval( $_POST['entry_id'] ) : 0;

		$fields = array(
			'trx_id'     => isset( $_POST['trx_id'] ) ? sanitize_key( $_POST['trx_id'] ) : '',
			'trx_status' => isset( $_POST['trx_status'] ) ? sanitize_key( $_POST['trx_status'] ) : 'pending',
			'amount'     => isset( $_POST['amount'] ) ? intval( $_POST['amount'] ) : '',
			'status'     => isset( $_POST['status'] ) ? sanitize_key( $_POST['status'] ) : 'pending',
			'updated_at' => current_time( 'mysql' ),
		);

		$escapes = array(
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
		);

		$fields  = apply_filters( 'wpbkash_entry_update_fields', $fields );
		$escapes = apply_filters( 'wpbkash_entry_update_fields_escape', $escapes );

		$updated = wpbkash_entry_update( $entry_id, $fields, $escapes );

		if ( is_wp_error( $updated ) ) {
			wp_die( $updated->get_error_message() );
		}

		if ( $updated ) {
			$redirected_to = admin_url( 'admin.php?page=wpbkash&entry=' . $entry_id . '&action=edit&entry-updated=true' );
		} else {
			$redirected_to = admin_url( 'admin.php?page=wpbkash&entry=' . $entry_id . '&action=edit&entry-updated=false' );
		}

		wp_redirect( $redirected_to );
		exit;

	}

	/**
	 * Register plugin settings
	 *
	 * @return void
	 */
	public function register_settings() {

		if ( isset( $_POST ) &&
			! empty( $_POST ) &&
			isset( $_POST['wpbkash_grant'] ) &&
			wp_verify_nonce( $_POST['wpbkash_grant'], 'wpbkash_clear_token' )
		) {
			delete_transient( 'wpbkash_token_key' );
		}

		add_settings_section(
			'docs_section_id',
			esc_html__( 'WPbKash Docs', 'wpbkash' ),
			array( $this, 'print_doc_info' ),
			'wpbkash_docs'
		);

		add_settings_field(
			'payment_id',
			esc_html__( 'Payment ID', 'wpbkash' ),
			array( $this, 'payment_id' ),
			'wpbkash_docs',
			'docs_section_id'
		);

		add_settings_field(
			'first_error_invoice',
			esc_html__( 'First error invoice number', 'wpbkash' ),
			array( $this, 'first_error_invoice' ),
			'wpbkash_docs',
			'docs_section_id'
		);

		add_settings_field(
			'first_error_timestamp',
			esc_html__( 'First error timestamp', 'wpbkash' ),
			array( $this, 'first_error_timestamp' ),
			'wpbkash_docs',
			'docs_section_id'
		);

		add_settings_field(
			'first_error_screenshot',
			esc_html__( 'First error screenshot url', 'wpbkash' ),
			array( $this, 'first_error_screenshot' ),
			'wpbkash_docs',
			'docs_section_id'
		);

		add_settings_field(
			'second_error_invoice',
			esc_html__( 'Second error invoice number', 'wpbkash' ),
			array( $this, 'second_error_invoice' ),
			'wpbkash_docs',
			'docs_section_id'
		);

		add_settings_field(
			'second_error_timestamp',
			esc_html__( 'Second error timestamp', 'wpbkash' ),
			array( $this, 'second_error_timestamp' ),
			'wpbkash_docs',
			'docs_section_id'
		);

		add_settings_field(
			'second_error_screenshot',
			esc_html__( 'Second error screenshot url', 'wpbkash' ),
			array( $this, 'second_error_screenshot' ),
			'wpbkash_docs',
			'docs_section_id'
		);

	}

	/**
	 * Trigger option update just check bkash connection
	 *
	 * @param string $old_value
	 * @param string $new_value
	 *
	 * @return void
	 */
	public function trigger_on_update( $old_value, $new_value ) {
		if ( ! empty( $new_value ) && $new_value !== $old_value ) {

			if ( empty( $new_value ) || empty( $new_value['app_key'] ) || empty( $new_value['app_secret'] ) || empty( $new_value['username'] ) || empty( $new_value['password'] ) ) {
				update_option( 'wpbkash__connection', 'wrong' );
				return false;
			}

			$token = Query::instance()->check_bkash_token();
            Utility::instance()->logger( $token );
			if ( ! empty( $token ) && false !== $token ) {
				update_option( 'wpbkash__connection', 'ok' );
			} else {
				update_option( 'wpbkash__connection', 'wrong' );
			}
		}
	}

	public function pretty_print( $data ) {
		$data = json_encode( $data, JSON_PRETTY_PRINT );
		return $data;
	}

	/**
	 * Doc print
	 */
	public function doc_build() {

		if ( isset( $_POST ) &&
			! empty( $_POST ) &&
			isset( $_POST['wpbkash_docs_fields'] ) &&
			isset( $_POST['wpbkash_security'] ) &&
			wp_verify_nonce( $_POST['wpbkash_security'], 'wpbkash_docs_nonce' )
		) :

			$wpbkash_docs_fields = $_POST['wpbkash_docs_fields'];
			$first_invoice       = isset( $wpbkash_docs_fields['first_invoice'] ) ? $wpbkash_docs_fields['first_invoice'] : '';
			$first_timestamp     = isset( $wpbkash_docs_fields['first_timestamp'] ) ? $wpbkash_docs_fields['first_timestamp'] : '';
			$first_screenshot    = isset( $wpbkash_docs_fields['first_screenshot'] ) ? $wpbkash_docs_fields['first_screenshot'] : '';
			$second_invoice      = isset( $wpbkash_docs_fields['second_invoice'] ) ? $wpbkash_docs_fields['second_invoice'] : '';
			$second_timestamp    = isset( $wpbkash_docs_fields['second_timestamp'] ) ? $wpbkash_docs_fields['second_timestamp'] : '';
			$second_screenshot   = isset( $wpbkash_docs_fields['second_screenshot'] ) ? $wpbkash_docs_fields['second_screenshot'] : '';
			$payment_id          = isset( $wpbkash_docs_fields['payment_id'] ) ? $wpbkash_docs_fields['payment_id'] : '';

			if ( ! empty( $payment_id ) ) {
				$option = get_option( 'wpbkash_general_fields' );
				if ( ! empty( $option['app_key'] ) && ! empty( $option['app_secret'] ) && ! empty( $option['username'] ) && ! empty( $option['password'] ) ) {
					$check_payment = Query::instance()->queryPayment( $payment_id );
				}
			}

			$get_requests = get_option( 'bkash_api_request' );

			echo '<div class="wpbkash--doc-area">';
			if ( empty( $get_requests ) ) {
				esc_html_e( 'Request empty. Try to use bKash checkout first.', 'wpbkash' );
				return;
			}

			wp_enqueue_style( 'wpbkash_admin' );

			foreach ( $get_requests as $key => $data ) {
				?>
			<div class="wpbkash--single-api-doc">
				<div class="wpbkash--doc-line">
					<h2><?php esc_html_e( 'API Ttitle', 'wpbkash' ); ?> :</h2>
					<h2><?php echo esc_html( $data['title'] ); ?></h2>
				</div>
				<div class="wpbkash--doc-line">
					<h2><?php esc_html_e( 'API URL', 'wpbkash' ); ?> :</h2>
					<h2 class="api-url"><?php echo esc_url( $data['url'] ); ?></h2>
				</div>
				<div class="wpbkash--doc-line">
					<h2><?php esc_html_e( 'Request Body', 'wpbkash' ); ?> :</h2>
					<div class="wpbkash--doc-inner-line">
						<h4><?php esc_html_e( 'Headers', 'wpbkash' ); ?> :</h4>
						<pre><?php print_r( $this->pretty_print( $data['headers'] ) ); ?></pre>
					</div>
					<div class="wpbkash--doc-inner-line">
						<h4><?php esc_html_e( 'Body params', 'wpbkash' ); ?> :</h4>
						<pre><?php print_r( $this->pretty_print( $data['body'] ) ); ?></pre>
					</div>
				</div>
				<div class="wpbkash--doc-line">
					<h2><?php esc_html_e( 'API Response', 'wpbkash' ); ?> :</h2>
					<pre><?php print_r( $this->pretty_print( $data['response'] ) ); ?></pre>
				</div>
			</div>
				<?php
			}
			?>
		
		<div class="wpbkash--err-implementation">
			<h2><?php _e( 'Error Message Implementation', 'wpbkash' ); ?></h2>
			<?php if ( ! empty( $first_invoice ) ) : ?>
			<div class="wpbkash--single-error-info">
				<div class="wpbkash--error-line"><strong><?php esc_html_e( 'Case#1', 'wpbkash' ); ?></strong></div>
				<div class="wpbkash--error-line"><strong><?php esc_html_e( 'Invoice number:', 'wpbkash' ); ?></strong> <?php echo esc_html( $first_invoice ); ?></div>
				<div class="wpbkash--error-line"><strong><?php esc_html_e( 'Time of Transaction:', 'wpbkash' ); ?></strong> <?php echo esc_html( $first_timestamp ); ?></div>
				<div class="wpbkash--error-line"><strong><?php esc_html_e( 'Screenshot:', 'wpbkash' ); ?></strong> <img src="<?php echo esc_url( $first_screenshot ); ?>" alt=""></div>
			</div>
				<?php
			endif;
			if ( ! empty( $second_invoice ) ) :
				?>
			<div class="wpbkash--single-error-info">
				<div class="wpbkash--error-line"><strong><?php esc_html_e( 'Case#2', 'wpbkash' ); ?></strong></div>
				<div class="wpbkash--error-line"><strong><?php esc_html_e( 'Invoice number:', 'wpbkash' ); ?></strong> <?php echo esc_html( $second_invoice ); ?></div>
				<div class="wpbkash--error-line"><strong><?php esc_html_e( 'Time of Transaction:', 'wpbkash' ); ?></strong> <?php echo esc_html( $second_timestamp ); ?></div>
				<div class="wpbkash--error-line"><strong><?php esc_html_e( 'Screenshot:', 'wpbkash' ); ?></strong> <img src="<?php echo esc_url( $second_screenshot ); ?>" alt=""></div>
			</div>
			<?php endif; ?>
		</div>
		
		
			<?php
			echo '</div>';
		else :
			$this->doc_settings_page();
		endif;
	}

	/**
	 * Doc settings page
	 */
	public function doc_settings_page() {
		?>
		<div class="wrap">
			 <form method="post">
				<?php
				do_settings_sections( 'wpbkash_docs' );
				wp_nonce_field( 'wpbkash_docs_nonce', 'wpbkash_security' );
				submit_button( esc_html__( 'Submit', 'wpbkash' ), 'primary' );
				?>
			 </form>
			 <form method="post">
				 <?php
					$token = get_transient( 'wpbkash_token_key' );
					wp_nonce_field( 'wpbkash_clear_token', 'wpbkash_grant' );
					?>
				 <p class="submit"><input type="submit" name="submit" id="submit" <?php echo ( empty( $token ) ) ? 'disabled="disabled"' : ''; ?> class="button button-primary" value="<?php esc_html_e( 'Clear Token Cache', 'wpbkash' ); ?>"></p>
			 </form>
		 </div>
		<?php
	}

	/**
	 * Load settings page content
	 *
	 * @return void
	 */
	public function settings_page() {

		$default_tab = 'general-settings';
		$active_tab  = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : $default_tab;
		$doc_url     = add_query_arg(
			array(
				'page' => 'api-docs',
			),
			admin_url( 'admin.php' )
		);

		?>
		 <div class="wrap">
			<h2><?php echo __( 'WPbKash Settings', 'content-workflow' ); ?></h2>
			<!-- Make a call to the WordPress function for rendering errors when settings are saved. -->
			<?php settings_errors(); ?>

			<?php
				echo '<h2 class="nav-tab-wrapper">';
			foreach ( $this->tabs as $tab => $name ) {
				$css_class = ( $tab == $active_tab ) ? ' nav-tab-active' : '';
				if ( 'docs-settings' === $tab ) {
					echo "<a class='nav-tab$css_class' href='$doc_url'>$name</a>";
				} else {
					echo "<a class='nav-tab$css_class' href='?page=wpbkash_settings&tab=$tab'>$name</a>";
				}
			}
				echo '</h2>';

			if ( $active_tab == 'tools-settings' ) {
				Tools::instance()->show_settings();

			}
			?>
			 <form method="post" id="wpbkash_settings_form" action="options.php">
				<?php

				if ( $active_tab == 'extra-settings' ) {
					Extra::instance()->show_settings();
				} elseif ( $active_tab == 'general-settings' ) {
					General::instance()->add_settings_page();
					submit_button( esc_html__( 'Submit', 'wpbkash' ) );
				}

				?>
			 </form>
		 </div>
		<?php
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input Contains all settings fields as array keys
	 */
	public function sanitize( $input ) {

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
	public function print_doc_info() {

		$connection = get_option( 'wpbkash__connection' );

		esc_html_e( 'NOTE: Before doing anything, make sure you clear the token cache first. after cleared token cache. Create bkash checkout using bkash payment gateway. Then grab those transaction invoice id and timestamp from wpbkash payment list.', 'wpbkash' );
	}


	/**
	 * Get payment id
	 */
	public function payment_id() {
		printf(
			'<input type="text" size="50" id="payment_id" name="wpbkash_docs_fields[payment_id]" value="%s" />',
			isset( $this->options['payment_id'] ) ? esc_attr( $this->options['payment_id'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function first_error_invoice() {
		printf(
			'<input type="text" size="50" id="first_invoice" name="wpbkash_docs_fields[first_invoice]" value="%s" />',
			isset( $this->options['first_invoice'] ) ? esc_attr( $this->options['first_invoice'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function first_error_timestamp() {
		printf(
			'<input type="text" size="50" id="first_timestamp" name="wpbkash_docs_fields[first_timestamp]" value="%s" />',
			isset( $this->options['first_timestamp'] ) ? esc_attr( $this->options['first_timestamp'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function first_error_screenshot() {
		printf(
			'<input type="text" size="50" id="first_screenshot" name="wpbkash_docs_fields[first_screenshot]" value="%s" />',
			isset( $this->options['first_screenshot'] ) ? esc_attr( $this->options['first_screenshot'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function second_error_invoice() {
		printf(
			'<input type="text" size="50" id="second_invoice" name="wpbkash_docs_fields[second_invoice]" value="%s" />',
			isset( $this->options['second_invoice'] ) ? esc_attr( $this->options['second_invoice'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function second_error_timestamp() {
		printf(
			'<input type="text" size="50" id="second_timestamp" name="wpbkash_docs_fields[second_timestamp]" value="%s" />',
			isset( $this->options['second_timestamp'] ) ? esc_attr( $this->options['second_timestamp'] ) : ''
		);
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function second_error_screenshot() {
		printf(
			'<input type="text" size="50" id="second_screenshot" name="wpbkash_docs_fields[second_screenshot]" value="%s" />',
			isset( $this->options['second_screenshot'] ) ? esc_attr( $this->options['second_screenshot'] ) : ''
		);
	}

	/**
	 * Main Settings Instance
	 *
	 * Ensures only one instance of Settings is loaded or can be loaded.
	 *
	 * @since  1.0.0
	 * @static
	 * @see    WordPress_Plugin_Template()
	 * @return Main Settings instance
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}
