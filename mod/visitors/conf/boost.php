<?php

	/**
	 * Visitors module for phpWebSite
	 *
	 * @author rck <http://www.kiesler.at/>
	 */

	$version = "1.2.0";
	$mod_pname = "Visitors";
	$mod_title = "visitors";
	$allow_view = "all";
	$priority = 50;
	$mod_class_files = array("visitors.php");
	$mod_sessions = array("OBJ_visitors");
	$init_object = array("OBJ_visitors"=>"PHPWS_visitors");
	$active = "on";
	$branch_allow = 1;
?>