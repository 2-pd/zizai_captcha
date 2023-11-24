<?php
/*_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/
 *
 *  Zizai CAPTCHA 23.10-2
 *
 *_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/
 *
 *  LICENSE
 *
 *   このソフトウェアは、無権利創作宣言に基づき著作権放棄されています。
 *   営利・非営利を問わず、自由にご利用いただくことが可能です。
 *
 *    https://www.2pd.jp/license/
 *
*/

define("ZIZAI_CAPTCHA_BASE32_TABLE", array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "2", "3", "4", "5", "6", "7"));

define("ZIZAI_CAPTCHA_ALPHANUMERIC", 0);
define("ZIZAI_CAPTCHA_HIRAGANA", 1);
define("ZIZAI_CAPTCHA_KATAKANA", 2);

class zizai_captcha {
    private $config;
    private $db_obj;
    
    private $config_dir;
    
    private $random_seed;
    
    function __construct ($config_path = "config.json") {
        $config_absolute_path = __DIR__."/".$config_path;
        
        $this->config = json_decode(file_get_contents($config_absolute_path), TRUE);
        
        if (!extension_loaded("sqlite3")) {
            print "このPHP実行環境にはSQLite3モジュールがインストールされていない、または、SQLite3モジュールが有効化されていません。";
            return FALSE;
        }
        
        $this->config_dir = dirname($config_absolute_path)."/";
        
        $this->db_obj = new SQLite3($this->config_dir.$this->config["db_path"]);
        
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
    
    private function xs_srand ($random_seed) {
        $this->random_seed = $random_seed;
    }
    
    private function xs_rand ($min, $max) {
        $r = $this->random_seed;
        
        $r = $r ^ ($r << 9);
        $r = $r ^ ($r >> 21);
        $r = $r ^ ($r << 11);
        
        $this->random_seed = $r;
        
        return abs($r) % ($max - $min + 1) + $min;
    }
    
    private function xs_srand_snapshot () {
        return $this->random_seed;
    }
    
    private function delete_expired_session () {
        $r1 = $this->db_obj->query("DELETE FROM `zizai_captcha_sessions` WHERE `generated_date_time` < '".date("Y-m-d H:i:s", time() - $this->config["timeout_seconds"])."'");
        if($r1 === FALSE){
            print "zizai_captcha::delete_expired_sessionのSQLクエリ1の実行に失敗しました。";
            return FALSE;
        }
    }
    
    function generate_id () {
        $this->delete_expired_session();
        
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
        if($r1 === FALSE){
            print "zizai_captcha::generate_idのSQLクエリ1の実行に失敗しました。";
            return FALSE;
        }
        
        return $session_id;
    }
    
    function check ($session_id, $characters) {
        $this->delete_expired_session();
        
        $r1 = $this->db_obj->query("DELETE FROM `zizai_captcha_attempt_logs` WHERE `date_time` < '".date("Y-m-d H:i:s", time() - $this->config["lockout_seconds"])."'");
        if($r1 === FALSE){
            print "zizai_captcha::checkのSQLクエリ1の実行に失敗しました。";
            return FALSE;
        }
        
        $log_count = $this->db_obj->querySingle("SELECT COUNT(*) FROM `zizai_captcha_attempt_logs` WHERE `ip_address` = '".$_SERVER["REMOTE_ADDR"]."'");
        if($log_count === FALSE){
            print "zizai_captcha::checkのSQLクエリ2の実行に失敗しました。";
            return FALSE;
        }
        
        $session_id = $this->db_obj->escapeString($session_id);
        
        if ($log_count == 0){
            $correct_characters = $this->db_obj->querySingle("SELECT `characters` FROM `zizai_captcha_sessions` WHERE `session_id` = '".$session_id."'");
            if($correct_characters === FALSE){
                print "zizai_captcha::checkのSQLクエリ3の実行に失敗しました。";
                return FALSE;
            }
        }
        
        $r4 = $this->db_obj->query("DELETE FROM `zizai_captcha_sessions` WHERE `session_id` = '".$session_id."'");
        if($r4 === FALSE){
            print "zizai_captcha::checkのSQLクエリ4の実行に失敗しました。";
            return FALSE;
        }
        
        if ($log_count == 0 && !empty($correct_characters)) {
            if ($this->config["script"] == ZIZAI_CAPTCHA_ALPHANUMERIC) {
                $characters = strtoupper($characters);
                $correct_characters = strtoupper($correct_characters);
            }
            
            if ($characters === $correct_characters) {
                return TRUE;
            }
        }
        
        $r5 = $this->db_obj->query("INSERT INTO `zizai_captcha_attempt_logs`(`ip_address`,`date_time`) VALUES ('".$_SERVER["REMOTE_ADDR"]."','".date("Y-m-d H:i:s")."')");
        if($r5 === FALSE){
            print "zizai_captcha::checkのSQLクエリ5の実行に失敗しました。";
            return FALSE;
        }
        
        return FALSE;
    }
    
    private function get_session_data ($session_id) {
        $r1 = $this->db_obj->query("SELECT `characters`,`random_seed` FROM `zizai_captcha_sessions` WHERE `session_id` = '".$this->db_obj->escapeString($session_id)."'");
        if($r1 === FALSE){
            print "zizai_captcha::print_imageのSQLクエリ1の実行に失敗しました。";
            return FALSE;
        }
        
        return $r1->fetchArray(SQLITE3_ASSOC);
    }
    
    private function print_image_from_data ($session_data) {
        $this->xs_srand($session_data["random_seed"]);
        
        $img_w = $this->config["image_height"] * $this->config["char_count"];
        $img_h = $this->config["image_height"];
        $img_w2 = $img_w * 2;
        $img_h2 = $img_h * 2;
        
        $char_img = imagecreatetruecolor($img_h2 * 3, $img_h2 * 3);
        imageantialias($char_img, TRUE);
        imageaffine($char_img, array(1, 0, 0, 1, 0, 0));//imageaffineは初回実行時にエラーとなることがあるため
        
        $font_size = round($img_h2 * 2 / 5);
        $base_x = round($img_h2 * 13 / 10);
        $base_y = round($img_h2 * 17 / 10);
        
        $imgs = array();
        for ($cnt = 0; $cnt < 2; $cnt++) {
            $imgs[$cnt] = imagecreatetruecolor($img_w2, $img_h2);
            
            $index_1 = $cnt % 2;
            $index_2 = ($cnt + 1) % 2;
            
            $char_bg_color = imagecolorallocate($char_img, $this->config["colors"][$index_1][0], $this->config["colors"][$index_1][1], $this->config["colors"][$index_1 % 2][2]);
            $font_color = imagecolorallocate($char_img, $this->config["colors"][$index_2][0], $this->config["colors"][$index_2][1], $this->config["colors"][$index_2][2]);
            
            if ($cnt == 0) {
                $seed = $this->xs_srand_snapshot();
            } else {
                $this->xs_srand($seed);
            }
            
            for ($cnt_2 = 0; $cnt_2 < $this->config["char_count"]; $cnt_2++) {
                imagefilledrectangle($char_img, 0, 0, $img_h2 * 3, $img_h2 * 3, $char_bg_color);
                imagettftext($char_img, $font_size, $this->xs_rand(-30, 30), $base_x, $base_y, $font_color, $this->config_dir.$this->config["fonts"][$this->xs_rand(0, count($this->config["fonts"]) - 1)], mb_substr($session_data["characters"], $cnt_2, 1));
                $char_img_affine = imageaffine($char_img, array($this->xs_rand(76, 125) / 100, $this->xs_rand(-40, 40) / 100, $this->xs_rand(-40, 40) / 100, $this->xs_rand(76, 125) / 100, 0, 0));
                
                imagecopy($imgs[$cnt], $char_img_affine, $img_h2 * $cnt_2, 0, round((imagesx($char_img_affine) - $img_h2) / 2 + $img_h2 * $this->xs_rand(-10, 10) / 100), round((imagesy($char_img_affine) - $img_h2) / 2 + $img_h2 * $this->xs_rand(-10, 10) / 100), $img_h2, $img_h2);
            }
        }
        
        if ($this->xs_rand(0, 1)) {
            $index_up = 0;
            $index_low = 1;
        } else {
            $index_up = 1;
            $index_low = 0;
        }
        
        if (!(($this->config["colors"][0][0] == 0 && $this->config["colors"][0][1] == 255 && $this->config["colors"][0][2] == 0) || ($this->config["colors"][1][0] == 0 && $this->config["colors"][1][1] == 255 && $this->config["colors"][1][2] == 0))) {
            $transparent_color = imagecolorallocate($imgs[$index_low], 0, 255, 0);
        } elseif (!(($this->config["colors"][0][0] == 0 && $this->config["colors"][0][1] == 0 && $this->config["colors"][0][2] == 255) || ($this->config["colors"][1][0] == 0 && $this->config["colors"][1][1] == 0 && $this->config["colors"][1][2] == 255))) {
            $transparent_color = imagecolorallocate($imgs[$index_low], 0, 0, 255);
        } else {
            $transparent_color = imagecolorallocate($imgs[$index_low], 255, 0, 0);
        }
        
        imagecolortransparent($imgs[$index_low], $transparent_color);
        imagefilledpolygon($imgs[$index_low], array($img_w2, 0, 0, 0, 0, round($img_h2 * $this->xs_rand(21, 80) / 100), $img_w2, round($img_h2 * $this->xs_rand(21, 80) / 100)), $transparent_color);
        
        imagecopy($imgs[$index_up], $imgs[$index_low], 0, 0, 0, 0, $img_w2, $img_h2);
        
        $img = imagecreatetruecolor($img_w, $img_h);
        imagecopyresampled($img, $imgs[$index_up], 0, 0, 0, 0, $img_w, $img_h, $img_w2, $img_h2);
        
        imagewebp($img);
        
        return TRUE;
    }
    
    function print_image ($session_id) {
        $session_data = $this->get_session_data($session_id);
        
        if (!empty($session_data)) {
            header("Content-Type: image/webp");
            
            return $this->print_image_from_data($session_data);
        } else {
            header("HTTP/1.1 404 Not Found");
            
            return FALSE;
        }
    }
    
    function get_image_blob ($session_id) {
        $session_data = $this->get_session_data($session_id);
        
        if (!empty($session_data)) {
            ob_start();
            
            $this->print_image_from_data($session_data);
            
            return ob_get_clean();
        } else {
            return FALSE;
        }
    }
    
    function get_config_values () {
        return $this->config;
    }
}
