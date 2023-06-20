<?php
define("ZIZAI_CAPTCHA_CONFIG_PATH", "config.json");

include "main.php";

$zc = new zizai_captcha();

if (!empty($_SERVER["PATH_INFO"])) {
    $zc->print_image(substr($_SERVER["PATH_INFO"], 1, 10));
} else {
    header("HTTP/1.1 404 Not Found");
}
