<?php

$mod_title = "security";
$mod_pname = "Apache Settings";
$mod_directory = "security";
$mod_filename = "index.php";
$allow_view = "all";
$priority = 50;
$active = "on";
$version = "1.2.7";
$uninstall_allow = 0;
$admin_mod = 1;

$mod_class_files = array("Security.php");
$mod_sessions = array("OBJ_security");
$init_object = array("OBJ_security"=>"PHPWS_Security");


?>