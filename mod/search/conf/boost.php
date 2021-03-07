<?php

$mod_title = "search";
$mod_pname = "Site Search";
$mod_directory = "search";
$mod_filename = "index.php";
$allow_view = "all";
$priority = 50;
$mod_class_files = array("Search.php");
$mod_sessions = array("OBJ_search");
$init_object = array("OBJ_search"=>"PHPWS_Search");
$active = "on";
$version = "2.2.4";
$uninstall_allow = 0;
?>