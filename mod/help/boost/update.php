<?php

/**
 * $Id: update.php,v 1.3 2004/11/04 18:44:27 steven Exp $
 */

if (!$_SESSION["OBJ_user"]->isDeity()){
  header("location:index.php");
  exit();
}

$status = 1;

if ($currentVersion < "1.2") {
    $content .= "+ Fixed commands dependant upon translate statements. <br />";
}

if (in_array($currentVersion, array("1.1", "1.2", "1.21"))) {
    $currentVersion = "1.2.1";
}

/* Begin using version_compare() */

?>