<?php
/**
 * @version $Id: uninstall.php,v 1.7 2004/03/11 21:12:11 darren Exp $
 */
if (!$_SESSION["OBJ_user"]->isDeity()){
  header("location:index.php");
  exit();
}

if ($GLOBALS['core']->sqlImport(PHPWS_SOURCE_DIR . "mod/announce/boost/uninstall.sql", 1, 1)) {
  $content .= "All Announcement tables successfully removed.<br />";

  $ok = PHPWS_File::rmdir("images/announce/");
  if($ok) {
    $content .= "The announce images directory was fully removed.<br />";
  } else {
    $content .= "The announce images directory could not be removed.<br />";
  }

  $status = 1;

} else {
  $content .= "There was a problem accessing the database.<br />";
  $status = 0;
}

?>