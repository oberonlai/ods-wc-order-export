<?php

/**
 * 取得訂單資訊
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if(!class_exists('ODS_WC_Order')){
  class ODS_WC_Order {

    /**
		 * 取得訂單內容
		 *
		 * @param int $id 訂單 post id
		 * @param string $field 訂單主要欄位名稱
		 * @param string $subfield 訂單次要欄位名稱
		 * 
		 * @return array $order_data 訂單資料
		 */
    public static function get_order_detail($id, $field = null, $subfield = null, $filter = array()) {

			if (is_wp_error($id))
					return false;

			// Get the decimal precession
			$dp = (isset($filter['dp'])) ? intval($filter['dp']) : 0;
			$order = wc_get_order($id); //getting order Object

			if ($order === false)
					return false;
			
			// 基本資料
			$order_data = array(
				'id' 													=> $order->get_id(),
				'order_number' 								=> $order->get_order_number(),
				'created_at' 									=> $order->get_date_created()->date('Y-m-d'),
				'updated_at' 									=> $order->get_date_modified()->date('Y-m-d'),
				'completed_at' 								=> !empty($order->get_date_completed()) ? $order->get_date_completed()->date('Y-m-d H:i:s') : '',
				'status' 											=> $order->get_status(),
				'currency' 										=> $order->get_currency(),
				'total' 											=> wc_format_decimal($order->get_total(), $dp),
				'subtotal' 										=> wc_format_decimal($order->get_subtotal(), $dp),
				'total_line_items_quantity' 	=> $order->get_item_count(),
				'total_tax' 									=> wc_format_decimal($order->get_total_tax(), $dp),
				'total_shipping' 							=> wc_format_decimal($order->get_total_shipping(), $dp),
				'cart_tax' 										=> wc_format_decimal($order->get_cart_tax(), $dp),
				'shipping_tax' 								=> wc_format_decimal($order->get_shipping_tax(), $dp),
				'total_discount' 							=> wc_format_decimal($order->get_total_discount(), $dp),
				'shipping_methods' 						=> $order->get_shipping_method(),
				'order_key' 									=> $order->get_order_key(),
				'send_status'									=> get_post_meta( $id, ODS_WC_ORDER_EXPORT_META_KEY, true ),
				'payment_details' 						=> array(
					'method_id' 									=> $order->get_payment_method(),
					'method_title' 								=> $order->get_payment_method_title(),
					'paid_at' 										=> !empty($order->get_date_paid()) ? $order->get_date_paid()->date('Y-m-d H:i:s') : '',
				),
				'billing_address' 						=> array(
					'first_name'									=> $order->get_billing_first_name(),
					'last_name' 									=> $order->get_billing_last_name(),
					'company' 										=> $order->get_billing_company(),
					'address_1' 									=> $order->get_billing_address_1(),
					'address_2' 									=> $order->get_billing_address_2(),
					'city' 												=> $order->get_billing_city(),
					'state' 											=> $order->get_billing_state(),
					'postcode' 										=> $order->get_billing_postcode(),
					'country' 										=> $order->get_billing_country(),
					'email' 											=> $order->get_billing_email(),
					'phone' 											=> $order->get_billing_phone()
				),
				'shipping_address' 						=> array(
					'first_name' 									=> $order->get_shipping_first_name(),
					'last_name' 									=> $order->get_shipping_last_name(),
					'company' 										=> $order->get_shipping_company(),
					'address_1' 									=> $order->get_shipping_address_1(),
					'address_2' 									=> $order->get_shipping_address_2(),
					'city' 												=> $order->get_shipping_city(),
					'state' 											=> $order->get_shipping_state(),
					'postcode' 										=> $order->get_shipping_postcode(),
					'country' 										=> $order->get_shipping_country(),
				),
				'taiwan_address'							=> $order->get_shipping_state().$order->get_shipping_city().$order->get_shipping_address_1(),
				'note' 												=> $order->get_customer_note(),
				'customer_ip' 								=> $order->get_customer_ip_address(),
				'customer_user_agent' 				=> $order->get_customer_user_agent(),
				'customer_id' 								=> $order->get_user_id(),
				'view_order_url' 							=> $order->get_view_order_url(),
				'shipping_lines' 							=> array(),
				'tax_lines' 									=> array(),
				'fee_lines' 									=> array(),
				'coupon_lines' 								=> array(),
			);
			
			foreach ($order->get_items() as $item_id => $item) {
				$product = $item->get_product();
				$product_id = null;
				$product_sku = null;
				if (is_object($product)) {
					$product_id = $product->get_id();
					$product_sku = $product->get_sku();
				}


				// 商品資料
				$order_data['line_items'][] = array(
					'id' 												=> $item_id,
					'subtotal' 									=> wc_format_decimal($order->get_line_subtotal($item, false, false), $dp),
					'subtotal_tax' 							=> wc_format_decimal($item['line_subtotal_tax'], $dp),
					'total' 										=> wc_format_decimal($order->get_line_total($item, false, false), $dp),
					'total_tax' 								=> wc_format_decimal($item['line_tax'], $dp),
					'price' 										=> wc_format_decimal($order->get_item_total($item, false, false), $dp),
					'regular_price' 						=> $product->get_regular_price(),
					'quantity' 									=> wc_stock_amount($item['qty']),
					'tax_class' 								=> (!empty($item['tax_class']) ) ? $item['tax_class'] : null,
					'name' 											=> $item['name'],
					'product_id' 								=> (!empty($item->get_variation_id()) && ('product_variation' === $product->post_type )) ? $product->get_parent_id() : $product_id,
					'variation_id' 							=> (!empty($item->get_variation_id()) && ('product_variation' === $product->post_type )) ? $product_id : 0,
					'product_url' 							=> get_permalink($product_id),
					'product_thumbnail_url' 		=> wp_get_attachment_image_src(get_post_thumbnail_id($product_id), 'thumbnail', TRUE)[0],
					'sku' 											=> $product_sku,
					'meta' 											=> wc_display_item_meta($item, ['echo' => false]),
					'product_discount'					=> wc_format_decimal( $item->get_subtotal() - $item->get_total(), ''),
				);

			}
			
			// 運費資料
			foreach ($order->get_shipping_methods() as $shipping_item_id => $shipping_item) {
				$order_data['shipping_lines'][] = array(
					'id' 					 	=> $shipping_item_id,
					'method_id'		 	=> $shipping_item['method_id'],
					'method_title' 	=> $shipping_item['name'],
					'total' 				=> wc_format_decimal($shipping_item['cost'], $dp),
				);
			}

			// 税相關資料
			foreach ($order->get_tax_totals() as $tax_code => $tax) {
				$order_data['tax_lines'][] = array(
					'id' 				=> $tax->id,
					'rate_id' 	=> $tax->rate_id,
					'code' 			=> $tax_code,
					'title' 		=> $tax->label,
					'total'			=> wc_format_decimal($tax->amount, $dp),
					'compound' 	=> (bool) $tax->is_compound,
				);
			}

			// 費用資料
			foreach ($order->get_fees() as $fee_item_id => $fee_item) {
				$order_data['fee_lines'][] = array(
					'id' 					=> $fee_item_id,
					'title' 			=> $fee_item['name'],
					'tax_class' 	=> (!empty($fee_item['tax_class']) ) ? $fee_item['tax_class'] : null,
					'total' 			=> wc_format_decimal($order->get_line_total($fee_item), $dp),
					'total_tax' 	=> wc_format_decimal($order->get_line_tax($fee_item), $dp),
				);
			}

			// 折價券資料
			foreach ($order->get_items('coupon') as $coupon_item_id => $coupon_item) {
				$order_data['coupon_lines'][] = array(
					'id' 			=> $coupon_item_id,
					'code' 		=> $coupon_item['name'],
					'amount' 	=> wc_format_decimal($coupon_item['discount_amount'], $dp),
				);
			}

			if( $field === 'line_items'){
				$line_items = array();
				$i=0;
				foreach ($order->get_items() as $item_id => $item) {
					$line_items[] = $order_data[$field][$i][$subfield];
					$i++;
				};
				return $line_items;
			} else {
				return ($subfield)?$order_data[$field][$subfield]:$order_data[$field];
			}
		}

		/**
     * 加入 order query 搜尋條件系統傳送狀態
     */
    public static function set_order_query_custom_key( $query, $query_vars ) {
      if ( ! empty( $query_vars[ODS_WC_ORDER_EXPORT_META_KEY] ) ) {
        $query['meta_query'][] = array(
          'key' => ODS_WC_ORDER_EXPORT_META_KEY,
          'value' => esc_attr( $query_vars[ODS_WC_ORDER_EXPORT_META_KEY] ),
        );
      }
      return $query;
    }
  }
}