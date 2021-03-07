<?php

/**
 * $Id: update.php,v 1.12 2004/11/04 18:49:04 steven Exp $
 */

if (!$_SESSION["OBJ_user"]->isDeity()){
    header("location:index.php");
    exit();
}

$status = 1;

if ($currentVersion < "1.10") {
    if ($GLOBALS['core']->query("ALTER TABLE mod_search_register ADD COLUMN `block_title` varchar(255) NOT NULL DEFAULT '' AFTER `show_block`", TRUE)) {
	$GLOBALS['core']->sqlDelete("mod_search_register", "module", "phatform");
	$GLOBALS['core']->sqlDelete("mod_search_register", "module", "phatfile");

	$result = $GLOBALS['core']->sqlSelect("mod_search_register", "module", "pagemaster");
	if (!$result && $GLOBALS['core']->moduleExists("pagemaster")) {
	    $search = array();
	    $search['module'] = "pagemaster";
	    $search['search_class'] = "PHPWS_PageMaster";
	    $search['search_function'] = "search";
	    $search['search_cols'] = "title, text"; 
	    $search['view_string'] = "&amp;PAGE_user_op=view_page&amp;PAGE_id=";
	    $search['show_block'] = 1;
	    $search['block_title'] = "Pages";
	    
	    if (!$GLOBALS["core"]->sqlInsert($search, "mod_search_register"))
		$content .= "Problem registering pagemaster search<br />";
	    else
		$content .= "Pagemaster registered with search module!<br />";
	}

	$result = $GLOBALS['core']->sqlSelect("mod_search_register", "module", "announce");
	if (!$result && $GLOBALS['core']->moduleExists("announce")) {
	    $search = array();
	    $search['module'] = "announce";
	    $search['search_class'] = "PHPWS_AnnouncementManager";
	    $search['search_function'] = "search";
	    $search['search_cols'] = "subject, summary, body";
	    $search['view_string'] = "&amp;ANN_user_op=view&amp;ANN_id=";
	    $search['show_block'] = 1;
	    $search['block_title'] = "Announcements";
	    
	    if (!$GLOBALS['core']->sqlInsert($search, "mod_search_register"))
		$content .= "Problem registering announce search<br />";
	    else
		$content .= "Announce registered with search module!<br />";
	}

	$content .= "Search successfully updated to 1.10.";
    } else {
	$status = 0;
	$content .= "Search update failed.";
    }
}

if ($currentVersion < "1.11") {
    if ($GLOBALS['core']->query("ALTER TABLE mod_search_register ADD COLUMN `class_file` varchar(255) NOT NULL DEFAULT '' AFTER `block_title`", TRUE)) {
	$content .= "Search successfully updated to 1.11.";
    } else {
	$status = 0;
	$content .= "Search update failed.";
    }
}

if (in_array($currentVersion, array("1", "0.80", "1.10", "1.11"))) {
    $currentVersion = "1.1.1";
}

/* Begin using version_compare() */

if (version_compare($currentVersion, "2.1.0") < 0) {
    if ($GLOBALS["core"]->sqlTableExists("mod_search_register", TRUE)) {
	$sql = "DROP TABLE {$GLOBALS['core']->tbl_prefix}mod_search_register";
	$GLOBALS['core']->query($sql);
    }

    if ($GLOBALS["core"]->sqlTableExists("mod_search_register_seq", TRUE)) {
	$sql = "DROP TABLE {$GLOBALS['core']->tbl_prefix}mod_search_register_seq";
	$GLOBALS['core']->query($sql);
    }

    if ($GLOBALS['core']->sqlImport(PHPWS_SOURCE_DIR."mod/search/boost/install.sql", 1, 1)) {
	$content .= "Search successfully updated to 2.1.0";
    } else {
	$status = 0;
	$content .= "Search update failed.";
    }
}

?>