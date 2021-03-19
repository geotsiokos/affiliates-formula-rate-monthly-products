<?php
/**
 * Plugin Name: Affiliates Formula Rate Monthly Products
 * Plugin URI: http://www.itthinx.com/shop/affiliates-pro/
 * Description: Affiliates Formula Rate Monthly Products
 * Version: 1.0.0
 * Author: gtsiokos
 * Author URI: netpad.gr
 * License: GPLv3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Affiliates_Formula_Rate_Monthly_Products {

	/**
	 * Init
	 */
	public static function init() {
		add_filter( 'affiliates_formula_computer_variables', array( __CLASS__, 'affiliates_formula_computer_variables' ), 10, 3 );
	}

	/**
	 * Sets new variable depending on previous months referrals
	 *
	 * @param array $variables
	 * @param object $rate
	 * @param array $context
	 * @return array
	 */
	public static function affiliates_formula_computer_variables( $variables, $rate, $context ) {

		// Get the monthly sales referred by each affiliate
		$affiliate_id = $context['affiliate_id'];
		$referrals    = self::get_affiliate_referrals( $affiliate_id );

		// The default value for the variable
		$variables['c'] = 0.05;

		// Checking previous month performance and adjust the commission rate
		if ( count( $referrals ) > 0 ) {
			$item_count  = 0;
			$total_items = 0;

			foreach ( $referrals as $post_id ) {
				$order = self::get_order( $post_id['post_id'] );
				if ( $order ) {
					$item_count = self::get_order_item_count( $order );
					$total_items += $item_count;

					// 5% 0-20 products sell in a month
					// 7% 21-40 products sell in a month
					// 10% 41-60 products sell in a month
					if ( $total_items > 0 && $total_items <= 20 ) {
						$variables['c'] = 0.05;
					} else if ( $total_items > 20 && $total_items <= 40 ) {
						$variables['c'] = 0.07;
					} else if ( $total_items > 40 && $total_items <= 60 ) {
						$variables['c'] = 0.1;
					} else {
						$variables['c'] = 0.05;
					}
				}
			}
		}
		return $variables;
	}

	/**
	 * Get affiliate referrals for previous month relative to the currect date
	 *
	 * @param int $affiliate_id
	 * @return array
	 */
	public static function get_affiliate_referrals( $affiliate_id ) {
		global $wpdb;
		$referrals_table = _affiliates_get_tablename( 'referrals' );
		$result = array();
		$from_date = date( 'Y-m-d', strtotime( 'first day of last month' ) );
		$thru_date = date( 'Y-m-d', strtotime( 'last day of last month' ) );

		// Query WHERE clause structure
		$where = " WHERE affiliate_id = %d";
		$values = array( $affiliate_id );
		$where .= " AND datetime >= %s AND datetime < %s ";
		$values[] = $from_date;
		$values[] = $thru_date;
		$where .= " AND type LIKE 'sale'";
		$where .= " AND integration LIKE 'affiliates-woocommerce'";
		$where .= " AND status LIKE 'accepted'";

		$query = $wpdb->prepare(
			"SELECT post_id FROM $referrals_table $where",
			$values
		);
		$result = $wpdb->get_results( $query, ARRAY_A );

		return $result;
	}

	/**
	 * Retrieve an order.
	 *
	 * @param int $order_id
	 * @return WC_Order or null
	 */
	public static function get_order( $order_id = '' ) {
		$result = null;
		if ( function_exists( 'wc_get_order' ) ) {
			if ( $order = wc_get_order( $order_id ) ) {
				$result = $order;
			}
		}
		return $result;
	}

	/**
	 * Get total items in a WC order
	 *
	 * @param WC_Order $order
	 * @return int
	 */
	public static function get_order_item_count( $order = null ) {
		$result = null;
		if ( function_exists( 'wc_get_order' ) ) {
			if ( $order ) {
				$result = $order->get_item_count();
			}
		}
		return intval( $result );
	}
} Affiliates_Formula_Rate_Monthly_Products::init();