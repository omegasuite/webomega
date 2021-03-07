<?php

require_once(PHPWS_SOURCE_DIR . "core/Form.php");
require_once(PHPWS_SOURCE_DIR . "core/File.php");
require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Array.php");

require_once(PHPWS_SOURCE_DIR . "mod/approval/class/Approval.php");
require_once(PHPWS_SOURCE_DIR.'mod/search/class/Search.php');


class PHPWS_Boost{
  function modFileExists($moduleDir){
    $modFile = PHPWS_SOURCE_DIR."mod/" . $moduleDir . "/conf/boost.php";

    if (file_exists($modFile))
      return $modFile;
    else
      return NULL;
  }

  function checkForBoostUpdate(){
    $info = $this->getVersionInfo("boost");
    include(PHPWS_SOURCE_DIR . "mod/boost/conf/boost.php");

    if ($version > $info["version"])
      return TRUE;
    else
      return FALSE;
  }


  function adminMenu(){
    $coreMods = array ("users", "approval", "help", "language", "layout", "search", "security", "fatcat", "controlpanel");
    $current_mods = $GLOBALS["core"]->listModules();
    $core_count = $non_count = 0;

    $template['CORE_VERSION_TEXT'] = $_SESSION['translate']->it("Core Version");
    $template['CORE_VERSION'] = $GLOBALS['core']->getOne("SELECT version FROM {$GLOBALS['core']->tbl_prefix}mod_boost_version WHERE mod_title='core'");
    $template['ROWS'] = NULL;

    $content = 
      "<form action=\"index.php\" method=\"post\">"
      . PHPWS_Form::formHidden(array("module"=>"boost", "boost_op"=>"update"));


    if(!is_writeable("./images/")) {
      $template["PERMISSION_WARNING"] = "<br /><span style=\"color:red\">".$_SESSION['translate']->it("Warning!")."</span><br />";
      $template["PERMISSION_WARNING"] .= "<span style=\"color:red\">".$_SESSION['translate']->it("The images and files directories must be webserver writable for upgrades, please fix and then continue.")."</span><br /><br />";
    }

    if ($this->checkForBoostUpdate()){
      $template["UPDATE_WARNING"] = $_SESSION["translate"]->it("You must update Boost before updating any other modules.");
      $template["UPDATE_BOOST"] = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Update"), "bst_update[boost]");
    } else {
      if (!($dir = PHPWS_File::readDirectory(PHPWS_SOURCE_DIR."mod/", 1)))
	exit("Error in Boost.php - adminMenu was unable to locate your modules directory.");

      sort($dir);
      foreach ($dir as $moduleDir){
        $url = NULL;
	$branch_allow = NULL;
	$version = NULL;
	$uninstall_allow = NULL;
	$mod_directory = $mod_filename = NULL;

	if ($moduleDir == "boost")
	  continue;

	$branch_block = 0;
	if ($modFile = $this->modFileExists($moduleDir)){
	  $rowTemplate = NULL;

	  include($modFile);

	  if (!isset($mod_directory))
	    $mod_directory = $mod_title;

	  if (!isset($mod_filename))
	    $mod_filename = "index.php";


	  if (isset($branch_allow) && $branch_allow === 0 && !$GLOBALS["core"]->isHub)
	    continue;

	  $rowTemplate["MOD_NAME"] = $mod_pname;

	  $rowTemplate["URL"] = '<a href="' . $url . '" target="_blank">' . $url . '</a>';

	  if (!$GLOBALS["core"]->moduleExists($mod_title)){
	    $rowTemplate["COMMAND"] = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Install"), "bst_install[$mod_directory]");
	    $rowTemplate["VERSION"] = $version;
	  }
	  else {
	    $moduleInfo = $this->getVersionInfo($mod_title);
	    $rowTemplate["VERSION"] = $moduleInfo["version"];

	    /* thanks to rhalff for tipping me off to version_compare() */
	    $test = explode(".", $moduleInfo["version"]);
	    if(sizeof($test) == 2) {
		if($moduleInfo["version"] < $version) {
		    $rowTemplate["COMMAND"] = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Update"), "bst_update[$mod_title]");
		    $allUpdates[] = $mod_title;
		} else
		    $rowTemplate["COMMAND"] = "<i>" .$_SESSION["translate"]->it("Up to Date") . "</i>";
	    } else {
		if (version_compare($version, $moduleInfo["version"]) == 1) {
		    $rowTemplate["COMMAND"] = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Update"), "bst_update[$mod_title]");
		    $allUpdates[] = $mod_title;
		} else
		    $rowTemplate["COMMAND"] = "<i>" .$_SESSION["translate"]->it("Up to Date") . "</i>";
	    }


	    if(!isset($uninstall_allow) || $uninstall_allow == 1)
	      $rowTemplate["UNINSTALL"] = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Uninstall"), "bst_uninstall[$mod_title]");
	  }

	  if (in_array($mod_title, $coreMods)){
	    $core_count++;
	    if ($core_count%2)
	      $rowTemplate["TOG1"] = " ";
	    else
	      $rowTemplate["TOG2"] = " ";

	    $coreRows[] = PHPWS_Template::processTemplate($rowTemplate, "boost", "coreRows.tpl");
	  }
	  else {
	    $non_count++;
	    if ($non_count%2)
	      $rowTemplate["TOG1"] = " ";
	    else
	      $rowTemplate["TOG2"] = " ";

	    $noncoreRows[] = PHPWS_Template::processTemplate($rowTemplate, "boost", "moduleRows.tpl");
	  }
	}
      }

      $template["CORE_MODS"] = $_SESSION["translate"]->it("Core Modules");
      $template["MOD_NAME"] = $template["NC_MOD_NAME"] = $_SESSION["translate"]->it("Module Name");
      $template["VERSION"] = $template["NC_VERSION"] = $_SESSION["translate"]->it("Version");
      $template["COMMAND"] = $template["NC_COMMAND"] = $_SESSION["translate"]->it("Install") . " / " . $_SESSION["translate"]->it("Update");
      $template["UNINSTALL"] = $template["NC_UNINSTALL"] = $_SESSION["translate"]->it("Uninstall");

      $template["CORE_ROWS"] = implode("", $coreRows);

      if(isset($noncoreRows)) {
	$template["NONCORE_ROWS"] = implode("", $noncoreRows);	
	$template["NONCORE_MODS"] = $_SESSION["translate"]->it("Other Modules");
      } else {
	unset($template["NC_MOD_NAME"]);
	unset($template["NC_VERSION"]);
	unset($template["NC_UNINSTALL"]);
	unset($template["NC_COMMAND"]);	
      }

      if (isset($allUpdates) && is_array($allUpdates))
	$template["UPDATE_ALL"] = PHPWS_Form::formSubmit($_SESSION["translate"]->it("Update All Modules"), "bst_updateAll");
    }

    $content .= PHPWS_Template::processTemplate($template, "boost", "adminMenu.tpl");
    if (isset($allUpdates) && is_array($allUpdates))
      $content .= PHPWS_Form::formHidden("allUpdates", implode(":", $allUpdates ));
    $content .= "</form>";
    PHPWS_Template::refreshTemplate("boost");
    return $content;
  }

  function checkUpdate($mod_title){
    $GLOBALS["CNT_boost"]["content"] .= $this->boostLink()."<br /><br />";

    if ($mod_title == "core")
      include(PHPWS_SOURCE_DIR . "conf/core_info.php");
    else {
      if (!($info = $GLOBALS["core"]->getModuleInfo($mod_title))){
	$GLOBALS["CNT_boost"]["content"] .= $_SESSION["translate"]->it("Unable to get information on [var1]", $mod_title);
	return;
      }
      extract($info);
    }
    extract($this->getVersionInfo($mod_title));

    $GLOBALS["CNT_boost"]["title"]   .= $_SESSION["translate"]->it("Check Update for ". $mod_pname);
    $file = PHPWS_Text::checkLink($update_link) . "update.txt";

    if (!($versionFile = @file($file))){
      $GLOBALS["CNT_boost"]["content"] .= $_SESSION["translate"]->it("Unable to read update.txt file from [var1]", PHPWS_Text::checkLink($update_link));
      return;
    }

    foreach ($versionFile as $data){
      $process = explode("::", $data);

      if ($process[0] != "download")
	$upgradeData[$process[0]] = $process[1];
      else
	$upgradeData["download"][] = $process[1];
    }

    if (!isset($upgradeData["version"])){
     $GLOBALS["CNT_boost"]["content"] .= $_SESSION["translate"]->it("The upgrade file has a syntax error").".";
     return;
    }

    $content .= "<b>" . $_SESSION["translate"]->it("Your Version") . ":</b> " . $version . "<br />";
    $content .= "<b>" . $_SESSION["translate"]->it("Current Version") . ":</b> " . $upgradeData["version"] . "<br /><br />";


    if ($upgradeData["version"] > $version){
      $content .= "<b>" . $_SESSION["translate"]->it("There is an upgrade available for this module")."!</b><br />";
      if ($upgradeData["download"])
	$content .= "<b>" . $_SESSION["translate"]->it("Click on a download link to get the most recent version")."!</b><br /><br />";
    }
    else
      $content .= "<b>" . $_SESSION["translate"]->it("This module's version appears to be current").".</b><br /><br />";

    if ($infoLink = $upgradeData["information"])
      $content .= "<a href=\"". PHPWS_Text::checkLink($infoLink)."\" target=\"_blank\">" .  $_SESSION["translate"]->it("Module Information") . "</a><br />";
    else
      $content .= $_SESSION["translate"]->it("Module Information link not provided").".<br />";

    if ($download = $upgradeData["download"]){
      $content .= "<hr /><b>" .$_SESSION["translate"]->it("Download the latest version from the following link(s)") . ":</b><br />";
      foreach ($download as $dlLinks)
	$content .= "<a href=\"". PHPWS_Text::checkLink($dlLinks)."\">" . PHPWS_Text::checkLink($dlLinks) . "</a><br />";
    } else {
      $content .= $_SESSION["translate"]->it("Download links were not provided"). "<br />";
      if ($infoLink)
	$content .= $_SESSION["translate"]->it("Please go to the Module Information link for more information"). "<br />";
    }

    $GLOBALS["CNT_boost"]["content"] .= $content;
  }


  function direct(){
    extract($_POST);
    $op_array["boost_op"] = "adminMenu";

    if (isset($bst_check)){
      list($updateMod) = each($bst_check);
      $this->checkUpdate($updateMod);
    }
    elseif (isset($bst_install)) {
      list($mod_dir) = each($bst_install);
      $modFile = $this->modFileExists($mod_dir);
      include($modFile);
      $GLOBALS["CNT_boost"]["title"] = $_SESSION["translate"]->it("Install Module") . " $mod_pname";
      $GLOBALS["CNT_boost"]["content"] .= PHPWS_Text::modulelink("Go Back", "boost", $op_array)."<br /><br />";
      $GLOBALS["CNT_boost"]["content"] .= $this->installModule($mod_dir, TRUE, TRUE, TRUE, TRUE);
    } elseif (isset($bst_uninstall)){
      list($mod_title) = each($bst_uninstall);
      $mod_dir = $GLOBALS["core"]->getModuleDir($mod_title);
      $modFile = $this->modFileExists($mod_dir);
      include($modFile);

      $GLOBALS["CNT_boost"]["title"] = $_SESSION["translate"]->it("Uninstall Module") . " $mod_pname";
      $GLOBALS["CNT_boost"]["content"] .= PHPWS_Text::modulelink("Go Back", "boost", $op_array)."<br /><br />";

      $GLOBALS["CNT_boost"]["content"] .= $_SESSION["translate"]->it("Are you sure you want to uninstall this module") . "?<br /><br />";
      $GLOBALS["CNT_boost"]["content"] .= PHPWS_Text::moduleLink($_SESSION["translate"]->it("Yes"), "boost", array("boost_op"=>"uninstallModule", "killMod"=>$mod_title)) . " ";
      $GLOBALS["CNT_boost"]["content"] .= PHPWS_Text::moduleLink($_SESSION["translate"]->it("No"), "boost", array("boost_op"=>"adminMenu"));

    } elseif (isset($bst_update)){
      list($mod_title) = each($bst_update);
      $GLOBALS["CNT_boost"]["content"] .= $this->boostLink()."<br /><br />";
      $this->updateModule($mod_title, TRUE, TRUE);
    } elseif ($bst_updateAll){
      $this->updateAll(TRUE);
    }
  }


  function updateAll($branchUpdate=FALSE){
    if (!$_POST["allUpdates"])
      $this->adminMenu();

    $updateList = explode(":", $_POST["allUpdates"]);

    foreach ($updateList as $mod_title){
      $this->updateModule($mod_title, $branchUpdate, TRUE);
      $GLOBALS["CNT_boost"]["content"] .= "<hr />\n";

    }
  }

  function updateModule($mod_title, $branchUpdate=NULL, $directLink=FALSE){
    require_once(PHPWS_SOURCE_DIR . "mod/controlpanel/class/ControlPanel.php");
    if ($GLOBALS['core']->moduleExists("branch"))
      require_once(PHPWS_SOURCE_DIR . "mod/branch/class/Branch.php");
    if (!isset($GLOBALS["CNT_boost"]["content"]))
      $GLOBALS["CNT_boost"]["content"] = NULL;
    $content = NULL;
    if ($mod_title == "core")
      $modFile = PHPWS_SOURCE_DIR . "conf/core_info.php";
    else {
      if (!($module = $GLOBALS["core"]->getModuleInfo($mod_title))){
	$GLOBALS["CNT_boost"]["content"] .= $_SESSION["translate"]->it("Unable to get module information on [var1]", $mod_title);
	return;
      }
      extract($module);
      $modFile = PHPWS_SOURCE_DIR . "mod/$mod_directory/conf/boost.php";

    }

    if (file_exists($modFile))
      include($modFile);
    else
      exit("Error: Missing module information file for $mod_pname.");

    if (!isset($mod_directory))
      $mod_directory = $mod_title;

    if (!isset($mod_filename))
      $mod_filename = "index.php";

    $versionInfo = PHPWS_Boost::getVersionInfo($mod_title);

    if (!$versionInfo){
      return NULL;
      // Used to auto install which wasn't really wanted by others
      // return phpws_boost::installModule($mod_directory, TRUE, TRUE, TRUE, TRUE);
    }

    extract ($versionInfo);

    $GLOBALS["CNT_boost"]["title"] = $_SESSION["translate"]->it("Update Module");
    $GLOBALS["CNT_boost"]["content"] .= "<b>" . $_SESSION["translate"]->it("Updating") . " $mod_pname</b><br />";

    if ($mod_title == "core")
      $updateFile = PHPWS_SOURCE_DIR . "boost/update.php";
    else {
      $updateFile = PHPWS_SOURCE_DIR . "mod/" . $mod_directory . "/boost/update.php";
    }

    $currentVersion = $version;

    if (!file_exists($updateFile)){
      $GLOBALS["CNT_boost"]["content"] .= $_SESSION["translate"]->it("Unable to locate update file for [var1]", $mod_title) . ".<br />";
      $GLOBALS["CNT_boost"]["content"] .= $_SESSION["translate"]->it("Assuming update needed registering only") . ".";
      $status = 1;
    } else 
      include($updateFile);

    if (!$status){
      $GLOBALS["CNT_boost"]["content"] .= "<span class=\"errortext\"><b>" . $_SESSION["translate"]->it("Some errors occurred while trying to update [var1]", $mod_pname).".</b></span>";
      return FALSE;
    } else {
      $GLOBALS["CNT_boost"]["content"] .= $content . "<br />";
      $GLOBALS["CNT_boost"]["content"] .= "<b>" . $_SESSION["translate"]->it("[var1] updated successfully", $mod_pname)."!</b><br />";


      $linkFile = PHPWS_SOURCE_DIR . "mod/$mod_directory/conf/controlpanel.php";
      if (is_file($linkFile)){
	include ($linkFile);
	if (isset($link)) {
	  foreach ($link as $modLink){
	    PHPWS_ControlPanel::drop($mod_title);
	    PHPWS_ControlPanel::import($mod_title);
	    if (isset($modLink['admin']) && (bool)$modLink['admin'] == TRUE)
	      $url = $modLink['url'];
	  }
	  if(isset($url) && $directLink == TRUE)
	    $GLOBALS["CNT_boost"]["content"] .= "<br /><a href=\"" . $url . "\">Go to Module</a>";
	}
      }


      PHPWS_Boost::setModuleInfo($modFile, "update");
      PHPWS_Boost::setVersionInfo($modFile, "update");

      if (class_exists('PHPWS_Branch') && $branchUpdate){
	PHPWS_Branch::updateBranches($mod_title);
      }

      return TRUE;
    }
  }

  function boostLink(){
    return PHPWS_Text::moduleLink("Back", "boost", array("boost_op"=>"adminMenu"));
  }


  function installModule($moduleDir, $regLayout=FALSE, $regLang=FALSE, $regMenu=FALSE, $directLink=FALSE){
    require_once(PHPWS_SOURCE_DIR . "mod/controlpanel/class/ControlPanel.php");
    $dependList = $content = NULL;
    if (strcasecmp(get_class($GLOBALS["core"]), "PHPWS_Core") != 0)
      exit("Error: Boost.php - installModules : Invalid DB connect object received");

    $installFile = PHPWS_SOURCE_DIR . "mod/" . $moduleDir . "/boost/install.php";

    if (!($modFile = PHPWS_Boost::modFileExists($moduleDir)))
      return $_SESSION["translate"]->it("Module Information file missing in") . " $moduleDir<br />";

    include($modFile);

    if (!isset($mod_directory))
      $mod_directory = $mod_title;

    if (!isset($mod_filename))
      $mod_filename = "index.php";


    if ($GLOBALS["core"]->moduleExists($modFile))
      return $_SESSION["translate"]->it("Module is already registered") . ".<br />";


    if (isset($depend) && is_array($depend)){
      foreach ($depend as $dependMod){
	if (!$GLOBALS["core"]->moduleExists($dependMod)){
	  $dependList .= "<li>$dependMod</li>";
	  $dependError = 1;
	}
      }
    }

    if (isset($dependError))
      return $_SESSION["translate"]->it("This module cannot install until the following modules are installed") . ":<ul>$dependList</ul>";

    if (!file_exists($installFile)){
      $content .= "<b>".$_SESSION["translate"]->it("Installation file missing for [var1]", $mod_pname) . ".</b> ";
      $content .= "<b>".$_SESSION["translate"]->it("Assuming it is not needed") . ".</b><br /><br />";
      $status = 1;
    }
    else
      include($installFile);

    if ($status){
      if ($regLang){
	if ($langContent = PHPWS_Language::installLanguages($moduleDir))
	  $content .= $langContent . "<br />";
	$langContent = NULL;
      }

      // register with search if contains conf file
      PHPWS_Search::register($mod_title);

      // register with help if module doesn't have postInstall file
      $postFile = PHPWS_SOURCE_DIR . "mod/" . $moduleDir . "/boost/postinstall.php";
      if (!file_exists($postFile))
	CLS_help::setup_help($mod_title);

      $content .= "<b>***** " . $_SESSION["translate"]->it("[var1] installation successful", $mod_pname) . "! *****</b><br /><br />";
      PHPWS_Boost::setModuleInfo($modFile, "insert");

      if($regMenu == TRUE)
	PHPWS_ControlPanel::import($mod_title);

      if ($regLayout)
	$_SESSION["OBJ_layout"]->installModule($mod_title);

      PHPWS_Boost::setVersionInfo($modFile);
      if ($directLink) {
	$linkFile = PHPWS_SOURCE_DIR . "mod/$mod_directory/conf/controlpanel.php";
	if (is_file($linkFile)){
	  include ($linkFile);
	  if (!isset($link))
	    break;
	  
	  foreach ($link as $modLink){
	    if (isset($modLink['admin']) && (bool)$modLink['admin'] == TRUE){
	      $url = $modLink['url'];
	      break;
	    }
	  }
	  if(isset($url)) {
	    $content .= "<a href=\"" . $url . "\">Go to Module</a>";
	  }
	}
      }
    }
    else
      $content .= "<span style=\"color: #ff0000;\"><b>" . $_SESSION["translate"]->it("[var1] installation NOT successful", $mod_pname) . "!</b></span><br />";

    return $content;
   
  }

  function setVersionInfo($modFile, $process="insert"){
    if (file_exists($modFile))
      include($modFile);
    else
      return;

    if ($process == "remove")
      return $GLOBALS["core"]->sqlDelete("mod_boost_version", "mod_title", $mod_title);
    
    $sql["mod_title"] = $mod_title;
    $sql["version"] = $version;

    //    $sql["update_link"] = $update_link;
    if (isset($branch_allow))
      $sql["branch_allow"] = $branch_allow;
    else
      $sql["branch_allow"] = 1;

    PHPWS_Array::dropNulls($sql);

    /* if an update get the current version */
    if($process == "update") {
      $version = $GLOBALS['core']->getOne("SELECT version FROM {$GLOBALS['core']->tbl_prefix}mod_boost_version WHERE mod_title='{$mod_title}'");

      /* if no current version exists then make sure it is inserted */
      if(!isset($version)) {
	$process = "insert";
      } 
   }

    if ($process == "insert")
      return $GLOBALS["core"]->sqlInsert($sql, "mod_boost_version", 1);
    elseif ($process == "update")
      return $GLOBALS["core"]->sqlUpdate($sql, "mod_boost_version", "mod_title", $mod_title);
    else 
      return $sql;

  }


  function uninstallModule($moduleDir, $regLayout=FALSE, $regLang=FALSE, $regMenu=TRUE){
    require_once(PHPWS_SOURCE_DIR . "mod/controlpanel/class/ControlPanel.php");
    $content = NULL;
    if (strcasecmp(get_class($GLOBALS["core"]), "PHPWS_Core") != 0)
      exit("Error: Boost.php - installModules : Invalid DB connect object received");

    $uninstallFile = PHPWS_SOURCE_DIR . "mod/" . $moduleDir . "/boost/uninstall.php";

    if (!($modFile = PHPWS_Boost::modFileExists($moduleDir))){
      $content .= $_SESSION["translate"]->it("Module Information file missing in") . " $moduleDir<br />";
      return $content;
    }

    include($modFile);

    if (!file_exists($uninstallFile)){
      $content .= "<b>".$_SESSION["translate"]->it("Uninstallation file missing for [var1]", $mod_pname) . ".</b> ";
      $content .= "<b>".$_SESSION["translate"]->it("Assuming it is not needed") . ".</b> <br /><br />";
      $status = 1;
    }
    else
      include($uninstallFile);

    if ($status){
      if ($regLayout)
	$content .= PHPWS_Layout::uninstallBoxStyle($moduleDir) . "<br />";

      if ($regLang)
	$content .= PHPWS_Language::uninstallLanguages($moduleDir) . "<br />";

      if ($regMenu)
	PHPWS_ControlPanel::drop($mod_title);

      PHPWS_Approval::remove(NULL, $mod_title);
      CLS_help::uninstall_help($mod_title);
      PHPWS_Search::unregister($mod_title);
      PHPWS_Boost::setVersionInfo($modFile, "remove");
      PHPWS_Fatcat::purge(NULL, $mod_title);

      $content .= "<b>" . $_SESSION["translate"]->it("[var1] uninstallation successful", $mod_pname) . "!</b><br />";
      PHPWS_Boost::setModuleInfo($modFile, "remove");
    }
    else
      $content .= "<span style=\"color: #ff0000;\"><b>" . $_SESSION["translate"]->it("[var1] uninstallation NOT successful", $mod_pname) . "!</b></span><br />";

    return $content;
   
  }


  function postInstall($defaultMods){
    $content = NULL;

    if (!is_array($defaultMods))
      exit("Error in Boost.php - postInstall requires an array of default modules.");

    foreach ($defaultMods as $moduleDir){
      $postDir = PHPWS_SOURCE_DIR . "mod/" . $moduleDir . "/boost/postinstall.php";

      if (!($modFile = PHPWS_Boost::modFileExists($moduleDir))){
	$content .= $_SESSION["translate"]->it("Module Information file missing in") . " $moduleDir<br />";
	return;
      }

      include($modFile);

      if (file_exists($postDir)){
	include($postDir);

	if ($status){
	  $content .= "<b>" . $_SESSION["translate"]->it("[var1] post-installation successful", $mod_pname) . "!</b><br /><br />";
	  PHPWS_Boost::setModuleInfo($modFile, "insert");

	}
	else
	  $content .= "<span style=\"color: #ff0000;\"><b>" . $_SESSION["translate"]->it("[var1] post-installation NOT successful", $mod_pname) . "!</b></span><br />";
      }
      PHPWS_ControlPanel::import($mod_title);
    }
    
    return $content;
  }

  function installModuleList($defaultMods, $regLayout=FALSE, $regLang=FALSE, $regMenu=FALSE){
    $content = NULL;
    if (!is_array($defaultMods))
      exit("Error in Boost.php - installModuleList requires an array of default modules.");

    if (strcasecmp(get_class($GLOBALS["core"]), "PHPWS_Core") != 0)
      exit("Error: Boost.php - installModuleList : Invalid DB connect object received");

    $set_time_limit = true;
    $safe_mode = ini_get('safe_mode');
    if($safe_mode == 1) {
      $set_time_limit = false;
    }

    foreach ($defaultMods as $mod_dir){
      if($set_time_limit) set_time_limit(60);
      $content[] = PHPWS_Boost::installModule($mod_dir, $regLayout, $regLang, $regMenu);
      flush();
    }
    return implode("", $content);
  }

  function getVersionInfo($mod_title){
    if (!($row = $GLOBALS["core"]->sqlSelect("mod_boost_version", "mod_title", $mod_title)))
      return FALSE;

    return $row[0];
  }


  function needsUpdate($mod_title){
    $modDirectory = $GLOBALS['core']->getModuleDir($mod_title);

    if ($modDirectory && is_file(PHPWS_SOURCE_DIR . "mod/$modDirectory/conf/boost.php"))
      include (PHPWS_SOURCE_DIR . "mod/$modDirectory/conf/boost.php");
    if (!isset($version))
      return;

    $versionInfo = phpws_boost::getVersionInfo($mod_title);

    if ($versionInfo){
	$test = explode(".", $versionInfo["version"]);
	if(sizeof($test) == 2) {
	    if ($version > $versionInfo['version'])
		return TRUE;
	    else
		return FALSE;
	} else {
	    if (version_compare($version, $versionInfo["version"]) == 1) {
		return true;
	    } else {
		return false;
	    }
	}
    } else
	return TRUE;
  }

  /**
   * Updates or creates the module information from an imported file.
   *
   * If the process = "insert", a new module is registered.
   * If the process = "update", a current module is updated.
   * If the process is not sent, then the settings array is returned.
   *
   * @author   Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @modified Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @param    string $info_dir File address of the module information file
   * @param    string $process  Determines whether to update or insert the database
   * @return   array  $sql      Array of the module registration settings
   * @access   public
   */
  function setModuleInfo($info_dir, $process=NULL){
    if (file_exists($info_dir))
      include($info_dir);
    else {
      echo "Warning: unable to find file $info_dir<br />";
      return NULL;
    }

    if ($process == "remove")
      return $GLOBALS['core']->sqlDelete("modules", "mod_title", $mod_title);

    $sql["mod_title"] = $mod_title;
    $sql["mod_pname"] = $mod_pname;

    if (!isset($mod_directory))
      $mod_directory = $mod_title;

    if (!isset($mod_filename))
      $mod_filename = "index.php";

    $sql["mod_directory"] = $mod_directory;
    $sql["mod_filename"] = $mod_filename;

    if (!$allow_view)
      $allow_view = array($mod_title=>1);

    $sql["allow_view"] = $allow_view;

    if (isset($priority) && is_numeric($priority))
      $sql["priority"] = $priority;
    else
      $sql["priority"] = 50;

    if (isset($user_mod))
      $sql["user_mod"] = $user_mod;

    if (isset($admin_mod))
      $sql["admin_mod"] = $admin_mod;

    if (isset($deity_mod))
      $sql["deity_mod"] = $deity_mod;

    if (isset($mod_class_files))
      $sql["mod_class_files"] = $mod_class_files;

    if (isset($mod_sessions))
      $sql["mod_sessions"] = $mod_sessions;

    if (isset($init_object))
      $sql["init_object"] = $init_object;

    $sql["active"] = $active;
    
    PHPWS_Array::dropNulls($sql);
    
    if ($process == "insert")
      return $GLOBALS['core']->sqlInsert($sql, "modules", 1);
    elseif ($process == "update")
      return $GLOBALS['core']->sqlUpdate($sql, "modules", "mod_title", $mod_title);
    else 
      return $sql;
  }// END FUNC setModuleInfo()

}

?>