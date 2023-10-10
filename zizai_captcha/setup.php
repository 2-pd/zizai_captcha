<?php

function zizai_captcha_setup_db ($config_path = "config.json") {
    $config_absolute_path = __DIR__."/".$config_path;
    
    $config = json_decode(file_get_contents($config_absolute_path), TRUE);
    
    if (!extension_loaded("sqlite3")) {
        print "このPHP実行環境にはSQLite3モジュールがインストールされていない、または、SQLite3モジュールが有効化されていません。";
        return FALSE;
    }
    
    $db_obj = new SQLite3(dirname($config_absolute_path)."/".$config["db_path"]);
    
    $r1 = $db_obj->query("CREATE TABLE IF NOT EXISTS `zizai_captcha_sessions` (`session_id` TEXT NOT NULL PRIMARY KEY, `characters` TEXT NOT NULL, `random_seed` INTEGER NOT NULL, `generated_date_time` TEXT NOT NULL)");
    if($r1 === FALSE){
        print "テーブル zizai_captcha_sessions の作成に失敗しました。";
        return FALSE;
    }
    
    $r1_2 = $db_obj->query("CREATE INDEX IF NOT EXISTS `zizai_captcha_idx_1` ON `zizai_captcha_sessions` (`generated_date_time`)");
    if($r1_2 === FALSE){
        print "インデックス zizai_captcha_idx_1 の作成に失敗しました。";
        return FALSE;
    }
    
    $r2 = $db_obj->query("CREATE TABLE IF NOT EXISTS `zizai_captcha_attempt_logs` (`ip_address` TEXT NOT NULL, `date_time` TEXT NOT NULL)");
    if($r2 === FALSE){
        print "テーブル zizai_captcha_attempt_logs の作成に失敗しました。";
        return FALSE;
    }
    
    $r2_2 = $db_obj->query("CREATE INDEX IF NOT EXISTS `zizai_captcha_idx_2` ON `zizai_captcha_attempt_logs` (`ip_address`,`date_time`)");
    if($r2_2 === FALSE){
        print "インデックス zizai_captcha_idx_2 の作成に失敗しました。";
        return FALSE;
    }
    
    $r2_3 = $db_obj->query("CREATE INDEX IF NOT EXISTS `zizai_captcha_idx_3` ON `zizai_captcha_attempt_logs` (`date_time`,`ip_address`)");
    if($r2_3 === FALSE){
        print "インデックス zizai_captcha_idx_3 の作成に失敗しました。";
        return FALSE;
    }
    
    return TRUE;
}
