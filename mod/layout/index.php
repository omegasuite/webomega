<?php
if (!isset($GLOBALS['core'])){
  header("location:../../");
  exit();
}

$CNT_layout["content"] = NULL;
$Layout_Panel["content"] = $Layout_Panel["title"] = NULL;
if (isset($_REQUEST["lay_adm_op"]) && $_SESSION["OBJ_user"]->allow_access("layout")){

  extract($_REQUEST);
  switch($lay_adm_op){

  case "updateBox":
    $_SESSION["OBJ_layout"]->updateBox($_POST["change_content_var"], $_POST["change_box_name"]);
    header("location:./" . preg_replace("/.*(index\.php.*|)$/Ui", "\\1", $_SERVER['HTTP_REFERER']));
    exit();
    break;

  case "updatePop":
    $_SESSION["OBJ_layout"]->updatePop($_POST["change_content_var"], $_POST["change_pop_name"]);
    header("location:./" . preg_replace("/.*(index\.php.*|)$/Ui", "\\1", $_SERVER['HTTP_REFERER']));
    exit();
    break;

  case "admin":
    $_SESSION["OBJ_layout"]->admin();
    break;

  case "panelCommand":
    $_SESSION["OBJ_layout"]->panelCommand();
    break;

  case "settingsCommand":
    $_SESSION["OBJ_layout"]->settingsCommand();
    break;

  case "editMeta":
    $_SESSION["OBJ_layout"]->editMeta();
    $_SESSION["OBJ_layout"]->settings();
    break;
    
  case "layout_admin":
    $_SESSION["OBJ_layout"]->admin_page();
    break;

  case "move_box":
    $_SESSION["OBJ_layout"]->move_box();
    header("location:./" . preg_replace("/.*(index\.php.*|)$/Ui", "\\1", $_SERVER['HTTP_REFERER']));
    exit();
  break;
  
  }
}

/* Start User Options */
if (isset($_REQUEST["layout_user"]) && $_SESSION["OBJ_user"]->isUser()){
  switch ($_REQUEST["layout_user"]){
  case "admin":
    if ($_SESSION['OBJ_layout']->userAllow)
      $_SESSION["OBJ_layout"]->userAdmin();
    else {
      $CNT_layout['title'] = $_SESSION["translate"]->it("Sorry");
      $CNT_layout['content'] = $_SESSION["translate"]->it("The administrator has turned off the user layout functionality");
    }
    break;

  case "userPanelCommand":
    if ($_SESSION['OBJ_layout']->userAllow)
      $_SESSION["OBJ_layout"]->userPanelCommand();
    break;

  }
}
/* End User Options */

if ($_SESSION["OBJ_layout"]->_panel)
     $_SESSION["OBJ_layout"]->panel();

if ($_SESSION["OBJ_layout"]->_userPanel)
     $_SESSION["OBJ_layout"]->userPanel();

echo $_SESSION["OBJ_layout"]->displayTheme();
?>