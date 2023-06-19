<?php
/*_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/
 *
 *  Zizai CAPTCHA 23.06-1
 *
 *_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/
 *
 *  LICENSE
 *
 *   このソフトウェアは、無権利創作宣言に基づき著作権放棄されています。
 *   営利・非営利を問わず、自由にご利用いただくことが可能です。
 *
 *    http://www.2pd.jp/license/
 *
*/

define("ZIZAI_CAPTCHA_ALPHANUMERIC", 0);
define("ZIZAI_CAPTCHA_HIRAGANA", 1);
define("ZIZAI_CAPTCHA_KATAKANA", 2);

class zizai_captcha {
    private $config;
    private $db_obj;
    
    function __construct ($config_path = "config.json") {
        $this->config = json_decode(file_get_contents(dirname(__FILE__)."/".$config_path), TRUE);
        
        if (!extension_loaded("sqlite3")) {
            print "このPHP実行環境にはSQLite3モジュールがインストールされていない、または、SQLite3モジュールが有効化されていません。";
            return FALSE;
        }
        
        $this->db_obj = new SQLite3(dirname(__FILE__)."/".$this->config["db_path"]);
        
        $this->db_obj->busyTimeout(5000);
    }
}
?>