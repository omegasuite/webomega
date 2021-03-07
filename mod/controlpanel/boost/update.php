<?php

/**
 * $Id: update.php,v 1.5 2004/11/04 18:43:05 steven Exp $
 */

if (!$_SESSION["OBJ_user"]->isDeity()){
    header("location:index.php");
    exit();
}

require_once (PHPWS_SOURCE_DIR . "core/File.php");

$status = 1;

if (in_array($currentVersion, array("0.80", "0.81"))) {
    $currentVersion = "0.8.1";
}

/* Begin using version_compare() */

if (version_compare($currentVersion, "2.0.3") < 0) {
    $content .= "Copy control panel left and right tab images.<br />";
    
    if (!is_dir($GLOBALS['core']->home_dir . "images/mod"))
	PHPWS_File::makeDir($GLOBALS['core']->home_dir . "images/mod");
    
    if (!is_dir($GLOBALS['core']->home_dir . "images/mod/controlpanel"))
	PHPWS_File::makeDir($GLOBALS['core']->home_dir . "images/mod/controlpanel");
    PHPWS_File::fileCopy(PHPWS_SOURCE_DIR . "mod/controlpanel/img/leftcorner.gif",  $GLOBALS['core']->home_dir."/images/mod/controlpanel/", "leftcorner.gif", false, false);
    
    PHPWS_File::fileCopy(PHPWS_SOURCE_DIR . "mod/controlpanel/img/rightcorner.gif", $GLOBALS['core']->home_dir."/images/mod/controlpanel/", "rightcorner.gif", false, false);
}

if (version_compare($currentVersion, "2.1.0") < 0) {
    $status = 0;

    $content .= "Controlpanel Updates (Version {$currentVersion})<br />\n";
    $content .= "-----------------------------------------------<br />\n";
    $content .= "+ Removed all serialization of link ids<br />\n";
    $content .= "+ Links now order by module name so they stay in place better<br />\n";
    
    $sql = "ALTER TABLE {$GLOBALS['core']->tbl_prefix}mod_controlpanel_link ADD COLUMN tab int default NULL AFTER approved";
    $GLOBALS['core']->query($sql);
    
    $tabs = $GLOBALS['core']->getAssoc("SELECT id, links FROM {$GLOBALS['core']->tbl_prefix}mod_controlpanel_tab");
    foreach($tabs as $id => $links) {
	$links = @unserialize($links);
	if (is_array($links) && (sizeof($links) > 0)) {
	    foreach($links as $link_id) {
		$GLOBALS['core']->query("UPDATE {$GLOBALS['core']->tbl_prefix}mod_controlpanel_link SET tab='{$id}' WHERE id='{$link_id}'");
	    }
	}
    }
    
    $sql = "ALTER TABLE {$GLOBALS['core']->tbl_prefix}mod_controlpanel_tab DROP COLUMN links";
    $GLOBALS['core']->query($sql);
    
    if (isset($_SESSION['PHPWS_ControlPanel']))
	PHPWS_Core::killSession("PHPWS_ControlPanel");
    
    $status = 1;
}

?>