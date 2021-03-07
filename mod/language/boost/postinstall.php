<?php
if (!$_SESSION["OBJ_user"]->isDeity()){
  header("location:index.php");
  exit();
}

$status = CLS_help::setup_help("language");

?>