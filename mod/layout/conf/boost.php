<?php
$mod_title = "layout";
$mod_pname = "Layout Manager";
$mod_directory = "layout";
$mod_filename = "index.php";
$priority = 99;
$admin_op = "&lay_adm_op=admin";
$user_op = "&layout_user=admin";
$allow_view = "all";
$mod_icon = "layout.gif";
$user_icon = "layout.gif";
$user_mod = 1;
$admin_mod = 1;
$deity_mod = 0;
$mod_class_files = array("Layout.php");
$mod_sessions = array("OBJ_layout");
$init_object = array("OBJ_layout"=>"PHPWS_Layout");
$active = "on";
$branch_allow = 1;
$version = "1.10.1";
$install_file = "install.php";
$uninstall_allow = 0;
?>