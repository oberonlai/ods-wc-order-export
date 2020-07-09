<?php

/**
 * Plugin Name:       ODS WooCommerce Order Export
 * Plugin URI:        https://oberonlai.blog
 * Description:       WooCommerce 客製化報表自動匯出&上傳功能
 * Version:           1.0.1
 * Author:            Oberon Lai
 * Author URI:        https://oberonlai.blog
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

// WC 訂單傳送狀態欄位名稱
const ODS_WC_ORDER_EXPORT_META_KEY = 'export_status';             

// WC 訂單傳送狀態欄位值
const ODS_WC_ORDER_EXPORT_META_VALUE = array('未傳送','已傳送','取消傳送');

// FTP 上傳資訊
const ODS_FTP_DATA = array(
  'enable'  => false, // 啟用/停用
  'host'    => '',
  'user'    => '',
  'pass'    => '',
  'path'    => 'wp-content/uploads/',
);

// 引入主要類別檔
require plugin_dir_path( __FILE__ ) . 'class/class-ods-hook.php';

// 加入排程 Hook
function sp_activation(){
  if ( ! wp_next_scheduled( 'ods_wc_order_export_cron' ) ) {
    wp_schedule_event( strtotime('9:00:00'), 'daily', 'ods_wc_order_export_cron' );
  }
}
register_activation_hook( __FILE__, 'sp_activation');

function sp_deactivation(){
  wp_clear_scheduled_hook('ods_wc_order_export_cron');
}
register_deactivation_hook( __FILE__, 'sp_deactivation');