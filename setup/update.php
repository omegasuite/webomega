<?php
define("UPDATE_BRANCHES", TRUE);

//if(is_file("../conf/pear_path.php"))
//  include("../conf/pear_path.php");

chdir("../");
define("PHPWS_SOURCE_DIR", getcwd()."/");
chdir("setup/");

require_once ("../core/Core.php");
require_once ("../core/Form.php");

$core = new PHPWS_Core(NULL, "../", TRUE);

require_once("../mod/language/class/Language.php");
require_once("../mod/boost/class/Boost.php");
require_once("../mod/users/class/Users.php");
require_once("../mod/layout/class/Layout.php");
require_once("../mod/help/class/CLS_help.php");

$coreVersion = PHPWS_Boost::getVersionInfo("core");
$currentVersion = $coreVersion['version'];

session_start();

include("allow_setup.php");

if ($GLOBALS['core']->sqlTableExists("branch_sites", TRUE)) $GLOBALS['branchExists'] = TRUE;
else $GLOBALS['branchExists'] = FALSE;

if (!isset($_SESSION["translate"]))
     $_SESSION["translate"] = new PHPWS_Language;

if (!isset($_SESSION["OBJ_user"]))
     $_SESSION["OBJ_user"] = new PHPWS_User;

if (!isset($_SESSION["OBJ_layout"]))
     $_SESSION["OBJ_layout"] = new PHPWS_Layout(FALSE);

$body[] = startpage($currentVersion);

if (isset($_POST['checkPass'])){
  $_SESSION['OBJ_user']->setDeity(checkPassword($_POST['updatePass']));
}

if (!$_SESSION['OBJ_user']->isDeity()){
  $body[] = loginAdmin();
} elseif (isset($_POST['updateModules'])){
  $body[] = "<h2>" . $_SESSION["translate"]->it("Updating modules") . "...</h2>";
  
  if (isset($_POST['addModules'])){
    if ($updateMessage = updateModules($_POST['addModules'])){
      $body[] = $updateMessage;
      
      if ($GLOBALS['branchExists'] && UPDATE_BRANCHES)
	$body[] = updateBranchModules($_POST['addModules']);

      $body[] = "<br />All Modules Updated!";
    } else
      $body[] = "<br />No modules selected...";
  }
} else {
  $body[] = updateCore($currentVersion);
  $body[] = updateForm();
}

$body[] = "<ul>";
$body[] = "<li><a href=\"../docs/CHANGELOG.txt\">Read ChangeLog</a></li>";
$body[] = "<li><a href=\"../index.php\">Return to Site</a></li>";
$body[] = "<li><a href=\"update.php\">Return to Update</a></li>";
$body[] = "</ul>";

$body[] = "</body>
</html>";

echo implode("", $body);


/************************************************************************
* Functions
*************************************************************************/
function startpage($currentVersion){
  $content = "<html>
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
<title>".$_SESSION["translate"]->it("phpWebSite Updater")."</title>
</head>
<body>
<img src=\"poweredby.jpg\" /><br />
<h1>" . $_SESSION["translate"]->it("phpWebSite Updater");
  $coreVersion = $GLOBALS['core']->version;

  if (version_compare($currentVersion, $coreVersion) < 0)
    $content .= ": " . $_SESSION["translate"]->it("Version [var1] to [var2]", $currentVersion, $GLOBALS['core']->version);

 $content .= "</h1>";

 return $content;
}


function loginAdmin(){
  $content[] = "<h2>" . $_SESSION["translate"]->it("Welcome to the phpWebSite Updater") . "!</h2><b>" . $_SESSION["translate"]->it("Enter your Installation Password below") . "</b><br /><br />";
  $form = new EZForm;
  $form->setAction("update.php");
  $form->add("checkPass", "hidden", 1);
  $form->add("updatePass", "password");
  $template = $form->getTemplate();
  $content[] =  implode("", $template);

  if(!is_writeable("../files")) {
    $content[] = "<span style=\"color:red\">The <b>files</b> directory is not webserver writable, please fix before you continue with the upgrade.</span><br />";
  }

  if(!is_writeable("../images")) {
    $content[] = "<span style=\"color:red\">The <b>images</b> directory is not webserver writable, please fix before you continue with the upgrade.</span><br />";
  }

  return implode("", $content);
}

function checkPassword($password){
   include("../conf/config.php");
   if ($install_pw == $password)
     return TRUE;
   else
     return FALSE;
 } 

function updateBranchCore($currentVersion) {
    if ($GLOBALS['branchExists'] && $sql = $GLOBALS["core"]->sqlSelect("branch_sites")){
	foreach ($sql as $branch){
	    if (!($GLOBALS["core"]->loadDatabase(PHPWS_SOURCE_DIR . "conf/branch/". $branch["configFile"], TRUE))){
		$content[] = "<b>" . $_SESSION["translate"]->it("Unable to connect to branch database for [var1]", $branch["branchName"]) . "</b><br /><br />";
		continue;
	    }
      
	    $content[] = "<b>".$_SESSION["translate"]->it("Updating branch").": ".$branch["branchName"]."</b><br />";
	    $GLOBALS['core']->home_dir = $branch['branchDir'];

	    updateCore($currentVersion, FALSE);

	    PHPWS_File::recursiveFileCopy(PHPWS_SOURCE_DIR . "themes/", $GLOBALS['core']->home_dir . "themes/");
	    PHPWS_File::recursiveFileCopy(PHPWS_SOURCE_DIR . "admin/", $GLOBALS['core']->home_dir . "admin/");
	    PHPWS_File::recursiveFileCopy(PHPWS_SOURCE_DIR . "js/", $GLOBALS['core']->home_dir . "js/");
	    chdir(PHPWS_SOURCE_DIR);
	    
	    if(!is_dir($GLOBALS['core']->home_dir."images/"))
		PHPWS_File::makeDir($GLOBALS['core']->home_dir."images/");
	    
	    PHPWS_File::recursiveFileCopy(PHPWS_SOURCE_DIR . "images/mod/", $GLOBALS['core']->home_dir . "images/mod/");
	    PHPWS_File::recursiveFileCopy(PHPWS_SOURCE_DIR . "images/javascript/", $GLOBALS['core']->home_dir . "images/javascript/");
	    
	    if(!is_dir($GLOBALS['core']->home_dir."images/core/")) 
		PHPWS_File::makeDir($GLOBALS['core']->home_dir."images/core/");
	    
	    if(!is_dir($GLOBALS['core']->home_dir."images/core/list/"))
		PHPWS_File::makeDir($GLOBALS['core']->home_dir."images/core/list/");
	    
	    PHPWS_File::fileCopy(PHPWS_SOURCE_DIR . "images/core/list/down_pointer.png", $GLOBALS['core']->home_dir . "images/core/list/", "down_pointer.png", TRUE, TRUE);
	    PHPWS_File::fileCopy(PHPWS_SOURCE_DIR . "images/core/list/up_pointer.png", $GLOBALS['core']->home_dir . "images/core/list/", "up_pointer.png", TRUE, TRUE);
	    PHPWS_File::fileCopy(PHPWS_SOURCE_DIR . "images/core/list/sort_none.png", $GLOBALS['core']->home_dir . "images/core/list/", "sort_none.png", TRUE, TRUE);
	    
	    PHPWS_File::fileCopy(PHPWS_SOURCE_DIR . "images/info.gif", $GLOBALS['core']->home_dir . "images/", "info.gif", TRUE, FALSE);
	    PHPWS_File::fileCopy(PHPWS_SOURCE_DIR . "images/print.gif", $GLOBALS['core']->home_dir . "images/", "print.gif", TRUE, FALSE);
	    PHPWS_File::fileCopy(PHPWS_SOURCE_DIR . "images/up.gif", $GLOBALS['core']->home_dir . "images/", "up.gif", TRUE, FALSE);
	    PHPWS_File::fileCopy(PHPWS_SOURCE_DIR . "images/down.gif", $GLOBALS['core']->home_dir . "images/", "down.gif", TRUE, FALSE);

	    $content[] = "<b>".$_SESSION["translate"]->it("All files copied out to branch").": ".$branch["branchName"]."</b><br /><br />";
	}
	
	$GLOBALS['core']->home_dir = PHPWS_SOURCE_DIR;
	$GLOBALS["core"]->loadDatabase();
	return implode("", $content);
    } else
	return NULL;
}

function updateBranchModules($moduleList){
  if ($GLOBALS['branchExists'] && $sql = $GLOBALS["core"]->sqlSelect("branch_sites")){
    foreach ($sql as $branch){
      $branchConfig = PHPWS_SOURCE_DIR . "conf/branch/". $branch["configFile"];
      if (!is_file($branchConfig)){
	$content[] = "<b>" . $_SESSION["translate"]->it("Unable to load file [var1] for branch [var2]", $branchConfig, $branch['branchName']) . "</b><br /><br />";
	continue;
      }

      if (!$GLOBALS["core"]->loadDatabase($branchConfig, TRUE)){
	$content[] = "<b>" . $_SESSION["translate"]->it("Unable to connect to branch database for [var1]", $branch["branchName"]) . "</b><br /><br />";
	continue;
      }

      $content[] = "<b>".$_SESSION["translate"]->it("Updating branch modules").": " . $branch["branchName"] . "</b><br /><br />";
      updateModules($moduleList);
    }
    
    $GLOBALS["core"]->loadDatabase();
    return implode("", $content);
  } else
    return NULL;
}

function updateCore($currentVersion, $isHub=TRUE){
  include("defaultMods.php");
  $update = FALSE;

  if (version_compare($currentVersion, "0.9.1") < 0) {
    include ("core_updates/update_091.php");
    $update = TRUE;
  }
  
  if (version_compare($currentVersion, "0.9.2") < 0) {
    include ("core_updates/update_092.php");
    $update = TRUE;
  }

  if (version_compare($currentVersion, "0.9.3-1") < 0) {
    $update = TRUE;
  }

  if (version_compare($currentVersion, "0.9.3-2") < 0) {
    $update = TRUE;
  }

  if (version_compare($currentVersion, "0.9.3-3") < 0) {
    $update = TRUE;
  }

  if (version_compare($currentVersion, "0.9.3-4") < 0) {
    PHPWS_Boost::updateModule("controlpanel");
    $update = TRUE;
  }

  if (version_compare($currentVersion, "0.10.0") < 0) {
      $update = TRUE;
  }

  if (version_compare($currentVersion, '0.10.1') < 0) {
      $update = TRUE;
  }

  if ($update){
    phpws_boost::setVersionInfo(PHPWS_SOURCE_DIR . "conf/core_info.php", "update");
    $content[] = "<h2>Core Updated!</h2>";
    if ($isHub && $GLOBALS['branchExists'] && UPDATE_BRANCHES)
      $content[] = updateBranchCore($currentVersion);
  }
  else
    $content[] = "<h2>Core update not required</h2>";

  foreach ($defaultMods as $mod_title){
    if (phpws_boost::needsUpdate($mod_title))
      $coreModList[] = $mod_title;
  }

  if (isset($coreModList))
    $content[] = updateModules($coreModList);

  return implode("", $content);
}


function updateForm(){
  include("defaultMods.php");

  $modules = $GLOBALS['core']->sqlSelect("modules");

  foreach ($modules as $modInfo){
    if (phpws_boost::needsUpdate($modInfo['mod_title']))
      $mods[$modInfo['mod_title']] = $modInfo['mod_pname'];
  }

  if (!isset($mods) || !count($mods)) {
    $content  = "No mods currently require updating.<br /><br />";
    return $content;
  }

  foreach ($defaultMods as $modRemove)
    unset($mods[$modRemove]);


  $content[] = "<b>You may update the following modules:</b>";
  $form = new EZForm("modules");
  $form->add("updateModules", "hidden", 1);
  $form->setAction("update.php");
  $content[] = $form->getStart();
  $content[] = $form->get("updateModules");
  foreach($mods as $modDir=>$modpname){
    $form->add("addModules[" . $modDir . "]", "checkbox", $modDir);
    $content[] = $form->get("addModules[" . $modDir . "]") . " " . $modpname . "<br />\n";
  }
  $form->add("button", "submit", "Update Modules");
  $content[] = "<br />" . $form->get("button");
  $content[] = PHPWS_Form::formButton($_SESSION["translate"]->it("Check All"), "toggle", "CheckAll();");
  $content[] = "</form>";

  return implode("", $content);
}

function updateModules($moduleList){
  if (!isset($moduleList)){
    $content[] = "No modules selected<br />";
    return NULL;
  } else {
    foreach ($moduleList as $mod_title){
      if (PHPWS_Boost::updateModule($mod_title, TRUE, FALSE)){
	$content[] = $GLOBALS["CNT_boost"]["content"] . "<hr />";
	unset($GLOBALS["CNT_boost"]["content"]);
      }
    }
  }

  if (isset($content))
    return implode("", $content);
  else
    return NULL;
}

?>