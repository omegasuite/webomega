<?php

require_once (PHPWS_SOURCE_DIR . "core/Text.php");

require_once (PHPWS_SOURCE_DIR . "core/Array.php");

require_once(PHPWS_SOURCE_DIR ."mod/help/class/CLS_help.php");

require_once(PHPWS_SOURCE_DIR ."mod/fatcat/conf/config.php");
require_once(PHPWS_SOURCE_DIR ."mod/fatcat/class/Category.php");
require_once(PHPWS_SOURCE_DIR ."mod/fatcat/class/Elements.php");
require_once(PHPWS_SOURCE_DIR ."mod/fatcat/class/Forms.php");

class PHPWS_Fatcat extends PHPWS_Fatcat_Forms{
  var $categories;
  var $settings;

  function PHPWS_fatcat(){
    $this->settings = $this->getSettings();
    if ($this->settings["defaultIcon"]){
      $defaultIcon = explode(":", $this->settings["defaultIcon"]);
      $icon["name"] = $defaultIcon[0];
      $icon["width"] = $defaultIcon[1];
      $icon["height"] = $defaultIcon[2];
      $this->settings["defaultIcon"] = $icon;
    }
  }

  /**
   * Removes module elements
   *
   * For use by developers who are deleting something from their module.
   * Just pass the same id. The module_title will be pulled from the core.
   * If a module id is not sent ALL refrences to that module will be removed.
   * If it errors, then send the mod_title to the second parameter
   *
   * @author                         Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param   integer  module_id     The module's element id
   * @param   string   module_title  The name of the module
   * @return  boolean                Result of attepted delete
   */
  function purge($module_id=NULL, $module_title=NULL){
    if (!PHPWS_Text::isValidInput($module_title))
      $module_title = $GLOBALS["core"]->current_mod;
    return PHPWS_Fatcat::deleteModuleElements($module_title, $module_id);
  }

  function getCatId($title){
    if ($this->categories){
      foreach ($this->categories as $cat_id=>$info){

	if ($info->title == $title)
	  return $cat_id;
      }
      return NULL;
    } else {
      if (!($row = $GLOBALS["core"]->sqlSelect("mod_fatcat_categories", "title", $title)))
	return NULL;

      return $row[0]["cat_id"];
    }
  }

  /**
   * Returns an array listing the elements in your module
   *
   * The array is composed as follows:
   * 
   * Main index
   * ['category_id'] -
   *                 |
   *                [id] = array of ids for your module
   *                 |
   *                [title] = Title of the category
   *
   * @author Matthew McNaney <matt@NOSPAM@tux.appstate.edu>                
   *
   */
  function listModuleElements($mod_title=NULL, $cat_id=NULL, $hideNull=TRUE){
    if (!$GLOBALS['core']->moduleExists($mod_title))
      if(!$mod_title = $GLOBALS["core"]->current_mod)
	return NULL;


    $elements = PHPWS_Fatcat_Elements::getModuleElements($mod_title, NULL, $cat_id);
    $categories = PHPWS_Fatcat::getCategoryList();

    foreach ($categories as $cat_id=>$title)
      $catList[$cat_id]['title'] = $title;

    foreach ($elements as $info)
      $catList[$info['cat_id']]['id'][] = $info;

    if ($hideNull == TRUE){
      foreach ($catList as $cat_id=>$elementArray)
	if (!isset($elementArray['id']))
	  unset($catList[$cat_id]);
    }

    return $catList;
  }

  /**
   * Prints the What's Related box
   *
   * The module_title will be derived by the core but it can be 
   * sent if their is a malfunction with your module.
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param   integer  module_id     The module's element id
   * @param   string   module_title  The name of the module
   */
  function whatsRelated($module_id, $module_title = NULL){
    if ($this->settings["relatedEnabled"] == 0) return;
	if (!PHPWS_Text::isValidInput($module_title))
      $module_title = $GLOBALS["core"]->current_mod;

    if (!($row = $this->getModuleElements($module_title, $module_id)))
      return NULL;

    foreach ($row as $get_element){
      if ($get_element['cat_id'] == 0)
	continue;

      $element_id[] = $get_element["element_id"];
    }

    if(isset($element_id)) {
      if ($content = $this->showWhatsRelated($element_id)){
	$GLOBALS["CNT_related"]["title"] = $_SESSION["translate"]->it("What's Related");
	$GLOBALS["CNT_related"]["content"] = $content;
      }
    }
  }

  function getCategoryList(){

    $cat = new PHPWS_Fatcat;
    $cat->loadCategories();

    $family = $cat->buildTree();

    $children = $cat->familyOption($family);

    if (count($children))
      natcasesort($children);
    
    return $children;
  }
  
  /**
   * Returns a listing of categories associated to your module
   *
   * The module_title will be derived by the core but it can be 
   * sent if their is a malfunction with your module.
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param   integer  module_id     The module's element id
   * @param   string   module_title  The name of the module
   */

  function fatcatLinks($module_id, $module_title = NULL){
    if (!PHPWS_Text::isValidInput($module_title))
      $module_title = $GLOBALS["core"]->current_mod;

    if (!($row = $this->getModuleElements($module_title, $module_id)))
      return NULL;
    $list = NULL;
    foreach ($row as $element){
      $links = $this->createCategoryLinks($this->getElementCatId($element["element_id"]), $module_title);
      foreach ($links as $link)
	$list .= $link."<br />";
    }

    return $list;
  }

  function setImageInfo($imageName, $type){
    $imageDir = PHPWS_HOME_DIR . "images/fatcat/$type/$imageName";

    $size = getimagesize($imageDir);
    $image["name"] = $imageName;
    $image["width"] = $size[0];
    $image["height"] = $size[1];

    return $image;
  }

  function getSettings(){
    $row = $GLOBALS["core"]->sqlSelect("mod_fatcat_settings");
    return $row[0];
  }


  function linkToAdmin(){
    return PHPWS_Text::moduleLink($_SESSION["translate"]->it("Admin Menu"), "fatcat", array("fatcat[admin]"=>"menu")); 
  }

  function printError(){
    $content = NULL;
    if (isset($this->error)){
      foreach ($this->error as $errorMessage)
	$content .= "<span class=\"errortext\">$errorMessage</span><br />";
      unset($this->error);
      return $content;
    }
    return NULL;
  }


  function savePic($postVar, $image_directory, $widthLimit=NULL, $heightLimit=NULL){
    if (!($filename = $_FILES[$postVar]["name"]))
      return FALSE;
    
    if ($filename){
      include(PHPWS_SOURCE_DIR.'conf/allowedImageTypes.php');
      if (!in_array($_FILES[$postVar]["type"], $allowedImageTypes)){
	$fileTypes = implode(", ", $allowedImageTypes);
	$this->error[] = $_SESSION["translate"]->it("You may only submit [var1] files", $fileTypes).".";
	return FALSE;
      }
      
      $tmp_file = $_FILES[$postVar]["tmp_name"];
      $file = $image_directory . $filename;
      
      if (move_uploaded_file($tmp_file, $file)){
	chmod($file, 0644);
      } else
	return FALSE;
    } else {
      return FALSE;
    }
    
    $imageSize = getimagesize($file);
    
    if ($widthLimit && ($imageSize[0] > $widthLimit)){
      @unlink($file);
      $this->error[] = $_SESSION["translate"]->it("Submitted image was too wide").".";
      return FALSE;
    }
    
    if ($heightLimit && ($imageSize[1] > $heightLimit)){
      @unlink($file);
      $this->error[] = $_SESSION["translate"]->it("Submitted image was too wide").".";
      return FALSE;
    }
    
    $image["name"] = $filename;
    $image["width"] = $imageSize[0];
    $image["height"] = $imageSize[1];
    return $image;
  }

  function getModuleElementList($cat_id, $module_title){
    $fatcount = 0;
    $template['ELEMENTS'] = NULL;

    if (!$GLOBALS["core"]->moduleExists($module_title))
      $GLOBALS["CNT_fatcat"] = "Error getModuleElementList: $module_title not installed.";

    if (!($modInfo = $GLOBALS["core"]->getModuleInfo($module_title)))
      $GLOBALS["CNT_fatcat"] = "Error getModuleElementList: $module_title not installed.";

    $confFile = PHPWS_SOURCE_DIR . "mod/" . $modInfo["mod_directory"] . "/conf/fatcat.php";

    if (file_exists($confFile)){
      include($confFile);
      if (!class_exists($className))
	return "Error getModuleElementList: Class $className does not exist.";
    } else
      $useDefault = 1;

    if (!($idList = $GLOBALS["core"]->getCol("select module_id from mod_fatcat_elements where cat_id=$cat_id and module_title='$module_title' and active=1 order by rating desc, created desc", TRUE)))
      return NULL;

    $pageIds = PHPWS_Array::paginateDataArray($idList, "index.php?".htmlentities($_SERVER["QUERY_STRING"], ENT_NOQUOTES), 10, TRUE, NULL, NULL, 20, TRUE);

    foreach ($pageIds[0] as $id){
      $fatList = NULL;
      if (isset($useDefault))
	$fatList["ELEMENT"] = PHPWS_Fatcat_Elements::getElement($id, $cat_id, $module_title);
      else
	$fatList["ELEMENT"] = call_user_func(array($className, $methodName), $id);

      if ($fatcount%2)
	$fatList["TOGGLE"] = " ";
      $fatcount++; 
      $template["ELEMENTS"] .= PHPWS_Template::processTemplate($fatList, "fatcat", "genericElement.tpl");   
    }

    $template["TITLE"] = $modInfo["mod_pname"];
    $template["DIRECTORY"] = $pageIds[1];
    $template["NUMBER"] = $pageIds[2];

    $data = PHPWS_Template::processTemplate($template, "fatcat", "genericList.tpl");
    return $data;
  }

}

?>