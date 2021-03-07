<?php

/**
 * $Id: update.php,v 1.9 2004/11/04 18:50:15 steven Exp $
 */

if (!$_SESSION["OBJ_user"]->isDeity()){
    header("location:index.php");
    exit();
}


$status = 1;

if ($currentVersion < "1.6") {
    $content .= "+ Fixed code causing problems when user signup was turned off.<br />";
    $content .= "+ Clarified email notification.<br />";
}

if (in_array($currentVersion, array("1.2", "1.5", "1.6", "1.7", "1.71"))) {
    $currentVersion = "1.7.1";
}

if (version_compare($currentVersion, "1.8.2") < 0) {
    $sql = "ALTER TABLE mod_users ADD COLUMN cookie varchar(32) AFTER last_on";
    if ($GLOBALS['core']->query($sql, TRUE))
	$content .= "+ Added cookie column for 'Remember Me' feature. <br />";
    else
	$status = 0;

    $sql = "ALTER TABLE mod_user_settings ADD COLUMN show_remember_me smallint AFTER show_login";
    if ($GLOBALS['core']->query($sql, TRUE))
	$content .= "+ Added show_remember_me column to turn on/off the 'Remember Me'. <br />";
    else
	$status = 0;

    CLS_help::uninstall_help("users");
    CLS_help::setup_help("users");
}

if ($currentVersion < "1.8.7"){
    if (!$GLOBALS['core']->sqlColumnExists('mod_user_settings', 'welcomeURL')) {
	$GLOBALS['core']->sqlAddColumn('mod_user_settings', array('welcomeURL'=>'text'));
	$content .= "+ Added welcomeURL column to specify the \"new user welcome page\" URL. <br />";
    }
    else
	$content .= 'welcomeURL column already in place<br />';
}

?>