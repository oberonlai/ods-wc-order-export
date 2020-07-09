<?php

/**
 * 1.設定批次操作要執行的動作
 * 2.組合CSV欄位
 * 3.執行FTP上傳
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if(!class_exists('ODS_CSV')){
  class ODS_CSV {

     /**
     * 訂單列表加入系統傳送狀態批次操作項目
     * 
     * @param array $bulk_actions 現有操作項目
     * 
     * @return array $bulk_actions 新增操作項目
     */
    public function set_send_custom_bulk_actions( $bulk_actions ){
      $bulk_actions['send_to_cancel'] = '將系統傳送狀態變更為'.ODS_WC_ORDER_EXPORT_META_VALUE[1];
      $bulk_actions['send_to_delivered'] = '將系統傳送狀態變更為'.ODS_WC_ORDER_EXPORT_META_VALUE[2];
      return $bulk_actions;
    }

    /**
     * 訂單列表系統傳送批次操作項目點選後要做的事
     * 
     * @param string $redirect_to 跳轉頁面
     * @param string $doaction 操作項目代稱
     * @param array $post_ids 被勾選的訂單 ID
     * 
     * @return string $redirect_to 操作完成後跳轉的頁面
     */
    public function set_send_custom_bulk_actions_handler( $redirect_to, $doaction, $post_ids){
      switch ($doaction) {
        case 'send_to_cancel':
          // 變為已傳送
          $redirect_to = $this->set_send_custom_bulk_actions_redirect( $post_ids, ODS_WC_ORDER_EXPORT_META_VALUE[0], ODS_WC_ORDER_EXPORT_META_VALUE[1], $redirect_to );
          break;
        case 'send_to_delivered':
          // 變為取消傳送
          $redirect_to = $this->set_send_custom_bulk_actions_redirect( $post_ids, ODS_WC_ORDER_EXPORT_META_VALUE[1], ODS_WC_ORDER_EXPORT_META_VALUE[2], $redirect_to ) ;
          break;
        default:
          # code...
          break;
      }
      return $redirect_to;
     
    }

    /**
     * 傳送訂單到 send
     * 
     * @param array $post_ids 要傳送的訂單 post ID
     * @param string $send_stauts 此次傳送處理的動作
     * @param string $send_status 傳送後的狀態
     */
    public function set_send_transfer($post_ids, $send_status, $send_status_update){

      //設定系統環境，確保輸出執行環境無礙
      set_time_limit(0);
      
      //開始準備一組匯出陣列
      $csv_arr = array();
      
      //先放置 CSV 檔案的標頭資料
      $csv_arr[] = array('訂購人','訂購人電話','訂單編號','收貨人','下單時間','商品編號','商品名稱','商品數量','訂單金額','運費','送貨郵遞區號','送貨地址','原售價','商品售價','付款方式');
      
      //設定檔案輸出名稱
      $filename = "order-export_" . current_time("YmdHis").".csv";
      header('Pragma: no-cache');
      header('Expires: 0');

      $order_data = array();
      $count = $amount = '';
      $i = 0;

      foreach ($post_ids as $post_id) {  
        $order_data[] = array(
          'billing_last_name'   => ODS_WC_Order::get_order_detail($post_id,'billing_address','last_name'),
          'billing_phone'       => ODS_WC_Order::get_order_detail($post_id,'billing_address','phone'),
          'order_id'            => ODS_WC_Order::get_order_detail($post_id,'order_number'),
          'shipping_last_name'  => ODS_WC_Order::get_order_detail($post_id,'shipping_address','last_name'),
          'order_date'          => ODS_WC_Order::get_order_detail($post_id,'created_at'),
          'sku'                 => ODS_WC_Order::get_order_detail($post_id,'line_items','sku'),
          'product_name'        => ODS_WC_Order::get_order_detail($post_id,'line_items','name'),
          'quantity'            => ODS_WC_Order::get_order_detail($post_id,'line_items','quantity'),
          'order_total'         => ODS_WC_Order::get_order_detail($post_id,'total'),
          'shipping_cost'       => ODS_WC_Order::get_order_detail($post_id,'total_shipping'),
          'biiling_postcode'    => ODS_WC_Order::get_order_detail($post_id,'billing_address','postcode'),
          'shipping_address'    => ODS_WC_Order::get_order_detail($post_id,'taiwan_address'),
          'regular_price'       => ODS_WC_Order::get_order_detail($post_id,'line_items','regular_price'),
          'payment_method'      => ODS_WC_Order::get_order_detail($post_id,'payment_details','method_title'),
        );
        $i++;
      }

      foreach( $order_data as $item){
        $j = $n = 0;

        /**
         * 組 csv row
         */
        foreach( $item['product_name'] as $name ){
          $csv_arr[] = array(
            $item['billing_last_name'],
            $item['billing_phone'],
            $item['order_id'],
            $item['shipping_last_name'],
            $item['order_date'],
            $item['sku'][$j],
            $name,
            $item['quantity'][$j],
            $item['order_total'],
            $item['shipping_cost'],
            $item['biiling_postcode'],
            $item['shipping_address'],
            $item['regular_price'][$j],
            $item['regular_price'][$j],
            $item['payment_method'],
          );
          $j++;
        }
      }

      // 正式循環輸出陣列內容
      $content = '';
      for ($j = 0; $j < count($csv_arr); $j++) {
        $content .= ODS_Helper::csvstr($csv_arr[$j])."\n";
      }

      // 把 csv 寫入 wp-content/uploads
      $upload_dir = wp_upload_dir();
      file_put_contents( $upload_dir['basedir'].'/send/'.$filename, $content, FILE_APPEND | LOCK_EX );

      // FTP 上傳作業
      if( ODS_FTP_DATA['enable'] ){
        $config = [
          'host'=>ODS_FTP_DATA['host'],
          'user'=>ODS_FTP_DATA['user'],
          'pass'=>ODS_FTP_DATA['pass'],
        ];
        $ftp = new ODS_FTP($config);
        $result = $ftp->connect();
        $local_file = $upload_dir['basedir'].'/send/'.$filename;
        $remote_file = ODS_FTP_DATA['path'].$filename;
        $ftp->upload($local_file,$remote_file,'ascii');
      }
    }

    /**
     * 訂單列表顯示系統傳送狀態變更完成後的訊息
     */
    public function set_send_custom_bulk_actions_notice(){ ?>
      <?php if ( ! empty( $_GET['send_status_text'] ) && ! empty( $_GET['send_status_changed'] ) ): ?>
        <?php if( 'error' === $_GET['send_status_changed'] ): ?>
        <div class="notice notice-error is-dismissible" style="display:block;">
          <p><?php echo $_GET['send_status_text']; ?></p>
        </div>
        <?php else: ?>
        <div class="notice notice-success is-dismissible" style="display:block;">
          <p><?php echo $_GET['send_status_text']; ?></p>
        </div>
        <?php endif; ?>
      <?php endif; ?>
    <?php }    

    /**
     * 訂單列表改變系統傳送顯示狀態
     * 
     * @param array $post_ids 有被勾選到的訂單 ID
     * 
     * @return string $status 送出完成後的新狀態
     */
    public function update_send_status( $post_ids, $status ){
      foreach ($post_ids as $post_id) {
        update_post_meta( $post_id, ODS_WC_ORDER_EXPORT_META_KEY, $status);
      }
    }

    /**
     * 取得選取訂單的系統傳送狀態
     * 
     * @param array $post_ids 有被勾選到的訂單 ID
     */
    private function get_send_status($post_ids){
      $send_stauts_array = [];
      foreach ($post_ids as $post_id) {
        $send_stauts_array[] = ODS_WC_Order::get_order_detail($post_id,'send_status');
        return $send_stauts_array;
      }
    }

    /**
     * 判斷狀態送出後的頁面跳轉要做的事
     * 
     * @param array $post_ids 有被勾選到的訂單 ID
     * @param string $send_status_before 訂單更新前的系統傳送狀態
     * @param string $send_status_updated 訂單更新後的系統傳送狀態
     * @param string $redirect 跳轉網址
     * 
     * @return string $redirect 返回更新狀態判斷與顯示文字
     */
    private function set_send_custom_bulk_actions_redirect( $post_ids, $send_status_before, $send_stauts_updated, $redirect ){
      if ( count($this->get_send_status($post_ids)) === 1 && end($this->get_send_status($post_ids)) === $send_status_before ) {
        $this->set_send_transfer( $post_ids, $send_status_before, $send_stauts_updated );
        $this->update_send_status( $post_ids,$send_stauts_updated );
        $redirect = add_query_arg( 'send_status_text', '所有選取訂單之系統傳送狀態變更完成！', $redirect );
        $redirect = add_query_arg( 'send_status_changed', 'success', $redirect );
      } else {
        $redirect = add_query_arg( 'send_status_text', '系統傳送狀態變更失敗，所有選取訂單之系統傳送狀態應皆為「'.$send_status_before.'」！', $redirect );
        $redirect = add_query_arg( 'send_status_changed', 'error', $redirect );
      }
      return $redirect;
    }

    /**
     * 每日自動處理配送中訂單的系統傳送狀態改為已傳送
     */
    public function set_send_transfer_delivered(){
      $query = new WC_Order_Query( array(
        'limit' => 9999,
        'return' => 'ids',
        ODS_WC_ORDER_EXPORT_META_KEY => ODS_WC_ORDER_EXPORT_META_VALUE[0]
      ));
      $orders = $query->get_orders();
      if( $orders ){
        foreach ($orders as $order_id) {
          $this->set_send_transfer( $order_id, ODS_WC_ORDER_EXPORT_META_VALUE[0], ODS_WC_ORDER_EXPORT_META_VALUE[1] );
          $this->update_send_status( $order_id, ODS_WC_ORDER_EXPORT_META_VALUE[1] );
        }
      }
    }

  }
}