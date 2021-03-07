<?php
error_reporting (E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

$session_failure  = NULL;
$install_password = NULL;
$check_session    = NULL;
$password_allowed = NULL;
$install_step     = NULL;
$install_pw       = NULL;
$error            = NULL;
$our_dir          = NULL;

// Set to TRUE if you want to override the version check
// Be warned that we cannot support php versions below 4.2.2
$overrideVersion = FALSE;

// Set the two character code for the default language table
// examples (en = English, de = German, es = Spanish, etc.)
$install_language="en";

if(file_exists("../conf/config.php")) {
  if(filesize("../conf/config.php") == 0) {
    header("Location: set_config.php");
    exit();
  }
} else {
  header("Location: set_config.php");
  exit();
}

if(!defined("PHPWS_SOURCE_DIR")) {
  define("PHPWS_SOURCE_DIR", "../");
}

require_once("../core/Core.php");
require_once("../core/Form.php");
require_once("../core/Text.php");
require_once("../conf/config.php");
require_once("../conf/core_info.php");
$install_hash = $hub_hash;

$core = new PHPWS_Core(NULL, "../", TRUE);
$core->current_mod = "boost";

require_once("../mod/boost/class/Boost.php");
require_once("../mod/language/class/Language.php");
require_once("../mod/search/class/Search.php");
require_once("../mod/help/class/CLS_help.php");
require_once("../mod/users/class/Users.php");
require_once("../mod/layout/class/Layout.php");
require_once("../mod/controlpanel/class/ControlPanel.php");
require_once("defaultMods.php");

if ($_POST)
     extract($_POST);

/* Fix for those with session auto start enabled */
if (get_cfg_var("session.auto_start") == "1")
    session_destroy();

$sessionName = md5($core->site_hash . PHPWS_HOME_HTTP);
session_name($sessionName);
session_start($sessionName);

include("allow_setup.php");

if (isset($_POST["check_session"]) && !isset($_SESSION["send_sess"])){
  $session_failure = 1;
  $install_step = null;
}

if (!isset($_SESSION["OBJ_user"]))
     $_SESSION["OBJ_user"]  = new PHPWS_User;

$_SESSION["translate"] = new PHPWS_Language;

if ($install_language != "en"){
  $_SESSION["translate"]->current_language = $install_language;
  $_SESSION["translate"]->langActive = 1;
  $_SESSION["translate"]->quickDict("lang/setup.$install_language.lng");
} else
$_SESSION["translate"]->langActive = 0;


if (!DB::isError($core->db)) $db_connect = TRUE;
else $db_connect = FALSE;


if (!isset($_SESSION["password_allowed"])){
  if ($dbuser == "your_username" || $dbpass == "your_password" || (isset($install_password) && ( $install_password == "default" || $install_password == "")))
    $install_step = NULL;
  elseif (isset($install_step) && (isset($install_password) && $install_password != $install_pw))
    $install_step = NULL;
  elseif (isset($install_password) && $install_password == $install_pw)
    $_SESSION["password_allowed"] = 1;
}
     
echo "
<html>
<head>
<meta name=\"robots\" content=\"noindex, nofollow\" />
<script language=\"JavaScript\" type=\"text/javascript\">
<!-- 
function CheckAll() {
   for (var i = 0; i < document.modules.elements.length; i++) {
       if( document.modules.elements[i].type == 'checkbox' ) {
           document.modules.elements[i].checked = !(document.modules.elements[i].checked);
       }
   }
}
//-->
</script>
<title>".$_SESSION["translate"]->it("phpWebSite Core Installation")."</title>
</head>
<body>
<img src=\"poweredby.jpg\" /><br />
<h1>" . $_SESSION["translate"]->it("phpWebSite Version") . " $version - " . $_SESSION["translate"]->it("Installation Utility") . "</h1>";

switch ($install_step){

 case "1":

   if ($_POST["hash"] != $install_hash)
     exit("You are not authorized to create a user.");

   if (submitUserInfo()){
     $_SESSION["OBJ_user"]->username = $_POST["username"];
     $_SESSION["OBJ_user"]->password = md5($_POST["pass1"]);
     $_SESSION["OBJ_user"]->deity    = 1;
     $_SESSION["OBJ_user"]->admin_switch = 1;
     $_SESSION['OBJ_user']->email = $_POST['email'];
     echo $_SESSION["translate"]->it("Your administrative user was successfully created") . ".<br /><br />";
     echo installSecModules($defaultMods);
   } else {
     if (isset($GLOBALS["errorMes"])){
       echo $GLOBALS["errorMes"];
       unset($GLOBALS["errorMes"]);
     } else
       echo $_SESSION["translate"]->it("You must create a deity user account that will have administrative rights to this phpWebSite installation") . ".<br />" . $_SESSION["translate"]->it("Make sure the password is 5 characters or more and not simple to guess") . ".<br /><br />" . $_SESSION["translate"]->it("Please enter the required information to continue") . ".<br /><br />";
     echo createUserForm();
   }
   break;

 case "2":
   if ($core->sqlImport($core->source_dir . "setup/install.sql", 1,1)){
     if ($dbversion == "mysql")
       $core->sqlModifyColumn("cache", "data", "MEDIUMTEXT NOT NULL");

     $langCreate = "
CREATE TABLE mod_lang_".strtoupper($install_language)." (
  phrase_id int unsigned NOT NULL,
  module varchar(30) NOT NULL default '',
  phrase text NOT NULL,
  translation text NOT NULL,
  PRIMARY KEY  (phrase_id),
  KEY module (module)
);";

     if (!$core->query($langCreate, TRUE))
       echo $_SESSION["translate"]->it("There was a problem creating the default language table") . ".<br />";
       
     echo "<b>".$_SESSION["translate"]->it("Core tables successfully installed") . "!</b><br />";
   } else {
     echo $_SESSION["translate"]->it("There was a problem installing the core tables") . ". <br />";
     echo $_SESSION["translate"]->it("Please check your core and try the installation again") . ".<br />";
     exit();
   }
   
   echo "<h2>" . $_SESSION["translate"]->it("Building required modules") . "</h2><hr />";
   echo PHPWS_Boost::installModuleList($defaultMods);
   PHPWS_ControlPanel::import("controlpanel");
   echo "<h2>" . $_SESSION["translate"]->it("Registering Default Language") . "</h2><hr />";
   echo PHPWS_Language::installModuleLanguage($defaultMods);
   echo "<h2>" . $_SESSION["translate"]->it("Post Installation Procedures") . "</h2><hr />";
   echo PHPWS_Boost::postInstall($defaultMods);

   if (isset($_POST["extraMods"]) && (isset($_POST["secModules"]) && count($_POST["secModules"]))){
     echo "<h2>" . $_SESSION["translate"]->it("Building extra modules") . "</h2><hr />";
     echo PHPWS_Boost::installModuleList($_POST["secModules"], FALSE, TRUE, TRUE);
   }
   
   PHPWS_BOOST::setVersionInfo("../conf/core_info.php", "insert");
   echo PHPWS_Text::link(PHPWS_HOME_HTTP, $_SESSION["translate"]->it("Go to my installation"));
   $_SESSION["OBJ_user"]->writeUser();
   $core->killAllSessions();
   
   break;
   

 default:
   $core->killSession("password_allowed");

 if (phpversion() < "4.2.2"){
   if ($overrideVersion)
     echo $_SESSION["translate"]->it("You have chosen to override the version checker") . ". " . $_SESSION["translate"]->it("Be aware that your version of php is NOT supported") . ".<br />";
   else {
     echo $_SESSION["translate"]->it("Sorry, but you appear to running PHP version") . " ".phpversion().". "
       . $_SESSION["translate"]->it("phpWebSite requires 4.2.2 or above") . ".<br />";
     $error = 1;
   }
 }
 
 if ($session_failure){
   echo $_SESSION["translate"]->it("phpWebSite failed a session test") . ". "
     . $_SESSION["translate"]->it("If your PHP installation is not configured to allow sessions, you will not be able to run phpWebSite") . ".<br />"
     . $_SESSION["translate"]->it("Please check your session path in your php.ini file and make sure your version of php supports superglobals")
     . "(" . $_SESSION["translate"]->it("greater than version") . " 4.1.2)<br />";
   $error = 1;
 }

 if ($db_connect == FALSE){
   echo $_SESSION["translate"]->it("[var1] not found", "<b>$dbname</b>") . ". "
     . $_SESSION["translate"]->it("You will need to create a database of this name before continuing") . ".<br />";
   $error = 1;
 } elseif ($db_connect == TRUE) {
     $table_check = count($core->listTables());
     
     if ($table_check && !isset($login) && !$table_prefix){
       echo $_SESSION["translate"]->it("There are tables in this database and you have not set a table prefix") . ".<br />"
	 . $_SESSION["translate"]->it("Please do so in your config.php file and return") . ".<br />";
       $error = 1;
     }
 }
     
 if (!$error){
   $_SESSION["send_sess"] = 1;
   echo "
<form action=\"".$our_dir."setup.php\" method=\"post\">"
     . PHPWS_Form::formHidden(array("install_step"=>1, "check_session"=>1, "hash"=>$install_hash))
     . $_SESSION["translate"]->it("Welcome to the phpWebSite installation utility") . "!<br />" . $_SESSION["translate"]->it("Please enter your installation password to continue.") . "<br /><br />" . PHPWS_Form::formPassword("install_password") . " " . PHPWS_Form::formSubmit($_SESSION["translate"]->it("Continue")) . "
</form>";
 }

   break;
   
}
echo "
</body>
</html>";

function createUserForm(){
  $core = $GLOBALS["core"];

  $content = "\n<form action=\"setup.php\" method=\"post\">\n";
  $content .= PHPWS_Form::formHidden(array("hash"=>$GLOBALS["install_hash"], "install_step"=>1, "login"=>1));
  $content .= "
<table cellpadding=\"4\" cellspacing=\"1\" bgcolor=\"#000000\" border=\"0\">
  <tr><td bgcolor=\"#dddddd\"><b>" . $_SESSION["translate"]->it("Username") . ":</b></td><td bgcolor=\"#dddddd\">" . PHPWS_Form::formTextField("username", (isset($_POST["username"])) ? $_POST["username"] : NULL) ."</td></tr>
  <tr><td bgcolor=\"#ffffff\"><b>" . $_SESSION["translate"]->it("Password") . ":</b></td><td bgcolor=\"#ffffff\">". PHPWS_Form::formPassword("pass1") . "</td></tr>
  <tr><td bgcolor=\"#dddddd\"><b>" . $_SESSION["translate"]->it("Password Confirmation") . ":</b></td><td bgcolor=\"#dddddd\">" . PHPWS_Form::formPassword("pass2") . "</td></tr>
  <tr><td bgcolor=\"#ffffff\"><b>" . $_SESSION["translate"]->it("Email Address") . ":</b></td><td bgcolor=\"#ffffff\">" . PHPWS_Form::formTextField("email", (isset($_POST['email'])) ? $_POST['email'] : NULL) . "</td></tr>
</table><br />
" . PHPWS_Form::formSubmit($_SESSION["translate"]->it("Continue")) ."
</form>";
  return $content;
}

function submitUserInfo(){
  $core = $GLOBALS["core"];

  if (!isset($_POST["login"]))
    return FALSE;

  if (!PHPWS_Text::isValidInput($_POST["username"])){
    $GLOBALS["errorMes"] = "<font color=\"red\">" . $_SESSION["translate"]->it("Bad user name") . ". "
      . $_SESSION["translate"]->it("Alphanumeric characters only") . ". "
      . $_SESSION["translate"]->it("No spaces") . ".</font><br />";
    return FALSE;
  } elseif(preg_match("/[^a-zA-Z0-9!@_]/", $_POST["pass1"])){
    $GLOBALS["errorMes"] = "<font color=\"red\">" . $_SESSION["translate"]->it("Some strange characters in that password") . ".</font><br />";
    return FALSE;
  } elseif(strlen($_POST["pass1"]) < 5){
    $GLOBALS["errorMes"] = "<font color=\"red\">" . $_SESSION["translate"]->it("Couple of letters missing in that password") . "?</font><br />";
    return FALSE;
  } elseif(preg_match("/(pass|password|phpwebsite|qwerty|passwd|asd|admin|fallout)/i", $_POST["pass1"])){
    $GLOBALS["errorMes"] = "<font color=\"red\">" . $_SESSION["translate"]->it("No seriously") . ". "
      . $_SESSION["translate"]->it("Even I guessed that one") .". "
      . $_SESSION["translate"]->it("Pick a REAL password") . ".</font><br />";
    return FALSE;
  } elseif ($_POST["pass1"] != $_POST["pass2"]){
    $GLOBALS["errorMes"] = "<font color=\"red\">" . $_SESSION["translate"]->it("Passwords did not match") . ". "
      . $_SESSION["translate"]->it("Try again") . ".</font><br />";
    return FALSE;
  } elseif (!phpws_text::isValidInput($_POST["email"], "email")){
    $GLOBALS["errorMes"] = "<font color=\"red\">" . $_SESSION["translate"]->it("You must enter a proper email address") . ". "
      . $_SESSION["translate"]->it("Try again") . ".</font><br />";
    return FALSE;
  } else
    return TRUE;

}

function installSecModules($required){
  $core = $GLOBALS["core"];
  if (!($dir = PHPWS_File::readDirectory(PHPWS_SOURCE_DIR."mod/", 1)))
    return NULL;

  foreach ($dir as $mod_dir_name){
    $filename = PHPWS_SOURCE_DIR."mod/$mod_dir_name/conf/boost.php";
    if (!(file_exists($filename)))
      continue;

    include($filename);
    $mods[$mod_directory] = $mod_pname;
  }

  foreach ($required as $modRemove)
    unset($mods[$modRemove]);

  $content = "<form action=\"setup.php\" method=\"post\" name=\"modules\">" . PHPWS_Form::formHidden("install_step", "2") . "\n";

  if (count($mods)){
    $content .= $_SESSION["translate"]->it("The modules listed below were found with your installation") . ".<br />" . $_SESSION["translate"]->it("These modules are not essential to the operation of phpWebSite") . ".<br />" .  $_SESSION["translate"]->it("If you wish to add or remove modules later, use the Boost Module in your administration panel") . ".<br /><br />" . $_SESSION["translate"]->it("Click the checkbox next to the modules you wish to install, then click the <b>Install Selected Modules</b> button") . ".<br />" . $_SESSION["translate"]->it("If you do not wish to install any extra modules at this time, click the <b>Install Core Only</b> button") . ".<br /><br />\n";
    
    foreach ($mods as $modDir=>$modName)
      $content .= PHPWS_Form::formCheckBox("secModules[]", $modDir) . " $modName<br />\n";

    $content .= PHPWS_Form::formButton($_SESSION["translate"]->it("Toggle All"), "toggle", "CheckAll();") . "<br /><br />";
    $content .= PHPWS_Form::formSubmit($_SESSION["translate"]->it("Install Selected Modules"), "extraMods");
  }

  $content .= "&#160;" . PHPWS_Form::formSubmit($_SESSION["translate"]->it("Install Core Only")) . "\n</form>";

  return $content;
}

?>