<?php
define("ZIZAI_CAPTCHA_CONFIG_PATH", "config.json");

include "main.php";

$zc = new zizai_captcha(ZIZAI_CAPTCHA_CONFIG_PATH);

$id = $zc->generate_id();
$config = $zc->get_config_values();

header("Content-Type: application/json");
?>
{
    "session_id" : "<?php print $id; ?>",
    "image_width" : <?php print $config["image_height"] * $config["char_count"]; ?>,
    "image_height" : <?php print $config["image_height"]; ?>
}