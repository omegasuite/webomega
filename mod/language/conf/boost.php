<?php

$mod_title = "language";
$mod_pname = "Language Administrator";
$mod_directory = "language";
$mod_filename = "index.php";
$admin_op = "&lng_adm_op=admin";
$user_op = "&lng_usr_op=user_admin";
$allow_view = "all";
$priority = 1;
$mod_icon = "language.gif";
$user_icon = "language.gif";
$user_mod = 1;
$admin_mod = 1;
$deity_mod = 0;
$mod_class_files = array("Language.php");
$mod_sessions = array("translate", "OBJ_lang");
$init_object = array("translate"=>"PHPWS_Language", "OBJ_lang"=>"PHPWS_Language");
$active = "on";
$version = "1.3.5";
$update_link = "phpwebsite.appstate.edu/fallout/updates/language/VERSION";
$branch_allow = 1;
$uninstall_allow = 0;
?>