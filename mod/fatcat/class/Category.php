<?php

require_once (PHPWS_SOURCE_DIR . "core/Text.php");

class PHPWS_Fatcat_Category {

  var $cat_id;
  var $title;
  var $description;
  var $template;
  var $image;
  var $icon;
  var $parent;
  var $children;
  var $error;

  function PHPWS_Fatcat_Category($cat_id=NULL){
    if (isset($cat_id))
      $this->initCategory($cat_id);
  }

  /**
   * Loads category information into the object
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   */
  function initCategory($cat_id){
    if (isset($cat_id)){
      if (!($row = $GLOBALS["core"]->sqlSelect("mod_fatcat_categories", "cat_id", $cat_id)))
	return FALSE;
      
      $this->cat_id      = $cat_id;
      $this->title       = stripslashes($row[0]["title"]);
      $this->description = stripslashes($row[0]["description"]);
      $this->template    = $row[0]["template"];

      if (!empty($row[0]["image"])){
	$image = explode(":", $row[0]["image"]);
	$this->image["name"]   = $image[0];
	$this->image["width"]  = $image[1];
	$this->image["height"] = $image[2];
      } else
	$this->image = NULL;

      if (!empty($row[0]["icon"])){
	$icon = explode(":", $row[0]["icon"]);
	$this->icon["name"]   = $icon[0];
	$this->icon["width"]  = $icon[1];
	$this->icon["height"] = $icon[2];
      } else 
	$this->icon = NULL;

      $this->parent      = $row[0]["parent"];

      if ($row[0]["children"])
	$this->children    = explode(":", $row[0]["children"]);
      return TRUE;
    } else {
      $this->cat_id      = NULL;
      $this->title       = NULL;
      $this->description = NULL;
      $this->template    = NULL;
      $this->image       = NULL;
      $this->parent      = NULL;
    }
  }

  function loadCategories($returnOnly=NULL){
    $this->categories = NULL;
    if(!($sql_categories = $GLOBALS["core"]->sqlSelect("mod_fatcat_categories")))
      return NULL;

    $categories = array();
    foreach ($sql_categories as $cats){
      if ($cats['cat_id'] == 0)
	continue;
      
      $categories[$cats["cat_id"]] = new PHPWS_Fatcat_Category($cats["cat_id"]);
    }

    if (!$returnOnly){
      $this->categories = $categories;
      return TRUE;
    } else
      return $categories;
  }

  function createCategoryLinks($cat_id, $module_title=NULL, $noTemplate=TRUE, $noKids=TRUE, $noParent=TRUE){
    $cat = new PHPWS_Fatcat_Category($cat_id);
    if (!isset($cat->cat_id))
      exit("Error in createCategoryLinks: Unable to load category #$cat_id");

    $links["CURRENT"] =  $cat->displayCategoryLink($cat->title, $cat->cat_id, $module_title);

    if (!$noParent && $cat->parent){
	$links["PARENT_LBL"] = $_SESSION["translate"]->it("Parent");

      $parent = new PHPWS_Fatcat_Category($cat->parent);
      $links["PARENT"] = $parent->displayCategoryLink($parent->title, $parent->cat_id, $module_title);
    }

    if (!$noKids && $cat->children){
	$links["CHILDREN_LBL"]  = $_SESSION["translate"]->it("Children");

      $links["CHILDREN"] = NULL; 
      foreach ($cat->children as $kidID){
	$kid = new PHPWS_Fatcat_Category($kidID);
	$links["CHILDREN"] .= $kid->displayCategoryLink($kid->title, $kid->cat_id, $module_title) . "<br />\n";
      }
    }

    if ($noTemplate)
      return $links;
    else
      return PHPWS_Template::processTemplate($links, "fatcat", "links.tpl");
  }


  function updateCategory(){
    if(isset($this->categories) && is_array($this->categories)) {
      $originalParent = $this->categories[$this->cat_id]->parent;
    } else {
      $originalParent = 0;
    }

    $update["title"]       = $this->title;
    $update["description"] = $this->description;
    $update["template"]    = $this->template;

    if ($this->image)
      $update["image"]     = implode(":", $this->image);
    else
      $update["image"]     = NULL;

    if ($this->icon)
      $update["icon"]     = implode(":", $this->icon);
    else
      $update["icon"]     = NULL;

    $update["parent"]      = $this->parent;

    $GLOBALS["core"]->sqlUpdate($update, "mod_fatcat_categories", "cat_id", $this->cat_id);

    if ($this->cat_id != 0 && $originalParent != $this->parent){
      $this->removeChild($originalParent, $this->cat_id);
      $this->addChild($this->parent, $this->cat_id);
    }
  }

  function createCategory($title, $description=NULL, $template=NULL, $image=NULL, $icon=NULL, $parent="0", $children=NULL){
    if (is_bool($parent))
      $parent = 0;

    if (!is_numeric($parent) && $parent != 0)
      exit("createCategory error: Parent must be a number.<br />");

    $title = PHPWS_Text::parseInput($title, "none");
    
    $description = PHPWS_Text::parseInput($description);

    $sqlWrite["parent"] = $parent;

    $sqlWrite["title"] = $title;
    $sqlWrite["description"] = $description;

    if ($template)
      $sqlWrite["template"] = $template;
    else
      $sqlWrite["template"] = "default.tpl";

    if ($image){
      if (is_array($image))
	$sqlWrite["image"] = implode(":", $image);
      elseif (preg_match("/\w+:\d+:\d+/", $image))
	$sqlWrite["image"] = $image;
    }

    if ($icon)
      $sqlWrite["icon"] = implode(":", $icon);

    if ($children)
      $sqlWrite["children"] = implode(":", $children);

    $child_id = $GLOBALS["core"]->sqlInsert($sqlWrite, "mod_fatcat_categories", 1, 1);
    // Update the parent's children
    PHPWS_Fatcat::addChild($parent, $child_id);
    return $child_id;
  }

  /**
   * Returns an array of categories and their children
   *
   * The array will branch off of the parents. The value of each parent will
   * be an array of its childen.
   */
  function buildTree(){
    if (!$this->categories)
      $this->loadCategories();

    if ($this->categories){
      foreach ($this->categories as $cat_id=>$OBJcat)
	if ($OBJcat->parent == 0)
	  $array[$cat_id] = $this->getChildren($cat_id);

      return $array;
    } else
      return NULL;
  }


  function displayCategoryLink($cat_title, $cat_id, $module_title=NULL){
    $link["fatcat[user]"] = "viewCategory";
    $link["fatcat_id"] = (int)$cat_id;
    if (!isset($cat_title))
      return NULL;

    if ($module_title)
      $link["module_title"] = $module_title;

    $fatLink = PHPWS_Text::moduleLink(strip_tags($cat_title), "fatcat", $link);
    return $fatLink;
  }


  function listCategories($list){
    if (!$list)
      return NULL;

    if (!$this->categories)
      $this->loadCategories();

    foreach ($list as $cat_id){
      $loop = 0;
      $parents = NULL;
      $parents = $this->getParents($cat_id);
      if (is_array($parents)){
	$parents = array_reverse($parents);

	foreach ($parents as $parent_id){
	  $cat_list[$cat_id][$parent_id] = $this->categories[$parent_id]->title;
	  $loop = 1;
	}
      }
      $cat_list[$cat_id][$cat_id] = $this->categories[$cat_id]->title;
    }

    return $cat_list;
  }

  function getParent($cat_id){
    return $GLOBALS['core']->getOne("select parent from mod_fatcat_categories where cat_id=" . (int)$cat_id, TRUE);
  }


  function getParents($cat_id, $list=NULL){
    if (!$this->categories)
      $this->loadCategories();

    $cat_array = $this->categories;

    $parent = $cat_array[$cat_id]->parent;

    if ($parent != 0){
      $list[] = $parent;
      $list = $this->getParents($parent, $list);
    } else {
      return $list;
    }
  }


  function getChildren($cat_id){
    if (!$this->categories)
      $this->loadCategories();

    if (isset($this->categories[$cat_id]->children) && count($this->categories[$cat_id]->children)){
      foreach ($this->categories[$cat_id]->children as $kid)
	$all_kids[$kid] =  $this->getChildren($kid);
      return $all_kids;
    }
    else
      return NULL;


  }

  function removeChild($cat_id, $child_id){
    $categories = $this->loadCategories(1);

    $cat = $categories[$cat_id];
    if ($cat->children){
      $key = array_search($child_id, $cat->children);
      unset($cat->children[$key]);
      if (!count($cat->children))
	$cat->children = NULL;
    }

    if ($cat->children)
      $update["children"] = implode(":", $cat->children);
    else
      $update["children"] = NULL;

    $GLOBALS["core"]->sqlUpdate($update, "mod_fatcat_categories", "cat_id", $cat_id);
  }


  function addChild($parent, $child_id){
    if ($parent != "0" && $row = $GLOBALS["core"]->sqlSelect("mod_fatcat_categories", "cat_id", $parent)){
      if ($row[0]["children"])
	$parent_kids = explode(":", $row[0]["children"]);

      if (!isset($parent_kids[$child_id]))
	$parent_kids[] = $child_id;

      $update["children"] = implode(":", $parent_kids);
      $GLOBALS["core"]->sqlUpdate($update, "mod_fatcat_categories", "cat_id", $parent);
    }
  }

  function eraseImage($cat_id){
    $update["image"] = NULL;
    $GLOBALS["core"]->sqlUpdate($update, "mod_fatcat_categories", "cat_id", $cat_id);
  }

  function eraseIcon($cat_id){
    $update["icon"] = NULL;
    $GLOBALS["core"]->sqlUpdate($update, "mod_fatcat_categories", "cat_id", $cat_id);
  }


  /**
   * Displays information about a category
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   */
  function viewCategory($cat_id, $module_title=NULL){
    $image_directory = PHPWS_HOME_DIR . "images/fatcat/images/";
    $image_address = PHPWS_HOME_HTTP . "images/fatcat/images/";

    $cat = new PHPWS_Fatcat_Category($cat_id);

    $viewCategory["TITLE"] = $cat->title;
    if ($cat->description)
      $viewCategory["DESCRIPTION"] = PHPWS_Text::parseOutput($cat->description);
    else
      $viewCategory["DESCRIPTION"] = "<i>".$_SESSION["translate"]->it("No description available") . ".</i>";

    if ($cat->image["name"]){
      if (!file_exists($image_directory . $cat->image["name"]))
	$cat->eraseImage($cat->cat_id);
      else
	$viewCategory["IMAGE"] = PHPWS_Text::imageTag($image_address.$cat->image["name"], $cat->title, $cat->image["width"], $cat->image["height"]);
    }

    $viewCategory["ICON"] = $cat->getIcon(null, false, false, $module_title);

    //    $viewCategory["LINKBACK"] = PHPWS_Text::link($_SERVER["HTTP_REFERER"], $_SESSION["translate"]->it("Go Back"));

    $viewCategory["LINKS"] = $cat->createCategoryLinks($cat->cat_id, $module_title, FALSE, FALSE, FALSE);

    if ($module_title)
      $viewCategory["ELEMENTS"] = PHPWS_Fatcat::getModuleElementList($cat_id, $module_title);

    $_SESSION["OBJ_layout"]->addPageTitle($cat->title);
    return PHPWS_Template::processTemplate($viewCategory, "fatcat", "display/".$cat->template);
  }


  /**
   * Returns the icon or icons associated with an element
   * 
   * @author                     Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param integer module_id    Id number of your module's element
   * @param boolean allIcons     If TRUE, will return an array of all the category icons associated with
   *                             this element. If FALSE, it will only return one
   * @param boolean onlyIcons    If TRUE, function will return icon image tags only. If FALSE, it will
   *                             return the title of the category instead
   * @param string  module_title The name of the module trying to access the elements.
   */
  function getIcon($module_id=NULL, $allIcons=FALSE, $onlyIcons = FALSE, $module_title = NULL){
    $icon_address = PHPWS_HOME_HTTP . "images/fatcat/icons/";
    $icon_directory = PHPWS_HOME_DIR . "images/fatcat/icons/";

    if (isset($module_id)){
      if (is_null($module_title))
	if (!($module_title = $GLOBALS["core"]->current_mod))
	  exit("getIcon error: Unable to derive module_title.<br />");

      if ($categories = PHPWS_Fatcat_Elements::getModulesCategories($module_title, $module_id)){
	foreach ($categories as $cat_id){

	  $cat = new PHPWS_Fatcat_Category($cat_id);

	  if ($onlyIcons && !$cat->icon["name"])
	    continue;

	  $icons[] = $cat->getIcon(null, false, false, $module_title);
	}

	if (!isset($icons)){
	  if (!empty($this->settings["defaultIcon"])){
	    $cat->icon = $this->settings["defaultIcon"];
	    return $cat->getIcon(null, false, false, $module_title);
	  }
	  else
	    return NULL;
	}

	if ($allIcons)
	  return $icons;
	else {
	  foreach ($icons as $testImage)
	    if (preg_match("/\<img.+src/", $testImage))
	      return $testImage;

	  return $icons[0];
	}
      } else 
	return;
    } elseif ($this->title){
	$cat = $this;
	if ($cat->icon["name"]){
	  if (!file_exists($icon_directory . $cat->icon["name"])){
	    $this->eraseIcon($cat->cat_id);
	    return NULL;
	  }
	  else
	    $icon = PHPWS_Text::imageTag($icon_address . $cat->icon["name"], $cat->title, $cat->icon["width"], $cat->icon["height"]);
	}
	else
	  return NULL;

	$link = PHPWS_Text::moduleLink($icon, "fatcat", array("fatcat[user]"=>"viewCategory", "fatcat_id"=>$cat->cat_id, "module_title"=>$module_title));
	return $link;
    } else
      exit("getIcon error: No module_id supplied and category not created.<br />");
  }

  /**
   * Returns the image or images associated with an element
   * 
   * @author                     Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param integer module_id    Id number of your module's element
   * @param boolean allImages     If TRUE, will return an array of all the category images associated with
   *                             this element. If FALSE, it will only return one
   * @param boolean onlyImages    If TRUE, function will return image tags only. If FALSE, it will
   *                             return the title of the category instead
   * @param string  module_title The name of the module trying to access the elements.
   */
  function getImage($module_id=NULL, $allImages=FALSE, $onlyImages = FALSE, $module_title = NULL){
    $image_address = PHPWS_HOME_HTTP . "images/fatcat/images/";
    $image_directory = PHPWS_HOME_DIR . "images/fatcat/images/";

    if (isset($module_id)){
      if (is_null($module_title)){
	if (!($module_title = $GLOBALS["core"]->current_mod))
	  exit("getImage error: Unable to derive module_title.<br />");
      }

      if ($categories = PHPWS_Fatcat_Elements::getModulesCategories($module_title, $module_id)){
	foreach ($categories as $cat_id){
	  if ($cat_id == 0)
	    break;
	  $cat = new PHPWS_Fatcat_Category($cat_id);
	  if ($onlyImages && empty($cat->image["name"]))
	    continue;

	  $images[] = $cat->getImage();
	}

	if (!isset($images))
	    return NULL;

	if ($allImages)
	  return $images;
	else {
	  foreach ($images as $testImage)
	    if (preg_match("/\<img.+src/", $testImage))
	      return $testImage;

	  return $images[0];
	}
      } else 
	return;
    } elseif ($this->title){
	$cat = $this;
	if ($cat->image["name"]){
	  if (!file_exists($image_directory . $cat->image["name"])){
	    $this->eraseImage($cat->cat_id);
	    return NULL;
	  }
	  else
	    $image = PHPWS_Text::imageTag($image_address . $cat->image["name"], $cat->title, $cat->image["width"], $cat->image["height"]);
	}
	else
	  return NULL;

	$link = PHPWS_Text::moduleLink($image, "fatcat", array("fatcat[user]"=>"viewCategory", "fatcat_id"=>$cat->cat_id));
	return $link;
    } else
      exit("getImage error: No module_id supplied and category not created.<br />");
  }

  function getTitle($cat_id){
    return $GLOBALS['core']->getOne("select title from mod_fatcat_categories where cat_id=" . (int)$cat_id, TRUE);
  }

  function deleteCategory($cat_id){
    $cat = new phpws_fatcat_category($cat_id);

    $GLOBALS['core']->sqlDelete("mod_fatcat_categories", "cat_id", $cat->cat_id);
    $GLOBALS['core']->sqlDelete("mod_fatcat_elements", "cat_id", $cat->cat_id);

    if (!empty($cat->children) && is_array($cat->children))
      foreach ($cat->children as $child_id)
    	phpws_fatcat_category::deleteCategory($child_id);
  }
}

?>