<?php

$mod_title = "users";
$mod_pname = "User Manager";
$mod_directory = "users";
$mod_filename = "index.php";
$admin_op = "&user_op=admin";
$user_op = "&norm_user_op=user_options";
$allow_view = "all";
$priority = 2;
$mod_icon = "users.gif";
$user_icon = "users.gif";
$user_mod = 1;
$admin_mod = 1;
$mod_class_files = array("Users.php");
$mod_sessions = array("OBJ_user", "group_assign", "usr_log_atmpt");
$init_object = array("OBJ_user"=>"PHPWS_user");
$active = "on";
$version = "1.9.13";
$branch_allow = 1;
$uninstall_allow = 0;
?>