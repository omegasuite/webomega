<?php

$mod_title = "fatcat";
$mod_pname = "FatCat Categorizer";
$mod_directory = "fatcat";
$mod_filename = "index.php";
$admin_op = "&fatcat[admin]=menu";
$allow_view = "all";
$priority = 50;
$mod_icon = "fatcat.gif";
$admin_mod = 1;
$mod_class_files = array("Fatcat.php");
$mod_sessions = array("OBJ_fatcat");
$init_object = array("OBJ_fatcat"=>"PHPWS_Fatcat");
$active = "on";
$version = "2.1.7";
$update_link = "phpwebsite.appstate.edu/fallout/updates/fatcat/VERSION";
$branch_allow = 1;
$uninstall_allow = 0;
?>