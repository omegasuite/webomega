<?php
if (!$_SESSION["OBJ_user"]->isDeity()){
  header("location:index.php");
  exit();
}

$status = CLS_Help::setup_help("layout");
$_SESSION["OBJ_layout"] = new PHPWS_Layout(FALSE);

?>