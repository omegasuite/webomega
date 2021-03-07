<?php
if (!isset($GLOBALS['core'])){
  header("location:../../");
  exit();
}

$CNT_boost["content"] = NULL;
if (isset($_REQUEST["boost_op"]) && $_SESSION["OBJ_user"]->isDeity()){
  switch($_REQUEST["boost_op"]){
    
  case "adminMenu":
    $CNT_boost["title"] = $_SESSION["translate"]->it("Module Installer/Updater");
    $CNT_boost["content"] = $OBJ_boost->adminMenu();
  break;
  
  case "update":
    $OBJ_boost->direct();
  break;

  case "uninstallModule":
    $CNT_boost["title"]    = $_SESSION["translate"]->it("Uninstalling Module");
    $CNT_boost["content"] .= $OBJ_boost->boostLink() . "<br /><br />";
    $CNT_boost["content"] .= $OBJ_boost->uninstallModule($_GET["killMod"], TRUE, TRUE, TRUE);
  break;

  }
}
?>