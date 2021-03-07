<?php

/**
 * $Id: update.php,v 1.9 2004/11/04 18:50:15 steven Exp $
 */

if (!$_SESSION["OBJ_user"]->isDeity()){
    header("location:index.php");
    exit();
}


$status = 1;


?>