/* Zizai CAPTCHA 23.06-1 */

const ZIZAI_CAPTCHA_GENERATE_ID_ENDPOINT = "generate_id.php";
const ZIZAI_CAPTCHA_IMAGE_PATH = "image.php";

var zizai_captcha_dir;
(function(){
    var script_elms = document.getElementsByTagName("script");
    var path_splitted = script_elms[script_elms.length - 1].src.split("/");
    path_splitted[path_splitted.length - 1] = "";
    
    zizai_captcha_dir = path_splitted.join("/");
}());

function zizai_captcha_get_id (callback_func) {
    var request_obj = new XMLHttpRequest();
    
    request_obj.onreadystatechange = function () {
        if (request_obj.readyState === 4) {
            if (request_obj.status === 200) {
                callback_func(JSON.parse(request_obj.responseText));
            } else {
                callback_func(false);
            }
        }
    };
    
    request_obj.open("GET", zizai_captcha_dir + ZIZAI_CAPTCHA_GENERATE_ID_ENDPOINT, true);
    request_obj.timeout = 10000;
    request_obj.send();
}