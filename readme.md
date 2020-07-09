# ODS WooCommerce Order Export 訂單資料傳送外掛說明

## 安裝方式

1. 將 ods-wc-order-export 資料夾壓縮為 zip 檔後上傳到 wp-content/plugins 目錄
2. 至 WordPress 後台外掛頁面將 「ODS WooCommerce Order Export」外掛進行啟用
3. 第一次會將所有 WooCommerce 訂單加入名為 export_status 的 meta key，meta value 預設為「未傳送」
4. 後續新訂單會自動加入該 meta key，meta value 預設為「未傳送」

## 參數修改說明

ods-wc-order-export 為外掛主程式，有以下常數可以修改：

- ODS_WC_ORDER_EXPORT_META_KEY - 系統傳送狀態 post meta 的 key，在第一次啟用後建議不要修改
- ODS_WC_ORDER_EXPORT_META_VALUE - 系統傳送狀態的顯示文字，目前預設為「未傳送」、「已傳送」、「未傳送」四種
- ODS_FTP_DATA - FTP 上傳資訊，要使用時把參數 enable 變更為 true

## 外掛資料夾結構說明

請參考 https://oberonlai.blog/woocommerce-order-export/