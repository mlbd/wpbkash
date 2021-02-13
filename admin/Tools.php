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

class Tools {

	protected $option_name = 'wpbkash_tools';
	protected $options;

    /**
	 * Call this method to get the singleton
	 *
	 * @return Tools|null
	 */
	public static function instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new Tools();
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

        add_action( 'wp_ajax_wpbkash_search_transaction', array( $this, 'wpbkash_search_transaction' ) );
		add_action( 'wp_ajax_wpbkash_search_paystatus', array( $this, 'wpbkash_search_paystatus' ) );
		add_action( 'wp_ajax_wpbkash_refund', array( $this, 'wpbkash_refund' ) );
		add_action( 'wp_ajax_wpbkash_refund_status', array( $this, 'wpbkash_refund_status' ) );

		RefundStatus::instance()->init();
		Refund::instance()->init();
		Payment::instance()->init();
	}

	public function init_settings() {

		add_settings_section(
			$this->option_name . '_section',
			esc_html__( 'Search bKash Transaction', 'wpbkash' ),
			array( $this, 'print_section_info' ),
			$this->option_name . '_settings'
		);

	}

	/**
	 * Search transaction request.
	 */
	public function wpbkash_search_transaction() {

		check_ajax_referer( 'wpbkash_nonce', '_trx_wpnonce' );

		$trx = ! empty( $_POST['wpbkash_trx'] ) ? sanitize_text_field( $_POST['wpbkash_trx'] ) : '';

		if ( empty( $trx ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'Transaction number can\'t be empty.', 'wpbkash' ),
				)
			);
			wp_die();
		}

		$transaction = Query::instance()->searchTransaction( $trx );
		$transaction = \json_decode( $transaction );
		if ( isset( $transaction ) && ! empty( $transaction ) && isset( $transaction->transactionStatus ) ) {
			wp_send_json_success(
				array(
					'transaction' => $transaction,
				)
			);
			wp_die();
		}

		$msg = isset( $transaction->errorMessage ) ? $transaction->errorMessage : esc_html__( 'Something wen\'t wrong, please try again', 'wpbkash' );
		wp_send_json_error(
			array(
				'message' => $msg,
			)
		);
		wp_die();
	}

	/**
	 * Check payment status ajax request.
	 */
	public function wpbkash_search_paystatus() {

		check_ajax_referer( 'wpbkash_nonce', '_trx_wpnonce' );

		$paymentID = ! empty( $_POST['wpbkash_payment_id'] ) ? sanitize_text_field( $_POST['wpbkash_payment_id'] ) : '';

		if ( empty( $paymentID ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'payment ID can\'t be empty.', 'wpbkash' ),
				)
			);
			wp_die();
		}

		$transaction = Query::instance()->queryPayment( $paymentID );
		$transaction = \json_decode( $transaction );
		if ( isset( $transaction ) && ! empty( $transaction ) && isset( $transaction->transactionStatus ) ) {
			wp_send_json_success(
				array(
					'transaction' => $transaction,
				)
			);
			wp_die();
		}

		$msg = isset( $transaction->errorMessage ) ? $transaction->errorMessage : esc_html__( 'Something wen\'t wrong, please try again', 'wpbkash' );
		wp_send_json_error(
			array(
				'message' => $msg,
			)
		);
		wp_die();
	}

	/**
	 * Refund ajax request
	 */
	public function wpbkash_refund() {

		check_ajax_referer( 'wpbkash_nonce', '_trx_wpnonce' );

		$trxID     = ! empty( $_POST['transactionIdForRefund'] ) ? sanitize_text_field( $_POST['transactionIdForRefund'] ) : '';
		$paymentID = ! empty( $_POST['paymentIdForRefund'] ) ? sanitize_text_field( $_POST['paymentIdForRefund'] ) : '';
		$amount    = ! empty( $_POST['amountForRefund'] ) ? sanitize_text_field( $_POST['amountForRefund'] ) : '';
		$sku       = ! empty( $_POST['skuForRefund'] ) ? sanitize_text_field( $_POST['skuForRefund'] ) : '';
		$reason    = ! empty( $_POST['reasonForRefund'] ) ? sanitize_text_field( $_POST['reasonForRefund'] ) : '';

		if ( empty( $trxID ) || empty( $paymentID ) || empty( $amount ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'payment ID, Transaction ID or Amount can\'t be empty.', 'wpbkash' ),
				)
			);
			wp_die();
		}

		$transaction_data = array(
			'trxID'     => $trxID,
			'paymentID' => $paymentID,
			'amount'    => $amount,
			'sku'       => $sku,
			'reason'    => $reason,
		);

		$transaction = Query::instance()->refundTransaction( $transaction_data );
		$transaction = \json_decode( $transaction );
		if ( isset( $transaction ) && ! empty( $transaction ) && isset( $transaction->transactionStatus ) ) {
			$this->insert_refund( $transaction );
			wp_send_json_success(
				array(
					'transaction' => $transaction,
				)
			);
			wp_die();
		}

		$msg = isset( $transaction->errorMessage ) ? $transaction->errorMessage : esc_html__( 'Something wen\'t wrong, please try again', 'wpbkash' );
		wp_send_json_error(
			array(
				'message' => $msg,
			)
		);
		wp_die();
	}
	
    public function wpbkash_refund_status() {

		check_ajax_referer( 'wpbkash_nonce', '_trx_wpnonce' );

		$trxID     = ! empty( $_POST['transactionIdForRefund'] ) ? sanitize_text_field( $_POST['transactionIdForRefund'] ) : '';
		$paymentID = ! empty( $_POST['paymentIdForRefund'] ) ? sanitize_text_field( $_POST['paymentIdForRefund'] ) : '';

		if ( empty( $trxID ) || empty( $paymentID ) ) {
			wp_send_json_error(
				array(
					'message' => esc_html__( 'payment ID and Transaction ID can\'t be empty.', 'wpbkash' ),
				)
			);
			wp_die();
		}

		$transaction_data = array(
			'trxID'     => $trxID,
			'paymentID' => $paymentID
		);

		$transaction = Query::instance()->refundTransaction( $transaction_data );
		$transaction = \json_decode( $transaction );
		if ( isset( $transaction ) && ! empty( $transaction ) && isset( $transaction->transactionStatus ) ) {
			$this->insert_refund( $transaction );
			wp_send_json_success(
				array(
					'transaction' => $transaction,
				)
			);
			wp_die();
		}

		$msg = isset( $transaction->errorMessage ) ? $transaction->errorMessage : esc_html__( 'Something wen\'t wrong, please try again', 'wpbkash' );
		wp_send_json_error(
			array(
				'message' => $msg,
			)
		);
		wp_die();
	}

	/**
	 * Insert refund transaction
	 *
	 * @param object $response
	 */
	function insert_refund( $response ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'wpbkash_refund',
			array(
				'originaltrxid'     => sanitize_key( $response->originalTrxID ),
				'refundtrxid'       => sanitize_key( $response->refundTrxID ),
				'currency'          => sanitize_key( $response->currency ),
				'transactionstatus' => sanitize_text_field( $response->transactionStatus ),
				'completedtime'     => sanitize_text_field( $response->completedTime ),
				'amount'            => absint( $response->amount ),
				'charge'            => absint( $response->charge ),
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%s',
			)
		);

		return $wpdb->insert_id;
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
			<form id="wpbkash__search_form" action="wpbkash_search_transaction">
				<input type="text" name="wpbkash_trx" autocomplete="off" placeholder="<?php esc_attr_e( 'bKash Transaction Number', 'wpbkash' ); ?>">
				<button type="submit" class="wpbkash__submit_btn"><?php esc_html_e( 'Submit', 'wpbkash' ); ?></button>
				<?php wp_nonce_field( 'wpbkash_nonce', '_trx_wpnonce' ); ?>
			</form>
			<div class="wpbkash--search-result"></div>
		</div>
		<?php
	}

	public function show_settings() {
		$sections           = $this->get_sections();
		$active_section     = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] )
			: 'search';
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
						echo esc_url( admin_url( 'admin.php?page=wpbkash_settings&tab=tools-settings' ) ) .
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
			if ( $active_section === 'search' ) {
				$this->add_settings_page();
			}

			if ( $active_section === 'payment' ) {
                Payment::instance()->add_settings_page();
			}

			if ( $active_section === 'refund' ) {
                Refund::instance()->add_settings_page();
			}

			if ( $active_section === 'refund-status' ) {
                RefundStatus::instance()->add_settings_page();
			}

			?>
		</div>
		<?php
	}

	public function get_sections() {
		$sections = array(
			'search'        => esc_html__( 'Search TrxID', 'content-workflow' ),
			'payment'       => esc_html__( 'Payment Status', 'content-workflow' ),
			'refund'        => esc_html__( 'Refund', 'content-workflow' ),
			'refund-status' => esc_html__( 'Refund Status', 'content-workflow' ),
		);

		return $sections;
	}


	/**
	 * Print the Section text
	 */
	public function print_section_info() {}


}
