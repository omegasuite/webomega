<?php
$mod_title = "boost";
$mod_pname = "Boost Module Upgrader";
$mod_directory = "boost";
$mod_filename = "index.php";
$admin_op = "&boost_op=adminMenu";
$allow_view = array("boost"=>1, "branch"=>1);
$priority = 98;
$mod_icon = "boost.gif";
$admin_mod = 1;
$deity_mod = 1;
$mod_class_files = array("Boost.php");
$init_object = array("OBJ_boost"=>"PHPWS_boost");
$active = "on";
$version = "1.6.1";
$branch_allow = 1;
$uninstall_allow = 0;
?>