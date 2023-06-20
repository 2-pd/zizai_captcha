/* Zizai CAPTCHA 23.06-1 */

var zizai_captcha_dir;
(function(){
    var script_elms = document.getElementsByTagName("script");
    var path_splitted = script_elms[script_elms.length - 1].src.split("/");
    path_splitted[path_splitted.length - 1] = "";
    
    zizai_captcha_dir = path_splitted.join("/");
}());

