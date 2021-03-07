<?php

/**
 * $Id: update.php,v 1.6 2005/05/18 16:35:32 matt Exp $
 */

if (!$_SESSION["OBJ_user"]->isDeity()){
    header("location:index.php");
    exit();
}

$status = 1;

if (version_compare($currentVersion, '1.7', '<')) {
    $GLOBALS['core']->query("ALTER TABLE mod_layout_config ADD `userAllow` SMALLINT DEFAULT '1' NOT NULL AFTER `page_title`", TRUE);
}

if (version_compare($currentVersion, '1.8', '<')) {
    $content .= "+ Layout now automatically changes all users back to the default theme if the admin decides not to allow user themes.<br />";
    $content .= "+ Changed addresses to relative paths.<br />";
    $content .= "+ Added displayPrintTheme() function from Eloi George. <br />";
}

if (in_array($currentVersion, array("1.3", "1.5", "1.7", "1.72", "1.8", "1.82", "1.83", "1.84"))) {
    $currentVersion = "1.8.4";
}

/* Begin using version_compare() */

?>