<?php
/**
 * @version $Id: install.php,v 1.10 2004/06/11 20:36:28 darren Exp $
 */
if (!$_SESSION["OBJ_user"]->isDeity()){
  header("location:index.php");
  exit();
}

require_once (PHPWS_SOURCE_DIR . "core/File.php");

if (0) { //$status = $GLOBALS['core']->sqlImport(PHPWS_SOURCE_DIR . "mod/announce/boost/install.sql", TRUE)){
  $content .= "All Announcement tables successfully written.<br />";
  
  /* Create image directory */
  PHPWS_File::makeDir($GLOBALS['core']->home_dir . "images/announce");
  if(is_dir("{$GLOBALS['core']->home_dir}images/announce"))
    $content .= "Announcements image directory {$GLOBALS['core']->home_dir}images/announce successfully created!<br />";
  else
    $content .= "Announcements could not create the image directory: {$GLOBALS['core']->home_dir}images/announce<br />You will have to do this manually!<br />";
 
  $status = 1;
}
  $status = 1;
?>