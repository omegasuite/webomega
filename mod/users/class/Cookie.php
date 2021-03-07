<?php

/**
 * The PHPWS_User_Cookie class contains the variables and functions that 
 * control user cookies. There are two types of cookies in the class: admin
 * and user. The Admin cookie is for registration only. The User cookie holds
 * generic preferences for general and registered users.
 *
 * You will never have to handle admin cookies. User cookies are useful in case
 * you need to store settings for a user regardless of their registration.
 * If you want to set something for a registered user only, use the setUserVar/
 * getUserVar functions. User cookies should never contain sensitive information.
 *
 * @version $Id: Cookie.php,v 1.24 2003/10/30 17:03:38 steven Exp $
 * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
 * @package Users
 */

require_once(PHPWS_SOURCE_DIR.'core/Form.php');

class PHPWS_User_Cookie {

  /**
   * Writes a cookie to check for javascript
   *
   * If the cookie gets written, then the user has javascript and it is
   * noted on the next page click. Whether they passed or not is noted in
   * a new phpWebSite specific cookie.
   */
  
  function triggerJS(){
    if (isset($this->js_on) && $this->user_id > 0)
      return;

    $js = $this->cookie_read("users", "js_on");
    if (is_null($this->js_on)){
      // js has not been set in the object yet
      if (!is_null($js)){
	// the cookie exists
	// set the object's js_on to the $js var
	$this->js_on = $js;
	$GLOBALS["core"]->killSession("checkJS");
	return;
      } else {
	// The js cookie does not exist
	if (!isset($_SESSION["checkJS"])){
	  // The session check has not been set
	  $js_func =  "var cookievar = \"". USER_COOKIE . "[users][js_on]=1\"; document.cookie = cookievar;";
	  $GLOBALS["core"]->js_func[] = $js_func;
	  $_SESSION["checkJS"]= 1;
	} else {
	  // the session check has been set
	  $this->js_on = 0;
	  $GLOBALS["core"]->killSession("checkJS");
	}
      }
    }
  }


  function refreshUserCookie(){
    if ($cookie_info = $this->getUserVar("cookie", NULL, "users")){
      foreach ($cookie_info as $modName=>$info)
	foreach ($info as $variable_name=>$value)
	$this->cookie_write($modName, $variable_name, $value);
      return TRUE;
    } else
      return FALSE;
    
  }

  function modify_cookie(){
    
    extract($this->getSettings());

    $GLOBALS["CNT_user"]["title"] = $_SESSION["translate"]->it("Set Cookie Preferences");
    $content = "<br />".$this->return_to_user()."<br /><br />".$_SESSION["translate"]->it("You have the option to allow your site to use cookies to assist in user authorization").". "
       .$_SESSION["translate"]->it("This allows for easier interaction and administration between sites using the same hub").".
<form action=\"index.php\" method=\"post\">
<input type=\"hidden\" name=\"module\" value=\"users\" />
<input type=\"hidden\" name=\"user_op\" value=\"update_cookie_settings\" />
<div class=\"bg_light\">". PHPWS_Form::formCheckBox("allow_cookies", 1, $allow_cookies)." ".$_SESSION["translate"]->it("Allow Cookies")."?</div><br />
".$_SESSION["translate"]->it("Time Limit")." ".PHPWS_Form::formTextField("timelimit", $timelimit, 3)."<br /><br />
".PHPWS_Form::formCheckBox("secure",1, $secure)." ".$_SESSION["translate"]->it("Secure Cookie")." <br /><br />";

    $content .= PHPWS_Form::formSubmit($_SESSION["translate"]->it("Update Cookie Settings"))."
</form>";

    $content .= "<br />".$_SESSION["translate"]->it("Keep in mind that these options only cover user cookies").".<ul>
<li><b>".$_SESSION["translate"]->it("Time Limit").":</b> ".$_SESSION["translate"]->it("The number of minutes you want to phpWebSite to allow the cookie to remain active").". "
       .$_SESSION["translate"]->it("This value must be greater than 10 minutes").".</li>
<li><b>".$_SESSION["translate"]->it("Simple vs. Domain/Directory").":</b> ".$_SESSION["translate"]->it("The simple cookie only contains the information and a time limit")
.". ".$_SESSION["translate"]->it("The Domain/Directory option also includes your web address for greater security").".</li>
<li><b>".$_SESSION["translate"]->it("Secure Cookie").":</b> ".$_SESSION["translate"]->it("A secure cookie will only be sent over a secure connection (HTTPS)").". "
       .$_SESSION["translate"]->it("In most cases, this can be left off").".</li>
</ul>"; 

    $GLOBALS["CNT_user"]["content"] .= $content;
  }


  function cookie_crumble(){
    setcookie(USER_COOKIE, "", time()-3600);
  }


  function cookie_unset($mod_name, $variable_name=NULL){
    if (is_null($variable_name))
      $cookieName = USER_COOKIE . "[$mod_name]";
    else
      $cookieName = USER_COOKIE . "[$mod_name][$variable_name]";

    setcookie($cookieName, '', time()-3600);

    if ($this->user_id){
      $cookieValue = $this->getUserVar("cookie", NULL, "users");
      if ($variable_name)
	unset($cookieValue[$mod_name][$variable_name]);
      else
	unset($cookieValue[$mod_name]);
    }

  }


  function cookie_write($mod_name, $variable_name, $value){
    $cookieName = USER_COOKIE . "[$mod_name][$variable_name]";
    $cookieValue[$mod_name][$variable_name] = $value;
    setcookie($cookieName, $value, time()+31536000);

    if (isset($_SESSION["OBJ_user"]) && $_SESSION["OBJ_user"]->user_id > 0)
      $_SESSION["OBJ_user"]->setUserVar("cookie", $cookieValue, NULL, "users");

  }

  function cookie_read($mod_name, $variable_name=NULL){
    if (!is_null($variable_name))
      return (isset($_COOKIE[USER_COOKIE][$mod_name][$variable_name])) ? $_COOKIE[USER_COOKIE][$mod_name][$variable_name] : NULL;
    else
      return $_COOKIE[USER_COOKIE][$mod_name];
  }


  function update_cookie_settings(){
    extract ($_POST);
    if (!$secure)
      $secure = 0;
    if (!$allow_cookies){
      $allow_cookies = 0;
    }
    if ($timelimit < 10){
      $GLOBALS["CNT_user"]["content"] .= "<span class=\"errortext\">".$_SESSION["translate"]->it("The Timelimit must be 10 minutes or more").".</span><br />";
      $this->modify_cookie();	
    } else {
      $update_sql = array("allow_cookies"=>$allow_cookies, "timelimit"=>$timelimit, "secure"=>$secure);
      $GLOBALS["core"]->sqlUpdate($update_sql, "mod_user_settings");
      $this->force_to_user();
    }
  }

}

?>