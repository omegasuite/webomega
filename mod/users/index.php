<?php
if (!isset($GLOBALS['core'])){
  header("location:../../");
  exit();
}

$CNT_user['content'] = $CNT_user['title'] = NULL;

if (isset($_REQUEST["norm_user_op"])) {
  if (is_array($_REQUEST["norm_user_op"]))
    list($operation,) = each ($_REQUEST["norm_user_op"]);
  else
    $operation = $_REQUEST["norm_user_op"];
}
elseif (isset($_POST["norm_user_op"])) {
  if (is_array($_POST["norm_user_op"]))
    list($operation,) = each ($_POST["norm_user_op"]);
  else
    $operation = $_POST["norm_user_op"];
}

if ($_SESSION["OBJ_user"]->user_id){
  extract($_REQUEST);
  extract($_POST);

  if (isset($operation)){
    switch($operation){

    case "usr_update_personal":
      if (!isset($CNT_user["content"]))
	$CNT_user["content"] = NULL;
      if ($_SESSION["OBJ_user"]->update_personal()){
	$_SESSION["OBJ_user"]->loadUser($_SESSION["OBJ_user"]->user_id, false);
	$CNT_user["title"] = $_SESSION["translate"]->it("Administer your Account");
	$CNT_user["content"] .= $_SESSION["translate"]->it("User information changed successfully") . ".<hr />";
	$CNT_user['content'] .= $_SESSION["OBJ_user"]->norm_user_opt();      
      } else {
	$CNT_user["title"] = $_SESSION["translate"]->it("Update My Information");
	$CNT_user["content"] .= $_SESSION["OBJ_user"]->update_personal_info();
      }
      break;

    case "profile_changed":
	  $CNT_user["title"] = "update personal information";
	  $CNT_user["content"] = "<table width=100%><tr><td height=800 align=center class='c1'>" . $_SESSION["OBJ_user"]->profileChanged() . "</td></tr></table>";
	break;

    case "change_profile":
	  $CNT_user["title"] = "update personal information";
	  $CNT_user["content"] = $_SESSION["OBJ_user"]->changeProfile();
	break;

	case "logout":
      $_SESSION["OBJ_user"]->logout();
      break;

    case "activate":
	$CNT_user["title"] = $_SESSION["translate"]->it("Account Activation Error");
	$CNT_user["content"] = $_SESSION["translate"]->it("It seems you already have an account with us. User " . $_SESSION["OBJ_user"]->username . " has logged on from this computer. If you are not " . $_SESSION["OBJ_user"]->username . ", please log out and try to activate your account again") . ".";
	break;

    case "user_options":
      if (isset($user_option))
	list ($user_option) = each($_POST["user_option"]);
      else
	$user_option = NULL;
      switch ($user_option){

      case "update_info":
	$CNT_user["title"] = $_SESSION["translate"]->it("Update My Information");
	$CNT_user["content"] = $_SESSION["OBJ_user"]->update_personal_info();
	break;

      case "cookie_refresh":
	if ($_SESSION["OBJ_user"]->refreshUserCookie()){
	  $CNT_user["title"] = $_SESSION["translate"]->it("Cookie Refreshed");
	  $CNT_user["content"] = $_SESSION["translate"]->it("You may need to log out for changes to take effect") . ".";
	} else {
	  $CNT_user["title"] = $_SESSION["translate"]->it("Missing Settings");
	  $CNT_user["content"] = $_SESSION["translate"]->it("Unable to locate saved cookie") . ".";
	}
	break;

      default:
	$CNT_user["title"] = $_SESSION["translate"]->it("Administrate your Account");
	$CNT_user['content'] = $_SESSION["OBJ_user"]->norm_user_opt();
	break;
      }
      break;
    
      // End of norm_user_op switch
    }
  }
} elseif (isset($operation)) {

  switch ($operation) {

  case "login":
	  if (!isset($_POST["block_username"]) && isset($_POST["login_username"])) $_POST["block_username"] = $_POST["login_username"];
    if (isset($_POST["block_username"]) && isset($_POST["password"]) && isset($_POST["rememberme"])) {
      $_SESSION["OBJ_user"]->validate_login($_POST["block_username"], $_POST["password"], $_POST["rememberme"]);
    } elseif(isset($_POST["block_username"]) && isset($_POST["password"])) {
      $_SESSION["OBJ_user"]->validate_login($_POST["block_username"], $_POST["password"]);
    } elseif (isset($_POST["block_username"])) {
      $_SESSION["OBJ_user"]->signup_user();
	}
    break;

  case "forgotPasswordForm":    
    $_SESSION["tempUser"] = new PHPWS_User;  
  $_SESSION["tempUser"]->loadUser($_REQUEST["id"], false);  
  $_SESSION["tempUser"]->forgotPasswordForm();  
  break;  
 
  case "forgotPasswordAction":  
    if ($_SESSION["tempUser"]){  
      if ($error = $_SESSION["tempUser"]->checkPassword($_POST["pass1"], $_POST["pass2"])){  
        $CNT_user["content"] = "<span class=\"errortext\">$error</span><br />";  
        $_SESSION["tempUser"]->forgotPasswordForm();  
      } else {  
        if ($GLOBALS['core']->sqlUpdate(array("password"=>md5($_POST["pass1"])), "mod_users", "user_id", $_SESSION["tempUser"]->user_id)){  
          $CNT_user["title"] = $_SESSION["translate"]->it("设置成功")."!";  
		  $CNT_user["content"] = "<span class=\"errortext\">" . $_SESSION["translate"]->it("设置成功") . "</span><br />"; 
          $_SESSION["tempUser"]->dropUserVar("forgotHash");  
          $_SESSION["tempUser"]->dropUserVar("forgotDateTime");  
          $_SESSION["OBJ_user"]->loadUser($_SESSION["tempUser"]->user_id, false);  
          $GLOBALS['core']->killSession("tempUser");
		  header("location: ./index.php");
        }  
      }  
    } else  
      PHPWS_WizardBag::home();  
    break;

  case "signup":
    $_SESSION["OBJ_user"]->signup_user();
  break;
  
  case "signupAction":
    PHPWS_User::signupAction();
    break;

  case "activate":
	  $CNT_user = $_SESSION["OBJ_user"]->activate($_REQUEST["user_name"], $_REQUEST["code"]);
	break;

  case "profile_created":
	  $CNT_user = $_SESSION["OBJ_user"]->profileChanged();
	break;

  default:
	  $CNT_user['content'] = "<span style=\"color:red\">" . $_SESSION["translate"]->it("Please log in first in order to use this function. If you don't have an account with us, please signup.") . "</span>";
	  $_SESSION["OBJ_user"]->signup_user();
//    header("Location: ./index.php");
    break;
  }
}

/*------------------------- Admin Section ------------------------------*/

if (isset($_REQUEST["user_op"]) && $_SESSION["OBJ_user"]->allow_access("users")){
  extract($_REQUEST);

  switch($_REQUEST["user_op"]){

  case "admin":
    $GLOBALS["CNT_user"]["title"] = $_SESSION["translate"]->it("Welcome")."!";
    $GLOBALS["CNT_user"]["content"] = $_SESSION["translate"]->it("Choose an option from the panel above").".";
       break;

  case "panelCommand":
    foreach ($_GET["usrCommand"] as $section=>$command);
    switch ($section){
    case "user":
      if ($command == "add"){
	$GLOBALS['core']->killSession("manageOptions");
	$_SESSION["newUser"] = new PHPWS_User;
	$_SESSION["newUser"]->createUserForm();
      } elseif ($command == "edit")
	$_SESSION["OBJ_user"]->manageUsers();
      break;

    case "group":
      if ($command == "add"){
	$_SESSION["newGroup"] = new PHPWS_User_Groups;
	$_SESSION["newGroup"]->createGroupForm();
      } elseif ($command == "edit") {
	$_SESSION["OBJ_user"]->manageGroups();
      }
      break;

    case "admin":
      if ($command == "settings")
	$_SESSION["OBJ_user"]->user_defaults();
      break;
    }
    break;

  case "manageGroups":
    $_SESSION["OBJ_user"]->manageGroups();
    break;

  case "manageUsers":
    if ($_SESSION["OBJ_user"]->allow_access("users"))
      $_SESSION["OBJ_user"]->manageUsers();
    break;

  case "turnOnAdmin":
    if ($_SESSION["OBJ_user"]->allow_access("users"))
      $_SESSION["OBJ_user"]->setAdminSwitch($_GET["user_id"], 1);
    
    $_SESSION["OBJ_user"]->manageUsers();
    break;

  case "turnOffAdmin":
    if ($_SESSION["OBJ_user"]->allow_access("users"))
      $_SESSION["OBJ_user"]->setAdminSwitch($_GET["user_id"], 0);

    $_SESSION["OBJ_user"]->manageUsers();
    break;


  case "createUserAction":
    if ($_SESSION["OBJ_user"]->allow_access("users")){
      if ($_SESSION["newUser"]){
	if ($_SESSION["newUser"]->userAction("add")){
	  $CNT_user["title"] = $_SESSION["translate"]->it("Success")."!";
	  $CNT_user["content"] = $_SESSION["translate"]->it("User created")."!";
	  $GLOBALS['core']->killSession("newUser");
	} else {
	  $CNT_user["content"] .= $_SESSION["newUser"]->listUserErrors();
	  $_SESSION["newUser"]->createUserForm();
	}
      }
    }
    break;

  case "editUserForm":
    if ($_SESSION["OBJ_user"]->allow_access("users")){
      $_SESSION["editUser"] = new PHPWS_User($_GET["user_id"]);
      $_SESSION["editUser"]->editUserForm();
    }
  break;

  case "editUserAction":
    if ($_SESSION["editUser"]){
      if ($_SESSION["editUser"]->userAction("edit")){
	$CNT_user["content"] = "<b>".$_SESSION["translate"]->it("User updated")."!</b><br /><br />";
	$GLOBALS['core']->killSession("editUser");
	$_SESSION["OBJ_user"]->manageUsers();
      } else {
	$CNT_user["content"] = $_SESSION["editUser"]->listUserErrors();
	$_SESSION["editUser"]->editUserForm();
      }
    } else 
      $_SESSION["OBJ_user"]->manageUsers();
    break;

  case "createGroupAction":
    if ($_SESSION["OBJ_user"]->allow_access("users")){
      if ($_SESSION["newGroup"]){
	if ($_SESSION["newGroup"]->groupAction("add")){
	  $CNT_user["title"] = $_SESSION["translate"]->it("Success")."!";
	  $CNT_user["content"] = $_SESSION["translate"]->it("Group created")."!";
	  $GLOBALS['core']->killSession("newGroup");
	} else {
	  $CNT_user["content"] = $_SESSION["newGroup"]->listGroupErrors();
	  $_SESSION["newGroup"]->createGroupForm();
	}
      }
    }
	else echo "access denied<br>";
    break;

  case "editGroupForm":
    if ($_SESSION["OBJ_user"]->allow_access("users")){
      $_SESSION["editGroup"] = new PHPWS_User_Groups($_GET["group_id"]);
      $_SESSION["editGroup"]->editGroupForm();
    }
  break;

  case "editGroupAction":
    if ($_SESSION["editGroup"]){
      if ($_SESSION["editGroup"]->groupAction("edit")){
	$CNT_user["content"] = "<b>".$_SESSION["translate"]->it("Group updated")."!</b><br /><br />";
	$GLOBALS['core']->killSession("editGroup");
	$_SESSION["OBJ_user"]->manageGroups();
      } else {
	$CNT_user["content"] = $_SESSION["editGroup"]->listGroupErrors();
	$_SESSION["editGroup"]->editGroupForm();
      }
    } else 
      $_SESSION["OBJ_user"]->manageGroups();
    break;

  
  case "write_extra_info":
    if ($_SESSION["OBJ_user"]->allow_access("users", "user_settings")){
      $_SESSION["OBJ_user"]->write_extra_info();
    } else
      $GLOBALS['core']->unauthorized(2, "users", $_SESSION["OBJ_user"]->user_id);
      break;

  case "deleteGroup":
    if ($_SESSION["OBJ_user"]->allow_access("users", "deleteGroup")){
      $_SESSION["OBJ_user"]->deleteGroup($_GET["group_id"], $_GET["confirm"]);
    }
    break;

  case "deleteUser":
    if ($_SESSION["OBJ_user"]->allow_access("users", "deleteUsers")){
      if (!isset($_REQUEST["confirm"]) || $_REQUEST["confirm"] == "yes")
	$_SESSION["OBJ_user"]->deleteUser($_REQUEST["user_id"], (isset($_REQUEST["confirm"]) ? $_REQUEST["confirm"] : NULL));
      else
	$_SESSION["OBJ_user"]->manageUsers();
    }
    break;

  case "annointUser":
    if ($_SESSION["OBJ_user"]->isDeity())
      PHPWS_User::deify($_REQUEST["user_id"]);
    break;

  case "castoutUser":
    if ($_SESSION["OBJ_user"]->isDeity())
      PHPWS_User::deify($_REQUEST["user_id"]);
    break;

  case "user_deify":
    if ($_SESSION["OBJ_user"]->isDeity()){
      if ($_REQUEST["deification"] == "bestow"){
	$GLOBALS['core']->sqlUpdate(array("deity"=>1), "mod_users", "user_id", $_REQUEST["user_id"]);
	$GLOBALS["CNT_user"]["title"] = $_SESSION["translate"]->it("Request Successful");
	$GLOBALS["CNT_user"]["content"] = $_SESSION["translate"]->it("User is a deity"). "!";
      }
      elseif ($_REQUEST["deification"] == "cast_down"){
	$GLOBALS['core']->sqlUpdate(array("deity"=>0), "mod_users", "user_id", $_REQUEST["user_id"]);
	$GLOBALS["CNT_user"]["title"] = $_SESSION["translate"]->it("Request Successful");
	$GLOBALS["CNT_user"]["content"] = $_SESSION["translate"]->it("User is a mere mortal"). ".";

      }
    }
    break;


  case "update_cookie_settings":
    //This case needs review. dump http_post_vars
    if ($_SESSION["OBJ_user"]->allow_access("users", "user_settings"))
      $_SESSION["OBJ_user"]->update_cookie_settings();
    else
      $GLOBALS['core']->unauthorized(2, "users", $_SESSION["OBJ_user"]->user_id);
    break;
    

  case "update_user_defaults":
    if (!isset($_POST["usr_login_box"]))
      $usr_login_box = 0;
    if (!isset($_POST["usr_rememberme_option"]))
      $usr_rememberme_option = 0;
    $update_array = array("user_authentication"=>$_POST["user_authentication"],
			  "external_auth_file"=>$_POST["external_filename"],
			  "user_contact"=>$_POST["user_contact"],
			  "nu_subj"=>$_POST["usr_subject"],
			  "greeting"=>$_POST["usr_greeting"],
			  "user_signup"=>$_POST["usr_signup"],
			  "show_login"=>$usr_login_box,
			  "show_remember_me"=>$usr_rememberme_option,
			  'welcomeURL'=>$_POST['welcomeURL']); 
    $GLOBALS['core']->sqlUpdate($update_array, "mod_user_settings");
    $_SESSION["OBJ_user"]->user_settings = NULL;
    $_SESSION["OBJ_user"]->getSettings();
    $_SESSION["OBJ_user"]->user_defaults();
    $GLOBALS["CNT_user"]["content"] .= $_SESSION["translate"]->it("* Successfully updated user settings.");

    break;

  }
  if (!isset($GLOBALS["CNT_userPanel"]["content"]))
    $GLOBALS["CNT_userPanel"]["content"] = NULL;

  $GLOBALS["CNT_user"]["content"] = $GLOBALS["CNT_userPanel"]["content"] . $_SESSION["OBJ_user"]->userPanel() . $GLOBALS["CNT_user"]["content"];
} else {
  $GLOBALS['core']->killSession("manageOptions");
  $GLOBALS['core']->killSession("editUser");
  $GLOBALS['core']->killSession("newUser");
  $GLOBALS['core']->killSession("editGroup");
  $GLOBALS['core']->killSession("newGroup");
}


if ($_SESSION["OBJ_user"]->user_id > 0){
  $CNT_user_small["title"] = $_SESSION["translate"]->it("Hello [var1]", $_SESSION["OBJ_user"]->username);
  $CNT_user_small["content"] = $_SESSION["OBJ_user"]->userMenu();
}
$_SESSION["OBJ_user"]->triggerJS();

?>