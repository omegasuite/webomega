<?php

/**
 * $Id: update.php,v 1.6 2004/11/04 18:44:03 steven Exp $
 */

if (!$_SESSION["OBJ_user"]->isDeity()){
    header("location:index.php");
    exit();
}

$status = 1;

if ($currentVersion < "1.6") {
    $content .= "+ Added code to prevent malicious XSS. <br />";
    $content .= "+ Fixed getIcon error. <br />";
    $content .= "+ Added error check to prevent errors in certain cases of What's Related. <br />";
    $content .= "+ Added ability to change the default module text in the What's Related box <br />
               &nbsp;(see your config.php in your conf/ fatcat module directory)<br />";
}

if (in_array($currentVersion, array("1", "1.5", "1.6", "1.61"))) {
    $currentVersion = "1.6.1";
}

/* Begin using version_compare() */

if (version_compare($currentVersion, "2.0.2") < 0){
    require_once PHPWS_SOURCE_DIR . "mod/fatcat/class/Fatcat.php";
    $sql = "insert into mod_fatcat_categories (cat_id, title, template) values (0, 'Uncategorized', 'default.tpl')";
    $GLOBALS['core']->query($sql, TRUE);
    $content .= "+ Added Uncategorized category. <br />";
}

if (version_compare($currentVersion, "2.0.4") < 0){
    require_once PHPWS_SOURCE_DIR . "mod/fatcat/class/Fatcat.php";
    $sql = "ALTER TABLE mod_fatcat_settings ADD COLUMN relatedEnabled smallint NOT NULL default '1' AFTER relatedLimit"; 
    $GLOBALS['core']->query($sql, TRUE);
    $content .= "+ Added relatedEnabled toggle to settings. <br />";
}
?>