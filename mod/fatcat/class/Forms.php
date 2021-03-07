<?php

/**
 * Creates a multiple or single drop down selection box of categories
 *
 * The default "mode" will create a multiple selection box. The size of the
 * selection box is dependant on the "rows" parameter. If "rows", is not sent
 * then the default is the number of the selections. The minimum amount to rows
 * allowed is three (3). Sending the mode parameter "single" will create a
 * standard drop down box.
 *
 * The "module_id" parameter controls what values are preselected. If an array is
 * sent, it will work with the multiple box. A string or int will work with the
 * single value. If, instead, the word "FORM" is sent, then the match will be
 * based off a recent form statement.
 *
 * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
 * @version $Id: Forms.php,v 1.53 2005/03/04 16:34:18 matt Exp $
 */


// Change this to FALSE to prevent fatcat from compressing
// the category names
define('FATCAT_COMPRESS', TRUE);

require_once (PHPWS_SOURCE_DIR . 'core/Error.php');

require_once (PHPWS_SOURCE_DIR . 'core/Form.php');

require_once (PHPWS_SOURCE_DIR . 'core/Text.php');

require_once (PHPWS_SOURCE_DIR . 'core/File.php');

require_once (PHPWS_SOURCE_DIR . 'mod/help/class/CLS_help.php');


class PHPWS_Fatcat_Forms extends PHPWS_Fatcat_Elements{

  function showSelect($module_id=NULL, $mode='multiple', $rows = NULL, $module_title=NULL, $purge=FALSE, $setSticky=TRUE){
    $sticky = $match = $content = NULL;

    if ($_SESSION['OBJ_user']->allow_access('fatcat'))
      $errorMessage = '<span class="errortext">'
	. PHPWS_Text::moduleLink($_SESSION['translate']->it('No Categories Available'), 'fatcat', array('fatcat[admin]'=>'menu')) . '</span>';
    else
      $errorMessage = '<span class="errortext">' . $_SESSION['translate']->it('No Categories Available') . '</span>';

    if (!PHPWS_Text::isValidInput($module_title))
      $module_title = $GLOBALS['core']->current_mod;

    $children = PHPWS_Fatcat::getCategoryList();

    if (is_null($rows))
      $rows = count($children);

    if (!count($children)){
      $defaultCat = '<input type="hidden" name="fatcatProcess" value="1" />';
      $defaultCat .= '<input type="hidden" name="fatSelect['.$module_title.'][]" value="0" />';

      if ($mode != 'single')
	return $errorMessage . $defaultCat;
      else {
	if ($module_title == 'fatcat'){
	  $row['0'] = '&lt;'.$_SESSION['translate']->it('Top Level').'&gt;';
	  return PHPWS_Form::formSelect('fatSelect['.$module_title.']', $row);
	} else
	  return $errorMessage . $defaultCat;
      }
    }

    if ($purge){
      foreach ($children as $cat_id=>$cat_title)
	if(!$hi = $GLOBALS['core']->getOne("select element_id from mod_fatcat_elements where module_title='$module_title' and cat_id=$cat_id", TRUE))
	  unset($children[$cat_id]);
      
      if(!count($children))
	return NULL;
    }

    if ($module_title == 'fatcat')
      $match = $module_id;
    else {
      if (isset($_REQUEST['fatSelect'][$module_title]))
        $match = $_REQUEST['fatSelect'][$module_title];
      elseif (!empty($module_id)){
        $match = $this->getModulesCategories($module_title, $module_id);
      }
    }

    if (isset($module_id))
      if ($GLOBALS['core']->getOne("select element_id from mod_fatcat_elements where module_id=$module_id and module_title='$module_title' and rating=999", TRUE))
	$sticky = 1;


    if ($mode != 'single'){
      if ($rows < 3)
	$rows = 3;
      elseif ($rows > 10)
	$rows = 10;

      $content .= PHPWS_Form::formMultipleSelect('fatSelect['.$module_title.']', $children, $match, NULL, 1, $rows);
    } else {
      if ($module_title == 'fatcat'){
	$merge['0'] = '&lt;'.$_SESSION['translate']->it('Top Level').'&gt;';
	$children = $merge + $children;
      }
      if (is_array($match))
	$match = $match[0];
      $content .= PHPWS_Form::formSelect('fatSelect['.$module_title.']', $children, $match, NULL, 1);
    }
    $content .= '<input type="hidden" name="fatcatProcess" value="1" />
';

    if ($module_title != 'fatcat' && $setSticky == TRUE)
      $content .= '<br />' . PHPWS_Form::formCheckBox('fatSticky['.$module_title.']', 1, $sticky) . ' ' . $_SESSION['translate']->it('Sticky');

    return $content;
  }


  function familyOption($family, $parent_title=NULL){
    $option = $separator = NULL;
    
    if (!$family)
      return NULL;

    if ($parent_title)
    $separator = ' &gt; ';

    $cut = 7;
    $margin = 3;

    foreach ($family as $cat_id=>$newFamily){
      if (!isset($this->categories[$cat_id]))
	continue;

      $title = $this->categories[$cat_id]->title;
      if (!isset($option[$cat_id]))
	$option[$cat_id] = $parent_title . $separator . $title;
      else
	$option[$cat_id] .= $parent_title . $separator . $title;
      if (FATCAT_COMPRESS && strlen($title) > ($cut + $margin))
	$title = substr($title, 0, $cut).'...';
      if ($newoption = $this->familyOption($newFamily, $parent_title . $separator . $title))
	$option = $option + $newoption;
    }
    return $option;
  }

  function deleteCategoryForm($cat_id){
    $category = new phpws_fatcat_category($cat_id);
    $content = '<b>' . $_SESSION['translate']->it('Are you sure you want to delete this category and all the categories beneath it') . '?</b><br />';
    $content .= PHPWS_Text::moduleLink($_SESSION['translate']->it('Yes'), 'fatcat', array('fatcat[admin]'=>'deleteCategoryAction', 'cat_id'=>$cat_id))
      . ' ' . PHPWS_Text::moduleLink($_SESSION['translate']->it('No'), 'fatcat', array('fatcat[admin]'=>'menu'));

    $GLOBALS['CNT_fatcat']['title'] = $_SESSION['translate']->it('Confirm Deletion of Category') . ' <i>' . $category->title . '</i>';
    $GLOBALS['CNT_fatcat']['content'] = $content;
    return TRUE;
  }


  function admin_menu(){
    $this->loadCategories();

    if (!empty($this->settings['defaultIcon']) && !empty($this->settings['defaultIcon']['name']))
      $defaultIcon = $this->settings['defaultIcon']['name'];
    else
      $defaultIcon = NULL;

    if (isset($this->error) && (sizeof($this->error) > 0)){
      foreach ($this->error as $errorMessage)
	$content .= "<span class=\"errortext\">$errorMessage</span><br />";
      unset($this->error);
    }

    $uncat = new PHPWS_Fatcat_Category(0);

    $title = $_SESSION['translate']->it('FatCat Administration Menu');
    $form = new EZform;
    $form->add('module', 'hidden', 'fatcat');
    $form->add('fatcat[admin]', 'hidden', 'categoryForm');
    if (count($this->categories)){
      $form->add('updateCategory', 'submit', $_SESSION['translate']->it('Edit Category'));
      $form->add('deleteCategory', 'submit', $_SESSION['translate']->it('Delete Category'));
    }
    if ($form->imageForm('defIcon', PHPWS_HOME_DIR . 'images/fatcat/icons/', $defaultIcon)){
      $form->add('defIcon', 'submit', $_SESSION['translate']->it('Set Default Icon'));
    } else 
      $noIcon = 1;

    for($i = 1; $i < 11 ; $i++)
      $limits[$i * 5] = $i * 5;
    
    $form->add('relatedLimit', 'select',  $limits);
    $form->add('set_limit', 'submit', $_SESSION['translate']->it('Set Limit'));
    $form->setMatch('relatedLimit', $this->settings['relatedLimit']);
	if ($this->settings['relatedEnabled'] == 0) {
		$form->add('relatedEnabled', 'submit', $_SESSION['translate']->it('Enable What\'s Related'));
	} else {
		$form->add('relatedEnabled', 'submit', $_SESSION['translate']->it('Disable What\'s Related'));
    }
	$form->add('uncat_name', 'text', $uncat->title);
    $form->add('set_uncat', 'submit', $_SESSION['translate']->it('Set Name'));
    $template = $form->getTemplate();
    $template['UNCAT_LABEL'] = $_SESSION['translate']->it('Uncategorized Label');
    $template['LIMIT_LABEL'] = $_SESSION['translate']->it('What\'s Related Limit') . CLS_help::show_link('fatcat', 'relatedLimit');
    $template['DEFAULT_LABEL'] = $_SESSION['translate']->it('Default Icon');
    $template['CREATE_CAT'] = $this->showSelect(NULL, 'single') . PHPWS_Form::formSubmit($_SESSION['translate']->it('Create Category'), 'createCategory');
    if (isset($noIcon))
      $template['DEFAULT_LABEL'] .= CLS_help::show_link('fatcat', 'fatcatNoIcon');
    else
      $template['DEFAULT_LABEL'] .= CLS_help::show_link('fatcat', 'fatcatDefIcon');

    $GLOBALS['CNT_fatcat']['title']   = $title . CLS_help::show_link('fatcat', 'fatcatAdmin');
    $GLOBALS['CNT_fatcat']['content'] .= PHPWS_Template::processTemplate($template, 'fatcat', 'adminMenu.tpl');
  }

  function saveDefaultIcon(){
    include_once PHPWS_SOURCE_DIR . 'mod/fatcat/conf/config.php';
    if ($_FILES['NEW_defIcon']['name']){
      if ($filename = $this->savePic('NEW_defIcon', PHPWS_HOME_DIR . 'images/fatcat/icons/', FATCAT_MAX_ICON_WIDTH, FATCAT_MAX_ICON_HEIGHT)){
	$GLOBALS['core']->sqlUpdate(array('defaultIcon'=>implode(':', $filename)), 'mod_fatcat_settings');
	$this->settings['defaultIcon'] = $filename;
	return TRUE;
      }
      else
	return FALSE;
    } elseif ($_POST['CURRENT_defIcon']){
      if($_POST['CURRENT_defIcon'] == 'none')
	$this->settings['defaultIcon']= $default = NULL;
      else {
	$imageArray = $this->setImageInfo($_POST['CURRENT_defIcon'], 'icons');
	$default = implode(':', $imageArray);
	$this->settings['defaultIcon'] = $imageArray;
      }
      $GLOBALS['core']->sqlUpdate(array('defaultIcon'=>$default), 'mod_fatcat_settings');

    } 
  }


  function createCategoryForm(){
    $this->parent = $_POST['fatSelect']['fatcat'];
    $title = $_SESSION['translate']->it('Create Category');
    $content = '
<form name="createCat" action="index.php" method="post" enctype="multipart/form-data">
';
    $content .= PHPWS_Form::formHidden(array('module'=>'fatcat', 'fatcat[admin]'=>'createCategoryAction'));
    $content .= $this->categoryForm();
    $content .= '
<br />'.PHPWS_Form::formSubmit($_SESSION['translate']->it('Create Category'));
    $content .= '</form>
';
    $GLOBALS['CNT_fatcat']['title']   = $title;
    $GLOBALS['CNT_fatcat']['content'] .= $this->linkToAdmin().'<br />'.$content;
  }

  function updateCategoryForm(){
    $title = $_SESSION['translate']->it('Update Category');
    $content = '
<form name="updateCat" action="index.php" method="post" enctype="multipart/form-data">
';
    $content .= PHPWS_Form::formHidden(array('module'=>'fatcat', 'fatcat[admin]'=>'updateCategoryAction'));
    $content .= $this->categoryForm();
    $content .= '
<br />'.PHPWS_Form::formSubmit($_SESSION['translate']->it('Update Category'));
    $content .= '</form>
';

    $GLOBALS['CNT_fatcat']['title']   = $title;
    $GLOBALS['CNT_fatcat']['content'] .= $this->linkToAdmin().'<br />'.$content;
    $GLOBALS['CNT_fatcat']['content'] .= '<hr />'.$this->viewCategory($this->cat_id);
  }


  function categoryForm(){
    $image_directory = PHPWS_HOME_DIR.'images/fatcat/images';
    $icon_directory = PHPWS_HOME_DIR.'images/fatcat/icons';

    $content = $this->printError();

    $template_directory = PHPWS_SOURCE_DIR . 'mod/fatcat/templates/display/';
    $templates = PHPWS_File::readDirectory($template_directory, FALSE, TRUE, NULL, array('tpl'));

    $categoryForm['PARENT']       = $_SESSION['translate']->it('Parent');
    $categoryForm['PARENT_FORM']  = $this->showSelect($this->parent, 'single');
    $categoryForm['TITLE']        = $_SESSION['translate']->it('Title');
    $categoryForm['TITLE_FORM']   = PHPWS_Form::formTextField('fat_title', $this->title);
    $categoryForm['DESC']         = $_SESSION['translate']->it('Description');
    
    $categoryForm['DESC_FORM']    = PHPWS_WizardBag::js_insert('wysiwyg', 'createCat', 'fat_desc', 1) . PHPWS_Form::formTextArea('fat_desc', $this->description, 10, 50);
    $categoryForm['TEMPLATE']     = $_SESSION['translate']->it('Template');
    if ($templates)
      $categoryForm['TEMPLATE_FORM']  = PHPWS_Form::formSelect('fat_template', $templates, $this->template, TRUE);
    else
      $categoryForm['TEMPLATE_FORM']  = $_SESSION['translate']->it('No templates found');

    // Images
    $categoryForm['IMAGE'] = $_SESSION['translate']->it('Image');
    if (is_dir($image_directory) && is_writable($image_directory)){
      $categoryForm['IMAGE'] .= CLS_help::show_link('fatcat', 'catFormImage');
      if ($current_images = PHPWS_File::readDirectory($image_directory, FALSE, TRUE)){
	foreach ($current_images as $imageName){
	  if (preg_match('/\.+(jpg|png|gif)$/i', $imageName))
	    $imageList[$imageName] = $imageName;
	}
	  
	if ($imageList)
	  $current_images = array('none'=>'&lt;'.$_SESSION['translate']->it('None').'&gt;') + $imageList;
	else
	  $current_images = array('none'=>'&lt;'.$_SESSION['translate']->it('None').'&gt;');
      }
      $categoryForm['IMAGE_FORM']      = PHPWS_Form::formFile('fat_image');
      if ($current_images)
	$categoryForm['IMG_SELECT_FORM'] = PHPWS_Form::formSelect('fat_current_image', $current_images, $this->image['name']) . ' ' . PHPWS_Form::formSubmit($_SESSION['translate']->it('Remove Image'), 'fat_removeImage');

    }
    else
      $categoryForm['IMAGE_FORM']    = CLS_help::show_link('fatcat', 'fatcatNoImage');

    // Icons
    $categoryForm['ICON'] = $_SESSION['translate']->it('Icon');
    if (is_dir($icon_directory) && is_writable($icon_directory)){
      $categoryForm['ICON'] .= CLS_help::show_link('fatcat', 'catFormIcon');
      if ($current_icons = PHPWS_File::readDirectory($icon_directory, FALSE, TRUE)){
	foreach ($current_icons as $iconName){
	  if (preg_match('/\.+(jpg|png|gif)$/i', $iconName))
	    $iconList[$iconName] = $iconName;
	}
	  
	if ($iconList)
	  $current_icons = array('none'=>'&lt;'.$_SESSION['translate']->it('None').'&gt;') + $iconList;
	else
	  $current_icons     = array('none'=>'&lt;'.$_SESSION['translate']->it('None').'&gt;');
      }
      $categoryForm['ICON_FORM']      = PHPWS_Form::formFile('fat_icon') . '<br />';

      if(count(get_extension_funcs('gd')))
	$categoryForm['ICON_FORM'] .= PHPWS_Form::formSubmit($_SESSION['translate']->it('Create Icon from Image'), 'fat_createTN').'<br />';

      if ($current_icons)
	$categoryForm['ICON_SELECT_FORM'] = PHPWS_Form::formSelect('fat_current_icon', $current_icons, $this->icon['name']) 
	  . ' ' . PHPWS_Form::formSubmit($_SESSION['translate']->it('Remove Icon'), 'fat_removeIcon');
	 

    }
    else
      $categoryForm['ICON_FORM']    = CLS_help::show_link('fatcat', 'fatcatNoIcon');

    $content .= PHPWS_Template::processTemplate($categoryForm, 'fatcat', 'categoryForm.tpl');
    return $content;
  }

  function categoryFormAction(){
    $image_directory = 'images/fatcat/images/';
    $icon_directory = 'images/fatcat/icons/';
    $this->error = NULL;

    if ($_POST['fat_title']){
      $this->title = $title = PHPWS_Text::parseInput($_POST['fat_title'], 'none');
    } else
      $this->error[] = $_SESSION['translate']->it('Missing Title').'.';

    $this->description = PHPWS_Text::parseInput($_POST['fat_desc']);

    if (PHPWS_Text::isValidInput($_POST['fat_template'], 'file'))
      $this->template = $_POST['fat_template'];
    else
      $this->error[] = $_SESSION['translate']->it('Use the selected templates only').'.';

    $this->parent = (int)$_POST['fatSelect']['fatcat'];

    if ($_FILES['fat_image']['name']){
      $image = EZform::saveImage('fat_image', $image_directory, FATCAT_MAX_IMAGE_WIDTH, FATCAT_MAX_IMAGE_HEIGHT);
      if (PHPWS_Error::isError($image)){
	$image->message('CNT_fatcat');
	$this->error[] = $_SESSION['translate']->it('Image not saved') . '.';
      } elseif (is_array($image)){
	$this->image = $image;
	if (isset($_POST['fat_createTN'])){
	  $this->icon = $this->thumbnail($_FILES['fat_image']['name'], $image_directory, $icon_directory);
	  $backToForm = 1;
	}
      }
    }
    elseif (isset($_POST['fat_current_image']) && $_POST['fat_current_image'] != 'none'){
      $image_size = getimagesize($image_directory . $_POST['fat_current_image']);

      if (isset($_POST['fat_createTN'])){
	$this->icon = $this->thumbnail($_POST['fat_current_image'], $image_directory, $icon_directory);
	$backToForm = 1;
      }

      if ($_POST['fat_current_image'] != 'none'){
	if (isset($_POST['fat_removeImage'])){
	  $backToForm = 1;
	  @unlink($image_directory . $_POST['fat_current_image']);
	} else {
	  $this->image['name'] = $_POST['fat_current_image'];
	  $this->image['width'] = $image_size[0];
	  $this->image['height'] = $image_size[1];
	}
      } else 
	$this->image = NULL;
    }

    // save icon
    if ($_FILES['fat_icon']['name']){
      $icon = EZform::saveImage('fat_icon', $icon_directory, FATCAT_MAX_ICON_WIDTH, FATCAT_MAX_ICON_HEIGHT);
      if (PHPWS_Error::isError($icon)){
	$icon->message('CNT_fatcat');
	$this->error[] = $_SESSION['translate']->it('Icon not saved') . '.';
      } elseif (is_array($icon))
	  $this->icon = $icon;
    }
    elseif (isset($_POST['fat_current_icon']) && $_POST['fat_current_icon'] != 'none'){
      if ($_POST['fat_current_icon'] != 'none'){
	if (isset($_POST['fat_removeIcon'])){
	  $backToForm = 1;
	  @unlink($icon_directory . $_POST['fat_current_icon']);
	} else {
	  $icon_size = getimagesize($icon_directory . $_POST['fat_current_icon']);
	  $this->icon['name'] = $_POST['fat_current_icon'];
	  $this->icon['width'] = $icon_size[0];
	  $this->icon['height'] = $icon_size[1];
	}
      } else 
	$this->icon = NULL;
    }
  
    if ($this->cat_id){
      if (!$this->error && !isset($backToForm)){
	$this->updateCategory();
	$GLOBALS['CNT_fatcat']['content'].= '<b>'.$_SESSION['translate']->it('Category updated') . '!</b><hr />';
      }
      else
	return FALSE;
    } else {
      if (!$this->error && !isset($backToForm)){
	$this->createCategory($this->title, $this->description, $this->template, $this->image, $this->icon, $this->parent);
	$GLOBALS['CNT_fatcat']['content'].= '<b>'.$_SESSION['translate']->it('Category created') . '!</b><hr />';
      }
      else
	return FALSE;
    }
    return TRUE;
  }

 
  function thumbnail($filename, $image_directory, $icon_directory){
    if (!is_file($image_directory . $filename)){
      $this->error[] = $_SESSION['translate']->it('[var1] does not exist', '<b>'.$image_directory.$filename.'</b>');
      return NULL;
    }

    if (preg_match('/\.(gif)$/i', $filename)){
      $this->error[] = $_SESSION['translate']->it('Cannot create icons from gif files').'.';
      return NULL;
    }
      
    $imageSize = getimagesize($image_directory . $filename);

    if ($imageSize[0] <= FATCAT_MAX_ICON_WIDTH && $imageSize[1] <= FATCAT_MAX_ICON_HEIGHT){
      if(!(PHPWS_File::fileCopy($image_directory . $filename, $icon_directory, $filename, 1, 0)))
	$this->error[] = $_SESSION['translate']->it('Unable to copy [var1] to [var2]', $image_directory . $filename, $icon_directory.$filename);
      else {
	$icon['name'] = $this->image['name'];
	$icon['width'] = $imageSize[0];
	$icon['height'] = $imageSize[1];
      }
    } else {
      $iconArray = PHPWS_File::makeThumbnail($filename, $image_directory, $icon_directory, FATCAT_MAX_ICON_WIDTH, FATCAT_MAX_ICON_HEIGHT);
      $icon['name'] = $iconArray[0];
      $icon['width'] = $iconArray[1];
      $icon['height'] = $iconArray[2];
    }
    return $icon;
  }


  function setLimit($limit){
    $this->settings['relatedLimit'] = $limit;
    return $GLOBALS['core']->sqlUpdate(array('relatedLimit'=>$limit), 'mod_fatcat_settings');
  }

  function toggleRelatedEnabled(){
    if ($this->settings['relatedEnabled'] == 0) {
    	$this->settings['relatedEnabled'] = 1;
    	return $GLOBALS['core']->sqlUpdate(array('relatedEnabled'=>1), 'mod_fatcat_settings');
	} else {
    	$this->settings['relatedEnabled'] = 0;
		return $GLOBALS['core']->sqlUpdate(array('relatedEnabled'=>0), 'mod_fatcat_settings');
	}
  }

}
?>