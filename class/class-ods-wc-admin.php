<?php

/**
 * 處理後台系統傳送欄位註冊、介面顯示
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if(!class_exists('ODS_WC_Admin')){
  
  class ODS_WC_Admin {
    /**
     * 註冊訂單列表系統傳送狀態顯示的標頭
     * 
     * @param array $columns 現有欄位
     * 
     * @return array $columns 新增欄位
     */
    public function set_admin_th( $columns ){
      $columns['send_status'] = '系統傳送狀態';    
      return $columns;
    }

    /**
     * 新增訂單的系統傳送狀態欄位
     */
    public function set_order_field( $num ){
      $args = array(
        'numberposts' => $num
      );
      $wc_orders = wc_get_orders( $args );
      if(!empty($wc_orders)){
        foreach ($wc_orders as $order) {
          if ( !metadata_exists( 'post', $order->get_id(), ODS_WC_ORDER_EXPORT_META_KEY ) ) {
            update_post_meta( $order->get_id(), ODS_WC_ORDER_EXPORT_META_KEY, ODS_WC_ORDER_EXPORT_META_VALUE[0] );
          }
        }
      }
    }

    /**
     * 註冊訂單列表的系統傳送狀態顯示
     * 
     * @param string $column_name 欄位名稱
     * @param int $post_id 訂單 ID
     * 
     * @return string $send_status 訂單系統傳送狀態顯示文字
     */
    public function set_admin_columns( $column_name, $post_id ){
      switch ( $column_name ) {
        case 'send_status':
          $order = new WC_Order( $post_id );
          echo $send_status = $order->get_meta(ODS_WC_ORDER_EXPORT_META_KEY);
          break;
      }
    }  

    /**
     * 訂單列表加入可搜尋系統傳送狀態關鍵字功能
     * 
     * @param array $serach_fields 搜尋欄位
     * 
     * @return array $search_fields 加入系統傳送搜尋狀態的欄位
     */
    public function set_status_seach($search_fields){
      $search_fields[] = ODS_WC_ORDER_EXPORT_META_KEY;
      return $search_fields;
    }

    /**
     * 訂單列表加入系統傳送狀態查詢下拉選單
     */
    public function set_status_filter(){
      global $typenow;
      global $wp_query;
        if ( $typenow == 'shop_order' ) { // Your custom post type slug
          $plugins = ODS_WC_ORDER_EXPORT_META_VALUE;
          $current_plugin = '';
          if( isset( $_GET['send_status'] ) ) {
            $current_plugin = $_GET['send_status']; // Check if option has been selected
          } ?>
          <select name="send_status" id="send_status" style="width: 200px;">
            <option value="all" <?php selected( 'all', $current_plugin ); ?>>系統傳送狀態（所有）</option>
            <?php foreach( $plugins as $key ) { ?>
              <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $current_plugin ); ?>><?php echo esc_attr( $key ); ?></option>
            <?php } ?>
          </select>
      <?php }
    }

    /**
     * 訂單列表取得系統傳送狀態查詢結果
     */
    public function get_status_filter_result( $query ){
      global $pagenow;
      $post_type = isset( $_GET['post_type'] ) ? $_GET['post_type'] : '';
      if ( is_admin() && $pagenow=='edit.php' && $post_type == 'shop_order' && isset( $_GET['send_status'] ) && $_GET['send_status'] !='all' ) {
        $query->query_vars['post_status'] = 'all';
        $query->query_vars['meta_key'] = ODS_WC_ORDER_EXPORT_META_KEY;
        $query->query_vars['meta_value'] = $_GET['send_status'];
        $query->query_vars['meta_compare'] = '=';
      }
    }

  }
}