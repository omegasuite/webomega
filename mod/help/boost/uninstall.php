<?php
if (($install_hash == $hub_hash) || ($_SESSION["OBJ_user"] && $_SESSION["OBJ_user"]->allow_access("boost"))){

  if ($GLOBALS["core"]->sqlImport(PHPWS_SOURCE_DIR."mod/help/boost/uninstall.sql", 1, 1)){
    $content .= "All help tables successfully removed.<br />";
  } else {
    $content .= "There was a problem accessing the database.<br />";
    $bst_error = 1;
  }
  
} else {
  header("location:index.php");
  exit();
}

?>