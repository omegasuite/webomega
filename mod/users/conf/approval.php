<?php

if ($_SESSION["OBJ_user"]->allow_access("users")){

  if ($approvalChoice == "yes")
    PHPWS_User::welcomeUser($id);

  elseif ($approvalChoice == "no"){
    $GLOBALS['core']->sqlDelete("mod_users", "user_id", $id);
    PHPWS_User::dropUser($id);
  }

  elseif ($approvalChoice == "view")
    echo PHPWS_User::viewUser($id);
}

?>