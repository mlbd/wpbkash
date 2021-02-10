<?php
/*
 * Settings class for Content Types settings
 *
 * @copyright   Copyright (c) 2020, Nugget Solutions, Inc
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 *
 */

namespace Themepaw\bKash;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/*
 * Extra Settings Class
 * @since 1.0
 */

class Invoice {

    /**
	 * Call this method to get the singleton
	 *
	 * @return Invoice|null
	 */
	public static function instance() {

		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new Invoice();
		}

		return $instance;
	}
        
    /**
     * Generate unique merchant invoice id.
     * 
     * @param string $namespace
     * 
     * @return string $invoice
     */
    public function get_random_invoice($namespace = '') {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $length = ! empty( $namespace ) ? 5 :  11;
        $length = apply_filters( 'wpbkash_random_invoice_length', $length, $namespace );
        $invoice = [];
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < $length; $i++) {
            $n = rand(0, $alphaLength);
            $invoice[] = $alphabet[$n];
        }
        $invoice = implode($invoice); //turn the array into a string
        if( ! empty( $namespace ) ) {
            $invoice = $namespace . $invoice;
        }
        $invoice = $this->check_invoice( $invoice );
        return $invoice;
    }

    /**
     * Get post id invoice
     * 
     * @param string $namespace
     * 
     * @return string $invoice
     */
    public function get_id_invoice( $namespace = '' ) {
        $invoice = $this->get_lastpost_id();
        $pad_length = apply_filters( 'wpbkash_pad_legth', 4, $namespace );
        $invoice = str_pad($invoice, $pad_length, '0', STR_PAD_LEFT); 
        if( ! empty( $namespace ) ) {
            $invoice = $namespace . $invoice;
        }
        $invoice = $this->check_invoice( $invoice );
        return $invoice;
    }

    /**
     * Get custom id invoice
     * 
     * @param string $namespace
     * 
     * @return string $invoice
     */
    public function custom_id_invoice( $namespace ) {
        $invoice = $this->get_custom_id();
        $pad_length = apply_filters( 'wpbkash_custom_pad_legth', 3, $namespace );
        $invoice = str_pad($invoice, $pad_length, '0', STR_PAD_LEFT); 
        if( ! empty( $namespace ) ) {
            $invoice = $namespace . $invoice;
        }
        $invoice = $this->check_invoice( $invoice );
        return $invoice;
    }

    /**
     * Get custom id invoice
     * 
     * @param string $namespace
     * 
     * @return int
     */
    public function get_custom_id() {
        $id = get_option( '_wpbkash_custom_token', 0 );
        $id = (int) $id;
        return $id+1;
    }

    /**
     * Check invoice already exists or not. If then add suffix to that and check again (loop)
     * 
     * @param string $invoice
     * 
     * @return string $invoice
     */
    public function check_invoice( $invoice ) {

        $check = $this->invoice_exists($invoice);
        if (!empty($check)) {
            $suffix = 1;
            while (!empty($check)) {
                $unique_invoice = $invoice . $suffix;
                $check = $this->invoice_exists($unique_invoice);
                $suffix++;
            }
            $invoice = $unique_invoice;
        }

        return $invoice;
    }

    /**
     * Get final invoice
     * 
     * @return string $invoice
     */
    public function get_invoice() {
        $option = get_option( 'wpbkash_invoice_fields' );
        $namespace = ( isset( $option['namespace'] ) && ! empty( $option['namespace'] ) ) ? sanitize_text_field( $option['namespace'] ) : '';
        $type = ( isset( $option['type'] ) && ! empty( $option['type'] ) ) ? sanitize_text_field( $option['type'] ) : 'random';
        
        if( 'post_id' === $type ) {
            $invoice = $this->get_id_invoice( $namespace );
        } elseif( 'number' === $type ) {
            $invoice = $this->custom_id_invoice( $namespace );
        } else {
            $invoice = $this->get_random_invoice( $namespace );
        }

        $invoice = strtoupper( $invoice );
        return $invoice;
    }

    /**
     * Check invoice meta exists in wc order
     * 
     * @param string $invoice
     * 
     * @return boolean
     */
    public function invoice_exists($invoice) {

        $iargs = array(
            'post_type'  => 'shop_order',
            'meta_query' => array(
                array(
                    'key'     => 'wpbkash_invoice',
                    'compare' => $invoice
                ),
            )
        );

        $order_list = new \WP_Query( $iargs );
        if ( $order_list->have_posts() ) {
            return true;
        }

        return false;
    }

    /**
     * Get last post ID
     */
    public function get_lastpost_id() {
        global $wpdb;

        $query = "SELECT ID FROM $wpdb->posts ORDER BY ID DESC LIMIT 0,1";

        $result = $wpdb->get_results($query);
        $row = $result[0];
        $id = $row->ID;

        return $id;
    }
   

}