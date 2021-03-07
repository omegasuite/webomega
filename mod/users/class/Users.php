<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');

require_once(PHPWS_SOURCE_DIR . 'mod/approval/class/Approval.php');
require_once(PHPWS_SOURCE_DIR . 'mod/phpmailer/class.phpmailer.php');
require_once(PHPWS_SOURCE_DIR . 'core/Upload.php');

/**
 * The user class handles the authorization and manipulation of the user
 * accounts within the system.
 *
 * The user class holds on to the data of the person who logged in. It 
 * carries with it their personal information and their rights within the
 * system, what modules they have access to and what parts of each module
 * are defined. This will described in more detail per function.
 *
 * @version $Id: Users.php,v 1.113 2005/05/19 12:38:41 darren Exp $
 * @author Matthew McNaney matt@NOSPAM.tux.appstate.edu
 * @module users
 * @modulegroup administration
 * @package phpWebSite
 */
define('USER_COOKIE', md5($GLOBALS['core']->site_hash.'_user'));
require(PHPWS_SOURCE_DIR . 'mod/users/init.php');


class PHPWS_User extends PHPWS_User_Groups{
  var $user_id;
  var $username;
  var $password;
  var $email;
  var $admin_switch;
  var $deity;
  var $groups;
  var $head;
  var $modSettings;
  var $permissions;
  var $groupPermissions;
  var $groupModSettings;
  var $error;

  var $temp_var;
  var $last_on;
  var $js_on;
  var $user_settings;
  var $jumpURL;

  var $address;
  var $city;
  var $state;
  var $org;
  var $rootOrg;
  var $zip;
  var $phone;
  var $pre_id;
  var $realname;
  var $appuid;
  var $Latitude;
  var $Longitude;
  var $locationtime;

  /**
   * Constructor for PHPWS_User class
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param integer id If greater than 0, loads the appropiate user
   */
  function PHPWS_User($id = 0){
    $this->username         = NULL;
    $this->password         = NULL;
    $this->email            = NULL;
    $this->phone            = NULL;
    $this->realname         = NULL;
    $this->admin_switch     = 0;
    $this->deity            = 0;
    $this->Longitude            = 0;
    $this->Latitude            = 0;
    $this->locationtime            = 0;
    $this->groups           = array();
    $this->modSettings      = NULL;
    $this->permissions      = NULL;
    $this->groupPermissions = NULL;
    $this->groupModSettings = NULL;
    $this->last_on          = NULL;
    $this->js_on            = NULL;
    $this->user_settings    = NULL;
    $this->jumpURL          = '.';
    $this->error            = array();

    $this->realname = NULL;
    $this->address = NULL;
    $this->city = NULL;
    $this->zip = NULL;
    $this->org = 0;
    $this->rootOrg = 0;
    $this->appuid = 0;
    $this->phone = NULL;
    $this->city = '芜湖市';
    $this->state = '安徽省';

	if (is_numeric($id) && $id > 0){
      if (!$this->loadUser($id, false)){
	$error = new PHPWS_Error('users', 'PHPWS_User', 'Unable to load user id' . $id, 'exit', 1);
	$error->message();
      }
    }
    else
      $this->user_id = 0;
  }

  /**
   * Loads the user information into the class
   * 
   * Permissions are handled separately in the load_permissions function
   *
   * @author            Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param integer id  User's id
   * @param boolean log If TRUE, update the user's log time
   */
  function loadUser ($id, $log=true){
    if (list($row) = $GLOBALS['core']->sqlSelect('mod_users', 'user_id', $id)){
      extract($row);

      $this->user_id      = $id;
      $this->username     = $username;
      $this->password     = $password;
      $this->email        = $email;

      $this->realname = $realname;
      $this->address = $address;
      $this->city = $city;
      $this->state = $state;
      $this->zip = $zip;
      $this->org = $org;
	  $this->rootOrg = $rootOrg;
      $this->phone = $phone;
	  $this->realname = $realname;
	  $this->head = $head;
      $this->zip = $zip;
      $this->appuid = $appuid;
      $this->Longitude = $Longitude;
      $this->Latitude = $Latitude;
      $this->locationtime = $locationtime;

	  if ($admin_switch) $this->admin_switch = TRUE;
      else $this->admin_switch = FALSE;

      if ($deity) $this->deity = TRUE;
      else $this->deity = FALSE;

      $this->last_on      = $last_on;

      $this->loadModSettings('user');
      $this->setPermissions();

      if ($groups) $this->groups = $this->listGroups();

      $this->groupPermissions = $this->getMemberRights();
      $this->groupModSettings = $this->loadUserGroupVars();
      if ($log) PHPWS_User::updateLogged($id);
      return TRUE;
    } else 
      return FALSE;
  }

  function name() { return $this->realname? $this->realname : $this->username; }

  function addGroup($gname){
	  $g = $GLOBALS['core']->sqlSelect('mod_user_groups', 'group_name', $gname);
	  if (!$g) return;
	  $id = $g[0]['group_id'];
	  if (in_array($gname, $this->groups)) return;
	  $this->groups[$id] = $gname;
	  $this->save();
  }

  function dropGroup($gname, $module_title = NULL){
	  $g = $GLOBALS['core']->sqlSelect('mod_user_groups', 'group_name', $gname);
	  if (!$g) return;
	  $id = $g[0]['group_id'];
	  if (!in_array($gname, $this->groups)) return;
	  $this->groups[$id] = NULL;
	  $this->save();
  }

  function updateLogged($id){
    $sql = 'UPDATE mod_users set log_sess = log_sess + 1, last_on = ' . mktime() . ' where user_id=' . $id;
    $GLOBALS['core']->query($sql, TRUE);
  }


  /**
   * Fetches a user id by searching by the username
   *
   * @author                   Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param   string  username Username to search by
   * @returns integer          Returns the id if found, zero otherwise
   */
  function getUserId($username=NULL){
    if (isset($username) && $user = $GLOBALS['core']->sqlSelect('mod_users', 'username', $username))
      return $user[0]['user_id'];
    elseif (isset($this))
      return $this->user_id;
    else return FALSE;
  }

  /**
   * Sets the user's username
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param string username Username to apply to user
   */
  function setUsername($username, $checkDuplicate=TRUE){
    if (!$this->allowUsername($username, $checkDuplicate))
      return FALSE;

    $this->username = $username;
    return TRUE;
  }


  /**
   * Returns the username of an user
   *
   * @author             Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param   integer id If set, function will pull username from the database
   * @returns string     Returns username of user
   */
  function getUsername($id=NULL){
    if ($id){
      $user = new PHPWS_User($id);
      return $user->username;
    } else
      return $this->username;
  }

  /**
   * Sets the user's password
   *
   * If the password does not fit certain standards, it will be rejected
   *
   * @author                   Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param   string  password String to be hashed as password
   * @returns boolean          TRUE is the password can be used, FALSE otherwise
   */
  function setPassword($password){
    $passcheck = $this->checkPassword($password, $password);

    if (is_string($passcheck)){
      $this->error[] = $passcheck;
      return FALSE;
    }

    $this->password = md5($password);
    return TRUE;
  }

  /**
   * Returns the user's hashed password
   *
   * @author         Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @returns string User's hashed password
   */
  function getPassword(){
    return $this->password;
  }


  /**
   * Sets a user's email address
   *
   * Will fail if the address is formatted incorrectly. Will also
   * fail if checkDuplicate is TRUE and there is another user with the
   * same email address in the system.
   *
   * @author                          Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param   string   email          Email address for the user
   * @param   boolean  checkDuplicate If TRUE, function will see if the email is used by another user.
   * @returns boolean                 TRUE is successful, FALSE otherwise.
   */
  function setEmail($email, $checkDuplicate=TRUE){
    if (!$this->allowEmail($email, $checkDuplicate))
      return FALSE;

    $this->email = $email;
    return TRUE;
  }


  /**
   * Returns a user's email address
   * 
   * The id can be passed if you don't wish to create the object
   * 
   * @author              Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param   integer id  Gets email address of the user specified by the id
   * @returns string      Email address of user
   */
  function getEmail($id=NULL){
    if ($id){
      $user = new PHPWS_User($id);
      return $user->email;
    } else
      return $this->email;
  }

 /**
   * Fetches a emailaddress by searching by the username
   *
   * @author                   Jon Bullen <Jon_Bullen@sytone.delspamdel.co.uk>
   * @param   string  username Username to search by
   * @returns string           Returns the email address if found, false otherwise
   */

  function getEmailAddress($username=NULL){
    if (isset($username) && $user = $GLOBALS['core']->sqlSelect('mod_users', 'username', $username))
      return $user[0]['email'];
    elseif (isset($this))
      return $this->email;
    else return FALSE;
  }


  /**
   * Fetches a user id by searching by the email
   *
   * @author                   Jon Bullen <Jon_Bullen@sytone.delspamdel.co.uk>
   * @param   string  email    Email Address to search by
   * @returns integer          Returns the id if found, zero otherwise
   */
  function getUserIdByEmail($email=NULL){
    if (isset($email) && $user = $GLOBALS['core']->sqlSelect('mod_users', 'email', $email))
      return $user[0]['user_id'];
    elseif (isset($this))
      return $this->user_id;
    else return FALSE;
  }



  /**
   * Sets the admin status of a user
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param boolean admin Admin status to set to user
   */
  function setAdmin($admin){
    if ($admin) $this->admin_switch = TRUE;
    else $this->admin_switch = FALSE;
  }

  /**
   * Returns whether an user is an admin or not
   *
   * @author              Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param   integer  id Id number of user (not need if used in object)
   * @returns boolean     Returns TRUE if user is an admin, FALSE otherwise.
   */
  function isAdmin($id=NULL){
    if ($id){
      $user = new PHPWS_User($id);
      if ($user->admin_switch)	return TRUE;
      else return FALSE;
    } else {
      if ($this->admin_switch)	return TRUE;
      else return FALSE;
    }
  }

  /**
   * Sets the deity status of a user
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param boolean admin Admin status to set to user
   */
  function setDeity($deity){
    if ($deity) $this->deity = TRUE;
    else $this->deity = FALSE;
  }

  /**
   * Returns whether an user is an deity or not
   *
   * @author              Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param   integer  id Id number of user (not need if used in object)
   * @returns boolean     Returns TRUE if user is an admin, FALSE otherwise.
   */
  function isDeity($id=NULL){
    if ($id){
      $user = new PHPWS_User($id);
      if ($user->deity)	return TRUE;
      else return FALSE;
    } else {
      if ($this->deity)	return TRUE;
      else return FALSE;
    }
  }

  function isService($id=NULL){
    if ($id){
      $user = new PHPWS_User($id);
      if ($user->username == "service")	return TRUE;
      else return FALSE;
    } else {
      if ($this->username == "service")	return TRUE;
      else return FALSE;
    }
  }



  /**
   * Checks the validity of an email address
   *
   * @author                         Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param   string  email          Email address to test
   * @param   boolean checkDuplicate If TRUE, function returns FALSE if there is a duplicate
   *                                 email address in the system.
   * @returns boolean
   */
  function allowEmail($email, $checkDuplicate=TRUE){
    if (!PHPWS_Text::isValidInput($email, 'email')){
      $this->error[] = "邮箱格式不正确: <b>$email</b>";
      return FALSE;
    }

    if($checkDuplicate && $GLOBALS['core']->sqlSelect('mod_users', 'email', $email)){
      $this->error[] = "邮箱地址与他人重复: <b>$email</b>";
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Checks the validity of a username address
   *
   * @author                         Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param   string  email          username to test
   * @param   boolean checkDuplicate If TRUE, function returns FALSE if there is a duplicate
   *                                 username in the system.
   * @returns boolean
   */
  function allowUsername($username, $checkDuplicate=TRUE){
    if (!PHPWS_Text::isValidInput($username, 'cn-gb')){
//echo 'Improperly formatted username';
      $this->error[] = "用户名格式不正确: <b>$username</b>";
      return FALSE;
    }

    if($checkDuplicate && PHPWS_User::getUserId($username)){
//echo 'Username already in use';
      $this->error[] = "用户名与他人重复: <b>$username</b>";
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Returns a non terminating error message
   *
   * @author          Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @returns string  Error message from last executed command
   */
  function getError(){
    if (empty($this->error) || !is_array($this->error))
      return FALSE;
    $msg = implode('<br />', $this->error);
    /* Clear error message */
    $this->error = null;
    return $msg;
  }


  /**
   * Creates or updates a user
   *
   * Make sure you have the various user variables set before calling this
   * function
   *
   * @author          Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @returns boolean TRUE if successful, FALSE otherwise
   */
  function save(){
    if (!isset($this->username) || !isset($this->password)){
      $this->error[] = $_SESSION['translate']->it('Username and password must be set before user can be saved') . '.';
      return FALSE;
    }

    $sql['username'] = $this->username;
    $sql['password'] = $this->password;
    $sql['city'] = $this->city;
    $sql['state'] = $this->state;
    $sql['address'] = $this->address;
    $sql['zip'] = $this->zip;
    $sql['head'] = $this->head;
    $sql['email'] = $this->email;
    $sql['org'] = $this->org;
    $sql['rootOrg'] = $this->rootOrg;
    $sql['phone'] = $this->phone;
    $sql['realname'] = $this->realname;
    $sql['appuid'] = $this->appuid;

    if ($this->admin_switch)
      $sql['admin_switch'] = 1;
    else
      $sql['admin_switch'] = 0;

    if ($this->deity)
      $sql['deity'] = 1;
    else
      $sql['deity'] = 0;

    if (isset($this->groups)){
      $groups = $this->groups;
      PHPWS_Array::dropNulls($groups);
      $sql['groups'] = implode(':', array_keys($groups));
    }

    if ($this->user_id < 1)
      $this->user_id = $GLOBALS['core']->sqlInsert($sql, 'mod_users', FALSE, TRUE);
    else
      $GLOBALS['core']->sqlUpdate($sql, 'mod_users', 'user_id', $this->user_id);

    $this->updateUserGroups();
    return TRUE;
  }


  function listUserErrors(){
    $content = NULL;
    if (isset($GLOBALS['userError'])){
      foreach ($GLOBALS['userError'] as $error)
	$content .= "<span class=\"errortext\">$error</span><br />\n";
    }
    
    unset($GLOBALS['userError']);

    return $content;
  }

  function setUserPermissions(){
    $permissions = $this->permissions;
    foreach ($permissions as $mod_title=>$rights){
      if (is_null($rights))	{
		  $this->dropUserVar($mod_title);
	  }
      else {
		  if (is_array($rights)) {
			  $this->setUserVar($mod_title, implode(':', $rights));
		  }
		  else {
			  $this->setUserVar($mod_title, 1);
		  }
      }
    }
  }

  function addUser($username, $password){
    if (!PHPWS_User::allowUsername($username))
      return FALSE;

    $user = new PHPWS_User();
    $user->username = $username;
    $user->password = md5($password);
    $user->writeUser();

	return TRUE;
  }

  function updateUser($username){

  }

  function userAction($mode){
    extract($_POST);
    $this->setFormPermissions();

    if (empty($edit_username))
      $error[] = $_SESSION['translate']->it('Missing username') . '.';
    elseif (!PHPWS_Text::isValidInput($edit_username))
      $error[] = $_SESSION['translate']->it('Username may not contain spaces or non-alphanumeric characters') . '.';

    if($edit_username != $this->username) {
      if($GLOBALS['core']->sqlSelect('mod_users', 'username', $edit_username))
       $error[] = $_SESSION['translate']->it('Username already in use') . '.';
    } 
  
    if(!isset($error))
      $this->username = $edit_username;
    

    if ($pass1 || $pass2){
      if ($passError = $this->checkPassword($pass1, $pass2))
	$error[] = $passError;
      else
	$this->password = md5($pass1);
    } elseif (!$this->password)
	$error[] = $_SESSION['translate']->it('Missing password');

    if ($email){
      if (!PHPWS_Text::isValidInput($email, 'email'))
	$error[] = $_SESSION['translate']->it('Malformed email address') . '.';
      elseif ($mode == 'add' && !$this->check_email($email))
	$error[] = $_SESSION['translate']->it('Email address already in use') . '.';
    }
    $this->email = $email;
    
    if (isset($admin_switch))
      $this->admin_switch = $admin_switch;
    else
      $this->admin_switch = 0;

    if (isset($deity) && $_SESSION['OBJ_user']->isDeity())
      $this->deity = 1;
    elseif (isset($deity))
      $this->deity = 0;
    
    if (isset($dropGroup)){
      if ($currentGroups)
	foreach ($currentGroups as $dropId)
	  $this->groups[$dropId] = NULL;

      return NULL;
    } elseif (isset($addGroup)) {
      if ($availableGroups)
	foreach ($availableGroups as $addId)
	  $this->groups[$addId] = PHPWS_USER_GROUPS::getGroupName($addId);
      return NULL;
    }

    if (isset($error)){
      $GLOBALS['userError'] = $error;

      return FALSE;
    } else {

      if ($mode == 'add'){
	$this->writeUser();
	$this->setUserPermissions();
	$this->updateUserGroups();
	return TRUE;
      } elseif ($mode == 'edit'){
	$groups = $this->groups;
	PHPWS_Array::dropNulls($groups);
	
	if ($groups)
	  $writeGroups = implode(':', array_keys($groups));
	else
	  $writeGroups = NULL;

	if ($_SESSION['OBJ_user']->isDeity() && $this->deity)
	  $this->deity = 1;
	else
	  $this->deity = 0;

	$sql_array = array ('username'    => $this->username,
			    'password'    => $this->password,
			    'email'       => $this->email,
			    'admin_switch'=> $this->admin_switch,
			    'groups'      => $writeGroups,
			    'deity'       => $this->deity);
	$GLOBALS['core']->sqlUpdate($sql_array, 'mod_users', 'user_id', $this->user_id);
	$this->updateUserGroups();
	$this->setUserPermissions();
	return TRUE;
      } else
	exit('Error userAction: Incorrect mode requested.');
    }
  }


  function writeUser(){
    $sql['username'] = $this->username;
    $sql['password'] = $this->password;

    if (isset($this->email))
      $sql['email'] = $this->email;

    if ($this->realname)
      $sql['realname'] = $this->realname;

	if (isset($this->admin_switch))
      $sql['admin_switch'] = $this->admin_switch;
    else
      $sql['admin_switch'] = 0;

    if (isset($this->deity))
      $sql['deity'] = $this->deity;
    else
      $sql['deity'] = 0;

    if (isset($this->groups)){
      $groups = $this->groups;
      PHPWS_Array::dropNulls($groups);
	$sql['groups'] = implode(':', array_keys($groups));
    }

    $user_id = $GLOBALS['core']->sqlInsert($sql, 'mod_users', 1, TRUE);
    if ($user_id){
      $this->user_id = $user_id;
      $this->updateUserGroups();
      return TRUE;
    } else 
      return FALSE;
  }

  function setAdminSwitch($user_id, $state){
    if ($state != 1 && $state != 0)
      return;
    return $GLOBALS['core']->sqlUpdate(array('admin_switch'=>$state), 'mod_users', 'user_id', $user_id);
  }

  function accepted_new_submit($data_array){
    extract($data_array);
    if (!($this->send_invitation($username, $email)))
      $GLOBALS['CNT_user']['content'] .= $_SESSION['translate']->it('Sorry but there is a problem with email');
  }


  function listUserGroups($user_id=NULL){
    if ($user_id)
      $user = new PHPWS_User((int)$user_id);
    else
      $user = $this;

    return $user->groups;
  }

  function userInGroup($group_id, $user_id=NULL){
    if ($user_id)
      $user = new PHPWS_User((int)$user_id);
    else
      $user = $this;

    return isset($user->groups[(int)$group_id]) || in_array($group_id, $user->groups);
    
  }

  function userInGroupName($group_name, $user_id=NULL){
    if ($user_id)
      $user = new PHPWS_User((int)$user_id);
    else
      $user = $this;

	$g = $GLOBALS['core']->sqlSelect("mod_user_groups", 'group_name', $group_name);
	if (!$g) return false;
	$group_id = $g[0]['group_id'];

    return isset($user->groups[(int)$group_id]) || in_array($group_id, $user->groups);    
  }

  function checkUserPermission($mod_title, $subright=NULL, $checkGroup=FALSE){
    // Check to see if the user has 'rights'
    // Will be an array or integer
    if(isset($this->permissions['MOD_'.$mod_title])) $rights = $this->permissions['MOD_'.$mod_title];
    else $rights = NULL;

    // Preempt to save some computation 
    if ($rights == 1 && !isset($subright)) return TRUE;

    // Check to see if the group has the permission
    if ((bool)$checkGroup == TRUE && isset($this->groupPermissions['MOD_' . $mod_title])){
		if (isset($rights) && is_array($rights) && is_array($this->groupPermissions['MOD_' . $mod_title])) {
			// If the rights are an array, groups merges with them
			$rights = array_merge($rights, $this->groupPermissions['MOD_' . $mod_title]);
      } else {
			// If rights are not an array, group permissions over write
			$rights = $this->groupPermissions['MOD_'.$mod_title];
      }
    }

    if (!isset($subright)) {
		if (isset($rights)) return TRUE;
    }
	elseif (isset($rights) && is_array($rights)) return in_array($subright, $rights);
    
	return FALSE;
  }

  function checkPassword($pass1, $pass2='blank'){
    if(preg_match('/[^a-zA-Z0-9!@_#$%^&*+=<>]/', $pass1))
      $error=  '密码只能用字母数字和!@_#$%^&*+=<>';
    elseif ($pass2 != 'blank' && $pass1 != $pass2)
      $error = '两次输入的密码不一致';
    elseif(strlen($pass1) < 5)
      $error = '密码不能少于5个字符';
    elseif(preg_match('/(pass|password|phpwebsite|blank|qwerty|passwd|admin|fallout)/i', $pass1))
      $error = '密码抬简单';
    else
      $error = NULL;

    return $error;
  }


  function getSettings(){    
    list(,$settings) = each($GLOBALS['core']->sqlSelect('mod_user_settings'));
    return $settings;
  }


  function viewUser($id){
    $user = new PHPWS_User($id);

    $template['USERNAME_LABEL'] = $_SESSION['translate']->it('Username');
    $template['USER_ID_LABEL']  = $_SESSION['translate']->it('User ID');
    $template['EMAIL_LABEL']    = $_SESSION['translate']->it('Email');
	
    $template['USERNAME']       = $user->username;
    $template['USER_ID']        = $user->user_id;
    $template['EMAIL']          = $user->email;

    return PHPWS_Template::processTemplate($template, 'users', 'viewuser.tpl');
  }

  function submit_new_user($username, $email, $phone = NULL){
    extract($this->getSettings());

    $GLOBALS['CNT_user']['title'] = $_SESSION['translate']->it('Thank you for applying for an account').'.';
    if ($user_signup=='hold'){
      $this->RSVP($username, $email);
	$GLOBALS['CNT_user']['content'] = $_SESSION['translate']->it('Your request is being reviewed').'.';
    } elseif ($user_signup == 'send') {
      if ($this->send_invitation($username, $email)){
	$GLOBALS['CNT_user']['content'] = $_SESSION['translate']->it('You should shortly receive an email with account verification information').'.';
      } else {
	$GLOBALS['CNT_user']['title'] = $_SESSION['translate']->it('A Problem Occurred');
	$GLOBALS['CNT_user']['content'] = $_SESSION['translate']->it('Sorry but there is a problem with our email').'. '.$_SESSION['translate']->it('Please try again later').'.';
      }
    /* Save user information & automatically log them in */
    } elseif ($user_signup == 'login') {
//echo "$user_signup == 'login'<br>";
//      $this->send_invitation($username, $email, $_POST['signup_password1']);
//    $GLOBALS['CNT_user']['content'] = $_SESSION['translate']->it('You should shortly receive an email with account activation information').'.';
//      $this->validate_login($username, $_POST['signup_password1']);
      $sql['username'] = $username;
      $sql['password'] = md5($_POST['signup_password1']);
      $sql['email'] = $email;
      $sql['phone'] = $phone;
      $sql['groups'] = '';

      $GLOBALS['core']->sqlInsert($sql, 'mod_users', FALSE, TRUE);

      $this->validate_login($username, $_POST['signup_password1'], NULL);
    }
  }


  function view_user($user_id){
    $temp_user = new CLS_user;
    $temp_user->loadUser($user_id, false);

    $table[] = array ('<b>'.$_SESSION['translate']->it('Username').'</b>', $temp_user->username);
    $table[] = array ('<b>'.$_SESSION['translate']->it('Email').'</b>', PHPWS_Text::link('mailto:'.$temp_user->email, $temp_user->email, 'index'));
    $temp_user->admin_switch ? $answer = $_SESSION['translate']->it('Yes') : $answer = $_SESSION['translate']->it('No');
    $table[] = array ('<b>'.$_SESSION['translate']->it('Admin').'?</b>', $answer);
    $content = PHPWS_Text::ezTable($table, 4, 4, 0, '100%');

    return $content;
  }

  function RSVP($username, $email){
    $information = '<b>'.$_SESSION['translate']->it('Username').":</b> $username<br /><b>".$_SESSION['translate']->it('Email').":</b> $email";
    $newUser = new PHPWS_User;
    $newUser->username = $username;
    $newUser->password = md5($newUser->createPassword());
    $newUser->email = $email;
    $newUser->writeUser();
    PHPWS_Approval::add($newUser->user_id, $information);
  }

  function createPassword(){
    $password = NULL;
    $upper = PHPWS_User::alphabet();
    $lower = PHPWS_User::alphabet('lower');
    $alphabet = array_merge($upper, $lower);
    
    for ($i=0;$i < 11; $i++){
      $key = rand(0,51);
      $password .= $alphabet[$key];
    }

    return $password;
  }

  function welcomeUser($user_id){
    $user = new PHPWS_User($user_id);

    extract(PHPWS_User::getSettings());

    if (!$user_contact){
      echo $_SESSION['translate']->it('WARNING').'! :'. $_SESSION['translate']->it('A contact email address has not been setup for this website').'.';
      return FALSE;
    }

    $password = PHPWS_User::createPassword();
    $update['password'] = md5($password);
	
    $message = "Hello " . $username . ",\n\n\n" . $greeting . " You have successfully signed up with your account. You may now use our services.";

    if(PHPWS_User::mailInvitation($user->email, $message)) {
	  $update['activatecode'] = 0;
      return $GLOBALS['core']->sqlUpdate($update, 'mod_users', 'user_id', $user->user_id);
	}
    else
      return FALSE;
  }

	/**
	* Saves new user information & sends a welcome email
	*
	* @module User
	* @param string username
	* @param string email
	* @param string password
	* @return bool Success or Faliure
	*/
  function send_invitation($username, $email, $password=null){
    if (!PHPWS_Text::isValidInput($username) || !PHPWS_Text::isValidInput($email, 'email'))
      exit("send_invitation error: <b>Username: '$username'</b> and/or <b>Email: '$email'</b> are malformed.<br />"); 

    extract($this->getSettings());
    if (!$user_contact){
      echo $_SESSION['translate']->it('WARNING').'! :'. $_SESSION['translate']->it('A contact email address has not been setup for this website').'.';
      return false;
    }
    if ($password===null)
      $password = $this->createPassword();

	$activate = explode('/', $_SERVER["PHP_SELF"]);
	do { $code = rand(); } while($code == 0);
    $message = "Hello " . $username . ",<br><br>" . $greeting . " To activate your account, click <a href=http://" . $GLOBALS["HTTP_SERVER_VARS"]["SERVER_NAME"];
	if (strpos($GLOBALS["HTTP_SERVER_VARS"]["SERVER_NAME"], 'dataserver') != 0) {
		$message .= "/phpwebsite";
	}
	$message .=  "/index.php?module=users&norm_user_op=activate&user_name=" . $username . "&code=" . $code . ">here</a>.<br><br>IMPORTANT NOTE:  If the URL does not work, please copy and paste the whole URL (" . "http://" . $GLOBALS["HTTP_SERVER_VARS"]["SERVER_NAME"];
	if (strpos($GLOBALS["HTTP_SERVER_VARS"]["SERVER_NAME"], 'dataserver') != 0) {
		$message .= "/phpwebsite";
	}
	$message .=  "/index.php?module=users&norm_user_op=activate&user_name=" . $username . "&code=" . $code	
	. ") into the location or address bar of your Web browser and press enter.<br><br>Please do not reply to this message.  Replies will not be processed.";
    if(PHPWS_User::mailInvitation($email, $message)){
//print_r($GLOBALS);echo "<hr>";
//print_r($_SESSION);
	  $this->mailNotice("User Signup", $username . " has signed up. Email is: " . $email);
      $insert = array ('username'=>$username, 'password'=>md5($password), 'email'=>$email, 'activatecode'=>$code);
      return $GLOBALS['core']->sqlInsert($insert, 'mod_users', 1);
    } else {
		echo 'error in sending mail.<br>';
      return FALSE;
	}
  }

  function mailNotice($subj, $message){
	$mail = new phpmailer();

	$mail->From     = "admin@mxkjwx.discoverlaws.com";
	$mail->FromName = "明轩科技";
	$mail->Host     = "mail.mxkjwx.discoverlaws.com";
	$mail->Mailer   = "smtp";

	$mail->Body    = $message;
	$mail->IsHTML(false);
	$mail->AddAddress("admin@mxkjwx.discoverlaws.com", NULL);
	$mail->Subject = $subj;

	return $mail->Send();
  }

  function mailInvitation($email, $message){
    extract(PHPWS_User::getSettings());

	$mail = new phpmailer();

	$mail->From     = "admin@mxkjwx.discoverlaws.com";
	$mail->FromName = "明轩科技";
	$mail->Host     = "mail.mxkjwx.discoverlaws.com";
	$mail->Mailer   = "smtp";

	$mail->Body    = $message;
	$mail->IsHTML(true);
	$mail->AddAddress($email, NULL);
	$mail->Subject = $nu_subj;

	return $mail->Send();
  }

  function check_email($address){
    if (PHPWS_Text::isValidInput($address, 'email')){
      if ($GLOBALS['core']->sqlSelect('mod_users', 'email', $address))
	return FALSE;
      else
	return TRUE;
    } else
      return FALSE;
  }

  function check_company($name, $taxid){
	  if (!$name || !$taxid) return false;

	  return TRUE;
  }

  function activate($user_name, $code){
    $user = $GLOBALS['core']->getRow("select user_id, activatecode, email from {$GLOBALS['core']->tbl_prefix}mod_users where username='$user_name'");

	if ($user['activatecode'] == 0) {
		$CNT_user["content"] .= $_SESSION["translate"]->it("Your account has already been activated. You may log in to start using our services") . ".";
	    return $CNT_user;
	}

	if (!empty($user) && $user['activatecode'] == $code) {
	  $this->email = $user['email'];
	  $CNT_user["content"] = $this->changeProfile(true, true);
	  $CNT_user["title"] = $_SESSION["translate"]->it("Account Activation");
	  $this->pre_id = $user['user_id'];
	}
	else {
	    $CNT_user["title"] = $_SESSION["translate"]->it("Account Activation Failed");
	    $CNT_user["content"] = $_SESSION["translate"]->it("Account name and activation code do not match.");
		if (!empty($user) && $user['activatecode'] != 0) {
	      PHPWS_User::dropUser($user['user_id']);
		  PHPWS_User::removeUserFromGroups($user['user_id']);
	      $GLOBALS['core']->sqlDelete('mod_users', 'user_id', $user['user_id']);
	      $CNT_user["content"] .= $_SESSION["translate"]->it("This account has been deleted for security reason. If you are the person signing up the account, please sign up again") . ".";
		}
	}
    return $CNT_user;
  }

  function deify($user_id){
    if (list($row) = $GLOBALS['core']->sqlSelect ('mod_users', 'user_id', $user_id))
      extract ($row);

    $GLOBALS['CNT_user']['title'] = $_SESSION['translate']->it('Make [var1] a Deity', $username);
    if ($deity){
      $GLOBALS['CNT_user']['content'] .= '<br />'.$_SESSION['translate']->it('This user is currently a deity').". ".$_SESSION["translate"]->it("Do you wish to remove their status")."?<br /><br />
<a href=\"index.php?module=users&amp;user_op=user_deify&amp;deification=cast_down&amp;user_id=$user_id\">".$_SESSION["translate"]->it("Yes, make them mortal")."</a> &nbsp;&nbsp; <a href=\"index.php?module=users&amp;user_op=user_deify&amp;deification=bestow&amp;user_id=$user_id\">".$_SESSION["translate"]->it("No, let them remain a deity")."</a>";
    } else {
      $GLOBALS["CNT_user"]["content"] .= "<br />".$_SESSION["translate"]->it("This is a mortal").". ".$_SESSION["translate"]->it("Shall we deify them")."?<br /><br />
<a href=\"index.php?module=users&amp;user_op=user_deify&amp;deification=bestow&amp;user_id=$user_id\">".$_SESSION["translate"]->it("Yes, make them a deity")."</a> &nbsp;&nbsp; <a href=\"index.php?module=users&amp;user_op=user_deify&amp;deification=cast_down&amp;user_id=$user_id\">".$_SESSION["translate"]->it("No, do leave them mortal")."</a>";
    }
  }
  
  function switch_admin(){
    if ($_REQUEST['admin'] && $_REQUEST['user_id']){
      if ($_REQUEST['admin'] == 'off')
	$sql = array('admin_switch'=>0);
      elseif ($_REQUEST['admin']=='on')
	$sql = array('admin_switch'=>1);
      
      $GLOBALS['core']->sqlUpdate($sql, 'mod_users', 'user_id', $_REQUEST['user_id']);
    }
  }


  function force_to_user(){
    header('location:index.php?module=users&user_op=admin');
    exit();
  }

  function getModIcon($icon_name, $mod_directory, $mod_pname){
    $mod_address = "mod/$mod_directory/img/$icon_name";

    if (!$icon_name)
      return NULL;
    return PHPWS_Text::imageTag(PHPWS_SOURCE_HTTP . $mod_address);
  }


  function forgotPassword($username){
    $settings = $this->getSettings();
    extract($settings);

    if(!$user_id = $this->getUserId($username))
      return FALSE;

    $user = new PHPWS_User;
    $user->loadUser($user_id, false);

    if (!isset($user->email))
      return FALSE;

    $subject = $username . "的密码重置请求";

    $password = md5($this->createPassword());
 
    $link = 'http://';
/*
    if (isset($_SERVER['HTTPS'])) {
	$link = 'https://';
    }
*/

    $link .= $GLOBALS['core']->home_http . 'index.php?module=users&id='.$user->user_id."&hash=$password&norm_user_op=forgotPasswordForm";
//    $message = "A request has been made to change your password.\nClick on the link below to change your password or copy and paste it into your browser.\n\nPassword change address:\n$link";
    $message = "你请求重置密码。<br>请点击下面的链接，或将下面网址复制粘贴到你浏览器的地址栏里：<a href=$link>$link</a>";

    if (!empty($user_contact)){
		$mail = new phpmailer();

		$mail->From     = "admin@lawclerksonline.com";
		$mail->FromName = "明轩科技";
		$mail->Host     = "mail.lawclerksonline.com";
		$mail->Mailer   = "smtp";

		$mail->Body    = $message;
		$mail->IsHTML(true);
		$mail->AddAddress($user->email, NULL);
		$mail->Subject = $subject;

		$user->setUserVar('forgotHash', $password);
		$user->setUserVar('forgotDateTime', mktime());

		return $mail->Send();
	  }

/* / SAE
		$mail = new SaeMail();
		$mail->quickSend(
			$user->email ,
			$subject ,
			$message , 
			"admin@discoverlaws.com" ,
			"qazplm",
			"mail.discoverlaws.com" ,
			25
		); // 指定smtp和端口
		$user->setUserVar('forgotHash', $password);
		$user->setUserVar('forgotDateTime', mktime());
		return TRUE;
    }
*/
    return FALSE;
  }

  function update_personal(){
    extract($_POST);
    if (!empty($pass1)){
      if ($errorMessage = $this->checkPassword($pass1, $pass2)){
	$error = new PHPWS_Error('users', 'update_personal', $errorMessage);
	$error->errorMessage('CNT_user');
	return FALSE;
      }else
	$personal_upd['password'] = md5($pass1);
    }

    if (!PHPWS_Text::isValidInput($usr_email, 'email')){
	$error = new PHPWS_Error('users', 'update_personal', '<span class=\'errortext\'>'.$error.'</span><br />');
	$error->errorMessage('CNT_user');

      $GLOBALS['CNT_user']['content'] .= '<span class=\'errortext\'>'.$_SESSION['translate']->it('Malformed email address').'.</span><br />';
      return FALSE;
    } else
      $personal_upd['email'] = $usr_email;

    if (isset($loginToList))
      $this->setUserVar('loginToList', 1);
    else
      $this->setUserVar('loginToList', 0);

    return $GLOBALS['core']->sqlUpdate($personal_upd, 'mod_users', 'user_id', $this->user_id);
    
  }

  function routeBack(){
    if ($_SESSION['OBJ_user']->jumpURL)
		$location = $_SESSION['OBJ_user']->jumpURL;
    elseif ($_SESSION['OBJ_user']->getUserVar('loginToList') == 1 && !strstr($_SERVER['HTTP_REFERER'], 'module='))
		$location = 'index.php?module=controlpanel';
    else {
		$location = './' . preg_replace('/.*(index\.php.*|)$/Ui', '\\1', $_SERVER['HTTP_REFERER']);
		if (empty($location)) $location = '.';
    }

    header('Location:' . $location );
    exit();

	if (strstr($location, 'module=users')) echo "<script> window.top.location = 'http://" . PHPWS_SOURCE_HTTP . "/'; </script>";
	else echo "<script> window.top.location = '$location'; </script>";
//	header('Location:' . $location );
//      echo 'Location: http://" . PHPWS_SOURCE_HTTP . "/';
    exit();
	echo "<script> top.open('$location', '_self'); </script>";
    exit();
      header('Location: http://" . PHPWS_SOURCE_HTTP . "/');
    exit();

    header('Location:' . $location );
    exit();
  }

  function routeLogin($username = null){
    $settings = $this->getSettings();
    $username = preg_replace("/[\n\r]/", 'NONL', $username);

    if ($settings['user_signup'] != 'none')
//		echo "<script> window.top.location.href = './{$GLOBALS['SCRIPT']}?module=users&norm_user_op=signup&block_username=$username'; </script>";
      header("location:{$GLOBALS['SCRIPT']}?module=users&norm_user_op=signup&block_username=$username");
    else 
//		echo "<script> window.top.location.href = './'; </script>";
      header('location: ./');
    exit();
  }

  function validate_login($username, $password, $rememberme=NULL){
    $settings = $this->getSettings();
    $username = preg_replace('/\W/', '_', $username);
    $hash_pass = md5($password);

    $user = $GLOBALS['core']->getRow("select user_id, password, activatecode from {$GLOBALS['core']->tbl_prefix}mod_users where username='$username'");


	if (!empty($user) && $user['password'] == $hash_pass && $user['activatecode'] != 0) {
	  $_SESSION['OBJ_user']->error[] = '帐户尚未激活，请根据我们发送给你的邮件中的说明激活帐户。';
      PHPWS_User::routeLogin($username);
	  return;
	}

    if ((empty($user) || $user['password'] != $hash_pass) && $settings['user_authentication'] == 'external'){
      $ext_file = PHPWS_SOURCE_DIR . 'mod/users/' . $settings['external_auth_file'];
      
      if (!is_file($ext_file))	exit("Missing external authorization file: $ext_file");
      
      include $ext_file;
      
      $authorized = authorize($username, $password);
      
      if (!is_bool($authorized)) exit('Unexpected result returned from external authorization script.');
      
      if ($authorized){
	if (isset($user)){
	  $this->loadUser($user['user_id'], false);
	  $this->password = $hash_pass;
	  $this->save();
	} else {
	  $this->username = $username;
	  $this->password = $hash_pass;
	  if (function_exists('processUser'))
	    processUser($this);

	  $this->save();
	  PHPWS_User::updateLogged($this->user_id);
	}

	PHPWS_User::routeBack();
      } else {
	     $_SESSION['OBJ_user']->error[] = '登录错误，用户名密码不匹配。';
		PHPWS_User::routeLogin($username);
      }
    } elseif ($user['password'] == $hash_pass) {
	  if(isset($rememberme) && $settings['show_remember_me']) {
		$cookie_value = md5(PHPWS_User::createPassword());
		PHPWS_User_Cookie::cookie_write('mod_users', 'rememberme', $cookie_value);
		$GLOBALS['core']->sqlUpdate(array('cookie'=>$cookie_value), 'mod_users', 'user_id', $user['user_id']);
      }

      $this->loadUser($user['user_id'], 1);

//	  $_SESSION["OBJ_user"] = $this;
//	  exit();

      PHPWS_User::routeBack();
    } else {
      $_SESSION['OBJ_user']->error[] = '登录错误，用户名密码不匹配。';
      PHPWS_User::routeLogin($username);
    }
  }

  function update_user(){
    $sql_array = array ('username'=>$this->username, 'password'=>$this->password, 'email'=>$this->email, 'admin_switch'=>$this->admin_switch);
    $GLOBALS['core']->sqlUpdate($sql_array, 'mod_users', 'user_id', $this->user_id);

  }
  
  function deleteUser($user_id, $confirm=NULL){
    if ($user_id)
      $user = new PHPWS_User($user_id);
    else
      return;

    if (is_null($confirm)){
      if ($user->username == $_SESSION['OBJ_user']->username)
	$GLOBALS['CNT_user']['content'] = $_SESSION['translate']->it('You sure you want to delete your OWN account').'?!<br />'.$_SESSION['translate']->it('You won\'t be able to log in afterwards').'.&nbsp;&nbsp;';
      else
	$GLOBALS['CNT_user']['content'] = $_SESSION['translate']->it('Are you sure you want to delete this user').'?&nbsp;&nbsp;';
      
      $GLOBALS['CNT_user']['content'] .= PHPWS_Text::moduleLink($_SESSION['translate']->it('Yes'), 'users', array('user_op'=>'deleteUser', 'confirm'=>'yes', 'user_id'=>$user->user_id))
	 . '&nbsp;&nbsp;'. PHPWS_Text::moduleLink($_SESSION['translate']->it('No'), 'users', array('user_op'=>'panelCommand', 'usrCommand[user]'=>'edit')). '<br /><br />';
      $GLOBALS['CNT_user']['content'] .= '<b>'.$_SESSION['translate']->it('Username').":</b> $user->username<br />";
      return;
    } elseif ($confirm == 'yes'){
      PHPWS_User::dropUser($user_id);
      PHPWS_User::removeUserFromGroups($user_id);
      $GLOBALS['core']->sqlDelete('mod_users', 'user_id', $user->user_id);
      $GLOBALS['CNT_user']['content'] .= $_SESSION['translate']->it('User deleted');
    }

	//added by sean 2006/04/16 begin
	//回收 /phpwebsite/mxupload/{username} 下的用户子目录{username}
	$store_dir = PHPWS_SOURCE_DIR."/mxupload/".$user->username."/";// 上传文件的储存位置  //要变的
	$this->removeDir($store_dir);
	//end-

    $this->manageUsers();
  }

  function allow_access($mod_title, $subright=NULL){
    if ($this->isDeity())
      return TRUE;

//    if ($this->admin_switch)
      if ($this->checkUserPermission($mod_title, $subright, TRUE))
	return TRUE;

    return FALSE;
  }

  function userMenu(){
    if ($this->isDeity())
	    $template['MODULES'] = PHPWS_Text::moduleLink($_SESSION['translate']->it('Control Panel'), 'controlpanel');
    $template['LOGOUT'] = PHPWS_Text::moduleLink($_SESSION['translate']->it('Log Out'), 'users', array('norm_user_op'=>'logout'));
    $template['HOME'] = PHPWS_Text::moduleLink($_SESSION['translate']->it('Home'));

    return PHPWS_Template::processTemplate($template, 'users', 'usermenus/Default.tpl');

  }

  function isUser(){
    if ($this->user_id > 0)
      return TRUE;
    else
      return FALSE;
  }

  function isUserApproved($username) {
    $user_id = $_SESSION['OBJ_user']->getUserId($username);

    if($GLOBALS['core']->sqlSelect('mod_approval_jobs',  'mod_id', $user_id))
      return FALSE;
    else
      return TRUE;
  }

  function logout(){
    PHPWS_User_Cookie::cookie_write('mod_users', 'rememberme', '-1');
    $GLOBALS['core']->killAllSessions();
//	foreach ($_COOKIE as $n=>$v) setcookie($n);
	extract($_REQUEST);
	if (!isset($redirect)) $redirect = "./";
    header('Location: ' . $redirect);
    exit();
  }

  function signupAction(){
      $settings = $_SESSION['OBJ_user']->getSettings();
      if (isset($_POST['usr_login'])){
		  if (isset($_POST['login_username']) && isset($_POST['password'])) {
			  if(isset($_POST['rememberme'])) {
				  $_SESSION['OBJ_user']->validate_login($_POST['login_username'], $_POST['password'], $_POST['rememberme']);
			  }
			  else {
				  $_SESSION['OBJ_user']->validate_login($_POST['login_username'], $_POST['password']);
			  }
		  }
		  else {
			  $_SESSION['OBJ_user']->signup_user();
		  }
	  }
//		  elseif (isset($_POST['login_username'])) $_SESSION['OBJ_user']->signup_user();
	  elseif (isset($_POST['signup_request'])) {
			  if ($settings['user_signup'] != 'none'){	      
				  /* If a URL to jump to after signing up is specified, set it here */
				  if(isset($_REQUEST['jump_to'])) $_SESSION['OBJ_user']->jumpURL = $_REQUEST['jump_to'];
				  elseif($settings['welcomeURL']) $_SESSION['OBJ_user']->jumpURL = $settings['welcomeURL'];

				  if ($_POST['term'] != 1) {
					  $_SESSION['OBJ_user']->error[] = "只有选择接受相关服务协议和条款才能注册帐户。";
					  echo "<script> alert('只有选择接受相关服务协议和条款才能注册帐户。'); </script>";
				  }
				  elseif ($_POST['signup_password1'] != $_POST['signup_password2']) {
						  $_SESSION['OBJ_user']->error[] =  '二次输入的密码不一致，请重试。';
						  echo "<script> alert('二次输入的密码不一致，请重试。'); </script>";
				  }
				  elseif (!$_POST['signup_password1']) {
						  $_SESSION['OBJ_user']->error[] =  '二次输入的密码不一致，请重试。';
						  echo "<script> alert('密码不能空，请重试。'); </script>";
				  }
				  elseif ($_SESSION['OBJ_user']->allowUsername($_POST['signup_username'])) {
/*
				  elseif ($_POST['usr_new_email'] && $_SESSION['OBJ_user']->allowUsername($_POST['signup_username'])){
					  if (!$_SESSION['OBJ_user']->check_email($_POST['usr_new_email'])) {
//						  $_SESSION['OBJ_user']->error[] =  '用户名与邮箱/手机号码和其他用户有冲突，请重试。';
//						  $_SESSION['OBJ_user']->error[] = ' ';
//						  $_SESSION['OBJ_user']->error[] =  "如果你已经有帐户，请用'忘记密码'选项。";
						  echo "<script> alert('用户名与邮箱/手机号码和其他用户有冲突，请重试。\n如果你已经有帐户，请用\"忘记密码\"选项。'); </script>";
					  }
					  else
*/
					  if (!$_POST['companyname'] || !$_POST['taxid']) {
						  $_SESSION['OBJ_user']->error[] =  '新单位必须提供公司名称和统一社会信用代码，请重试。';
						  $_SESSION['OBJ_user']->error[] = ' ';
						  $_SESSION['OBJ_user']->error[] =  "如果你单位已经有帐户，请与单位管理员联系开通新账户。";
						  echo "<script> alert('新单位必须提供公司名称和统一社会信用代码，请重试。\n如果你单位已经有帐户，请与单位管理员联系开通新账户。'); </script>";
					  }
					  elseif (!$_SESSION['OBJ_user']->check_company($_POST['companyname'], $_POST['taxid']) ){
						  $_SESSION['OBJ_user']->error[] =  "单位账户已开通，请与单位管理员联系开通个人账户。";
						  echo "<script> alert('单位账户已开通，请与单位管理员联系开通个人账户。'); </script>";
					  }
					  else {
						  $uid = $GLOBALS['core']->sqlInsert(array('`username`'=>$_POST['signup_username'], '`password`'=>md5($_POST['signup_password1']), '`groups`'=>'', '`rootOrg`'=>0), 'mod_users', FALSE, TRUE);

						  require_once(PHPWS_SOURCE_DIR . 'mod/sysadmin/class/sysadmin.php');

						  $id = PHPWS_SysAdmin::_addorg($_POST['companyname'], $uid, $_POST['taxid']);

						  $GLOBALS['core']->sqlUpdate(array('org'=>$id, 'rootOrg'=>$id), 'mod_users', 'user_id', $uid);

						  $_SESSION['OBJ_user']->org = $_SESSION['OBJ_user']->rootOrg = $id;

						  $_SESSION['OBJ_user']->jumpURL = "http://" . $_SERVER['SERVER_NAME'] . "/{$GLOBALS['SCRIPT']}?module=sale&MOD_op=newplayer&op=announce";

						  $_SESSION['OBJ_user']->validate_login($_POST['signup_username'], $_POST['signup_password1'], NULL);
					  }
				  }
				  else {
					  $_SESSION['OBJ_user']->error[] = '用户名和其他用户有冲突，请重试。<br>如果你已经有帐户，请用"忘记密码"选项。';
//					  echo "<script> alert('用户名和其他用户有冲突，请重试。如果你已经有帐户，请用\"忘记密码\"选项。'); </script>";
				  }
			  } else {
				  $_SESSION['OBJ_user']->error[] = '用户名与邮箱/手机号码和其他用户有冲突，请重试。';						  $_SESSION['OBJ_user']->error[] = ' ';
				  $_SESSION['OBJ_user']->error[] = "如果你已经有帐户，请用'忘记密码'选项。";
				  echo "<script> alert('用户名与邮箱/手机号码和其他用户有冲突，请重试。\n如果你已经有帐户，请用\"忘记密码\"选项。'); </script>";
//				  $_SESSION['OBJ_user']->signup_user();
			  }
//		  }
      }
	  elseif (isset($_POST['forgotPW'])){
		  if (!PHPWS_Text::isValidInput($_POST['forgot_username'])){
			  $_SESSION['OBJ_user']->error[] = '你输入的用户名无效。';
			  echo "<script> alert('你输入的用户名无效。'); </script>";
			  $_SESSION['OBJ_user']->signup_user();
		  }
		  elseif (!($user_id = $_SESSION['OBJ_user']->getUserId($_POST['forgot_username']))) {
			  echo "<script> alert('你输入的用户名无效。'); </script>";
			  $_SESSION['OBJ_user']->error[] = '你输入的用户名无效。';
			  $_SESSION['OBJ_user']->signup_user();	  
		  }
		  elseif ($_SESSION['OBJ_user']->isDeity($user_id)) {
			  echo "<script> alert('你无权改变密码。'); </script>";
			  $_SESSION['OBJ_user']->error[] = '你无权改变密码。';
			  $_SESSION['OBJ_user']->signup_user();
		  }
		  elseif (!PHPWS_User::isUserApproved($_POST['forgot_username'])) {
			  echo "<script> alert('管理员还没有核准你的用户名。'); </script>";
			  $_SESSION['OBJ_user']->error[] = '管理员还没有核准你的用户名。';
			  $_SESSION['OBJ_user']->signup_user();
		  }
		  elseif (!$_SESSION['OBJ_user']->forgotPassword($_POST['forgot_username'])) {
			  echo "<script> alert('无法给你发送邮件，请联系管理员。'); </script>";
//			  $_SESSION['OBJ_user']->error[] =  $_SESSION['translate']->it('Unable to email change form').'. ';
//			  $_SESSION['OBJ_user']->error[] =  $_SESSION['translate']->it('Please contact the systems administrator').'.';
			  $_SESSION['OBJ_user']->signup_user();
		  }
		  else {
			  echo "<script> alert('变更密码的邮件已经发送到您的邮箱，请检查邮件并按里面的指示重置密码。'); window.open('./index.php', '_self'); </script>";
			  exit();
		  }
      }   
  }

  /**
   * Display change profile window
   *
   * @access public
   */
  function changeProfile($showRequired = false, $tocreate = false, $err = false) {
	  $required = $showRequired? '*' : '';
	  $tags = array();
	  $jurisdictionArray = array("", "上海市", "北京市", "天津市", "重庆市", "河北省", "山西省", "辽宁省", "吉林省", "黑龙江省", "江苏省", "浙江省", "安徽省", "福建省", "江西省", "山东省", "河南省", "湖北省", "湖南省", "广东省", "海南省", "四川省", "贵州省", "云南省", "陕西省", "甘肃省", "青海省", "台湾省", "内蒙古自治区", "广西壮族自治区", "宁夏回族自治区", "新疆维吾尔自治区", "西藏自治区");	//, "香港特别行政区", "澳门特别行政区");
	  if ($err) $tags["ERROR"] = $err;
	  $tags["ADDRESS1_LBL"] = "地址";
	  $address = $this->address;
	  $tags["ADDRESS1_FIELD"] = PHPWS_Form::formTextField("address", $address, 40, 255, NULL, "form-control");
	  $tags["CITY_LBL"] = "城市";
	  $tags["CITY_FIELD"] = PHPWS_Form::formTextField("city", $this->city, 20, 255, NULL, "form-control");
	  $tags["STATE_LBL"] = "省份";
	  $tags["STATE_FIELD"] = PHPWS_Form::formSelect("state", $jurisdictionArray, $this->state, TRUE, FALSE, NULL, NULL, "form-control");
	  $tags["ZIP_LBL"] = "邮编";
	  $tags["ZIP_FIELD"] = PHPWS_Form::formTextField("zip", $this->zip, 10, 255, NULL, "form-control");
	  $tags["PHONE_LBL"] = "电话号码";
	  $tags["PHONE"] = PHPWS_Form::formTextField("phone", $this->phone, 20, 255, NULL, "form-control");
	  $tags["EMAIL_LBL"] = "电子邮箱";
	  $em = explode('@', $this->email);
	  $tags["EMAIL_USER_FIELD"] = '<input style="width:30%;display:inline-block" class="form-control" type="text" id="email" name="email" value="' . $em[0] . '" size="20" maxlength="255">';	// PHPWS_Form::formTextField("email", $em[0], 10, 255, NULL, "form-control");
	  $tags["EMAIL_DOMAIN_FIELD"] = '<input type="text" style="width:28%;display:inline-block" class="form-control" id="email_domain" name="email_domain" value="' . $em[1] . '" size="20" maxlength="255">'; //PHPWS_Form::formTextField("email_domain", $em[1], 10, 255, NULL, "form-control");
	  $tags["INTRO_LBL"] = "姓名/称呼";
	  $tags["INTRO"] = PHPWS_Form::formTextField("intro", $this->realname, 5, 40, NULL, "form-control");
	  $tags["SUBMIT_BUTTON"] = PHPWS_Form::formSubmit("更新", "action", "btn btn-primary");
	  $tags['THEME_DIRECTORY'] = "./themes/" . $_SESSION['OBJ_layout']->current_theme . "/";

	  if ($this->head) $tags["WEIXINHEAD"] = $this->head;

	  $tags["ACTION"] = '"index.php"';
	  $tags["PASS_SUBMIT_BUTTON"] = PHPWS_Form::formSubmit("更改密码", "action", "btn btn-primary");
	  $tags["CURRENT_PASS"] = "密码";

	  return $err . PHPWS_Template::processTemplate($tags, "users", "profile.tpl");
  }

  function profileChanged() {
      extract($_POST);
	  switch ($infochanged) {
		  case 'personalinfo':
			  $update['address'] = $this->address = $address;
			  $update['city'] = $this->city = $city;
			  $update['state'] = $this->state = $state;
			  $update['zip'] = $this->zip = $zip;
			  if ($phone) $update['phone'] = $this->phone = $phone;
			  if ($intro) $update['realname'] = $this->realname = $intro;

			  if (PHPWS_Text::isValidInput($email . '@' . $email_domain, 'email'))
				  $update['email'] = $this->email = $email . '@' . $email_domain;
			  elseif ($email && $email_domain) $xtra = "，但因为邮箱地址错误，邮箱没有更新";

			  if ($this->user_id) {
				  $GLOBALS['core']->sqlUpdate($update, 'mod_users', 'user_id', $this->user_id);
				  $res = '个人信息更新完成' . $xtra . '。<br>';
			  }
			  else {
				  $update['activatecode'] = 0;
				  $GLOBALS['core']->sqlUpdate($update, 'mod_users', 'user_id', $this->pre_id);
				  $CNT_user["title"] = $_SESSION["translate"]->it("Account Activation Successful");
				  $CNT_user["content"] = $_SESSION["translate"]->it("Congratulations! Your account has been activated. You may now log in to use our services") . ".<br>";
				  $this->mailNotice("User account has been activated. Email: " . $this->email);
				  $this->pre_id = 0;
				  $res = $CNT_user;
			  }

			  
			  return $res;
			  break;
		  case 'password':
			  if (!$this->externaluser && (empty($pass0) || $this->password != md5($pass0)))
				  $content = $this->changeProfile(false, false, "<span style=\"color:red\">密码不正确.</span><br>");
			  else {
				if (empty($pass1) || ($errorMessage = $this->checkPassword($pass1, $pass2))) {
					return $this->changeProfile(false, false, "<span style=\"color:red\">密码错误</span><br>");
				}
				else $this->password = $personal_upd['password'] = md5($pass1);

				$GLOBALS['core']->sqlUpdate($personal_upd, 'mod_users', 'user_id', $this->user_id);
				$content = "密码修改成功。";
			  }
			  return $content;
 			  break;
	  }
  }

  /**
   * Display change password window
   *
   * @access public
   */
  function changePassword() {
		$content = "<form action=\"{$GLOBALS['SCRIPT']}\" method=\"post\">".PHPWS_Form::formHidden("module", "account")." ".PHPWS_Form::formHidden("ACCOUNT_op", "password_changed");
    
	    $table[] = array("<b>".$_SESSION["translate"]->it("当前密码：")."</b>", PHPWS_Form::formPassword("pass0", "10"));
	    $table[] = array("<b>".$_SESSION["translate"]->it("新密码：")."</b>", PHPWS_Form::formPassword("pass1", "10"));
	    $table[] = array("<b>".$_SESSION["translate"]->it("重输新密码：")."</b>", PHPWS_Form::formPassword("pass2", "10"));

	    $content .= PHPWS_Text::ezTable($table, 4)." ".PHPWS_Form::formSubmit($_SESSION["translate"]->it("更改密码"))."</form>";
		return $content;
  }

  /**
   * Handle change of password
   *
   * @access public
   */
  function passwordChanged() {
      extract($_POST);

	  $hash_pass = md5($pass0);

	  if (empty($pass0) || $this->password != $hash_pass)
		  $content = "<span style=\"color:red\">密码不正确</span><br>" . $this->changePassword();
	  else {
	    if (!empty($pass1)){
		  if ($errorMessage = $this->checkPassword($pass1, $pass2)){
		    return "<span style=\"color:red\">$errorMessage</span><br>" . $this->changePassword();
		  }else
			$personal_upd['password'] = md5($pass1);
	    }

	    $GLOBALS['core']->sqlUpdate($personal_upd, 'mod_users', 'user_id', $this->user_id);
		$content = "改密成功";
	  }
	  return $content;
  }

  /**
   * Creates an array of the English alphabet
   *
   * If '$letter_case' is lower then the character set
   * will be lowercase. If it is NULL, then uppercase.
   * Needs internationalization
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string $letter_case Indicates to return an uppercase or lowercase array
   * @return array  $ret_array   Numerically indexed array of alphabet
   * @access public
   */
  function alphabet($letter_case=NULL) {
    if ($letter_case == 'lower') {
      $start = ord('a');
      $end = ord('z');
    } else {
      $start = ord('A');
      $end = ord('Z');
    }
    
    for ($i=$start;$i<=$end;$i++)
      $ret_array[] = chr($i);

    return $ret_array;
  }// END FUNC alphabet()
}

?>