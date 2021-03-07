<?php

/**
 * $Id: update.php,v 1.1 2004/11/04 18:44:57 steven Exp $
 */

if (!$_SESSION["OBJ_user"]->isDeity()){
    header("location:index.php");
    exit();
}

$status = 1;

if (in_array($currentVersion, array("1.1", "1.2", "1.21"))) {
    $currentVersion = "1.2.1";
}

/* Begin using version_compare() */

?>