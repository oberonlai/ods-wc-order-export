<?php

/**
 * 1.引入 class
 * 2.訂單新增系統傳送狀態欄位，用來紀錄該訂單是否已被傳送至企業內部系統
 * 3.處理 Hooks
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

require plugin_dir_path( __FILE__ ) . 'class-ods-helper.php';
require plugin_dir_path( __FILE__ ) . 'class-ods-wc-admin.php';
require plugin_dir_path( __FILE__ ) . 'class-ods-wc-order.php';
require plugin_dir_path( __FILE__ ) . 'class-ods-csv.php';
require plugin_dir_path( __FILE__ ) . 'class-ods-ftp.php';

if(!class_exists('ODS_Hook')){
  
  class ODS_Hook {
    
    private $plugin_version;

    function __construct( $wc_admin, $csv ){

      // 第一次啟用外掛時加入所有 WC 訂單的傳送狀態欄位
      $this->plugin_version = get_option('ods_wc_order_export');
      if( $this->plugin_version == '' ){
        update_option( 'ods_wc_order_export', 'enable' );
        add_action('init',array($wc_admin,'set_order_field'),99,'9999,1');
      }
      
      // 新訂單加入系統傳送狀態的 Post Meta
      if( get_option('ods_wc_order_export') === 'enable' ){
        add_action('woocommerce_new_order',array($wc_admin,'set_order_field'),10,'1,1');
      }

      // 訂單列表顯示系統傳送狀態
      add_filter('manage_shop_order_posts_columns', array($wc_admin,'set_admin_th'),99);
      add_action('manage_shop_order_posts_custom_column' , array($wc_admin,'set_admin_columns'), 10, 2 );

      // 訂單列表操作介面
      add_filter( 'woocommerce_shop_order_search_fields', array($wc_admin,'set_status_seach'),100 );
      
      // 訂單篩選下拉選單
      add_action( 'restrict_manage_posts',  array($wc_admin,'set_status_filter'),20 );
      add_filter( 'parse_query', array($wc_admin,'get_status_filter_result'),99,1 );
      
      // 訂單批次操作加入新動作
      add_filter( 'bulk_actions-edit-shop_order', array($csv,'set_send_custom_bulk_actions'),10,1 );
      add_filter( 'handle_bulk_actions-edit-shop_order', array($csv,'set_send_custom_bulk_actions_handler'), 10, 3 );
      add_action( 'admin_notices', array($csv,'set_send_custom_bulk_actions_notice'),20 );

      // 把系統傳送狀態加入 wc order query 的搜尋條件
      add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', 'ODS_WC_Order::set_order_query_custom_key', 10, 2 );
      
      // 系統狀態未傳送訂單自動匯出
      add_action('ods_wc_order_export_cron', array( $csv, 'set_send_transfer_delivered'));
    }
  }
  
  $erp = new ODS_Hook(
    new ODS_WC_Admin(),
    new ODS_CSV()
  );
}