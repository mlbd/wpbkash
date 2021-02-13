<?php
/**
 * @package WPbKash
 */
namespace Themepaw\bKash;

class Activate {

    /**
	 * Call this method to get the singleton
	 *
	 * @return Activate|null
	 */
	public static function instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new Activate();
		}

		return $instance;
	}

	/**
	 * Activate initialize
	 *
	 * @return void
	 */
	public function activate() {
		$this->create_table();
		$this->install();
		flush_rewrite_rules();
	}

	/**
	 * SQL for wpbkash db table.
	 */
	private function sql() {

        global $wpdb;

		$table  = $wpdb->prefix . 'wpbkash';
		$refund = $wpdb->prefix . 'wpbkash_refund';

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE `{$table}` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `trx_id` varchar(15) DEFAULT NULL,
            `trx_status` varchar(15) DEFAULT NULL,
            `sender` varchar(320) DEFAULT NULL,
            `ref` varchar(100) DEFAULT NULL,
            `ref_id` varchar(100) DEFAULT NULL,
            `payment_id` varchar(100) DEFAULT NULL,
            `invoice` varchar(100) DEFAULT NULL,
            `status` varchar(15) DEFAULT NULL,
            `amount` varchar(10) DEFAULT NULL,
            `created_at` datetime DEFAULT NULL,
            `updated_at` datetime DEFAULT NULL,
            `key_token` varchar(150) DEFAULT NULL,
            `key_created` datetime DEFAULT NULL,
            `data` longtext,
            `form_data` longtext,
            PRIMARY KEY (`id`),
            KEY `trx_id` (`trx_id`)
        ) $charset_collate;
        CREATE TABLE `{$refund}` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
            `originaltrxid` varchar(15) DEFAULT NULL,
            `refundtrxid` varchar(15) DEFAULT NULL,
            `charge` varchar(100) DEFAULT NULL,
            `currency` varchar(100) DEFAULT NULL,
            `transactionstatus` varchar(15) DEFAULT NULL,
            `amount` varchar(10) DEFAULT NULL,
            `completedtime` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `refundtrxid` (`refundtrxid`)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Create DB Table
	 *
	 * @return void
	 */
	public function create_table() {
		$prev_version = get_option( 'wpbkash_version' );
		$this->sql();
		if ( version_compare( $prev_version, WPBKASH_VERSION, '!=' ) ) {
			$sql = $this->sql();
		}
	}

	/**
	 * Update and store options when activated
	 *
	 * @return void
	 */
	public function install() {
		set_transient( 'wpbkash_flush', 1, 60 );
		update_option( 'wpbkash_version', WPBKASH_VERSION );
		$installed = get_option( 'wpbkash_installed' );
		if ( ! $installed ) {
			update_option( 'wpbkash_installed', time() );
		}
	}
}
