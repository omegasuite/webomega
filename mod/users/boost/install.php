<?php
if (!$_SESSION["OBJ_user"]->isDeity()){
  header("location:index.php");
  exit();
}

$status = $GLOBALS['core']->sqlImport(PHPWS_SOURCE_DIR."mod/users/boost/install.sql", 1, 1);
?>