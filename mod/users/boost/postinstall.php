<?php
if (!$_SESSION["OBJ_user"]->isDeity()){
  header("location:index.php");
  exit();
}

if ($status = $GLOBALS['core']->sqlUpdate(array("user_contact"=>$_SESSION['OBJ_user']->getEmail()), "mod_user_settings"))
     CLS_Help::setup_help("users");
?>