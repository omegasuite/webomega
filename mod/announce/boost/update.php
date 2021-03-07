<?php

/**
 * $Id: update.php,v 1.14 2005/03/04 21:49:41 matt Exp $
 */

if (!$_SESSION["OBJ_user"]->isDeity()){
    header("location:index.php");
    exit();
}

$status = 1;

if ($currentVersion < "1.10") {
    $content .= "Updating Announce Module to Version 1.10<br />";
    $content .= "Adding \"poston\" column to the \"mod_announce\" table.<br />";
    $sql = "ALTER TABLE mod_announce ADD poston datetime NOT NULL";
    $GLOBALS["core"]->query($sql, TRUE);
    $content .= "Column added successfully.<br />";
    $content .= "Setting \"post on\" dates for all announcements to their date created.<br />";
    
    $sql = "SELECT id FROM mod_announce";
    $result = $GLOBALS["core"]->getAll($sql, TRUE);
    if (sizeof($result) > 0) {
	$i = 0;
	foreach($result as $row) {
	    $sql = "UPDATE mod_announce SET poston=dateCreated WHERE id=" .
		$row["id"];
	    $GLOBALS["core"]->query($sql, TRUE);
	    $i++;
	}
	
	$content .= $i . " announcements were updated!<br />";
    } else {
	$content .= "No announcements were found. Skipping this step.<br />";
    }
    
    $content .= "Update successful!";
}

if ($currentVersion < "1.24") {
    $content .= "Announce Module Version 1.24<br />";
    $content .= "- fixed bug with annouce not working fully in other languages<br />";
}

if ($currentVersion < "1.25") {
    $content .= "Announce Module Version 1.25<br />";
    $content .= "- now using defines in dateSettings.php when showing announcements<br />";
}

if (in_array($currentVersion, array("1", "1.2", "1.10", "1.11", "1.21", "1.24", "1.25", "1.26", "1.27"))) {
    $currentVersion = "1.2.7";
}

/* Begin using version_compare() */

if (version_compare($currentVersion, "2.0.1") < 0) {
    $content .= "Announce Module Version 2.0.1<br />";
    $content .= "------------------------------------------------------<br />";
    $content .= "+ major interface improvements<br />";
    $content .= "+ category view for user side<br /><br />";
}

if (version_compare($currentVersion, "2.0.2") < 0) {
    $anonymous = $_SESSION['translate']->it("anonymous");
    $sql = "UPDATE {$GLOBALS['core']->tbl_prefix}mod_announce SET userCreated='$anonymous' WHERE userCreated=''";
    $GLOBALS['core']->query($sql);
    $sql = "UPDATE {$GLOBALS['core']->tbl_prefix}mod_announce SET userUpdated='$anonymous' WHERE userUpdated=''";
    $GLOBALS['core']->query($sql);
    require_once(PHPWS_SOURCE_DIR.'mod/search/class/Search.php');
    PHPWS_Search::register("announce");
}

if (version_compare($currentVersion, "2.2.4") < 0) {
    $sql = "ALTER TABLE mod_announce ADD COLUMN sticky_id int NOT NULL DEFAULT '0'";
    $GLOBALS['core']->query($sql, TRUE);
    $content .= '+ added sticky ability to announcements (Thomas Gordon patch)';
}

?>
