<?php

/**
 * $Id: update.php,v 1.6 2004/11/04 18:39:26 steven Exp $
 */

if (!$_SESSION["OBJ_user"]->isDeity()){
    header("location:index.php");
    exit();
}

if (in_array($currentVersion, array("1.1", "1.5"))) {
    $currentVersion = "1.5.0";
}

/* Begin using version_compare() */

if (version_compare($currentVersion, "1.5.1") < 0){
  $status = $core->sqlModifyColumn("mod_boost_version", "version", "varchar(20) NOT NULL default ''");
}

if (version_compare($currentVersion, "1.5.2") < 0){

    if (!is_dir($GLOBALS['core']->home_dir . "images/mod/")){
	if (is_writable($GLOBALS['core']->home_dir . "images/")){
	    $content .= "Creating mod directory.";
	    PHPWS_File::recursiveFileCopy(PHPWS_SOURCE_DIR . "images/mod/", $GLOBALS['core']->home_dir . "images/mod/");
	    
	    PHPWS_File::fileCopy(PHPWS_SOURCE_DIR . "images/down.gif", $GLOBALS['core']->home_dir . "images/", "down.gif", TRUE, FALSE);
	    PHPWS_File::fileCopy(PHPWS_SOURCE_DIR . "images/up.gif", $GLOBALS['core']->home_dir . "images/", "up.gif", TRUE, FALSE);
	    PHPWS_File::fileCopy(PHPWS_SOURCE_DIR . "images/info.gif", $GLOBALS['core']->home_dir . "images/", "info.gif", TRUE, FALSE);
	    PHPWS_File::fileCopy(PHPWS_SOURCE_DIR . "images/print.gif", $GLOBALS['core']->home_dir . "images/", "print.gif", TRUE, FALSE);
	    
	} else {
	    $content .= "Please copy your hub's images/mod/ directory into your branches.<br />";
	}
    }
}

$status = 1;

?>