<?php

/**
 * $Id: update.php,v 1.8 2005/03/08 14:47:04 steven Exp $
 */

if (!$_SESSION["OBJ_user"]->isDeity()){
    header("location:index.php");
    exit();
}

$status = 1;

if ($currentVersion < "1.1") {
    $_SESSION["OBJ_security"] = NULL;
}

if(in_array($currentVersion, array("1", "1.1", "1.11"))) {
    $currentVersion = "1.1.1";
}

/* Begin using version_compare() */
if (version_compare($currentVersion, "1.2.4") < 0) {
  $GLOBALS['core']->query("ALTER TABLE mod_security_log MODIFY COLUMN `offense` text", TRUE);
  $content .= "Security Updates Version (1.2.4)<br />";
  $content .= "-------------------------------------<br />";
  $content .= "- Now you can log events with OBJ_security->log()<br />";
}


?>