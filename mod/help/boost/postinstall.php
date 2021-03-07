<?php
if (!$_SESSION["OBJ_user"]->isDeity()){
  header("location:index.php");
  exit();
}

$status = CLS_Help::setup_help("help");
$_SESSION['translate']->registerModule("help", "mod_help", "help_id", "label:content");

?>