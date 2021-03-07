<?php
if (!isset($GLOBALS['core'])){
  header("location:../../");
  exit();
}

if (isset($_REQUEST["approval_op"]) && $_SESSION["OBJ_user"]->allow_access("approval")){
  $CNT_approval["title"] = $_SESSION["translate"]->it("Approval List") . CLS_help::show_link("approval", "approval");
  switch ($_REQUEST["approval_op"]){
  case "admin":
  $CNT_approval["content"] = PHPWS_Approval::admin();
  break;

  case "process":
    PHPWS_Approval::process();
    $CNT_approval["content"] = $_SESSION["translate"]->it("Jobs processed") . "<hr />";
    $CNT_approval["content"] .= PHPWS_Approval::admin();
  break;

  case "view":
    $approve = new PHPWS_Approval;
  $approve->get($_GET["id"]);
  if (!$approve->view())
    echo $approve->getError();
    break;
  }
}

?>