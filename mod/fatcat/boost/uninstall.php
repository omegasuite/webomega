<?php
if (!$_SESSION["OBJ_user"]->isDeity()){
  header("location:index.php");
  exit();
}

$content .= "Removing images directory " . PHPWS_HOME_DIR . "images/fatcat<br />";

$GLOBALS['core']->sqlDropTable("mod_fatcat_categories");
$GLOBALS['core']->sqlDropTable("mod_fatcat_elements");
$GLOBALS['core']->sqlDropTable("mod_fatcat_settings");
$status = 1;
?>