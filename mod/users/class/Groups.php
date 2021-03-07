<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Array.php");

class PHPWS_User_Groups extends PHPWS_User_Forms {

  var $group_id;
  var $group_name;
  var $description;
  var $members;
  var $modSettings;
  var $permissions;

  function PHPWS_User_Groups($group_id=NULL){
    if (isset($group_id) && is_numeric($group_id)){
      if (!$this->loadGroup($group_id))
	$this->members=array();
    }
    else
      $this->members=array();
  }

  function loadGroup($group_id){
    if (!is_numeric($group_id) || is_null($group_id))
      exit("Error in loadGroup: group_id is missing.");

    if (!($rows = $GLOBALS["core"]->sqlSelect("mod_user_groups", "group_id", $group_id))){
      return FALSE;
    }

    extract($rows[0]);
    $this->group_id = $group_id;
    $this->group_name = $group_name;
    $this->description = $description;
    if ($members)
      $this->members = $this->listMembers();
    else
      $this->members = array();

    $this->loadModSettings("group");
    $this->setPermissions();
    return TRUE;
  }


  function listGroupErrors(){
    $content = NULL;
    if (isset($GLOBALS["groupError"])){
      foreach ($GLOBALS["groupError"] as $error)
	$content .= "<span class=\"errortext\">$error</span><br />\n";
    }
    
    unset($GLOBALS["groupError"]);

    return $content;
  }

  function removeUserFromGroups($user_id=NULL){
    if ($user_id)
      $user = new PHPWS_User((int)$user_id);
    else
      $user = $this;

    if ($user->groups){
      foreach ($user->groups as $group_id=>$group_name){
	$group = new PHPWS_User_groups($group_id);
	if (!count($group->members))
	  continue;

	unset($group->members[$user->user_id]);

	$sql["members"] = implode(":", array_keys($group->members));
	$GLOBALS["core"]->sqlUpdate($sql, "mod_user_groups", "group_id", $group->group_id);
      }
    }
  }

  function updateUserGroups($user_id=NULL){
    if ($user_id)
      $user = new PHPWS_User((int)$user_id);
    else
      $user = $this;
    if (isset($user->groups)){
      foreach ($user->groups as $group_id=>$group_name){
	$group = new PHPWS_User_groups($group_id);
	if(is_null($group_name))
	  unset($group->members[$user->user_id]);
	else
	  $group->members[$user->user_id] = PHPWS_User::getUsername($user->user_id);

	$sql["members"] = implode(":", array_keys($group->members));
	$GLOBALS["core"]->sqlUpdate($sql, "mod_user_groups", "group_id", $group->group_id);
      }
    }
  }
  
  function getMemberRights($user_id=NULL){
    if ($user_id)
      $user = new PHPWS_User((int)$user_id);
    else
      $user = $this;

    if (!($groups = $user->groups))
      return;

    PHPWS_Array::dropNulls($groups);

    foreach ($groups as $group_id=>$group_name){
      $group = new PHPWS_User_Groups($group_id);
      if ($group->permissions){
	foreach ($group->permissions as $rightName=>$subRight){
	  if (!isset($permissions[$rightName]))
	    $permissions[$rightName] = $subRight;
	  elseif (isset($permissions[$rightName])){
	    if (is_array($subRight)){
	      if ($permissions[$rightName] == 1)
		$permissions[$rightName] = $subRight;
	      elseif(is_array($permissions[$rightName]))
		  $permissions[$rightName] = array_merge($permissions[$rightName], $subRight);
	    }
	  }
	}
      }
    }

    if (isset($permissions))
      return $permissions;
    else
      return NULL;
  }
  

  function updateMembers($group_id=NULL){
    if ($group_id)
      $group = new PHPWS_User_Groups((int)$group_id);
    else
      $group = $this;

    if (isset($_SESSION['Users_current_members'][$this->group_id])) {
        foreach($_SESSION['Users_current_members'][$this->group_id] as $dump_id=>$nullit) { 
            PHPWS_User_Groups::removeGroupFromUser($dump_id, $this->group_id);
        }
    }

    unset($_SESSION['Users_current_members']);

    if ($group->members){
      foreach ($group->members as $user_id=>$username){
	$user = new PHPWS_User($user_id);
	if (is_null($username))
	  unset($user->groups[$group->group_id]);
	else
	  $user->groups[$group->group_id] = PHPWS_User_Groups::getGroupName($group->group_id);

	$sql["groups"] = implode(":", array_keys($user->groups));
	$GLOBALS["core"]->sqlUpdate($sql, "mod_users", "user_id", $user->user_id);
      }
    }
  }

  function listGroups($user_id=NULL){
    if ($user_id)
      $user = new PHPWS_User((int)$user_id);
    else
      $user = $this;

    if (!(list($row) = $GLOBALS["core"]->sqlSelect("mod_users", "user_id", $user->user_id)))
      exit("Error listGroups: Unable to load user");

    $groups = $row["groups"];
 
    if ($groups){
      $groupList = explode(":", $groups);
      /* Optimization: Group names are retrieved in one query now - (Eloi George) */
      $groupTable = $this->listAllGroups();
      
      foreach ($groupList as $group_id){
	$allGroups[$group_id] = $groupTable[$group_id];
      }
      unset($groupTable);
    }
    
    return $allGroups;
  }


  function listMembers($group_id=NULL){
    if ($group_id)
      $group = new PHPWS_User_Groups((int)$group_id);
    else
      $group = $this;

    if (!(list($row) = $GLOBALS["core"]->sqlSelect("mod_user_groups", "group_id", $group->group_id)))
      exit("Error listMembers: Unable to load group");

    $members = $row["members"];

    if ($members){
      $memberList = explode(":", $members);

      foreach ($memberList as $user_id){
	$username = $GLOBALS["core"]->getOne("select username from mod_users where user_id=$user_id order by username", TRUE);
	$allMembers[$user_id] = $username;
      }
    }
    return $allMembers;
  }


  function groupAction($mode){
    extract($_POST);
    $this->setFormPermissions();

    if (empty($groupName))
      $error[] = $_SESSION["translate"]->it("You must give your group a name").".";
    else
      $this->group_name = strip_tags($groupName);

    $this->description = PHPWS_Text::parseInput($groupDesc);

    if (isset($dropMember)){
      if (isset($currentMembers))
	foreach ($currentMembers as $dropId)
	  unset($this->members[$dropId]);

      return NULL;
    } elseif (isset($addMember)) {
      if ($availableMembers)
	foreach ($availableMembers as $addId)
	  $this->members[$addId] = PHPWS_USER::getUsername($addId);
      return NULL;
    }

    if (isset($error)){
      $GLOBALS["groupError"] = $error;
      return FALSE;
    } else {
      $sql["group_name"]  = $this->group_name;
      $sql["description"] = $this->description;

      $users = $this->members;

      if ($users){
	PHPWS_Array::dropNulls($users);
	$writeUsers = implode(":", array_keys($users));
      }
      else
	$writeUsers = NULL;

      $sql["members"]     = $writeUsers;
      if ($mode == "add"){
	if ($this->group_id = $GLOBALS["core"]->sqlInsert($sql, "mod_user_groups", TRUE, TRUE)){
	  $this->setGroupPermissions();
	  $this->updateMembers();
	  return TRUE;
	} else 
	  exit("Error groupAction - Problem writing to the database.");
      }
      elseif ($mode == "edit"){
	if ($GLOBALS["core"]->sqlUpdate($sql, "mod_user_groups", "group_id", $this->group_id)){
	  $this->setGroupPermissions();
	  $this->updateMembers();
	  return TRUE;
	} else 
	  exit("Error groupAction - Problem writing to the database.");
      }
      else
	exit("Error groupAction - Incorrect mode requested.");
    }

  }

  function setGroupPermissions(){
    $permissions = $this->permissions;

    foreach ($permissions as $mod_title=>$rights){
      if (is_null($rights))
	$this->dropGroupVar($mod_title);
      else {
	if (is_array($rights))
	  $this->setGroupVar($mod_title, implode(":", $rights));
	else
	  $this->setGroupVar($mod_title, 1);
      }
    }
  }

  function listAllGroups(){
    if (!($rows = $GLOBALS["core"]->sqlSelect("mod_user_groups")))
      return NULL;

    foreach ($rows as $groupInfo)
      $groups[$groupInfo["group_id"]] = $groupInfo["group_name"];
    
    return $groups;
  }


  function loadAllGroups(){
    if (!($rows = $GLOBALS["core"]->sqlSelect("mod_user_groups")))
      return NULL;

    foreach ($rows as $groupInfo)
      $groups[] = new PHPWS_User_Groups($groupInfo["group_id"]);

    return $groups;
  }

  
  function deleteGroup($group_id, $confirm=NULL){
    if (!$confirm){
      $sql = "select group_name from mod_user_groups where group_id='$group_id'";
      $group_name = $GLOBALS["core"]->getOne($sql, TRUE);
      
      $GLOBALS["CNT_user"]["title"] = $_SESSION["translate"]->it("Confirmation");
      $content = $_SESSION["translate"]->it("Are you sure you want to delete the [var1] group", "<b>".$group_name."</b>")."?&nbsp;&nbsp;";
      $content .= PHPWS_Text::moduleLink($_SESSION["translate"]->it("Yes"), "users", array("user_op"=>"deleteGroup", "confirm"=>"yes", "group_id"=>$group_id))."&nbsp;&nbsp;";
      $content .= PHPWS_Text::moduleLink($_SESSION["translate"]->it("No"), "users", array("user_op"=>"manageGroups"));
      $GLOBALS["CNT_user"]["content"] .= $content;
    } else {
      $group = new PHPWS_User_Groups($group_id);
      if ($group->members){
	foreach ($group->members as $user_id=>$username){
	  PHPWS_User_Groups::removeGroupFromUser($user_id, $group_id);
	  $nameList[] = $username;
	}
      }

      
      $GLOBALS["core"]->sqlDelete("mod_user_groups", "group_id", $group_id); 
      $GLOBALS["CNT_user"]["title"] = $_SESSION["translate"]->it("Group Removed") . ".";
      if (isset($nameList)){
	$GLOBALS["CNT_user"]["content"] .= "<b>".$_SESSION["translate"]->it("Users updated") .":</b><br />";
	foreach ($nameList as $username)
	  $GLOBALS["CNT_user"]["content"] .= $username . "<br />";
      }
    }
  }


  function group_name_match($group_name){
    $sql = "select group_id from {$GLOBALS['core']->tbl_prefix}mod_user_groups where group_name='$group_name'";
    $sql_result = $GLOBALS["core"]->query($sql);
    return $sql_result->numrows();
  }

  function removeGroupFromUser($user_id, $group_id){
    $user = new PHPWS_User($user_id);
    unset($user->groups[$group_id]);

    if (!empty($user->groups)){
      $update["groups"] = implode(":", array_keys($user->groups));
    }
    else {
      $update["groups"] = NULL;
    }
    return $GLOBALS["core"]->sqlUpdate($update, "mod_users", "user_id", $user_id);
  }

  function getGroupName($group_id){
    if ($group = $GLOBALS["core"]->sqlSelect("mod_user_groups", "group_id", (int)$group_id))
      return $group[0]["group_name"];
    else return FALSE;

  }

  function checkGroupPermission($mod_title, $subright=NULL){
    if (isset($this->permissions["MOD_".$mod_title]))
      $rights = $this->permissions["MOD_".$mod_title];

    if (isset($rights)){
      if (is_null($subright))
	return TRUE;
      elseif (is_array($rights))
	return in_array($subright, $rights);
    }
    else
      return FALSE;
  }

  function loadModSettings($mode){
    if ($mode == "user"){
		$table = "mod_user_uservar";
		$column = "user_id";
		if (!($id = $this->user_id))
			exit("Error loadModSettings: Missing User Id");
	} elseif ($mode == "group"){
		$table = "mod_user_groupvar";
		$column = "group_id";
		if (!($id = $this->group_id))
			exit("Error loadModSettings: Missing Group Id");
	} else exit("Error loadModSettings: Incorrect mode");

	if (!($settings = $GLOBALS["core"]->sqlSelect($table, $column, $id))){
		return NULL;
	}

    foreach ($settings as $info){
      extract($info);

      if (!empty($varValue)){
			if (preg_match("/^[aO]:\d+:/", $varValue))
				if (is_array(unserialize($varValue)))
					$varValue = unserialize($varValue);
			$this->modSettings[$module_title][$varName] = $varValue;
		}
    }
    return TRUE;
  }

  function setPermissions(){
    $modules = $GLOBALS["core"]->sqlSelect("modules", array("admin_mod"=>1, "deity_mod"=>0));

    foreach($modules as $info) {
		if (isset($this->modSettings["users"]["MOD_".$info["mod_title"]])) {
			$modset = $this->modSettings["users"]["MOD_".$info["mod_title"]];

			if ($modset != 1)
				$this->permissions["MOD_".$info["mod_title"]] = explode(":", $modset);
			else $this->permissions["MOD_".$info["mod_title"]] = 1;
			
			unset($this->modSettings["users"]["MOD_".$info["mod_title"]]);
			if (!count($this->modSettings["users"]))
				unset($this->modSettings["users"]);
		}
	}
  }


}
?>