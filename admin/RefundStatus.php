<?php
/**
 * @package WPbKash
 */

namespace Themepaw\bKash\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

use Themepaw\bKash\Api\Query;

/*
 * Extra Settings Class
 * @since 1.0
 */

class RefundStatus {

	protected $option_name = 'wpbkash_refund_status';
	protected $options;

    /**
	 * Call this method to get the singleton
	 *
	 * @return RefundStatus|null
	 */
	public static function instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new RefundStatus();
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

		add_settings_section(
			$this->option_name . '_section',
			esc_html__( 'Refund Status', 'wpbkash' ),
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
		do_settings_sections( $this->option_name . '_settings' );
		?>
		<div class="wpbkash--search-wrapper">
			<form id="wpbkash__search_form" action="wpbkash_refund_status">
                <div class="wpbkash--refund-layout">
                    <div class="wpbkash--form-column">
                        <label for="paymentIdForRefund"><?php esc_html_e( 'Payment ID', 'wpbkash' ); ?></label>
                        <input type="text" placeholder="<?php esc_attr_e( 'Payment ID', 'wpbkash' ); ?>" name="paymentIdForRefund" id="paymentIdForRefund">
                    </div>
                    <div class="wpbkash--form-column">
                        <label for="transactionIdForRefund"><?php esc_html_e( 'Transaction ID', 'wpbkash' ); ?></label>
                        <input type="text" placeholder="<?php esc_attr_e( 'Transaction ID', 'wpbkash' ); ?>" name="transactionIdForRefund" id="transactionIdForRefund">
                    </div>
                </div>
				<button type="submit" class="wpbkash__submit_btn"><?php esc_html_e( 'Submit', 'wpbkash' ); ?></button>
				<?php wp_nonce_field( 'wpbkash_nonce', '_trx_wpnonce' ); ?>
			</form>
			<div class="wpbkash--search-result"></div>
		</div>
		<?php
	}

	/**
	 * Print the Section text
	 */
	public function print_section_info() {}
}
