# CTKPro ERP 訂單資料傳送外掛說明

## 安裝方式

1. 將 ctkpro-erp 資料夾壓縮為 zip 檔後上傳到 wp-content/plugins 目錄
2. 至 WordPress 後台外掛頁面將 「CTKPro ERP 匯出功能」外掛進行啟用
3. 第一次會將所有 WooCommerce 訂單加入名為 ctkpro_erp_status 的 meta key，meta value 預設為「未傳送」
4. 後續新訂單會自動加入該 meta key，meta value 預設為「未傳送」

## 參數修改說明

cktpro_erp.php 為外掛主程式，有以下常數可以修改：

- ERP_STATUS_META_KEY - ERP 狀態 post meta 的 key，在第一次啟用後建議不要修改
- ERP_STATUS_STRING - ERP 狀態的顯示文字，目前預設為「未傳送」、「預售」、「銷售」、「退帳」四種
- ERP_TO_FTP - FTP 上傳資訊，要使用時把參數 enable 變更為 true
- CSV_DIVIDER - CSV 的分隔符號，用 true 為逗號，false 為 ||

## 資料夾結構說明

class 資料夾有三隻 class，說明如下：

1. class/class_erp_admin.php -  負責後台 ERP 相關功能的操作介面
2. class/class_erp_csv.php -  負責組合 CSV 以及改變 ERP 狀態
3. class/class_erp_export.php - 負責 Hooks
4. class/class_erp_ftp.php - 負責 FTP 
5. class/class_erp_order_detail.php - 負責產出訂單資訊以及訂單品項內容

## 增加報表欄位說明