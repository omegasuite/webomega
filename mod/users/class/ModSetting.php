<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");

class PHPWS_User_ModSetting extends PHPWS_User_Cookie{

  function getUserVar($varName, $user_id=NULL, $module_title=NULL){
    if ($user_id)
      $user = new PHPWS_User((int)$user_id);
    else
      $user = $this;

    if (!$user->user_id)
      return FALSE;

    if (!($GLOBALS["core"]->moduleExists($module_title)))
      if (!($module_title = $GLOBALS["core"]->current_mod))
	exit("getUserVar error: <b>$module_title</b> is malformed or does not exist.<br /><b>varName</b> = $varName.");
    
    return (isset($user->modSettings[$module_title][$varName])) ? $user->modSettings[$module_title][$varName] : NULL;
  }


  function setUserVar($varName, $varValue, $user_id=NULL, $module_title=NULL){
    if (!is_null($user_id))
      $user = new PHPWS_User((int)$user_id);
    else
      $user = $this;

    $currentVar = $this->getUserVar($varName);
    
    if (is_array($currentVar) && is_array($varValue)){
      foreach ($varValue as $key=>$value)
	$currentVar[$key] = $value;

      $varValue = $currentVar;
    }

    if (!$user->isUser())
      exit("setUserVar error: User ID ($user_id) is not registered in the system.<br />");

    if (!($GLOBALS["core"]->moduleExists($module_title)))
      if (!($module_title = $GLOBALS["core"]->current_mod))
	exit("setUserVar error: <b>$module_title</b> is malformed or does not exist.");

    if (!(PHPWS_Text::isValidInput($varName)))
      exit("setUserVar error: <b>$varName</b> is not a valid variable name.");

    $insert["module_title"] = $module_title;
    $insert["user_id"]      = $user->user_id;
    $insert["varName"]      = $varName;
    $insert["varValue"]     = $varValue;

    $user->dropUserVar($varName, NULL, $module_title);

    if($GLOBALS["core"]->sqlInsert($insert, "mod_user_uservar", 1)){
      if (is_null($user_id))
	$this->modSettings[$module_title][$varName] = $varValue;
      return TRUE;
    } else
      return FALSE;
  }

  function dropUserVar($varName, $user_id=NULL, $module_title=NULL){
    if ($user_id)
      $user = new PHPWS_User((int)$user_id);
    else
      $user = $this;

    if (!$user->user_id)
      return;

    if (!($GLOBALS["core"]->moduleExists($module_title)))
      if (!($module_title = $GLOBALS["core"]->current_mod))
	exit("dropUserVar error: <b>$module_title</b> is malformed or does not exist.");

    if (!(PHPWS_Text::isValidInput($varName)))
      exit("dropUserVar error: <b>$varName</b> is not a valid variable name.");

    $where["module_title"] = $module_title;
    $where["user_id"]      = $user->user_id;
    $where["varName"]      = $varName;

    $GLOBALS["core"]->sqlDelete("mod_user_uservar", $where);

    if (!$user_id){
      if (isset($this->modSettings[$module_title][$varName]))
	unset($this->modSettings[$module_title][$varName]);
    }

  }

  function dropUserModule($module_title=NULL){
    if (!($GLOBALS["core"]->moduleExists($module_title)))
      if (!($module_title = $GLOBALS["core"]->current_mod))
	exit("dropUserModule error: <b>$module_title</b> is malformed or does not exist.");

    $where["module_title"] = $module_title;
    $GLOBALS["core"]->sqlDelete("mod_user_uservar", $where);
  }

  function dropUser($user_id){
    $where["user_id"]      = $user_id;
    $GLOBALS["core"]->sqlDelete("mod_user_uservar", $where);
  }

  /*---------- Group module variables -------------------*/

  function loadUserGroupVars($user_id=null){
    if ($user_id)
      $user = new PHPWS_User((int)$user_id);
    else
      $user = $this;

    if (!$user->user_id)
      return;

    if (!$user->groups)
      return NULL;

    foreach ($user->groups as $group_id=>$group_name){
      $group = new PHPWS_User_Groups($group_id);
      if ($group->modSettings)
	$rights[$group_id] = $group->modSettings;
    }
    if (isset($rights))
      return $rights;
    else
      return NULL;
  }

  function listUserGroupVars($user_id=NULL, $module_title=NULL){
    if ($user_id)
      $user = new PHPWS_User((int)$user_id);
    else
      $user = $this;

    if (!$user->user_id)
      return;

    if (!($GLOBALS["core"]->moduleExists($module_title)))
      if (!($module_title = $GLOBALS["core"]->current_mod))
	exit("listUserGroupVars error: <b>$module_title</b> is malformed or does not exist.");

    if ($user->groupModSettings)
      foreach ($user->groupModSettings as $group_id=>$modVars)
	$groupVars[$group_id] = $modVars[$module_title];

    return $groupVars;
  }

  function getGroupVar($varName, $group_id=NULL, $module_title=NULL){
    if ($group_id)
      $group = new PHPWS_User_Groups($group_id);
    else
      $group = $this;

    if (!($GLOBALS["core"]->moduleExists($module_title)))
      if (!($module_title = $GLOBALS["core"]->current_mod))
	exit("getGroupVar error: <b>$module_title</b> is malformed or does not exist.");
    
    if (isset($group->modSettings[$module_title][$varName]))
      return $group->modSettings[$module_title][$varName];
    else
      return NULL;
  }


  function setGroupVar($varName, $varValue, $group_id=NULL, $module_title=NULL){
    if ($group_id)
      $group = new PHPWS_User_Groups($group_id);
    else
      $group = $this;


    $currentVar = $this->getGroupVar($varName);
    
    if (is_array($currentVar) && is_array($varValue)){
      foreach ($varValue as $key=>$value)
	$currentVar[$key] = $value;

      $varValue = $currentVar;
    }


    if (!$group->group_id)
      exit("setGroupVar error: Group ID ($group_id) is not registered in the system.<br />");

    if (!($GLOBALS["core"]->moduleExists($module_title)))
      if (!($module_title = $GLOBALS["core"]->current_mod))
	exit("setGroupVar error: <b>$module_title</b> is malformed or does not exist.");

    if (!(PHPWS_Text::isValidInput($varName)))
      exit("setGroupVar error: <b>$varName</b> is not a valid variable name.");

    $insert["module_title"] = $module_title;
    $insert["group_id"]     = $group->group_id;
    $insert["varName"]      = $varName;
    $insert["varValue"]     = $varValue;

    $group->dropGroupVar($varName, NULL, $module_title);

    if($GLOBALS["core"]->sqlInsert($insert, "mod_user_groupvar", 1)){
      if (!$group_id)
	$this->modSettings[$module_title][$varName] = $varValue;
      return TRUE;
    } else
      return FALSE;
  }

  function dropGroupVar($varName, $group_id=NULL, $module_title=NULL){
    if ($group_id)
      $group = new PHPWS_User_Groups($group_id);
    else
      $group = $this;

    if (!($GLOBALS["core"]->moduleExists($module_title)))
      if (!($module_title = $GLOBALS["core"]->current_mod))
	exit("dropGroupVar error: <b>$module_title</b> is malformed or does not exist.");

    if (!(PHPWS_Text::isValidInput($varName)))
      exit("dropGroupVar error: <b>$varName</b> is not a valid variable name.");

    $where["module_title"] = $module_title;
    $where["group_id"]      = $group->group_id;
    $where["varName"]      = $varName;

    $GLOBALS["core"]->sqlDelete("mod_user_groupvar", $where);
    if (!$group_id){
      if (isset($this->modSettings[$module_title][$varName]))
	unset($this->modSettings[$module_title][$varName]);
    }
   
  }

  function dropGroupModule($module_title=NULL){
    if (!($GLOBALS["core"]->moduleExists($module_title)))
      if (!($module_title = $GLOBALS["core"]->current_mod))
	exit("dropGroupModule error: <b>$module_title</b> is malformed or does not exist.");

    $where["module_title"] = $module_title;
    $GLOBALS["core"]->sqlDelete("mod_user_groupvar", $where);
  }

  function dropGroup($group_id, $module_title=NULL){
    if (!($GLOBALS["core"]->moduleExists($module_title)))
      if (!($module_title = $GLOBALS["core"]->current_mod))
	exit("dropGroupModule error: <b>$module_title</b> is malformed or does not exist.");

    $where["group_id"]      = $group_id;
    $GLOBALS["core"]->sqlDelete("mod_user_groupvar", $where);
  }

}
?>