<?php

require_once(PHPWS_SOURCE_DIR . "core/Form.php");
require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/WizardBag.php");

class PHPWS_User_Forms extends PHPWS_User_ModSetting {

  function userPanel(){
    if ($this->allow_access("users")){
      $count = 4;
      $template["ADD_USER"]   = PHPWS_Text::moduleLink($_SESSION["translate"]->it("Add User"), "users", array("user_op"=>"panelCommand", "usrCommand[user]"=>"add"));
      $template["EDIT_USER"]  = PHPWS_Text::moduleLink($_SESSION["translate"]->it("Manage Users"), "users", array("user_op"=>"panelCommand", "usrCommand[user]"=>"edit"));
      $template["ADD_GROUP"]  = PHPWS_Text::moduleLink($_SESSION["translate"]->it("Add Group"), "users", array("user_op"=>"panelCommand", "usrCommand[group]"=>"add"));
      $template["EDIT_GROUP"] = PHPWS_Text::moduleLink($_SESSION["translate"]->it("Manage Groups"), "users", array("user_op"=>"panelCommand", "usrCommand[group]"=>"edit"));

      if ($this->allow_access("users", "settings")){
	$template["SETTINGS"]   = PHPWS_Text::moduleLink($_SESSION["translate"]->it("Settings"), "users", array("user_op"=>"panelCommand", "usrCommand[admin]"=>"settings"));
	$count = 5;
	$settings = TRUE;
      }
      
      $width = "width=\"".floor(100 / $count)."%\"";

      $template["WIDTH1"] = $width;
      $template["WIDTH2"] = $width;
      $template["WIDTH3"] = $width;
      $template["WIDTH4"] = $width;
 
      
      if (isset($settings) && $settings == TRUE){
	$template["WIDTH5"] = $width;
	$template["WIDTH6"] = $width;
      }
      
      return PHPWS_Template::processTemplate($template, "users", "forms/panel.tpl");
    }
  }

  function manageGroups(){
    unset($_SESSION['Users_current_members']);

    $count = 0;
    $template["GROUP_ROWS"] = NULL;

    if (!($groups = $this->loadAllGroups())){
      $GLOBALS["CNT_user"]["title"] = $_SESSION["translate"]->it("No groups found");
      $GLOBALS["CNT_user"]["content"] = $_SESSION["translate"]->it("Create a group by clicking on Add Group above"). ".";
      return;
    }

    foreach ($groups as $info){
      $tplRows = NULL;
      $count++;
      $tplRows["GROUP_NAME"] = $info->group_name;
      $tplRows["GROUP_DESC"] = $info->description;
      $commands = PHPWS_Text::moduleLink($_SESSION["translate"]->it("Edit"), "users", array("user_op"=>"editGroupForm", "group_id"=>$info->group_id));

      if ($_SESSION["OBJ_user"]->allow_access("users", "deleteGroups"))
	$commands .= " | " . PHPWS_Text::moduleLink($_SESSION["translate"]->it("Delete"), "users", array("user_op"=>"deleteGroup", "group_id"=>$info->group_id));

      $tplRows["COMMANDS"] = $commands;
      if ($info->members)
	$tplRows["COUNT"] = count($info->members);
      else
	$tplRows["COUNT"] = "0";

      if ($count%2)
	$tplRows["TOGGLE_ONE"] = " ";
      else
	$tplRows["TOGGLE_TWO"] = " ";

      $template["GROUP_ROWS"] .= PHPWS_Template::processTemplate($tplRows, "users", "forms/groupRows.tpl");
    }

    $template["GROUP_NAME"] = $_SESSION["translate"]->it("Name");
    $template["GROUP_DESC"] = $_SESSION["translate"]->it("Description");
    $template["COUNT"]      = $_SESSION["translate"]->it("Members");
    $template["COMMANDS"]   = $_SESSION["translate"]->it("Commands");

    $GLOBALS["CNT_user"]["title"] = $_SESSION["translate"]->it("Manage Groups");
    $GLOBALS["CNT_user"]["content"] .= PHPWS_Template::processTemplate($template, "users", "forms/manageGroups.tpl");
  }


  function manageUsers(){
    $userList = $this->getUserList();    

    $nothingFound = $template = NULL;
    $count = 0;

    if (!empty($userList))
      $pagedUsers = PHPWS_Array::paginateDataArray($userList, "index.php?module=users&amp;user_op=manageUsers", $_SESSION["manageOptions"]["limit"], TRUE, $curr_sec_decor = array("<b>[ ", " ]</b>"), NULL, 10, TRUE);

    $alphabet = PHPWS_User::alphabet();
    $GLOBALS["CNT_user"]["title"]   = $_SESSION["translate"]->it("Manage Users");
    $template["ALPHABET"] = PHPWS_Text::moduleLink($_SESSION["translate"]->it("ALL"), "users", array("user_op"=>"manageUsers", "manageLetter"=>"all") ) . "&nbsp;\n";
    foreach ($alphabet as $alphachar)
      $template["ALPHABET"] .= PHPWS_Text::moduleLink($alphachar, "users", array("user_op"=>"manageUsers", "manageLetter"=>$alphachar) ) . "&nbsp;\n";

    if (!empty($userList)){
      $template["ADMIN"] = $_SESSION["translate"]->it("Admin");
      $template["USERNAME"] = $_SESSION["translate"]->it("Username");
      $template["COMMANDS"] = $_SESSION["translate"]->it("Commands");
      $template["USER_ROWS"] = NULL;

      $processed_info = array();

      $sql = "select user_id, username, deity, admin_switch from {$GLOBALS['core']->tbl_prefix}mod_users where ";
      foreach($pagedUsers[0] as $user) {
	$sql_result_user = $GLOBALS["core"]->sqlSelect("mod_users", "user_id", $user["user_id"]);
	$sql .= "user_id = " . $sql_result_user[0]["user_id"];
	$sql .= " OR ";
      }
      $sql = substr($sql, 0 , -4);
      $sql .= " order by username";

      $sql_result_users = $GLOBALS["core"]->query($sql);

      $pagedUsers[0] = NULL;
      $counter = 0;
      while($user = $sql_result_users->fetchRow()) {
      	$pagedUsers[0][$counter]["user_id"]   = $user["user_id"];
      	$pagedUsers[0][$counter]["username"] = $user["username"];
      	$pagedUsers[0][$counter]["deity"]     = $user["deity"];
      	$pagedUsers[0][$counter]["admin_switch"] = $user["admin_switch"];
	$counter++;
      }

      foreach($pagedUsers[0] as $userInfo){
	$count++;
	unset($userRows);
	if ($userInfo["admin_switch"])
	  $userRows["ADMIN"] = PHPWS_Text::moduleLink($_SESSION["translate"]->it("Yes"), "users", array("user_op"=>"turnOffAdmin", "user_id"=>$userInfo["user_id"]));
	else
	  $userRows["ADMIN"] = PHPWS_Text::moduleLink($_SESSION["translate"]->it("No"), "users", array("user_op"=>"turnOnAdmin", "user_id"=>$userInfo["user_id"]));

	$userRows["USERNAME"] = $userInfo["username"];

	$userRows["COMMANDS"]  = PHPWS_Text::moduleLink($_SESSION["translate"]->it("Edit"), "users", array("user_op"=>"editUserForm", "user_id"=>$userInfo["user_id"]));

	if ($this->allow_access("users", "deleteUsers"))
	  $userRows["COMMANDS"] .= " | " . PHPWS_Text::moduleLink($_SESSION["translate"]->it("Delete"), "users", array("user_op"=>"deleteUser", "user_id"=>$userInfo["user_id"]));

	if ($this->deity) {
	  if ($userInfo["deity"])
	    $userRows["COMMANDS"] .= " | " . PHPWS_Text::moduleLink($_SESSION["translate"]->it("Deity"), "users", array("user_op"=>"castoutUser", "user_id"=>$userInfo["user_id"]));
	  else
	    $userRows["COMMANDS"] .= " | " . PHPWS_Text::moduleLink($_SESSION["translate"]->it("Mortal"), "users", array("user_op"=>"annointUser", "user_id"=>$userInfo["user_id"]));
	}

	if ($count%2)
	  $userRows["TOGGLE_ONE"] = " ";
	else
	  $userRows["TOGGLE_TWO"] = " ";

	$processed_info[] = PHPWS_Template::processTemplate($userRows, "users", "forms/userRows.tpl");
      }

      $template["USER_ROWS"] = implode("", $processed_info);
      $template["PAGES"] = $pagedUsers[1] . "<br />" . $pagedUsers[2];
    } else 
      $nothingFound = "<i>" . $_SESSION["translate"]->it("No users found") . "</i>";

    $listLimit = PHPWS_Text::moduleLink("5", "users", array("user_op"=>"manageUsers", "manageLimit"=>"5")) . " ";
    $listLimit .= PHPWS_Text::moduleLink("10", "users", array("user_op"=>"manageUsers", "manageLimit"=>"10")) . " ";
    $listLimit .= PHPWS_Text::moduleLink("25", "users", array("user_op"=>"manageUsers", "manageLimit"=>"25")) . " ";
    $listLimit .= PHPWS_Text::moduleLink("50", "users", array("user_op"=>"manageUsers", "manageLimit"=>"50")) . " ";
    $listLimit .= PHPWS_Text::moduleLink("100", "users", array("user_op"=>"manageUsers", "manageLimit"=>"100"));
    $template["LIMIT"] = $_SESSION["translate"]->it("Limit");
    $template["LIMIT_LINK"] = $listLimit;

    $template["SEARCH"] = $_SESSION["translate"]->it("Search");
    $template["SEARCH_BUTTON"] = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Go"));
    $template["SEARCH_FORM"] = 
      PHPWS_Form::formHidden("module", "users")
      . PHPWS_Form::formHidden("user_op", "manageUsers")
      . PHPWS_Form::formTextField("manageSearch", NULL, 15);
    
    $GLOBALS["CNT_user"]["content"] .= "<form action=\"index.php\" method=\"post\">\n";
    $GLOBALS["CNT_user"]["content"] .=  $nothingFound . PHPWS_Template::processTemplate($template, "users", "forms/manageUsers.tpl") . "\n</form>";
  }

  function getUserList(){
    $change = 0;
    $and_or = $compare = $where = NULL;

    if (!isset($_SESSION["manageOptions"])){
      $options["page"]   = 1;
      $options["limit"]  = 10;
      $change = 1;
    } else
      $options = $_SESSION["manageOptions"];

    if(isset($_GET["manageLetter"])){
      $letter  = $_GET["manageLetter"];
      if (PHPWS_Text::isValidInput($letter)){
	if ($letter != "all")
	  $options["letter"] = $letter;
	else
	  unset($options["letter"]);
	$change = 1;
      }
      
    }

    if(isset($_GET["manageLimit"]))
      $limit   = $_GET["manageLimit"];

    if(isset($_REQUEST["managePage"]))
      $page    = $_REQUEST["managePage"];

    if(isset($_POST["manageSearch"]))
      $search  = $_POST["manageSearch"];

    if(isset($_POST["manageAdmin"]))
      $admin   = $_POST["manageAdmin"];

    
    if (isset($limit)){
      $options["limit"] = $limit;
      $page = 1;
    }
    
    if (isset($page))
      $options["page"] = $page;


    if (isset($admin)){
      $options["admin"] = $admin;
      $change = 1;
    }

    if ($change){
      unset($search);
      unset($options["search"]);
    }

    if (isset($search) && PHPWS_Text::isValidInput($search)){
      $change = 1;
      $options["search"] = $search;
    }

    if ($options){
      if (isset($options["letter"])){
	$where["username"]   = "^[" . $options["letter"] . strtolower($options["letter"]) . "]";
	$compare["username"] = "regexp";
      }

      if (!is_numeric($options["limit"]))
	$options["limit"] = $defaultLimit;

      if (isset($options["search"])){
	$where["username"]   = $options["search"];
	$compare["username"] = "regexp";
      }

      if ($change)
	$options["page"] = 1;

      if (isset($options["page"]))
	$limit = (($options["page"] - 1) * $options["limit"]) . ", ". $options["limit"];
       
      if (isset($options["admin"]))
	$where["admin_switch"] = $options["admin"];

    }

    $_SESSION["manageOptions"] = $options;

    if (!$this->deity) {
      $and_or["deity"] = "AND";
      $compare["deity"] = "=";
      $where["deity"] = 0;
    }

    if ($change)
      $_SESSION["manageOptions"]["userCount"] = count($GLOBALS["core"]->sqlSelect("mod_users", $where, NULL, "username", $compare, $and_or));

    if (is_null($compare)) $compare = "=";
    $sql = "select user_id from {$GLOBALS['core']->tbl_prefix}mod_users";

    if(!empty($where)) {
      if (is_array($where)) $sql .= PHPWS_Database::makeWhere($where, $compare, $and_or);
    }

    $sql .= " order by username ASC";
    
    $sql_result = $GLOBALS["core"]->query($sql);
    $rows = array();
    while($user = $sql_result->fetchRow()) {
      $rows[] = $user;
    }

    return $rows;
  }

  function setFormPermissions(){
    $modules = $GLOBALS["core"]->sqlSelect("modules", array("admin_mod"=>1, "deity_mod"=>0));
    foreach ($modules as $info){
      if (isset($_POST["mainRights"][$info["mod_title"]]))	$this->permissions["MOD_".$info["mod_title"]] = 1;
      else	$this->permissions["MOD_".$info["mod_title"]] = NULL;

      $rightDir = PHPWS_SOURCE_DIR . "mod/".$info["mod_directory"]."/conf/module_rights.txt";
      if (file_exists($rightDir)){
		if ($rows = file($rightDir)){
			PHPWS_Array::dropNulls($rows);
			foreach ($rows as $rights){
				$subright = explode("::", $rights);
				if (isset($_POST["subRights_".strtoupper($info["mod_title"])][$subright[0]])){
					if ($this->permissions["MOD_".$info["mod_title"]] == 1)
						$this->permissions["MOD_".$info["mod_title"]] = array();
					$this->permissions["MOD_".$info["mod_title"]][] = $subright[0];
				}
			}
		}
      }
    }
  }

  
  /**
   * Updates the database with the new user rights
   *
   * This function is called recursively within a while loop. The module
   * permissions are set dynamically. When the form displays the modules
   * and the rights within, it assigns the module name as the key to an array.
   * The rights for that module are the values in that array. The mod_title
   * tells the function what it needs to update and the type tells it whether
   * it is updating a group or an user.
   *
   * @author Matthew McNaney matt@NOSPAM.tux.appstate.edu
   * @param array mod_array array of switches for module rights.
   * @param string mod_title name of module currently acted upon
   * @param string type indicator of user or group
   */


  function norm_user_opt(){
    $content = "
<form action=\"index.php\" method=\"post\">
<input type=\"hidden\" name=\"module\" value=\"users\" />
".PHPWS_Form::formHidden("norm_user_op", "user_options")."
".PHPWS_Form::formSubmit($_SESSION["translate"]->it("Update My Information"), "user_option[update_info]")."<br /><br />
".PHPWS_Form::formSubmit($_SESSION["translate"]->it("Refresh My Cookie"), "user_option[cookie_refresh]")."
</form>";
    return $content;
  }


  function update_personal_info(){
    $content = "
<form action=\"index.php\" method=\"post\">
".PHPWS_Form::formHidden("module", "users")."
".PHPWS_Form::formHidden("norm_user_op", "usr_update_personal");
    
    $table[] = array("<b>".$_SESSION["translate"]->it("Default to Control Panel")."</b>", PHPWS_Form::formCheckBox("loginToList", "1", $this->getUserVar("loginToList")));
    $table[] = array("<b>".$_SESSION["translate"]->it("Password")."</b>", PHPWS_Form::formPassword("pass1", "10")." match ".PHPWS_Form::formPassword("pass2", "10"));
    $table[] = array("<b>".$_SESSION["translate"]->it("Email")."</b>", PHPWS_Form::formTextField("usr_email", $this->email, 30));

    $content .= PHPWS_Text::ezTable($table, 4)."
".PHPWS_Form::formSubmit($_SESSION["translate"]->it("Update"))."
</form>
";
    return $content;
  }


  function createGroupForm(){
    $template["GROUP_INFO"] = $this->groupForm();

    if ($_SESSION["OBJ_user"]->allow_access("users", "permissions"))
      $template["MODULE_INFO"] = $this->moduleForm("group");
    $template["USER_LIST"] = $this->memberForm();
    $template["SUBMIT"] = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Create Group"));

    $groupForm = PHPWS_Template::processTemplate($template, "users", "forms/groupForm.tpl");

    $content =
       "\n<form action=\"index.php\" method=\"post\" name=\"permissionsForm\">"
       . PHPWS_Form::formHidden(array("module"=>"users", "user_op"=>"createGroupAction"))
       . $groupForm
       . "\n</form>";

    $GLOBALS["CNT_user"]["title"] = $_SESSION["translate"]->it("Create Group");
    $GLOBALS["CNT_user"]["content"] .= $content;

  }

  function editGroupForm(){
    $template["GROUP_INFO"] = $this->groupForm();
    if ($_SESSION["OBJ_user"]->allow_access("users", "permissions"))
      $template["MODULE_INFO"] = $this->moduleForm("group");
    $template["USER_LIST"] = $this->memberForm();
    $template["SUBMIT"] = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Update Group"));

    $groupForm = PHPWS_Template::processTemplate($template, "users", "forms/groupForm.tpl");

    $content =
       "\n<form name=\"permissionsForm\" action=\"index.php\" method=\"post\">"
       . PHPWS_Form::formHidden(array("module"=>"users", "user_op"=>"editGroupAction"))
       . $groupForm
       . "\n</form>";

    $GLOBALS["CNT_user"]["title"] = $_SESSION["translate"]->it("Update Group");
    $GLOBALS["CNT_user"]["content"] .= $content;

  }

  function groupForm(){
    /* Translates */
    $infoTemplate["NAME"] = $_SESSION["translate"]->it("Group Name");
    $infoTemplate["DESCRIPTION"] = $_SESSION["translate"]->it("Description");

    $infoTemplate["NAME_FORM"]     = PHPWS_Form::formTextField("groupName", $this->group_name, 40);
    $infoTemplate["DESCRIPTION_WYSI"] = PHPWS_WizardBag::js_insert("wysiwyg", "groupedit", "groupDesc");
    $infoTemplate["DESCRIPTION_FORM"] = PHPWS_Form::formTextArea("groupDesc", $this->description , 10, 50);

    $content = PHPWS_Template::processTemplate($infoTemplate, "users", "forms/groupInfo.tpl");

    return $content;

  }


  function joinGroupForm(){
    if ($row = $GLOBALS["core"]->sqlSelect("mod_user_groups", NULL, NULL, "group_name")){
      foreach ($row as $group){
	if (!isset($this->groups[$group["group_id"]]))
	  $available[$group["group_id"]] = $group["group_name"];
      }
    } else
      return "<i>" . $_SESSION["translate"]->it("No groups available") ."</i>";

    if (!isset($available))
      $available = array();

    $availableCount = count($available);
    $groupCount = count($this->groups);

    $availableCount > $groupCount ? $size = $availableCount : $size = $groupCount;

    if ($size > 20)
      $size = 20;
    elseif ($size < 5)
      $size = 4;

    $groupList = $this->groups;
    
    if ($groupList)
      PHPWS_Array::dropNulls($groupList);
    
    $template["DROP_BUTTON"] = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Drop Group"), "dropGroup");
    $template["ADD_BUTTON"]  = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Add Group"), "addGroup");
    $template["CURRENT"]     = PHPWS_Form::formMultipleSelect("currentGroups", $groupList, NULL, NULL, NULL, $size);
    $template["AVAILABLE"]   = PHPWS_Form::formMultipleSelect("availableGroups", $available, NULL, NULL, NULL, $size);

    $template["CURRENT_TITLE"] = $_SESSION["translate"]->it("In Groups");
    $template["AVAILABLE_TITLE"] = $_SESSION["translate"]->it("Available Groups");

    return PHPWS_Template::processTemplate($template, "users", "forms/joinGroup.tpl");
  }

  function memberForm(){
    if (!($row = $GLOBALS["core"]->query("SELECT user_id, username FROM {$GLOBALS['core']->tbl_prefix}mod_users order by username")))
      exit("Error memberForm: Unable to find any users.");
    
    while($user = $row->fetchRow()) {      
      if (!isset($this->members[$user["user_id"]]))
	$available[$user["user_id"]] = $user["username"];
    }

    if (!isset($available))
      $available = array();

    $availableCount = count($available);
    $memberCount = count($this->members);

    $availableCount > $memberCount ? $size = $availableCount : $size = $memberCount;

    if ($size > 20)
      $size = 20;
    elseif ($size < 5)
      $size = 4;

    if (!isset($_SESSION['Users_current_members'][$this->group_id])) {
        $_SESSION['Users_current_members'][$this->group_id] = $this->members;
    }

    $template["DROP_BUTTON"] = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Drop Member"), "dropMember");
    $template["ADD_BUTTON"]  = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Add Member"), "addMember");
    $template["CURRENT"]     = PHPWS_Form::formMultipleSelect("currentMembers", $this->members, NULL, NULL, NULL, $size);
    $template["AVAILABLE"]   = PHPWS_Form::formMultipleSelect("availableMembers", $available, NULL, NULL, NULL, $size);

    $template["CURRENT_TITLE"] = $_SESSION["translate"]->it("In Group");
    $template["AVAILABLE_TITLE"] = $_SESSION["translate"]->it("Available Users");

    return PHPWS_Template::processTemplate($template, "users", "forms/memberForm.tpl");
  }

  function createUserForm(){
    $template["USER_INFO"] = $this->userForm();
    if ($_SESSION["OBJ_user"]->allow_access("users", "permissions"))
      $template["MODULE_INFO"] = $this->moduleForm();
    $template["GROUP_INFO"] = $this->joinGroupForm();
    $template["SUBMIT"] = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Create User"));
    $userForm = PHPWS_Template::processTemplate($template, "users", "forms/userForm.tpl");

    $content =
       "\n<form action=\"index.php\" method=\"post\" name=\"permissionsForm\">"
       . PHPWS_Form::formHidden(array("module"=>"users", "user_op"=>"createUserAction"))
       . $userForm
       . "\n</form>";

    $GLOBALS["CNT_user"]["title"] = $_SESSION["translate"]->it("Create User");
    $GLOBALS["CNT_user"]["content"] .= $content;

  }

  function editUserForm(){
    $template["USER_INFO"]   = $this->userForm();
    if ($_SESSION["OBJ_user"]->allow_access("users", "permissions"))
      $template["MODULE_INFO"] = $this->moduleForm();
    $template["GROUP_INFO"] = $this->joinGroupForm();
    $template["SUBMIT"] = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Update User"));
    $userForm = PHPWS_Template::processTemplate($template, "users", "forms/userForm.tpl");

    $content =
       "\n<form action=\"index.php\" method=\"post\" name=\"permissionsForm\">"
       . PHPWS_Form::formHidden(array("module"=>"users", "user_op"=>"editUserAction"))
       . $userForm
       . "\n</form>";

    $GLOBALS["CNT_user"]["title"] = $_SESSION["translate"]->it("Update User");
    $GLOBALS["CNT_user"]["content"] .= $content;
  }


  function moduleRightsForm($mode=NULL, $limiting = true){
    $count = 0;

	if ($limiting) {
		$limit = $GLOBALS["core"]->sqlSelect("mod_roles", array('rootOrg'=>$_SESSION['OBJ_user']->rootOrg, 'org'=>0));
		$permlimit = array();
		$t = unserialize($limit[0]['rights']);
		foreach ($t as $mod=>$rt) 
			$permlimit['MOD_' . $mod] = explode(":", "$rt");
	}

	$modules = $GLOBALS["core"]->sqlSelect("modules", array("admin_mod"=>1,	"deity_mod"=>0));
	$template["PERMISSIONS"] = NULL;

	if ($mode != "group" &&	isset($this->groups))
		$inherited = $this->getMemberRights();

	foreach	($modules as $checkMod) {
		if (!$GLOBALS["core"]->sqlSelect("mod_authorization", "module", $checkMod['mod_title'])) continue;

		$rowtpl =	NULL;
		$rowtpl["MODULE_RIGHTS"] = NULL;
		$count++;
		$mod_title = $checkMod["mod_title"];

		if ($limiting && !isset($permlimit["MOD_".$mod_title])) continue;

		if (isset($inherited)) {
			if (isset($inherited["MOD_".$mod_title]))
				$rowtpl["INHERIT"] = $_SESSION["translate"]->it("Yes");
			else  $rowtpl["INHERIT"] = $_SESSION["translate"]->it("No");
		}
		
		$rightDir	= PHPWS_SOURCE_DIR . "mod/".$checkMod["mod_directory"]."/conf/module_rights.txt";
		
		if ($mode	== "group"){
			if ($this->checkGroupPermission($mod_title))
				$mod_allow = 1;
			else
				$mod_allow = 0;
		}	else {
			if ($this->checkUserPermission($mod_title))
				$mod_allow = 1;
			else
				$mod_allow = 0;
		}
		
		$rowtpl["MOD_CHECK"] = PHPWS_Form::formCheckBox("mainRights[".$mod_title."]",	1, $mod_allow);
		$rowtpl["MODULE_NAME"] = $_SESSION['translate']->it($checkMod["mod_pname"]);
		
		if (file_exists($rightDir)){
			if ($rows =	file($rightDir)){
				foreach ($rows as $rights){
					$subright =	explode("::", $rights);
					
					if ($limiting && !in_array($subright[0], $permlimit["MOD_" . $mod_title])) continue;

					if ($mode == "group"){
						if ($this->checkGroupPermission($mod_title, $subright[0]))
							$sub_allow = 1;
						else
							$sub_allow = 0;
					} else {
						if ($this->checkUserPermission($mod_title, $subright[0]))
							$sub_allow = 1;
						else
							$sub_allow = 0;
					}
					if (count($subright) ==	2){
						$rowtpl["MODULE_RIGHTS"] .= PHPWS_Form::formCheckBox("subRights_"	. strtoupper($mod_title) . "[" . $subright[0] .	"]", 1,	$sub_allow)	. "	" .	$_SESSION['translate']->it(trim($subright[1])) . "<br />";
						$permissions[$mod_title][$subright[0]] = $subright[1];
					}
				}
			}
		}	else
			$permissions[$mod_title] = NULL;
		
		$pnames[$mod_title] =	$checkMod["mod_pname"];
		if ($count%2)	$rowtpl["TOGGLE_ONE"] =	" ";
		else	$rowtpl["TOGGLE_TWO"] =	" ";
		
		$template["PERMISSIONS"] .= PHPWS_Template::processTemplate($rowtpl, "users",	"forms/rightsRows.tpl");
	}
	
	if (isset($inherited))	  $template["INHERIT_TITLE"] = $_SESSION["translate"]->it("Inherits");
	
	$template["CHECK_TITLE"]  =	$_SESSION["translate"]->it(" 使用权&nbsp;&nbsp;&nbsp;&nbsp;");
	$template["NAME_TITLE"]	  =	$_SESSION["translate"]->it(" 功能模块&nbsp;&nbsp;&nbsp;&nbsp;");
	$template["RIGHTS_TITLE"] =	$_SESSION["translate"]->it(" 权限 ");

	return PHPWS_Template::processTemplate($template, "users", "forms/rightsForm.tpl");
  }

  function moduleRights($mode=NULL){
    $count = 0;

	$modules = $GLOBALS["core"]->sqlSelect("modules", array("admin_mod"=>1,	"deity_mod"=>0));
	$template["PERMISSIONS"] = NULL;

	if ($mode != "group" &&	isset($this->groups))
		$inherited = $this->getMemberRights();

	foreach	($modules as $checkMod) {
		if (!$GLOBALS["core"]->sqlSelect("mod_authorization", "module", $checkMod['mod_title'])) continue;

		$rowtpl =	NULL;
		$rowtpl["MODULE_RIGHTS"] = NULL;
		$count++;
		$mod_title = $checkMod["mod_title"];
		
		if (isset($inherited)) {
			if (isset($inherited["MOD_".$mod_title]))
				$rowtpl["INHERIT"] = $_SESSION["translate"]->it("Yes");
			else  $rowtpl["INHERIT"] = $_SESSION["translate"]->it("No");
		}
		
		$rightDir	= PHPWS_SOURCE_DIR . "mod/".$checkMod["mod_directory"]."/conf/module_rights.txt";
		
		if ($mode	== "group"){
			if ($this->checkGroupPermission($mod_title))
				$mod_allow = 1;
			else
				$mod_allow = 0;
		}	else {
			if ($this->checkUserPermission($mod_title))
				$mod_allow = 1;
			else
				$mod_allow = 0;
		}

		if (!$mod_allow) continue;
		
		$rowtpl["MOD_CHECK"] = '';
		$rowtpl["MODULE_NAME"] = $_SESSION['translate']->it($checkMod["mod_pname"]);
		
		if (file_exists($rightDir)){
			if ($rows =	file($rightDir)){
				foreach ($rows as	$rights){
					$subright =	explode("::", $rights);
					if ($mode == "group"){
						if ($this->checkGroupPermission($mod_title, $subright[0]))
							$sub_allow = 1;
						else
							$sub_allow = 0;
					} else {
						if ($this->checkUserPermission($mod_title, $subright[0]))
							$sub_allow = 1;
						else
							$sub_allow = 0;
					}
					if (count($subright) ==	2 && $sub_allow){
						$rowtpl["MODULE_RIGHTS"] .= $_SESSION['translate']->it(trim($subright[1])) . "<br />";
						$permissions[$mod_title][$subright[0]] = $subright[1];
					}
				}
			}
		}
		else $permissions[$mod_title] = NULL;
		
		$pnames[$mod_title] =	$checkMod["mod_pname"];
		if ($count%2)	$rowtpl["TOGGLE_ONE"] =	" ";
		else	$rowtpl["TOGGLE_TWO"] =	" ";
		
		$template["PERMISSIONS"] .= PHPWS_Template::processTemplate($rowtpl, "users",	"forms/rightsRows.tpl");
	}
	
	return PHPWS_Template::processTemplate($template, "users", "forms/rightsForm.tpl");
  }

  function moduleForm($mode=NULL){
    $count = 0;
    $modules = $GLOBALS["core"]->sqlSelect("modules", array("admin_mod"=>1, "deity_mod"=>0));
    $template["PERMISSIONS"] = NULL;

    if ($mode != "group" && isset($this->groups))
      $inherited = $this->getMemberRights();

    foreach ($modules as $checkMod){
      $rowtpl = NULL;
      $rowtpl["MODULE_RIGHTS"] = NULL;
      $count++;
      $mod_title = $checkMod["mod_title"];

      if (isset($inherited)){
	if (isset($inherited["MOD_".$mod_title]))
	  $rowtpl["INHERIT"] = $_SESSION["translate"]->it("Yes");
	else
	  $rowtpl["INHERIT"] = $_SESSION["translate"]->it("No");
      }

      $rightDir = PHPWS_SOURCE_DIR . "mod/".$checkMod["mod_directory"]."/conf/module_rights.txt";

      if ($mode == "group"){
	if ($this->checkGroupPermission($mod_title))
	  $mod_allow = 1;
	else
	  $mod_allow = 0;
      } else {
	if ($this->checkUserPermission($mod_title))
	  $mod_allow = 1;
	else
	  $mod_allow = 0;
      }

      $rowtpl["MOD_CHECK"] = PHPWS_Form::formCheckBox("mainRights[".$mod_title."]", 1, $mod_allow);
      $rowtpl["MODULE_NAME"] = $_SESSION['translate']->it($checkMod["mod_pname"]);

      if (file_exists($rightDir)){
	if ($rows = file($rightDir)){
	  foreach ($rows as $rights){

	    $subright = explode("::", $rights);
	    if ($mode == "group"){
	      if ($this->checkGroupPermission($mod_title, $subright[0]))
		$sub_allow = 1;
	      else
		$sub_allow = 0;
	    } else {
	      if ($this->checkUserPermission($mod_title, $subright[0]))
		$sub_allow = 1;
	      else
		$sub_allow = 0;
	    }
	    if (count($subright) == 2){
	      $rowtpl["MODULE_RIGHTS"] .= PHPWS_Form::formCheckBox("subRights_" . strtoupper($mod_title) . "[" . $subright[0] . "]", 1, $sub_allow) . " " . $_SESSION['translate']->it(trim($subright[1])) . "<br />";
	      $permissions[$mod_title][$subright[0]] = $subright[1];
	    }
	  }
	}
      } else
	$permissions[$mod_title] = NULL;
    
      $pnames[$mod_title] = $checkMod["mod_pname"];

      if ($count%2)
	$rowtpl["TOGGLE_ONE"] = " ";
      else
	$rowtpl["TOGGLE_TWO"] = " ";

      $template["PERMISSIONS"] .= PHPWS_Template::processTemplate($rowtpl, "users", "forms/rightsRows.tpl");

    }

    if (isset($inherited))
      $template["INHERIT_TITLE"] = $_SESSION["translate"]->it("Inherits");

    $template["CHECK_TITLE"]  = $_SESSION["translate"]->it("Allow");
    $template["NAME_TITLE"]   = $_SESSION["translate"]->it("Module Name");
    $template["RIGHTS_TITLE"] = $_SESSION["translate"]->it("Module Rights");
    $template["TOGGLE"]       = PHPWS_WizardBag::js_insert("check_all", "permissionsForm");
    return PHPWS_Template::processTemplate($template, "users", "forms/rightsForm.tpl");

  }


  function userForm(){
    /* Translates */
    $infoTemplate["ADMIN"]    = $_SESSION["translate"]->it("Administrator");
    $infoTemplate["USERNAME"] = $_SESSION["translate"]->it("Username");
    $infoTemplate["PASSWORD"] = $_SESSION["translate"]->it("Password");
    $infoTemplate["MATCH"]    = $_SESSION["translate"]->it("match");
    $infoTemplate["EMAIL"]    = $_SESSION["translate"]->it("Email");

    $infoTemplate["ADMIN_SWITCH"]      = PHPWS_Form::formCheckBox("admin_switch", 1, $this->admin_switch);
    $infoTemplate["USERNAME_FORM"]     = PHPWS_Form::formTextField("edit_username", $this->username);
    $infoTemplate["PASSWORD_FORM_ONE"] = PHPWS_Form::formPassword("pass1", 15);
    $infoTemplate["PASSWORD_FORM_TWO"] = PHPWS_Form::formPassword("pass2", 15);
    $infoTemplate["EMAIL_FORM"]        = PHPWS_Form::formTextField("email", $this->email, 25);

    $content = PHPWS_Template::processTemplate($infoTemplate, "users", "forms/userInfoForm.tpl");

    return $content;
  }


  function signup_user(){
//    $settings = $this->getSettings();
    $settings = PHPWS_User::getSettings();
    $showAll=FALSE;

    if(isset($_SESSION["OBJ_user"]->error) && !empty($_SESSION["OBJ_user"]->error)) {
	  if(is_array($_SESSION["OBJ_user"]->error)) {	
		$finaltpl["ERROR_MESSAGE"] = implode("<br />", $_SESSION["OBJ_user"]->error);
		$_SESSION["OBJ_user"]->error = array();
      }
    }

    if (isset($_REQUEST["signup_username"]))
      $username = $_REQUEST["signup_username"];
    elseif (isset($_REQUEST["block_username"]))
      $username = $_REQUEST["block_username"];
    elseif (isset($_REQUEST["login_username"]))
      $username = $_REQUEST["login_username"];
    elseif (isset($_REQUEST["forgot_username"]))
      $username = $_REQUEST["forgot_username"];
    else
      $username = NULL;

    $username = preg_replace("/[\n\r]/", "NONL", $username);

    if(!isset($_REQUEST["forgot_password"]) && !isset($_REQUEST["account_signup"])) {
	  $showAll=TRUE;
    } else {
      if(isset($_REQUEST["signup_request"])) {
			$_REQUEST["account_signup"] = TRUE;
			unset($_REQUEST["forgot_password"]);
      } else if(isset($_REQUEST["forgotPW"])) {
			$_REQUEST["forgot_password"] = TRUE;
			unset($_REQUEST["account_signup"]);
      }
    }
    if(isset($_REQUEST['error'])) {
      if($_REQUEST['error'] == "timeout") {
			$template["ERROR"]          = $_SESSION["translate"]->it("Your session timed out. Please log back in.");
      } else if($_REQUEST['error'] == "access") {
			$template["ERROR"]          = $_SESSION["translate"]->it("Access was denied. Please log in.");
      }		
    }

    if($showAll) {
      if($settings["show_remember_me"] && $settings["user_authentication"] == "local") {
			$template["REMEMBERME"] = $_SESSION["translate"]->it("记住我");
			$template["REMEMBERME_FORM"] = PHPWS_Form::formCheckBox("rememberme", 1);
      }

      $template["USERNAME"]         = $_SESSION["translate"]->it("用户名");
      $template["PASSWORD"]         = $_SESSION["translate"]->it("密码");
      $template["PASSWORD_NOTICE1"] = $_SESSION["translate"]->it("Your password will be sent to your email address");
      $template["PASSWORD_NOTICE2"] = $_SESSION["translate"]->it("When you receive it, log in using your new username and password");
      $template["LOGIN_TITLE"]      = $_SESSION["translate"]->it("登录");
      $template["PASSWORD_FORM"]    = PHPWS_Form::formPassword("password");
      $template["LOGIN_USERNAME"]    = PHPWS_Form::formTextField("login_username", $username);
      $template["LOGIN_BUTTON"]     = PHPWS_Form::formSubmit($_SESSION["translate"]->it("登录"), "usr_login");

      $finaltpl["LOGIN"] = PHPWS_Template::processTemplate($template, "users", "forms/login.tpl");
    }

    if($showAll || isset($_REQUEST["forgot_password"])) {
      $template["LBL_FORGOT_USERNAME"]         = $_SESSION["translate"]->it("用户名");
      $template["FORGET_TITLE"]     = $_SESSION["translate"]->it("忘记密码了?");
      $template["FORGOT_USERNAME"]    = PHPWS_Form::formTextField("forgot_username", $username);
      if(!$showAll)
	  $template["FORGOT_HIDDEN"] = PHPWS_Form::formHidden("forgot_password", TRUE);
      $template["FORGET_BUTTON"]     = PHPWS_Form::formSubmit($_SESSION["translate"]->it("发送请求"), "forgotPW");

      $finaltpl["FORGET"] = PHPWS_Template::processTemplate($template, "users", "forms/forget.tpl");
    }

    if($showAll || isset($_REQUEST["account_signup"]) || isset($_POST["account_signup"])) {
      if ($settings["user_signup"] != "none" && !empty($settings['user_contact'])){
			$template["LBL_SIGNUP_USERNAME"]         = $_SESSION["translate"]->it("用户名");
			$template["SIGNUP_USERNAME"]    = PHPWS_Form::formTextField("signup_username", $username);
			$template["SIGNUP_TITLE"]  = $_SESSION["translate"]->it("注册新账户");
			$template["EMAIL"]         = $_SESSION["translate"]->it("邮箱");
			$template["EMAIL_FORM"]    = PHPWS_Form::formTextField("usr_new_email", (isset($_POST["usr_new_email"])) ? $_POST["usr_new_email"] : NULL, 40);
			if ($settings["user_signup"] == "hold")
			  $template["SIGNUP_BUTTON"] = PHPWS_Form::formSubmit($_SESSION["translate"]->it("申请"), "signup_request");
			else {
			  $template["SIGNUP_BUTTON"] = PHPWS_Form::formSubmit($_SESSION["translate"]->it("注册"), "signup_request", 'btn btn-primary btn-lg');
			}
			if(!$showAll)
			  $template["SIGNUP_HIDDEN"]   = PHPWS_Form::formHidden("account_signup", TRUE);
			/* Double password entry for sites with immediate sign ins */
			if ($settings['user_signup'] == 'login') {
				$template['PASSWORD_1']    = PHPWS_Form::formPassword('signup_password1');
				$template['PASSWORD_2']    = PHPWS_Form::formPassword('signup_password2');
				$template["PASSWORD_LABEL1"] = $_SESSION["translate"]->it('输入至少5个字母数字的密码');
				$template["PASSWORD_LABEL2"] = $_SESSION["translate"]->it('重输一次密码');
				$template["TERMS"] = PHPWS_Form::formCheckBox('term', 1, 1) . $_SESSION["translate"]->it('我同意《明轩供应链管理平台用户协议》');
			}
			$template['COMPANYNAME']    = PHPWS_Form::formTextField('companyname');
			$template["COMPANYNAME_LABEL"] = $_SESSION["translate"]->it('公司名称');
			$template['TAXID']    = PHPWS_Form::formTextField('taxid');
			$template["TAXID_LABEL"] = $_SESSION["translate"]->it('统一社会信用代码');
			$finaltpl["SIGNUP"] = PHPWS_Template::processTemplate($template, "users", "forms/signup.tpl");
      }
    }

    $finish = PHPWS_Template::processTemplate($finaltpl, "users", "forms/useraccount.tpl");

    $GLOBALS["CNT_user"]["content"] .= 
       "\n<form action=\"index.php\" method=\"post\" name=\"user_login\">"
       . PHPWS_Form::formHidden(array("module"=>"users", "norm_user_op"=>"signupAction"))
       . $finish
       . "\n</form>";
  }

  function login_box(){
    $settings = PHPWS_User::getSettings();

    if ($settings["show_login"]) {
      $login_box["USERNAME"] = $_SESSION["translate"]->it("Username");
      $login_box["PASSWORD"] = $_SESSION["translate"]->it("Password");

      $username = (isset($_REQUEST["block_username"])) ? $_REQUEST["block_username"] : NULL;
      $username = preg_replace("/[\n\r]/", "NONL", $username);

      $login_box["USERNAME_FORM"] = PHPWS_Form::formTextField("block_username", $username, 15);
      $login_box["PASSWORD_FORM"] = PHPWS_Form::formPassword("password", 15);

      if($settings["show_remember_me"] && $settings["user_authentication"] == "local") {
			$login_box["REMEMBERME"] = $_SESSION["translate"]->it("Remember Me");
			$login_box["REMEMBERME_FORM"] = PHPWS_Form::formCheckBox("rememberme", 1);
      }

      $login_box["SUBMIT"] = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Log In"), "login_button");

      if (!empty($settings['user_contact'])){
			$forgot = $_SESSION["translate"]->it("Forgot Your Password?");
			$forgot_vars["module"] = "users";
			$forgot_vars["norm_user_op"] = "signup";
			$forgot_vars["forgot_password"] = 1;
			$login_box["FORGOT"] = PHPWS_Text::link("index.php", $forgot, "index", $forgot_vars);
		
			$account_vars["norm_user_op"] = "signup";
			$account_vars["account_signup"] = 1;
			$account_vars["module"] = "users";
				
			if ($settings["user_signup"] != "none"){
				$new_account = $_SESSION["translate"]->it("New Account Signup");
				$login_box["NEW_ACCOUNT"] = PHPWS_Text::link("index.php", $new_account, "index", $account_vars);
			}
      }

      $login_box["LOGIN"] = $GLOBALS["CNT_user_small"]["title"] = $_SESSION["translate"]->it("Log In");

      $content = PHPWS_Template::processTemplate($login_box, "users", "forms/loginBox.tpl");
      $GLOBALS["CNT_user_small"]["content"] = "\n<form action=\"index.php\" method=\"post\" name=\"user_login_box\">\n"	 . PHPWS_Form::formHidden(array("module"=>"users", "norm_user_op"=>"login")) . $content . "\n" . "</form>";
    }
  }

  function user_defaults(){
    extract($this->getSettings());

    $GLOBALS["CNT_user"]["title"] = $_SESSION["translate"]->it("User Defaults");
    $GLOBALS["CNT_user"]["content"] = "
<h3>".$_SESSION["translate"]->it("Contact Information"). CLS_help::show_link("users", "contact") . "</h3>
<form action=\"index.php\" method=\"post\">
".PHPWS_Form::formHidden("user_op", "update_user_defaults")."
".PHPWS_Form::formHidden("module", "users")."
<table cellpadding=\"4\">
<tr><td><b>".$_SESSION["translate"]->it("User Email Contact").":</b></td><td>".PHPWS_Form::formTextField("user_contact", $user_contact, 40);

    if (!$user_contact)
      $GLOBALS["CNT_user"]["content"] .= " <span class=\"errortext\">".$_SESSION["translate"]->it("This MUST be set")."!</span>";

$GLOBALS["CNT_user"]["content"] .= "</td></tr>
<tr><td><b>".$_SESSION["translate"]->it("Subject Line").":</b></td><td>".PHPWS_Form::formTextField("usr_subject", $nu_subj, 30)."</td></tr>
<tr><td valign=\"top\"><b>".$_SESSION["translate"]->it("Greeting").":</b></td><td>".PHPWS_Form::formTextArea("usr_greeting", $greeting)."</td></tr>
</table>
<hr />
<h3>" . $_SESSION["translate"]->it("Allow New User Signup") . CLS_help::show_link("users", "signup") . "</h3>";
    $GLOBALS["CNT_user"]["content"] .= PHPWS_Form::formRadio("usr_signup", "none", $user_signup, NULL, " <b>".$_SESSION["translate"]->it("None")."</b>")."<br />";
    $GLOBALS["CNT_user"]["content"] .= PHPWS_Form::formRadio("usr_signup", "send", $user_signup, NULL, " <b>".$_SESSION["translate"]->it("All users can apply")."</b>")."<br />";
    $GLOBALS['CNT_user']['content'] .= PHPWS_Form::formRadio('usr_signup', 'send', $user_signup, NULL, ' <b>'.$_SESSION['translate']->it('All users can apply - password will be emailed').'</b>').'<br />';
    /* Immediate approval signup option */
    $GLOBALS['CNT_user']['content'] .= PHPWS_Form::formRadio('usr_signup', 'login', $user_signup, NULL, ' <b>'.$_SESSION['translate']->it('All users can apply - immediate login').'</b>');
    /* Option to use a 'Welcome' redirect page [Eloi] */
    $GLOBALS['CNT_user']['content'] .= '<ul>'
        . PHPWS_Form::formTextField('welcomeURL' ,$welcomeURL ,50 ,NULL
            ,$_SESSION['translate']->it('Default page to send users to after signing up').':<br />ex:"index.php?module=announce&amp;ANN_user_op=view&amp;ANN_id=54" ')
        . '</ul>';
    $GLOBALS["CNT_user"]["content"] .= PHPWS_Form::formRadio("usr_signup", "hold", $user_signup, NULL, " <b>".$_SESSION["translate"]->it("Only approved users can apply")."</b>")."<br />";
    $GLOBALS["CNT_user"]["content"] .= "<hr /><h3>".$_SESSION["translate"]->it("Authentication Method") . CLS_help::show_link("users", "authentication") . "</h3>";
    $GLOBALS["CNT_user"]["content"] .= PHPWS_Form::formRadio("user_authentication","local",$user_authentication, NULL, " <b>".$_SESSION["translate"]->it("Local Database")."</b>")."<br />";
    $GLOBALS["CNT_user"]["content"] .= PHPWS_Form::formRadio("user_authentication","external",$user_authentication, NULL, " <b>".$_SESSION["translate"]->it("External PHP function")."</b>&nbsp;&nbsp;");
    $GLOBALS["CNT_user"]["content"] .= PHPWS_Form::formTextField("external_filename", $external_auth_file);
    $GLOBALS["CNT_user"]["content"] .= "<br /><hr />" . PHPWS_Form::formCheckBox("usr_login_box", 1, $show_login) . " <b>" . $_SESSION["translate"]->it("Show Login Box") . CLS_help::show_link("users", "login") . "</b>";
    $GLOBALS["CNT_user"]["content"] .= "<br />" . PHPWS_Form::formCheckBox("usr_rememberme_option", 1, $show_remember_me) . " <b>" . $_SESSION["translate"]->it("Show 'Remember Me' Option") . CLS_help::show_link("users", "rememberme") . "</b>";
    $GLOBALS["CNT_user"]["content"] .= "<br /><br />".PHPWS_Form::formSubmit($_SESSION["translate"]->it("Update"));
    $GLOBALS["CNT_user"]["content"] .= "</form>";

    $hidden = PHPWS_Form::formHidden(array("module"=>"users", "user_op"=>"usr_login_box"));

  }

  function forgotPasswordForm(){  
    if ($this->deity){  
      $GLOBALS["CNT_user"]["title"] = $_SESSION["translate"]->it("Sorry")."...";  
      $GLOBALS["CNT_user"]["content"] = $_SESSION["translate"]->it("Deity users are not permitted to use this form").".<br />";  
      $GLOBALS["CNT_user"]["content"] .= $_SESSION["translate"]->it("Another deity will need to change your password").".";  
      return;  
    }  
    $compareHash = $this->getUserVar("forgotHash");  
    if ($compareHash != $_REQUEST["hash"]){  
      // Security check needed here  
      $GLOBALS["CNT_user"]["title"] = $_SESSION["translate"]->it("Sorry")."...";  
      $GLOBALS["CNT_user"]["content"] = $_SESSION["translate"]->it("You have not requested a password change").".<br />";  
      return;  
    }  
    $requestDate = $this->getUserVar("forgotDateTime");  
    $dayEarlier = mktime() - (24 * 3600);  
 
    if ($requestDate < $dayEarlier){  
      $GLOBALS["CNT_user"]["title"] = $_SESSION["translate"]->it("Sorry")."...";  
      $GLOBALS["CNT_user"]["content"] = $_SESSION["translate"]->it("You have run out of time to update your password") . "<br />"  
         . $_SESSION["translate"]->it("Please apply for a new password change form") .".<br />"  
         . PHPWS_Text::moduleLink($_SESSION["translate"]->it("Go to signup page"), "users", array("norm_user_op"=>"signup"));  
      $this->dropUserVar("forgotHash");  
      $this->dropUserVar("forgotDateTime");  
    } else {  
      $content =  
         "\n<form action=\"index.php\" method=\"post\">"  
         . PHPWS_Form::formHidden(array("norm_user_op"=>"forgotPasswordAction", "hash"=>$compareHash)) . "\n"  
         . PHPWS_Form::formPassword("pass1") . " " . $_SESSION["translate"]->it("新密码") . "<br />\n"  
         . PHPWS_Form::formPassword("pass2") . " " . $_SESSION["translate"]->it("再输一次") . "<br />\n"  
         . PHPWS_Form::formSubmit($_SESSION["translate"]->it("设置新密码"))  
         . "\n</form>";  
 
      $GLOBALS["CNT_user"]["title"]    = $_SESSION["translate"]->it("Change Your Password");  
      $GLOBALS["CNT_user"]["content"] .= $content;  
    }  
  }
}
?>