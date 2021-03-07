<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/File.php');

/**
 * PHPWS_ControlPanel
 *
 * Main control stucture to link all the administration features 
 * of phpWebSite together
 */

require_once(PHPWS_SOURCE_DIR . "mod/controlpanel/class/Tab.php");

class PHPWS_ControlPanel {

  /**
   * All of the tabs for the control panel
   *
   * @var     array
   * @access  private
   * @example $this->_tabs[1] = "Site Content";
   */
  var $_tabs = array();

  /**
   * Stores the id of the current active tab
   *
   * @var     integer
   * @access  private
   * @example $this->_currentTab = 2;
   */
  var $_currentTab = NULL;

  /**
   * Constructor for the control panel
   *
   * Initializes the array of tabs for this control panel
   * @access public
   */
  function PHPWS_ControlPanel() {
    $sql = "SELECT id FROM " . PHPWS_TBL_PREFIX . "mod_controlpanel_tab ORDER BY taborder";

    $tabResult = $GLOBALS['core']->query($sql, FALSE, TRUE);
    if($tabResult) {
      $default = TRUE;
      while($tab = $tabResult->fetchrow(DB_FETCHMODE_ASSOC)) {
	$tabObject = new PHPWS_ControlPanel_Tab($tab['id']);

	/* only add to to tabs array if it contains links for the user */
	if(!$tabObject->isEmpty()) {
	  $id = $tabObject->getId();
	  $title = $tabObject->getTitle();
	  $this->_tabs[$id] = $title;

	  /* set the current tab to the first tab */
	  if($default) {
	    $this->_currentTab = $id;
	    $default = FALSE;
	  }
	}
      }
    }
  } // END FUNC PHPWS_ControlPanel()

  /**
   * Display for the control panel
   *
   * Displays this control panel to the user
   * @access public
   * @return TRUE on success and FALSE on failure
   */
  function display() {
    if(!$_SESSION["OBJ_user"]->getUserId()) {
      header("Location: index.php");
      exit();
    }

    /* set the current tab if there is an ID being passed */
    if(isset($_REQUEST['CP_TAB']) && is_numeric($_REQUEST['CP_TAB'])) {
      $this->_currentTab = $_REQUEST['CP_TAB'];
    }

    if(is_array($this->_tabs) && (sizeof($this->_tabs) > 0)) {
      foreach($this->_tabs as $id => $title) {
	/* checking to see what was the current tab clicked */
	if($this->_currentTab == $id) {
	  $tabTags['TITLE'] = $title;

	  $panelTags['TABS'][] = PHPWS_Template::processTemplate($tabTags, "controlpanel", "tab/active.tpl");
	} else {
	  /* only create the link if it is not the current tab */
	  $tabTags['HREF'] = "./index.php?module=controlpanel&amp;CP_TAB=" . $id;
	  $tabTags['TITLE'] = $title;

	  $panelTags['TABS'][] = PHPWS_Template::processTemplate($tabTags, "controlpanel", "tab/inactive.tpl");
	}
      }

      $tab = new PHPWS_ControlPanel_Tab($this->_currentTab);

      $panelTags['TABS'] = implode("", $panelTags['TABS']);
      $panelTags['LINKS'] = $tab->getTab();

      $GLOBALS['CNT_controlpanel']['content'] = PHPWS_Template::processTemplate($panelTags, "controlpanel", "panel.tpl");

      return TRUE;
    } else {
      return FALSE;
    }
  }// END FUNC display()

  /**
   * Set the currentTab private variable
   *
   * @access public
   * @param  integer $tab the of the tab to make current
   * @return boolean TRUE on success and FALSE on failure
   */
  function setCurrentTab($CP_TAB = NULL) {
    if(isset($CP_TAB) && is_int($CP_TAB)) {
      $this->_currentTab = $CP_TAB;
      return TRUE;
    } else {
      return FALSE;
    }
  } // END FUNC setCurrentTab()

  /**
   * Get the currentTab private variable
   *
   * @access public
   * @return integer the id of the current tab
   */
  function getCurrentTab() {
    return $this->_currentTab;
  } // END FUNC getCurrentTab()

  function import($module){
    if(!$modInfo = $GLOBALS['core']->getModuleInfo($module)){
      $message = $_SESSION["translate"]->it("The requested module does not exist.");
      return new PHPWS_Error("controlpanel", "PHPWS_ControlPanel::import", $message);
    }

    $file = PHPWS_SOURCE_DIR . "mod/" . $modInfo["mod_directory"] . "/conf/controlpanel.php";
    $boostFile = PHPWS_SOURCE_DIR . "mod/" . $modInfo["mod_directory"] . "/conf/boost.php";

    if (is_file($file))
      include($file);
    elseif (is_file($boostFile)){
      include ($boostFile);
      if (isset($admin_mod) || isset($user_mod)){
	$link = new PHPWS_ControlPanel_Link;
	$link->setLabel($mod_pname);
	$link->setModule($mod_title);
	
	if (isset($admin_mod) && (bool)$admin_mod == TRUE){
	  $link->setURL("index.php?module=" . $mod_title . $admin_op);
	  
	  if (isset($mod_icon)) {
	    $link->setImage(array("name"=>$mod_icon, "alt"=>$mod_pname));
	    PHPWS_File::fileCopy(PHPWS_SOURCE_DIR."mod/{$modInfo['mod_directory']}/img/{$mod_icon}", $GLOBALS['core']->home_dir."/images/mod/controlpanel/", $mod_icon, false, false);
	  }

	  $link->setAdmin(TRUE);
	  
	  $tab = new PHPWS_ControlPanel_Tab;
	  if (isset($deity_mod) && (bool)$deity_mod == TRUE)
	    $tab->load("administration");
	  else
	    $tab->load("content");

	  $link->setTab($tab->getId());

	  $result = $link->save();
	  if (PHPWS_Error::isError($result))
	    echo $result->message;
	}
      }
	
      if (isset($user_mod) && (bool)$user_mod == TRUE){
	$link->setURL("index.php?module=" . $mod_title . $user_op);
	if (isset($user_icon)) {
	  $link->setImage(array("name"=>$user_icon, "alt"=>$mod_pname));
	  PHPWS_File::fileCopy(PHPWS_SOURCE_DIR."mod/{$modInfo['mod_directory']}/img/{$user_icon}", $GLOBALS['core']->home_dir."/images/mod/controlpanel/", $user_icon, false, false);
	}
	$link->setAdmin(FALSE);
	
	$tab = new PHPWS_ControlPanel_Tab;
	$tab->load("my_settings");
	
	$link->setTab($tab->getId());
	$result = $link->save();
	if (PHPWS_Error::isError($result))
	  echo $result->message;
      }
    }
    else
      return FALSE;
    
    if (isset($tab) && is_array($tab)){
      foreach($tab as $tabData){
	if(PHPWS_ControlPanel_Tab::tabExists($tabData['label']))
	  continue;
	$newtab = new PHPWS_ControlPanel_Tab();
	$newtab->setLabel($tabData['label']);
	$newtab->setTitle($tabData['title']);
	if (isset($tabData['grid']))
	    $newtab->setGrid($tabData['grid']);
	$newtab->save();
      }
    }

    if (isset($link) && is_array($link)){
      foreach($link as $linkData){
	$newlink = new PHPWS_ControlPanel_Link();
	$newlink->setLabel($linkData['label']);
	$newlink->setModule($linkData['module']);
	$newlink->setURL($linkData['url']);

	if(isset($linkData['admin'])) {
	  $newlink->setAdmin($linkData['admin']);
	}

	if (isset($linkData['description']))
	    $newlink->setDescription(str_replace("\n", "", $linkData['description']));

	if (is_array($linkData['image'])) {
	  $result = $newlink->setImage($linkData['image']);
	  PHPWS_File::fileCopy(PHPWS_SOURCE_DIR."mod/{$modInfo['mod_directory']}/img/{$linkData['image']['name']}", $GLOBALS['core']->home_dir."images/mod/controlpanel/", "{$linkData['image']['name']}", false, false);

	  if (PHPWS_Error::isError($result))
	    echo $result->_message;
	}

	if (isset($linkData['tab']) && PHPWS_ControlPanel_Tab::tabExists($linkData['tab'])){
	  $tab = new PHPWS_ControlPanel_Tab();
	  $tab->load($linkData['tab']);

	  $newlink->setTab($tab->getId());
	  $newlink->save();
	}
      }
    }

    if (isset($_SESSION['PHPWS_ControlPanel']))
      PHPWS_Core::killSession("PHPWS_ControlPanel");    
    
    return TRUE;
  } // END FUNC import()
  
  function drop($module){
    $sql = "DELETE FROM {$GLOBALS['core']->tbl_prefix}mod_controlpanel_link WHERE module='{$module}'";
    $GLOBALS['core']->query($sql);
    
    if (isset($_SESSION['PHPWS_ControlPanel']))
      PHPWS_Core::killSession("PHPWS_ControlPanel");
  }
  
} // END CLASS PHPWS_ControlPanel

?>