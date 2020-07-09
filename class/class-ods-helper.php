<?php 

/**
 * 工具類 class
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if(!class_exists('ODS_Helper')){
  
  class ODS_Helper {

     /**
     * 確保輸出內容符合 CSV 格式，定義下列方法來處理
     */
    public static function csvstr(array $fields): string{
      $f = fopen('php://memory', 'r+');
      if (fputcsv($f, $fields) === false) {
        return false;
      }
      rewind($f);
      $csv_line = stream_get_contents($f);
      return rtrim($csv_line);
    }

  }
}