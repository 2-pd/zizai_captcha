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

define("ZIZAI_CAPTCHA_BASE32_TABLE", array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "2", "3", "4", "5", "6", "7"));

define("ZIZAI_CAPTCHA_ALPHANUMERIC", 0);
define("ZIZAI_CAPTCHA_HIRAGANA", 1);
define("ZIZAI_CAPTCHA_KATAKANA", 2);

class zizai_captcha {
    private $config;
    private $db_obj;
    
    function __construct ($config_path = "config.json") {
        $config_absolute_path = __DIR__."/".$config_path;
        
        $this->config = json_decode(file_get_contents($config_absolute_path), TRUE);
        
        if (!extension_loaded("sqlite3")) {
            print "このPHP実行環境にはSQLite3モジュールがインストールされていない、または、SQLite3モジュールが有効化されていません。";
            return FALSE;
        }
        
        $this->db_obj = new SQLite3(dirname($config_absolute_path)."/".$this->config["db_path"]);
        
        $this->db_obj->busyTimeout(5000);
    }
    
    private function bin_to_int ($bin, $start, $length) {
        if ($length > PHP_INT_SIZE * 8 - 1) {
            return FALSE;
        }
        
        if (PHP_INT_SIZE >= 8) {
            $format = "J";
        } else {
            $format = "N";
        }
        
        $end = $start + $length;
        
        $byte_start = floor($start / 8);
        
        $bin_int = unpack($format, str_pad(substr($bin, $byte_start, ceil($end / 8) - $byte_start), PHP_INT_SIZE, "\0", STR_PAD_LEFT));
        
        if ($end % 8 !== 0) {
            return $bin_int[1] >> (8 - $end % 8) & (2**$length - 1);
        } else {
            return $bin_int[1] & (2**$length - 1);
        }
    }
    
    function generate_id () {
        $random_bin = pack("J", (mt_rand(0, 0x3FFFFFFF) << 20) + (microtime(TRUE) * 1000000 % 0x100000));
        
        $session_id = "";
        for ($cnt = 0; $cnt < 10; $cnt++) {
            $session_id .= ZIZAI_CAPTCHA_BASE32_TABLE[$this->bin_to_int($random_bin, $cnt * 5 + 14, 5)];
        }
        
        switch ($this->config["script"]) {
            case ZIZAI_CAPTCHA_ALPHANUMERIC:
                $char_list = array("A","B","C","D","E","F","G","H","J","K","L","M","N","P","Q","R","S","T","U","V","W","X","Y","Z","a","b","c","d","e","f","g","h","i","j","k","m","n","o","p","r","s","t","u","v","w","x","y","z","1","2","3","4","5","6","7","8","9");
                
                break;
            case ZIZAI_CAPTCHA_HIRAGANA:
                $char_list = array("あ","い","う","え","お","か","き","く","け","こ","さ","し","す","せ","そ","た","ち","つ","て","と","な","に","ぬ","ね","の","は","ひ","ふ","へ","ほ","ま","み","む","め","も","や","ゆ","よ","ら","り","る","れ","ろ","わ","を","ん");
                
                break;
            case ZIZAI_CAPTCHA_KATAKANA:
                $char_list = array("ア","イ","ウ","エ","オ","カ","キ","ク","ケ","コ","サ","シ","ス","セ","ソ","タ","チ","ツ","テ","ト","ナ","ニ","ヌ","ネ","ノ","ハ","ヒ","フ","ヘ","ホ","マ","ミ","ム","メ","モ","ヤ","ユ","ヨ","ラ","リ","ル","レ","ロ","ワ","ヲ","ン");
                
                break;
        }
        
        $characters = "";
        for ($cnt = 0; $cnt < $this->config["char_count"]; $cnt++) {
            $characters .= $char_list[mt_rand(0, count($char_list) - 1)];
        }
        
        $r1 = $this->db_obj->query("INSERT INTO `zizai_captcha_sessions`(`session_id`,`characters`,`random_seed`,`generated_date_time`) VALUES ('".$session_id."','".$characters."',".mt_rand().",'".date("Y-m-d H:i:s")."')");
        if($r1 == FALSE){
            print "zizai_captcha::generate_idのSQLクエリ1の実行に失敗しました。";
            return FALSE;
        }
        
        return $session_id;
    }
}
?>