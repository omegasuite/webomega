<?php
if (!isset($GLOBALS['core'])){
  header("location:../../");
  exit();
}

if(isset($_REQUEST["secure_op"]) && $_SESSION["OBJ_user"]->allow_access("security")) {
  switch ($_REQUEST["secure_op"]) {
  case "admin_menu":
    $_SESSION["OBJ_security"]->admin_menu();
    break;
  case "clear_log":
      $_SESSION["OBJ_security"]->clear_logs();
      break;

  case "admin_ops":
    list($sec_admin_op) = each($sec_admop);
    
    switch ($sec_admin_op) {
    case "view_log":
      $_SESSION["OBJ_security"]->show_log();
      break;
      
    case "error_pages":
      $_SESSION["OBJ_security"]->manage_error_page();
      break;

    case "edit_error_page":
      $_SESSION["OBJ_security"]->edit_error_page($_REQUEST["sec_admop"]["edit_error_page"]);
      break;

    case "save_error_page":
      $_SESSION["OBJ_security"]->save_error_page($_REQUEST["sec_admop"]["save_error_page"]);
      break;

    case "add_error_page":
      $_SESSION["OBJ_security"]->add_error_page();
      break;

    case "add_save_error_page":
      $_SESSION["OBJ_security"]->add_save_error_page();
      break;

    case "delete_error_page":
      $_SESSION["OBJ_security"]->delete_error_page();
      break;

    case "delete_error_page_conf":
      $_SESSION["OBJ_security"]->delete_error_page_conf();
      break;

    case "manage_access_menu":
      $_SESSION["OBJ_security"]->manage_access_menu();
      break;

    case "set_access":
      $_SESSION["OBJ_security"]->set_allow_deny($_REQUEST["access_default"]);
      break;

    case "delete_this_ipban":
      $_SESSION["OBJ_security"]->delete_this_ipban();
      break;
    
    case "add_this_ipban":
      $_SESSION["OBJ_security"]->add_this_ipban();
      break;
    
    case "edit_htaccess_extra":
      $_SESSION["OBJ_security"]->edit_htaccess_extra();
      break;

    case "save_htaccess_extra":
      $_SESSION["OBJ_security"]->save_htaccess_extra();
      break;
    }
    break;
  }
}

if(isset($_REQUEST["page"]))
     $_SESSION["OBJ_security"]->display_error_page($_REQUEST["page"]);

?>