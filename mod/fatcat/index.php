<?php
if (!isset($GLOBALS['core'])){
  header("location:../../");
  exit();
}

$CNT_fatcat["content"] = $CNT_fatcat["title"] = NULL;
if (isset($_REQUEST["fatcat"])){
  list($section, $command) = each ($_REQUEST["fatcat"]);
  
  if ($section == "admin" && $_SESSION["OBJ_user"]->allow_access("fatcat")){
    switch ($command){
    case "menu":
      $_SESSION["OBJ_fatcat"]->admin_menu();
    break;

    case "createCategoryAction":
      if ($_SESSION["createCat"]){
	if($_SESSION["createCat"]->categoryFormAction()){
	  $content = $_SESSION["translate"]->it("Category Created")."!<br />";
	  $core->killSession("createCat");
	  $_SESSION["OBJ_fatcat"]->admin_menu();
	} else
	  $_SESSION["createCat"]->createCategoryForm();
      } else 
	$_SESSION["OBJ_fatcat"]->admin_menu();
    break;

    case "updateCategoryAction":
      if ($_SESSION["updateCat"]){
	if ($_SESSION["updateCat"]->categoryFormAction()){
	  $content = $_SESSION["translate"]->it("Category Updated")."!<br />";
	  $core->killSession("updateCat");
	  $_SESSION["OBJ_fatcat"]->admin_menu();
	} else
	  $_SESSION["updateCat"]->updateCategoryForm();
      } else
	$_SESSION["OBJ_fatcat"]->admin_menu();
    break;

    case "categoryForm":
      if (isset($_POST["createCategory"])) {
	$_SESSION["createCat"] = new PHPWS_Fatcat;
	$_SESSION["createCat"]->parent = $_POST["fatSelect"]["fatcat"];
	$_SESSION["createCat"]->createCategoryForm();
      } elseif (isset($_POST["updateCategory"])) {
	if ($cat_id = $_POST["fatSelect"]["fatcat"]){
	  $_SESSION["updateCat"] = new PHPWS_Fatcat();
	  $_SESSION["updateCat"]->initCategory($cat_id);
	  $_SESSION["updateCat"]->loadCategories();
	  $_SESSION["updateCat"]->parent = $_SESSION["updateCat"]->categories[$cat_id]->parent;
	  $_SESSION["updateCat"]->updateCategoryForm();
	} else
	  $_SESSION["OBJ_fatcat"]->admin_menu();
      } elseif (isset($_POST["defIcon"])){
	if ($_SESSION["OBJ_fatcat"]->saveDefaultIcon()){
	  $CNT_fatcat["content"] .= $_SESSION["translate"]->it("Default icon set") . ".<hr />";
	  $_SESSION["OBJ_fatcat"] = new PHPWS_Fatcat;
	}
	else
	  $CNT_fatcat["content"] .= $_SESSION["OBJ_fatcat"]->printError();

	$_SESSION["OBJ_fatcat"]->admin_menu();
      } elseif (isset($_POST["set_limit"])){
	$_SESSION["OBJ_fatcat"]->setLimit($_POST["relatedLimit"]);
	$CNT_fatcat["content"] .= $_SESSION["translate"]->it("Limit Set")."<hr />";
	$_SESSION["OBJ_fatcat"]->admin_menu();
      } elseif (isset($_POST["relatedEnabled"])){
	$_SESSION["OBJ_fatcat"]->toggleRelatedEnabled();
	$_SESSION["OBJ_fatcat"]->admin_menu();
      } elseif (isset($_POST['deleteCategory'])){
	$catId = $_POST['fatSelect']['fatcat'];
	if (empty($catId) || !is_numeric($catId) || $catId < 1)
	  $_SESSION["OBJ_fatcat"]->admin_menu();
	else
	  phpws_fatcat_forms::deleteCategoryForm($catId);
      } elseif (isset($_POST['set_uncat'])){
	if (isset($_POST['uncat_name']) && !empty($_POST['uncat_name'])){
	  $cat = new PHPWS_Fatcat_Category(0);
	  $cat->title = PHPWS_Text::parseInput($_POST['uncat_name']);
	  $cat->updateCategory();
	  $CNT_fatcat["content"] .= $_SESSION["translate"]->it("Uncategorized name set") . ".<hr />";
	  $_SESSION["OBJ_fatcat"]->admin_menu();
	} else
	  $_SESSION["OBJ_fatcat"]->admin_menu();
      } else
	$_SESSION["OBJ_fatcat"]->admin_menu();
    break;

    case "deleteCategoryAction":
      $cat_id = (int)$_REQUEST['cat_id'];
      phpws_fatcat_category::deleteCategory($cat_id);
      $_SESSION["OBJ_fatcat"]->categories = NULL;
      $_SESSION["OBJ_fatcat"]->admin_menu();
      break;
    }// END switch for command (admin)

  }// END admin section

  if ($section == "user"){
    switch($command){
    case "viewCategory":
      $fatcat_id = (int)$_REQUEST['fatcat_id'];
      if (isset($_REQUEST["module_title"]))
	$moduleTitle = $_REQUEST["module_title"];
      else
	$moduleTitle = NULL;
      $CNT_fatcat["title"] = $_SESSION["OBJ_fatcat"]->categories[$fatcat_id]->title;
      $CNT_fatcat["content"] = $_SESSION["OBJ_fatcat"]->viewCategory($fatcat_id, $moduleTitle);
      break;

    }
  }// END user section

}// END fatRequest check

?>