<?php
if (!$_SESSION["OBJ_user"]->isDeity()){
  header("location:index.php");
  exit();
}

$status = $GLOBALS['core']->sqlDropTable("mod_approval_jobs");

?>