<?php

/**
 * $Id: update.php,v 1.14 2005/03/04 21:49:41 matt Exp $
 */

if (!$_SESSION["OBJ_user"]->isDeity()){
    header("location:index.php");
    exit();
}

$status = 1;

?>
