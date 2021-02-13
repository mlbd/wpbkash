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

use Themepaw\bKash\Api\Query;

/*
 * Extra Settings Class
 * @since 1.0
 */

class Refund {

	protected $option_name = 'wpbkash_refund';
	protected $options;

    /**
	 * Call this method to get the singleton
	 *
	 * @return Refund|null
	 */
	public static function instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new Refund();
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
			esc_html__( 'Refund', 'wpbkash' ),
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
			<form id="wpbkash__search_form" action="wpbkash_refund">
                <div class="wpbkash--refund-layout">
                    <div class="wpbkash--form-column">
                        <label for="paymentIdForRefund"><?php esc_html_e( 'Payment ID', 'wpbkash' ); ?></label>
                        <input type="text" placeholder="<?php esc_attr_e( 'Payment ID', 'wpbkash' ); ?>" name="paymentIdForRefund" id="paymentIdForRefund">
                    </div>
                    <div class="wpbkash--form-column">
                        <label for="transactionIdForRefund"><?php esc_html_e( 'Transaction ID', 'wpbkash' ); ?></label>
                        <input type="text" placeholder="<?php esc_attr_e( 'Transaction ID', 'wpbkash' ); ?>" name="transactionIdForRefund" id="transactionIdForRefund">
                    </div>
                    <div class="wpbkash--form-column">
                        <label for="amountForRefund"><?php esc_html_e( 'Refund Amount', 'wpbkash' ); ?></label>
                        <input type="text" placeholder="<?php esc_attr_e( 'Refund Amount', 'wpbkash' ); ?>" name="amountForRefund" id="amountForRefund">
                    </div>
                    <div class="wpbkash--form-column">
                        <label for="skuForRefund"><?php esc_html_e( 'SKU (Name of items for refund)', 'wpbkash' ); ?></label>
                        <textarea name="skuForRefund" id="skuForRefund" placeholder="<?php esc_html_e( 'Sku', 'wpbkash' ); ?>"></textarea>
                    </div>
                    <div class="wpbkash--form-column">
                        <label for="reasonForRefund"><?php esc_html_e( 'Reason For Refund', 'wpbkash' ); ?></label>
                        <textarea name="reasonForRefund" id="reasonForRefund" placeholder="<?php esc_attr_e( 'Reason', 'wpbkash' ); ?>"></textarea>
                    </div>
                </div>
				<button type="submit" class="wpbkash__submit_btn"><?php esc_html_e( 'Refund', 'wpbkash' ); ?></button>
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
