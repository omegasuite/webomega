<?php
if (!isset($GLOBALS['core'])){
  header("location:../../");
  exit();
}

$CNT_help['content'] = NULL;

if(isset($_REQUEST["help_op"]) && $_REQUEST["help_op"] == "show_help")
{
  $_SESSION["OBJ_help"] = new CLS_help($_GET["hreg_id"]);
  $_SESSION["OBJ_help"]->show_popup($_GET["label_name_id"]);
}
else if(isset($_REQUEST["help_op"]) && $_SESSION["OBJ_user"]->allow_access("help"))
{
  if($_REQUEST["help_op"])
    $CNT_help["title"] = "Absolute Help";

  if (is_array($_REQUEST["help_op"]))
    list($help_op,) = each($_REQUEST["help_op"]);
  else
    $help_op = $_REQUEST["help_op"];

  switch($help_op) {

  case "main_menu":
  $_SESSION["OBJ_help"] = new CLS_help();
  $_SESSION["OBJ_help"]->main_menu();
  break;

  case "load_module":
  $_SESSION["OBJ_help"] = new CLS_help($_POST["reg_id"]);
  $_SESSION["OBJ_help"]->edit_help_content();
  break;

  case "save_data":
  $_SESSION["OBJ_help"] = new CLS_help($_POST["reg_id"]);
  $_SESSION["OBJ_help"]->save_help();
  break;

  case "active_on":
  $_SESSION["OBJ_help"] = new CLS_help($_GET["reg_id"]);
  $_SESSION["OBJ_help"]->active_help_set();
  break;

  case "act_deact_module":
  $_SESSION["OBJ_help"] = new CLS_help();
  $_SESSION["OBJ_help"]->act_deact_module();
  break;
		
  case "save_act_deact":
  $_SESSION["OBJ_help"] = new CLS_help();
  $_SESSION["OBJ_help"]->save_act_deact_module();
  break;

  case "reload":
  $_SESSION["OBJ_help"] = new CLS_help();
  $_SESSION["OBJ_help"]->reload_conf($_POST["reg_id"]);
  break;

  case "load_module":
  $_SESSION["OBJ_help"] = new CLS_help();
  $_SESSION["OBJ_help"]->reload_help($_POST["load_module"]);
  break;

  case "reload_conf":
  $_SESSION["OBJ_help"] = new CLS_help();
  $_SESSION["OBJ_help"]->reload_help($_GET["mod_name"]);
  break;
  }
}
?>