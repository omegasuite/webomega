<?php
if (!$_SESSION["OBJ_user"]->isDeity()){
  header("location:index.php");
  exit();
}

if ($status = $GLOBALS['core']->sqlImport(PHPWS_SOURCE_DIR."mod/language/boost/install.sql", 1, 1)){
  if (isset($_SESSION['translate']->current_language))
    $languageSet = $_SESSION['translate']->current_language;
  else
    $languageSet = "en";

  $settings = array("langActive"=>$_SESSION['translate']->langActive,
		    "dynActive"=>0,
		    "ignoreDefault"=>0,
		    "mark"=>1,
		    "auto_up"=>1,
		    "default_language"=>strtolower($languageSet));
  $GLOBALS['core']->sqlInsert($settings, "mod_language_settings");
  
}

?>