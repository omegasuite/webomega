<?php
if (!$_SESSION["OBJ_user"]->isDeity()){
  header("location:index.php");
  exit();
}

require_once (PHPWS_SOURCE_DIR . "core/File.php");

if ($GLOBALS['core']->sqlImport(PHPWS_SOURCE_DIR."mod/fatcat/boost/install.sql", 1, 1)){

  if (!is_dir("{$GLOBALS['core']->home_dir}images/fatcat"))
    PHPWS_File::makeDir($GLOBALS['core']->home_dir . "images/fatcat");
  
  if (!is_dir("{$GLOBALS['core']->home_dir}images/fatcat/images"))
    PHPWS_File::makeDir($GLOBALS['core']->home_dir . "images/fatcat/images");
  
  if (!is_dir("{$GLOBALS['core']->home_dir}images/fatcat/icons"))
    PHPWS_File::makeDir($GLOBALS['core']->home_dir . "images/fatcat/icons");
  
  if(is_dir("{$GLOBALS['core']->home_dir}images/fatcat"))
    $content .= "FatCat image directories successfully created!<br />";
  else
    $content .= "FatCat could not create the image directories:<br /> "
      . $GLOBALS['core']->home_dir . "images/fatcat/images/<br />" 
      . $GLOBALS['core']->home_dir . "images/fatcat/icons/<br />You will have to do this manually!<br />";

  require_once PHPWS_SOURCE_DIR . "mod/fatcat/class/Fatcat.php";
  $sql = "insert into mod_fatcat_categories (cat_id, title, template) values (0, 'Uncategorized', 'default.tpl')";
  $GLOBALS['core']->query($sql, TRUE);
  
  $status = 1;
} else
  $content .= "There was a problem writing to the database.<br />";

?>