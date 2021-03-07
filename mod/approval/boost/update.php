<?php

/**
 * $Id: update.php,v 1.1 2004/11/04 18:37:14 steven Exp $
 */

if (!$_SESSION["OBJ_user"]->isDeity()){
    header("location:index.php");
    exit();
}

$status = 1;

if (in_array($currentVersion, array("1.1", "2.0", "2.01"))) {
    $currentVersion = "2.0.1";
}

/* Begin using version_compare() */

?>