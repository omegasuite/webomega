<?php

require_once(PHPWS_SOURCE_DIR . 'core/List.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');

/**
 * The CategoryView class will display a category view of all FAQs.
 * If a category contains a FAQ then it will be hyperlink, which when clicked
 * will show display links to all the FAQs in that category.  If a category appears that is not a 
 * hyperlink then the category contains no FAQ but one of its children does contain one or more FAQs.  
 * FAQ uses recursion to process the categories so if a FAQ is several levels deep it will still be seen.  
 * For all child categories the parent categories will be shown.
 *
 * @version $Id: CategoryView.php,v 1.6 2005/01/29 17:32:45 darren Exp $
 * @author Darren Greene <dg49379@appstate.edu>
 * @modified Steven Levin <steven@tux.appstate.edu>
 * @package 
 */
class CategoryView {

  var $_categoryId        = NULL;
  var $_module            = NULL;
  var $_op                = NULL;
  var $_list              = NULL;
  var $_showCatUpdatedMsg = FALSE;
  var $_showCatNumItems   = FALSE;
  var $_customTopBullet   = NULL;
  var $_customSubBullet   = NULL;

  function showCount($toggle) {
    $this->_showCatNumItems = $toggle;
  }

  function showUpdated($toggle) {
    $this->_showCatUpdatedMsg = $toggle;
  }

  function setCustomTopBullet($srcTopBullet) {
    $this->_customTopBullet = $srcTopBullet;
  }

  function setCustomSubBullet($srcSubBullet) {
    $this->_customSubBullet = $srcSubBullet;
  }

  function getAllCategories() {
    $catTable = PHPWS_TBL_PREFIX . "mod_fatcat_categories";
    return $GLOBALS["core"]->query("SELECT cat_id, title, children FROM $catTable WHERE parent=0");
  }

  function getParentCategories() {
    $catTable         = PHPWS_TBL_PREFIX . "mod_fatcat_categories";
    $parentCategories = array();

    if($result = $GLOBALS["core"]->query("SELECT cat_id, children, title FROM $catTable WHERE parent=0")) {
      while($tmpCat = $result->fetchRow()) {
	if($this->recursivegetParentCategories($tmpCat["cat_id"], $tmpCat["children"])) {
	  $tmp = array("cat_id"=>$tmpCat["cat_id"], "title"=>$tmpCat["title"], "children"=>$tmpCat["children"]);
	  $parentCategories[$tmpCat["cat_id"]] = $tmp;		   			      	  
	}
      }       			      
    }
    
    return $parentCategories;
  }
  
  // return true or false if have children
  function recursivegetParentCategories($cat_id, $childArr) {
    $count = 0;
    $catTable         = PHPWS_TBL_PREFIX . "mod_fatcat_categories";
    $catElementsTable = PHPWS_TBL_PREFIX . "mod_fatcat_elements";

    $categoryElements = $GLOBALS["core"]->query("SELECT cat_id FROM $catElementsTable WHERE module_title='$this->_module' AND cat_id=$cat_id");
    $elementRows = $categoryElements->fetchRow();

    if(count($elementRows) > 0) {
      return ++$count;
    } else {
      // check children
      if($childArr != NULL && $childArr != "") {
	$childArrEx = explode(":", $childArr);
	
	foreach($childArrEx as $child) {
	  $result2 = $GLOBALS["core"]->query("SELECT cat_id, children FROM $catTable WHERE cat_id=$cat_id");
	  $tmpChild = $result2->fetchRow();
	  $count += $this->recursivegetParentCategories($child, $tmpChild["children"]);
	}

	return $count;
      } else {
	// no children
	return $count;
      }
    }
  }

  function getMostRecent($cat_id) {
    $most_recent = (int) $GLOBALS["core"]->sqlMaxValue("mod_fatcat_elements", "created", array("module_title"=>$this->_module, "cat_id"=>$cat_id));
    
    if(isset($most_recent) && $most_recent > 0) 
      return (substr($most_recent, 4, 2) . "/" . substr($most_recent, 6, 2) . "/" . substr($most_recent, 0, 4));
    else
      return NULL;
  }

  function getChildren($cat_id) {
    $sql = "SELECT cat_id, children, title FROM " . PHPWS_TBL_PREFIX . "mod_fatcat_categories WHERE cat_id=".$cat_id;
    $currChildCatObj = $GLOBALS["core"]->query($sql);
    return $currChildCatObj->fetchRow();
  }

  function getParent($cat_id) {
    $sql = "SELECT cat_id, parent FROM " . PHPWS_TBL_PREFIX . "mod_fatcat_categories WHERE cat_id=".$cat_id;
    $currCatObj = $GLOBALS["core"]->query($sql);
    $row = $currCatObj->fetchRow();
    return $row["parent"];
  }

  function getTitle($cat_id) {
    $sql = "SELECT title FROM " . PHPWS_TBL_PREFIX . "mod_fatcat_categories WHERE cat_id=".$cat_id;
    $currCatObj = $GLOBALS["core"]->query($sql);
    $row = $currCatObj->fetchRow();
    return $row["title"];
  }

  function getDescription($cat_id) {
    $sql = "SELECT description FROM " . PHPWS_TBL_PREFIX . "mod_fatcat_categories WHERE cat_id=".$cat_id;
    $currCatObj = $GLOBALS["core"]->query($sql);
    $row = $currCatObj->fetchRow();
    return $row["description"];
  }

  function getNumChildren($cat_id) {
    $sql="SELECT COUNT(module_id) FROM mod_fatcat_elements WHERE module_title='$this->_module' AND active=1 AND cat_id=".$cat_id;
    $countChildElementsObj = $GLOBALS["core"]->query($sql, TRUE);
    $countChildElementsRow = $countChildElementsObj->fetchRow();

    return $countChildElementsRow["COUNT(module_id)"];
  }

  function getNums($cat_id) {
    $sql= "SELECT COUNT(module_id) FROM mod_fatcat_elements WHERE module_title='$this->_module' AND active=1 AND cat_id=".$cat_id;
    
    $countElementsObj = $GLOBALS["core"]->query($sql, TRUE);
    $tmpRow = $countElementsObj->fetchRow();

    return $tmpRow["COUNT(module_id)"];
  }

  function getCatInfo($cat_id) {
    $sql = "SELECT cat_id, title, description FROM ".PHPWS_TBL_PREFIX."mod_fatcat_categories WHERE cat_id=".$cat_id;
    $currChildCatObj = $GLOBALS["core"]->query($sql);
    return $currChildCatObj->fetchRow();
  }

  function buildCatLink($content, $cat_id) {
    $address        = "./index.php?module=$this->_module&amp;$this->_op&amp;category=$cat_id";
    $displayContent = $content;
    return PHPWS_Text::link($address, $displayContent, "index");
  }

  function imageTag($level) {
    if($level == "top") {
      if(!isset($this->_customTopBullet)) {
	return "<img src=\"./images/mod/fatcat/default_top_bullet.gif\" />";
      } else {
	if(!is_null($this->_customTopBullet)) {
	  return "<img src=\"$this->_customTopBullet\" />"; 
	}
      }
    } else if($level == "sub") {
      if(!isset($this->_customSubBullet)) {
	 return "<img src=\"./images/mod/fatcat/default_sub_bullet.gif\" />";
      } else {
	if(!is_null($this->_customSubBullet)) {
	  return "<img src=\"$this->_customSubBullet\" />"; 
	}
      }
    }
  }

  function formatTopLevelItem($cat_id, $title) {
    if($this->_showCatUpdatedMsg) {
      $catTags["UPDATED_LBL"] = $_SESSION["translate"]->it("Updated");
      $catTags["MOST_RECENT"] = $this->getMostRecent($cat_id);
    }    

    $catTags["TOP_CATEGORY_IMAGE"] = $this->imageTag("top");
    $numItems = $this->getNums($cat_id);
    
    if($numItems > 0) {
      if($this->_showCatNumItems)
	$catTags["C_NUMBER_OF_ITEMS"] = "(".$this->getNums($cat_id).")";
      $catTags["CATEGORY_TITLE"] = $this->buildCatLink($title, $cat_id);
    } else {
      $catTags["CATEGORY_TITLE"] = $title;
    }   

    return PHPWS_Template::processTemplate($catTags, "fatcat", "categories/main_single_category.tpl");
  }

  function spaceSubs() {
    return "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
  }

  function formatSubLevelItem($cat_id, $title, $level = 0) {
    if($this->_showCatUpdatedMsg) {
      $catTags["UPDATED_LBL"] = $_SESSION["translate"]->it("Updated");
      $catTags["MOST_RECENT"] = $this->getMostRecent($cat_id);
    }

    $catTags["SUB_CATEGORY_IMAGE"] = $this->imageTag("sub");
    $numItems = $this->getNums($cat_id);
    $catTags["SPACING"] = "";

    for($i = 0; $i <= $level; $i++)
      $catTags["SPACING"] .= $this->spaceSubs();

    if($numItems > 0) {
      $catTags["BEGIN_BOLD_TEXT"] = "<b>";
      $catTags["END_BOLD_TEXT"]   = "</b>";

      if($this->_showCatNumItems)
	$catTags["SC_NUMBER_OF_ITEMS"] = "(".$this->getNums($cat_id) . ")";
      $catTags["SUB_CATEGORY_TITLE"] = $this->buildCatLink($title, $cat_id);
      //$numItems++;
    } else {
      $catTags["SUB_CATEGORY_TITLE"] = $title;
    }

    return array("content"=>PHPWS_Template::processTemplate($catTags, "fatcat", "categories/main_sub_category.tpl"), "count"=>$numItems);
  }

  function listingCatProcess($cat_id, $title, $level = 0) {
    return $this->formatSubLevelItem($cat_id, $title, $level);
  }

  function singleCatProcess($cat_id, $title) {
    $numItems = $this->getNums($cat_id);
    
    if($numItems > 0) {
      return array("content"=>$this->buildCatLink($title, $cat_id) . " :: ", "count"=>$numItems);
    }
  }

  function processChildren($children, $level = 0, $type = "listing") {
    // the following function is recursive to go through all FatCat categories
    $templateArr = array();
    $templateArr["content"] = "";  // contains current level child and all its child
    $templateArr["count"] = 0;     // total number of children if any
    $moreChildrenArr = array();
    $moreChildrenArr["content"] = "";
    $moreChildrenArr["count"] = 0;

    $childArr = explode(":", $children);  // get children

    // loop through all the children of the current level
    foreach($childArr as $child) {
      $singleChild = $this->getChildren($child);  // see if this child has children

      if($type == "listing")
	$tempFormatedSubLevel = $this->listingCatProcess($child, $singleChild["title"], $level);
      else if($type = "single") 
	$tempFormatedSubLevel = $this->singleCatProcess($child, $singleChild["title"]);

      $templateArr["count"] += $tempFormatedSubLevel["count"];  // any item is this level

      if($singleChild["children"] != NULL) {
	$moreChildrenArr = $this->processChildren($singleChild["children"], ($level + 1), $type); // child has children so process them
      }

      // if this level has any items *OR* this level's children contained items
      if($tempFormatedSubLevel["count"] > 0 || ($singleChild["children"] != NULL && $moreChildrenArr["count"] > 0)) {
	$templateArr["content"] .= $tempFormatedSubLevel["content"];	

	if($singleChild["children"] != NULL) {
	  $templateArr["content"] .= $moreChildrenArr["content"];  // content for all this child's children

	  //	  if($type == "single") $templateArr["content"] .= "<br /><br />";
	  $templateArr["count"]   += $moreChildrenArr["count"];    // number of item in its children	
	}
      }
    }

    if($type == "single")
     $templateArr["content"] = substr($templateArr["content"], 0, -4);

    return $templateArr;
  }


  /**
   * This function is used to produce the category listing of Item.  Each category
   * that contains items will be a hyperlink with the number of items inside that category and
   * the last time a item in the category has been updated.
   */
  function categoriesMainListing() {
    //$atLeastOneFAQ = FALSE;
    $templatedCategories = "";  // stores complete item categories listing
    $mainSubCatTags = "";

    $allCatArr = $this->getParentCategories();

    if($allCatArr != NULL && count($allCatArr) > 0) {
      foreach($allCatArr as $cat) {
	$templatedCategories .= $this->formatTopLevelItem($cat["cat_id"], $cat["title"]);

	if($cat["children"] != NULL) {
	  $temp = $this->processChildren($cat["children"]);
	  $templatedCategories .= $temp["content"];
	}
      }

      $listTags["CATEGORY_LISTINGS"] = $templatedCategories;
      return PHPWS_Template::processTemplate($listTags, "fatcat", "categories/main_cat_listings.tpl");
    } else {
      return $_SESSION["translate"]->it("There are currently no items to display.");
    }
  }

  function buildParentLinkStructure($cat_id, $first_item=TRUE) {
    $content = "";

    if($cat_id == 0) {
      return $content;
    } else {
      $catTable = PHPWS_TBL_PREFIX . "mod_fatcat_categories";      
      $result =$GLOBALS["core"]->getAll("SELECT cat_id, title, parent FROM $catTable WHERE cat_id=$cat_id");      
      $numItems = $this->getNumChildren($cat_id);

      if($first_item != TRUE) {
	if($numItems > 0)
	  $content = $this->buildCatLink($result[0]["title"], $result[0]["cat_id"]);
	else
	  $content = $result[0]["title"];
      }

      if($result[0]["parent"] != 0) {
	if($first_item == FALSE)
	  $content .= " :: ";
	
	return $this->buildParentLinkStructure($result[0]["parent"], FALSE) . $content;

      } else {
	return $content .=  " :: ";
      }
    
    }
  }

  function getParentLinks($cat_id) {
    return substr($this->buildParentLinkStructure($cat_id), 0, -4);    
  }


  function buildParenNavLinks($cat_id) {
    $content = "";
    $_SESSION["OBJ_fatcat"]->initCategory($cat_id);
    $itemsInCategory = $_SESSION["OBJ_fatcat"]->getModuleElements($this->_module, NULL, $cat_id);
    
    if($_SESSION["OBJ_fatcat"]->parent) {
      $itemsInCategory = $_SESSION["OBJ_fatcat"]->getModuleElements($this->_module, NULL, $_SESSION["OBJ_fatcat"]->parent);
      
      $numItems = 0;
      if($itemsInCategory) {
	foreach ($itemsInCategory as $item) {
	  if($item["active"] == 1)
	    $numItems++;
	}
      }

      if($numItems) {
	$showItemTags["CATEGORIES_LINK"] = "Top: &#160;";
	$_SESSION["OBJ_fatcat"]->initCategory($_SESSION["OBJ_fatcat"]->parent);
	$address = "./index.php?module=$this->_module&amp;$this->_op&amp;category=".$_SESSION["OBJ_fatcat"]->cat_id;
	$linkText = $_SESSION["OBJ_fatcat"]->title;

	$content .= PHPWS_Text::link($address, $linkText, "index");
	$content .= " &#160;&#160;";
	$content .= " :: &#160;&#160;&#160;";
      }
    }
    return $content;
  }

  function buildChildNavLinks($cat_id) {
    /* now any children */
    $buildChildContent = "";
    if(count($_SESSION["OBJ_fatcat"]->getChildren($cat_id))) {
      $childArr = array_keys($_SESSION["OBJ_fatcat"]->getChildren($cat_id));

      foreach ($childArr as $child) {
	$numItemsObj = $GLOBALS["core"]->query("SELECT COUNT(element_id) FROM mod_fatcat_elements WHERE module_title='$this->_module' AND active=1 AND cat_id='$child'", TRUE);

	if(!DB::isError($numItemsObj)) {
	  $numInChildRow = $numItemsObj->fetchRow();
	  $numItems = $numInChildRow['COUNT(element_id)'];
	}

	if($numItems) {
	  $_SESSION["OBJ_fatcat"]->initCategory($child);
	  $address = "./index.php?module=$this->_module&amp;$this->_op&amp;category=".$_SESSION["OBJ_fatcat"]->cat_id;
	  $linkText = $_SESSION["OBJ_fatcat"]->title;
	  
	  $buildChildContent .= PHPWS_Text::link($address, $linkText, "index");
	  $buildChildContent .= " :: ";
	}
      }

      if($buildChildContent != "") {
	return substr($buildChildContent, 0, -4);
      }
    }
  }

  function buildCatNavLink() {
    return "<a href=\"./index.php?module=$this->_module&amp;$this->_op\">Back to Category Listing</a>";
  }

  function buildSameLevelLinks($cat_id) {
    $parent = $this->getParent($cat_id);
    $content = "";

    $childArr = $this->getChildren($parent);

    if($childArr == null)
      return;

    $children = explode(":", $childArr["children"]);

    foreach($children as $child) {
      if($cat_id != $child) {
	$catArr = $this->singleCatProcess($child, $this->getTitle($child));
	$content .= $catArr["content"];
      }
    }
    
    if($content != "") {
      $swapContent = substr($content, 0, -4);
      $content = " :: " . $swapContent;
      return substr($content, 4);
    }
  }
  
  function buildNavLinks($cat_id) {
    $parent = $this->getParent($cat_id);
    $sameLabel = "";
    //    $content = "<span style='text-align:left;'>".$this->buildCatNavLink();	
    //    $content .= "</span>";

    $content = "<p align=\"right\">";

    if($parent == 0) {
      $childArr = $this->getChildren($cat_id);
      $children = $this->buildChildNavLinks($cat_id);
      $content .= $children;

    } else {
      $content .= $this->getParentLinks($cat_id);
      $sameLevel = $this->buildSameLevelLinks($cat_id);
     
      if($sameLevel != "" )
	$content .= " [ " . $sameLevel . " ] ";
    }

    $content .= "</p>";

    return $content;
  }

  function categoriesSCView() {
    // SHOW ITEMS IN CATEGORY CHOOSEN
    if(!isset($_SESSION['PHPWS_FatcatList']) || ($_SESSION['PHPWS_FatcatList']->getModule() != $this->_module) || !isset($this->_categoryId) || $this->_categoryId != $_REQUEST['category']) {	
      $_SESSION['PHPWS_FatcatList'] = new PHPWS_List;
      $this->_categoryId = $_REQUEST['category'];
    }     

    $itemsInCategory = $_SESSION["OBJ_fatcat"]->getModuleElements($this->_module, NULL, $this->_categoryId);

    if(!is_null($itemsInCategory)) {
      $showItemTags["CATEGORY_LINKS"] = $this->buildNavLinks($this->_categoryId);

      $_SESSION['OBJ_fatcat']->initCategory($this->_categoryId);
      $title = $_SESSION['OBJ_fatcat']->title;
      $parent = $_SESSION['OBJ_fatcat']->parent;
      $description = $_SESSION['OBJ_fatcat']->description;

      if($parent != 0) {
	$children = $this->buildChildNavLinks($this->_categoryId);

	if($children != "") {
	  $showItemTags["RELATED_CATEGORIES"] = "<p align='left' class='smalltext'>". $title . " --> ";
	  $showItemTags["RELATED_CATEGORIES"] .= $children . "</p>";
	}
      }

      $showItemTags["CATEGORY_NAME"] = $title;

      if(isset($_SESSION['OBJ_fatcat']->icon)) {
	$iconname = $_SESSION['OBJ_fatcat']->icon['name'];
	$iconheight = $_SESSION['OBJ_fatcat']->icon['height'];
	$iconwidth = $_SESSION['OBJ_fatcat']->icon['width'];
	$showItemTags["CATEGORY_ICON"] = "<img src=\"./images/fatcat/icons/$iconname\" height=\"$iconheight\" width=\"$iconwidth\" alt=\"$iconname\" title=\"$iconname\" />";
      }

      if($description)
        $showItemTags["CATEGORY_DESCRIPTION"] = $description;

      include(PHPWS_SOURCE_DIR."mod/{$this->_module}/conf/categories.php");

      $_SESSION['PHPWS_FatcatList']->setModule($this->_module);
      $_SESSION['PHPWS_FatcatList']->setClass($class);
      $_SESSION['PHPWS_FatcatList']->setTable($table);

      if(isset($idColumn)) {
	$_SESSION['PHPWS_FatcatList']->setIdColumn($idColumn);
      }

      $_SESSION['PHPWS_FatcatList']->setDbColumns($dbColumns);
      $_SESSION['PHPWS_FatcatList']->setListColumns($listColumns);
      $_SESSION['PHPWS_FatcatList']->setName($name);

      if(isset($template)) {
	$_SESSION['PHPWS_FatcatList']->setTemplate($template);
      }
      
      $_SESSION['PHPWS_FatcatList']->setOp("$this->_op&amp;category=".$this->_categoryId);
      $_SESSION['PHPWS_FatcatList']->setPaging(array("limit"=>10, "section"=>TRUE, "limits"=>array(5,10,20,50), "back"=>"&#60;&#60;", "forward"=>"&#62;&#62;", "anchor"=>FALSE));

      if(isset($listTags)) {
	$_SESSION['PHPWS_FatcatList']->setExtraListTags($listTags);
      }

      if(isset($rowTags)) {
	$_SESSION['PHPWS_FatcatList']->setExtraRowTags($rowTags);
      }

      $ids = array();
      foreach($itemsInCategory as $item) {
	$ids[] = $item['module_id'];
      }

      $_SESSION['PHPWS_FatcatList']->setIds($ids);
      
      if(isset($where)) {
	$_SESSION['PHPWS_FatcatList']->setWhere($where);
      }

      if(isset($order)) {
	$_SESSION['PHPWS_FatcatList']->setOrder($order);
      }

      $showItemTags["LISTINGS"] = $_SESSION['PHPWS_FatcatList']->getList();
      return PHPWS_Template::processTemplate($showItemTags, "fatcat", "categories/cat_listings.tpl");
    }
  }

  function setModule($module) {
    if(is_string($module) && (strlen($module) > 0)) {
      $this->_module = $module;
      return TRUE;
    } else {
      return FALSE;
    }
  }

  function setOp($op) {
    if(is_string($op) && (strlen($op) > 0)) {
      $this->_op = $op;
      return TRUE;
    } else {
      return FALSE;
    }    
  }
}

?>