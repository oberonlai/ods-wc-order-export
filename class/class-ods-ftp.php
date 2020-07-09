<?php

/**
 * FTP 作業
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

if(!class_exists('ODS_FTP')){
  class ODS_FTP {
    
    private $host='';//遠端伺服器地址
    private $user='';//ftp使用者名稱
    private $pass='';//ftp密碼
    private $port=21;//ftp登入埠
    private $error='';//最後失敗時的錯誤資訊
    protected $conn;//ftp登入資源

    /**
     * 可以在例項化類的時候配置資料，也可以在下面的connect方法中配置資料
     * Ftp constructor.
     * @param array $config
     */
    public function __construct(array $config=[]){
        empty($config) OR $this->initialize($config);
    }

    /**
     * 初始化資料
     * @param array $config 配置檔案陣列
     */
    public function initialize(array $config=[]){
        $this->host = $config['host'];
        $this->user = $config['user'];
        $this->pass = $config['pass'];
        $this->port = isset($config['port']) ?: 21;
    }

    /**
     * 連線及登入ftp
     * @param array $config 配置檔案陣列
     * @return bool
     */
    public function connect(array $config=[]){
      empty($config) OR $this->initialize($config);
      if (FALSE == ($this->conn = @ftp_connect($this->host))){
          $this->error = "主機連線失敗";
          return FALSE;
      }
      if ( ! $this->_login()){
          $this->error = "伺服器登入失敗";
          return FALSE;
      }
      return TRUE;
    }

    /**
     * 上傳檔案到ftp伺服器
     * @param string $local_file 本地檔案路徑
     * @param string $remote_file 伺服器檔案地址
     * @param bool $permissions 資料夾許可權
     * @param string $mode 上傳模式(ascii和binary其中之一)
     */
    public function upload($local_file='',$remote_file='',$mode='auto',$permissions=NULL){
        if ( ! file_exists($local_file)){
            $this->error = "本地檔案不存在";
            return FALSE;
        }
        if ($mode == 'auto'){
            $ext = $this->_get_ext($local_file);
            $mode = $this->_set_type($ext);
        }
        //建立資料夾
        ftp_pasv($this->conn,true);
        $this->_create_remote_dir($remote_file);
        $mode = ($mode == 'ascii') ? FTP_ASCII : FTP_BINARY;
        $result = @ftp_put($this->conn,$remote_file,$local_file,$mode);//同步上傳
        if ($result === FALSE){
          $this->error = "檔案上傳失敗";
          return FALSE;
        }
        return TRUE;
    }

    /**
     * 從ftp伺服器下載檔案到本地
     * @param string $local_file 本地檔案地址
     * @param string $remote_file 遠端檔案地址
     * @param string $mode 上傳模式(ascii和binary其中之一)
     */
    public function download($local_file='',$remote_file='',$mode='auto'){
        if ($mode == 'auto'){
            $ext = $this->_get_ext($remote_file);
            $mode = $this->_set_type($ext);
        }
        $mode = ($mode == 'ascii') ? FTP_ASCII : FTP_BINARY;
        $result = @ftp_get($this->conn, $local_file, $remote_file, $mode);
        if ($result === FALSE){
            return FALSE;
        }
        return TRUE;
    }

    /**
     * 刪除ftp伺服器端檔案
     * @param string $remote_file 檔案地址
     */
    public function delete_file(string $remote_file=''){
        $result = @ftp_delete($this->conn,$remote_file);
        if ($result === FALSE){
            return FALSE;
        }
        return TRUE;
    }
    /**
     * ftp建立多級目錄
     * @param string $remote_file 要上傳的遠端圖片地址
     */
    private function _create_remote_dir($remote_file='',$permissions=NULL){
        $remote_dir = dirname($remote_file);
        $path_arr = explode('/',$remote_dir); // 取目錄陣列
        //$file_name = array_pop($path_arr); // 彈出檔名
        $path_div = count($path_arr); // 取層數
        foreach($path_arr as $val) // 建立目錄
        {
            if(@ftp_chdir($this->conn,$val) == FALSE)
            {
                $tmp = @ftp_mkdir($this->conn,$val);//此處建立目錄時不用使用絕對路徑(不要使用:2018-02-20/ceshi/ceshi2，這種路徑)，因為下面ftp_chdir已經已經把目錄切換成當前目錄
                if($tmp == FALSE)
                {
                    echo "目錄建立失敗，請檢查許可權及路徑是否正確！";
                    exit;
                }
                if ($permissions !== NULL){
                    //修改目錄許可權
                    $this->_chmod($val,$permissions);
                }
                @ftp_chdir($this->conn,$val);
            }
        }

        for($i=0;$i<$path_div;$i++) // 回退到根,因為上面的目錄切換導致當前目錄不在根目錄
        {
            @ftp_cdup($this->conn);
        }
    }

    /**
     * 更改 FTP 伺服器上的檔案或目錄名
     * @param string $old_file 舊檔案/資料夾名
     * @param string $new_file 新檔案/資料夾名
     */
    public function remane(string $old_file='',string $new_file=''){
        $result = @ftp_rename($this->conn,$old_file,$new_file);
        if ($result === FALSE){
            $this->error = "移動失敗";
            return FALSE;
        }
        return TRUE;
    }

    /**
     * 列出ftp指定目錄
     * @param string $remote_path
     */
    public function list_file(string $remote_path=''){
        $contents = @ftp_nlist($this->conn, $remote_path);
        return $contents;
    }

    /**
     * 獲取檔案的字尾名
     * @param string $local_file
     */
    private function _get_ext($local_file=''){
        return (($dot = strrpos($local_file,'.'))==FALSE) ? 'txt' : substr($local_file,$dot+1);
    }

    /**
     * 根據檔案字尾獲取上傳編碼
     * @param string $ext
     */
    private function _set_type($ext=''){
        //如果傳輸的檔案是文字檔案，可以使用ASCII模式，如果不是文字檔案，最好使用BINARY模式傳輸。
        return in_array($ext, ['txt', 'text', 'php', 'phps', 'php4', 'js', 'css', 'htm', 'html', 'phtml', 'shtml', 'log', 'xml'], TRUE) ? 'ascii' : 'binary';
    }

    /**
     * 修改目錄許可權
     * @param $path 目錄路徑
     * @param int $mode 許可權值
     */
    private function _chmod($path,$mode=0755){
        if (FALSE == @ftp_chmod($this->conn,$path,$mode)){
            return FALSE;
        }
        return TRUE;
    }

    /**
      * 登入Ftp伺服器
      */
    private function _login(){
        return @ftp_login($this->conn,$this->user,$this->pass);
    }

    /**
      * 獲取上傳錯誤資訊
      */
    public function get_error_msg(){
        return $this->error;
    }
    /**
    * 關閉ftp連線
    * @return bool
    */
    public function close(){
      return $this->conn ? @ftp_close($this->conn_id) : FALSE;
    }
  }
}