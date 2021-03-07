<?php
if (!$_SESSION["OBJ_user"]->isDeity()){
  header("location:index.php");
  exit();
}

if ($GLOBALS['core']->sqlImport(PHPWS_SOURCE_DIR."mod/search/boost/install.sql", 1, 1)) {
  $status = 1;
} else {
  $content .= "There was a problem writing to the database.<br />";
}

?>