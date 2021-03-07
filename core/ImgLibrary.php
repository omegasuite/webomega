<?php

// Must require classes being used in this class
require_once(PHPWS_SOURCE_DIR.'core/Form.php');
require_once(PHPWS_SOURCE_DIR.'core/File.php');
require_once(PHPWS_SOURCE_DIR.'core/Pager.php');
require_once(PHPWS_SOURCE_DIR.'core/WizardBag.php');
require_once(PHPWS_SOURCE_DIR.'core/Template.php');
require_once(PHPWS_SOURCE_DIR.'core/Error.php');

/**
 * Maintains & retrieves selected URLs from an image library
 *
 * This class allows you to easily create & manage a library of images for
 * your module and allow the user to select a desired image. 
 * 
 * See /docs/developer/Image_Library.txt for usage instructions.
 *
 * @version $Id: ImgLibrary.php,v 1.4 2005/03/22 18:51:55 steven Exp $
 * @author  Eloi George <eloi@NOSPAM.bygeorgeware.com>
 * @package Core
 */
class PHPWS_IMGLib {
  /**
   * Name of module this image library belongs to.
   * Changing this allows you to have more than 1 library per module.
   * @var string
   * @access public
   */
  var $_module;

  /**
   * Image Library base directory's name.  Defaults to "library"
   * Changing this allows you to have more than 1 library per module.
   * @var string
   * @access public
   */
  var $_base_dir;

  /**
   * File Path of the library that's being accessed. 
   * @var string
   * @access public
   */
  var $_library_path;

  /**
   * What to call the image ex:"avatar", "mugshot".
   * @var string
   * @access public
   */
  var $_image_type;

  /**
   * Data to send on exit from the gallery.
   * @var string
   * @access public
   */
  var $_return_data;

  /**
   * Content_Var to use for displaying data.
   * @var string
   * @access public
   */
  var $_block;

  /**
   * Denotes that the user can add or delete images.
   * @var boolean
   * @access private
   */
  var $_can_manage_images;

  /**
   * Denotes that the user can add or delete galleries.
   * @var boolean
   * @access private
   */
  var $_can_manage_galleries;

  /**
   * Denotes that the user can select images from the galleries.
   * @var boolean
   * @access private
   */
  var $_can_select_images;

  /**
   * Display=>Directory names of all galleries.
   * @var array
   * @access private
   */
  var $_galleries;

  /**
   * Currently selected image.
   * @var string
   * @access public
   */
  var $_current_image;

  /**
   * Currently selected gallery.
   * @var string
   * @access public
   */
  var $_current_gallery;

  /**
   * Maximum size of uploaded images. In kilobytes.
   * @var array
   * @access public
   */
  var $_max_image_size;

  /**
   * Maximum height of uploaded images.
   * @var array
   * @access public
   */
  var $_max_image_height;

  /**
   * Maximum width of uploaded images.
   * @var array
   * @access public
   */
  var $_max_image_width;

  /**
   * The maximum number of images to show per page.
   * @var array
   * @access private
   */
  var $_pager_limit;

  /**
   * This flag is set to true whenever an op is ready for external processing.
   * @var array
   * @access public
   */
  var $_done;

  /**
   * This flag is set to true if a new image library was just created.
   * @var array
   * @access public
   */
  var $_created;

  /**
	* Constructor for the PHPWS_IMGLib object. 
  *
  * If all class data is not available as a $_POST variable, PHPWS_IMGLib
  * will read it from /images/<module name>/library/config.php 
  * core is loaded, it passes the configuration file name
  * to this function to initialize it. Besides preparing the hub
  * it can be used to open a branch database as well.
  *
  * @author Eloi George <eloi@NOSPAM.bygeorgeware.com>
  * @param  boolean $can_manage_images  Calling module     
  * @param  boolean $can_manage_galleries Calling module     
  * @param  boolean $can_select_images Calling module     
  * @param  string  $return_data      Data to send on exit from the gallery.
  * @param  string  $current_gallery  Currently selected gallery     
  * @param  string  $current_image    Currently selected image     
  * @param  string  $module           Calling module     
  * @param  string  $base_dir         Image Library base directory name. "library"    
  * @param  string  $block            Content_Var to use for displaying data.  
  * @param  string  $image_type       What to call the image ex:"avatar"     
  * @param  int     $max_image_size   Maximum size of uploaded images. In kilobytes.
  * @param  int     $max_image_height Maximum height of uploaded images.     
  * @param  int     $max_image_width  Maximum width of uploaded images.     
  * @return none
  * @access public
  */
  function PHPWS_IMGLib ($can_manage_images=false, $can_manage_galleries=false
        , $can_select_images=true, $return_data, $current_gallery=null, $current_image=null
        , $module=null, $base_dir='library', $block='CNT_user', $image_type='image'
        , $max_image_size=26, $max_image_height=400, $max_image_width=400) {
    $this->_done = $this->_created = false;
    $this->_galleries = array();
    $this->_pager_limit=10;
    /* Assign parameters to class variables */
    $this->_can_manage_images = $can_manage_images;
    $this->_can_manage_galleries = $can_manage_galleries;
    if (isset($_REQUEST['IMGLib_can_select_images']))
      $this->_can_select_images = $_REQUEST['IMGLib_can_select_images'];
    else
      $this->_can_select_images = $can_select_images;
    if (isset($_REQUEST['IMGLib_return_data']))
      /* Decode this string if necessary */
      $this->_return_data = $_REQUEST['IMGLib_return_data'];
    else
      $this->_return_data = $return_data;
    if (isset($_REQUEST['IMGLib_current_image']))
      $this->_current_image = stripslashes($_REQUEST['IMGLib_current_image']);
    else  
      $this->_current_image = $current_image;
    if (isset($_REQUEST['IMGLib_current_gallery']))
      $this->_current_gallery = stripslashes($_REQUEST['IMGLib_current_gallery']);
    else  
      $this->_current_gallery = $current_gallery;
    if ($module)
      $this->_module = $module;
    else
      $this->_module = $GLOBALS['core']->current_mod;
    $this->_base_dir         = $base_dir;
    $this->_block            = $block;
    $this->_image_type       = $image_type;
    $this->_max_image_size   = $max_image_size;
    $this->_max_image_height = $max_image_height;
    $this->_max_image_width  = $max_image_width;
     // PHPWS_HOME_DIR define has been deprecated because it needs to be able to change on the fly for branches and defines cannot do that
    $this->_library_path = $GLOBALS['core']->home_dir.'images/'.$this->_module.'/'.$this->_base_dir.'/';
    /* Pull settings data from textfile */
    $config_file = $this->_library_path.'config.php';
    if (!file_exists($config_file) && !$this->create_library()) {
      exit('ERROR (PHPWS_IMGLib): No library settings file found or created!');
    }
    include($config_file);
    /* Start processing any POSTed current gallery view data */
    if (isset($_REQUEST['IMGLib_selected_view'])) 
      $this->_current_view = $_REQUEST['IMGLib_selected_view'];
    /* If no current view was requested, use the current image's gallery */
    elseif(!empty($this->_current_gallery)) {
      $g = $this->_current_gallery;
      /* Strip any trailing slashes */
      if (substr($g,-1)=='/') $g = substr($g,0,-1);
      /* Strip any parent directory names & the trailing slash */
      if (strrchr($g, '/')) $g = substr(strrchr($g, '/'), 1);
      /* Make sure that the supplied gallery exists */
      if (array_key_exists($g, $this->_galleries)) 
        $this->_current_view = $g;
    }
    /* otherwise, just pick the first gallery */
    else {
      reset($this->_galleries);
      $this->_current_view = key($this->_galleries);
    }
    $this->_done = false;
    if (isset($_REQUEST['PAGER_limit']))
      $this->_pager_limit = $_REQUEST['PAGER_limit'];
  }// END FUNC PHPWS_IMGLib()


  /**
	* Determines & performs the requested PHPWS_IMGLib operation. 
  *
  * @author Eloi George <eloi@NOSPAM.bygeorgeware.com>
  * @param  string  $op   Action to be performed
  * @return none
  * @access public
  */
  function action ($op='view_gallery') {
  	if ($op=='upload_image' && $this->_can_manage_images)
  		$this->upload_image();
  	elseif ($op=='delete_image' && $this->_can_manage_images)
  		$this->delete_image();
  	elseif ($op=='move_image' && $this->_can_manage_images) 
   		$this->move_image();
  	elseif ($op=='image_mgmt' && isset($_POST['IMGLib_delete']) && $this->_can_manage_images)
  		$this->delete_image();
  	elseif ($op=='create_gallery' && $this->_can_manage_galleries) {
  	  if (!$this->update_settings(stripslashes($_POST['IMGLib_new_gallery'])) 
  	    || !$this->_current_view = 
  	          array_search(stripslashes($_POST['IMGLib_new_gallery']), $this->_galleries)) { 
        reset($this->_galleries);
        $this->_current_view = key($this->_galleries);
    	}
  		$this->view_gallery();
  	}
  	elseif ($op=='delete_gallery' && $this->_can_manage_galleries) 
  		$this->delete_gallery();
  	elseif ($op=='rename_gallery' && $this->_can_manage_galleries)
  		$this->rename_gallery();
  	elseif ($op=='update_settings' && $this->_can_manage_galleries)
  		$this->update_settings();
    else
  		$this->view_gallery();
  }// END FUNC action()


  /**
	* Updates the library settings file. 
  *
  * This is also used to create a new image gallery.
  *
  * @author Eloi George <eloi@NOSPAM.bygeorgeware.com>
  * @param  string  $new_gallery   New gallery to be created
  * @return none
  * @access public
  */
  function update_settings ($new_gallery=null) {
    /* If a new gallery is requested & is not a duplicate... */
    $lastnum = true; 
    if (!empty($new_gallery)) {
      if (array_search($new_gallery, $this->_galleries)!==false) {
        echo 'ERROR (PHPWS_IMGLib): A Gallery named "'.$new_gallery.'" already exists!<br />';
        return false;
      }
      /* Determine the last directory id */
      if ($dirlist = PHPWS_File::readDirectory($this->_library_path, true)) {
        asort($dirlist);
        $lastnum = array_pop($dirlist); 
      }
      else 
        $lastnum = 0; 
      /* Create a new directory */
      $dir = $this->_library_path . ++$lastnum.'/';
     if (@mkdir($dir)) {
        chmod($dir, 0755);
        $this->_galleries[$lastnum] = $new_gallery;
      }
      else {
        /* exit with an error */
        echo 'ERROR (PHPWS_IMGLib): Couldn\'t create directory '.$dir.'<br />';
        return false;
      }
    }

    /* Create a new config.php file */
    $config_info = "<?php\n";
    ksort($this->_galleries);
    foreach ($this->_galleries as $loc => $name) 
      $config_info .= '$this->_galleries["'.$loc.'"] = stripslashes("'.addslashes($name). "\");\n"; 
    $config_info .= '?>';

    if (!PHPWS_File::writeFile($this->_library_path.'config.php', $config_info, TRUE, TRUE)) {
      echo 'There was an error writing to the file'.$this->_library_path.'config.php <br />'.'Settings have not been changed!<br />';
      return false;
    }
    return $lastnum;
  }// END FUNC update_settings()

  /**
	* Displays the current gallery and lets the user pick an image. 
  *
  * @author Eloi George <eloi@NOSPAM.bygeorgeware.com>
  * @param  none
  * @return none
  * @access public
  */
  function view_gallery () {
    /* Set up persistent image row variables */
    $vars = $this->post_class_vars();
    if ($this->_can_select_images) 
      $ops = array('select_image'=>'Select');
    if ($this->_can_manage_images) {
      $ops['move_image'] = 'Move';
      $ops['delete_image'] = 'Delete';
    }
    /* Make a sorted list of all files in the currently selected gallery */
    if (!$result = PHPWS_File::readDirectory($this->_library_path.$this->_current_view,false,true)) {
      $result = array();
    }
    natcasesort($result);

    /* Set up paging information */
    $pager = new PHPWS_Pager;
    $pager->makeArray(TRUE);
    $pager->limit = $this->_pager_limit;
    $pager->setlinkback($GLOBALS['SCRIPT'] . '?module='.$this->_module
      . '&IMGLib_op=view_gallery&IMGLib_can_select_images='.$this->_can_select_images
      . '&IMGLib_current_image='.$this->_current_image
      . '&IMGLib_current_gallery='.$this->_current_gallery
      . '&IMGLib_selected_view='.$this->_current_view
      . '&IMGLib_return_data='.$this->_return_data);
    $pager->setData($result);
    $pager->pageData();
    $result = $pager->getData();
    $tags['PAGE_BACKWARD_LINK'] = $pager->getBackLink();
    $tags['SECTION_LINKS'] = $pager->getSectionLinks();
    $tags['PAGE_FORWARD_LINK'] = $pager->getForwardLink();
    $tags['SECTION_INFO'] = $pager->getSectionInfo() . ucfirst($this->_image_type);
    $tags['LIMIT_LINKS'] = $pager->getLimitLinks();
    $tags['LIMIT_LINKS_LABEL'] = $_SESSION['translate']->it('Rows to show per page');

    /* Get all image information & max H&W */
    $maxh = $maxw = 90;
    $filelist = array();
    foreach($result as $f) {
      $filelist[$f] = getimagesize($this->_library_path.$this->_current_view.'/'.$f); 
      if ($filelist[$f][1] > $maxh) $maxh = $filelist[$f][1];
      if ($filelist[$f][0] > $maxw) $maxw = $filelist[$f][0];
    }

    /* Create image row HTML */
    $row['IHEIGHT'] = $maxh;
    $row['HEIGHT'] = $maxh + 30;
    $row['WIDTH']  = $maxw + 20;
    $tags['IMAGE_SELECT_LST'] = '';
    $bg = null;
    foreach($filelist as $fname=>$finfo) { 
      $row['BG'] = $bg;
      // All image access should be relative, http:// was being hard-coded which would break in ssl sites (https://)
      $row['IMAGE'] = '<img src="./images/'.$this->_module.'/'
          . $this->_base_dir.'/'.$this->_current_view.'/'.$fname.'" alt="'.$fname.'" title="'.$fname.'" '.$finfo[3].' />';
      $row['IMAGE_NAME'] = PHPWS_Form::formCheckBox('IMGLib_selected_image['.$fname.']', $fname)
          . preg_replace("/[^a-zA-Z0-9]/", ' ', str_replace(strrchr($fname, '.'), '', $fname));
      if(strpos($this->_current_gallery, '/'.$this->_current_view) && $fname==$this->_current_image)
        $row['IMAGE_NAME'] .= '<br />'.$_SESSION['translate']->it('[Currently Selected]');
      $tags['IMAGE_SELECT_LST'] .= PHPWS_Template::processTemplate($row,'core','ImgLibrary_view_row.tpl');
  		PHPWS_WizardBag::toggle($bg, ' class="bg_light"');
    }
  	if (empty($filelist)) 
      $tags['IMAGE_SELECT_LST'] = '<br /><br />'.$_SESSION['translate']->it('This gallery is empty!').'<br /><br />';
  	else 
    	$tags['IMAGE_SELECT_DLG'] = $vars 
        . PHPWS_Form::formHidden('IMGLib_selected_gallery', $this->_base_dir.'/'.$this->_current_view.'/')
  	    . $_SESSION['translate']->it('With selected [var1]',$this->_image_type).': '
  	    . PHPWS_Form::formSelect('IMGLib_op', $ops)
  	    . PHPWS_Form::formSubmit($_SESSION['translate']->it('Go'), 'IMGLib_btn'); 

    /* Populate the rest of the template tags */
    $tags['IMAGE_SELECT_LBL'] = $_SESSION['translate']->it('Select An [var1]',ucfirst($this->_image_type));

  	$form[0] = $vars . PHPWS_Form::formHidden('IMGLib_op', 'view_gallery')
  	    . $_SESSION['translate']->it('Choose another [var1] gallery',$this->_image_type).': '
  	    . PHPWS_Form::formSelect('IMGLib_selected_view', $this->_galleries, $this->_galleries[$this->_current_view])
  	    . PHPWS_Form::formSubmit($_SESSION['translate']->it('Go!'), 'IMGLib_btn'); 
    $tags['GALL_CHOOSE_DLG'] = PHPWS_Form::makeForm('IMGLib_choose_dlg', 'index.php', $form, 'post', 0, TRUE);
    $tags['GALL_EXIT_LNK'] = '[<a href="index.php?module='.$this->_module
        . '&IMGLib_op=exit&IMGLib_return_data='.$this->_return_data.'">' . $_SESSION['translate']->it('Exit The Gallery').'</a>]';
    if ($this->_can_manage_images) {
      $tags['IMAGE_MGMT_TITLE']   = ucfirst($this->_image_type).' '.$_SESSION['translate']->it('Upload');
      $tags['IMAGE_UPLOAD_LBL'] = 
          $_SESSION['translate']->it('Your [var1] must be no bigger than [var2] pixels high by [var3] pixels wide.'
              , $this->_image_type, $this->_max_image_height, $this->_max_image_width) .'<br />'
          . $_SESSION['translate']->it('Maximum uploaded [var1] size is [var2]KB.'
              , $this->_image_type, $this->_max_image_size);
      $form[0] = PHPWS_Form::formFile('IMGLib_loaded_image', 33, 255) 
      		. ' '.$this->gallery_button($_SESSION['translate']->it('Save'), 'upload_image', true);
      $tags['IMAGE_UPLOAD_DLG'] = PHPWS_Form::makeForm('IMGLib_upload_image_dlg', 'index.php', $form, 'post', 0, TRUE);
    }
    if ($this->_can_manage_galleries) {
      $tags['GALL_MGMT_TITLE'] = $_SESSION['translate']->it('Gallery Management');
      $tags['GALL_CREATE_LBL'] = $_SESSION['translate']->it('<b>Create A Gallery:</b> Give your new gallery a name as it will appear in the gallery selection box.');
      $tags['GALL_DELETE_LBL'] = $_SESSION['translate']->it('<b>Delete:</b> Click the button below to delete this gallery and all images located inside it.');
      $tags['GALL_RENAME_LBL'] = null;
      $form[0] = PHPWS_Form::formTextField('IMGLib_new_gallery', '', 35, 70) 
      		. ' '.$this->gallery_button($_SESSION['translate']->it('Create'), 'create_gallery', true);
      $tags['GALL_CREATE_DLG'] = PHPWS_Form::makeForm('IMGLib_create_gallery_dlg', 'index.php', $form, 'post', 0, TRUE);
      $form[0] = PHPWS_Form::formTextField('IMGLib_new_gallery', '', 35, 70, $_SESSION['translate']->it('<b>Rename:</b> Change this gallery\'s name to').': ') 
      		. ' '.$this->gallery_button($_SESSION['translate']->it('Rename'), 'rename_gallery', true);
      $tags['GALL_RENAME_DLG'] = PHPWS_Form::makeForm('IMGLib_rename_gallery_dlg', 'index.php', $form, 'post', 0, TRUE);
      $tags['GALL_DELETE_DLG'] = $this->gallery_button($_SESSION['translate']->it('Delete This Gallery'), 'delete_gallery');
    }

    $GLOBALS[$this->_block]['title'] = $_SESSION['translate']->it('[var1] Gallery : [var2]', ucfirst($this->_image_type), str_replace('.','',$this->_galleries[$this->_current_view]));
    $GLOBALS[$this->_block]['content'] = PHPWS_Template::processTemplate($tags,'core','ImgLibrary_view_gallery.tpl');
  }// END FUNC view_gallery()

  /**
	* Deletes an image gallery. 
  *
  * @author Eloi George <eloi@NOSPAM.bygeorgeware.com>
  * @param  none
  * @return none
  * @access public
  */
  function delete_gallery () {
    if(isset($_POST['IMGLib_yes'])) {
    	$g = $this->_galleries[$this->_current_view];
      if (PHPWS_File::rmdir($this->_library_path.$this->_current_view.'/')) {
      	unset($this->_galleries[$this->_current_view]);
      	$this->update_settings();
      	$str1 = $_SESSION['translate']->it('Gallery Deleted');
      	$str2 = 'has successfully been';
      }
      else {
      	$str1 = $_SESSION['translate']->it('ERROR');
      	$str2 = 'could not be';
      }
    	$GLOBALS[$this->_block]['title'] = $_SESSION['translate']->it('Image Library').' - '. $str1 .'!';
    	$content = $_SESSION['translate']->it('The [var1] <b>"[var2]"</b> '.$str2.' [var3]'
              , $_SESSION['translate']->it('gallery'), $g
              , '<b>'.$_SESSION['translate']->it('deleted').'</b>!')
    	    . '<br /><br />';
    	$GLOBALS[$this->_block]['content'] = $content .'<br /><br /><center>'. $this->gallery_button().'</center>';
    }

    elseif(isset($_POST['IMGLib_no'])) {
  		$this->view_gallery();
    }

    else {
    	$myform[0] = $this->post_class_vars()
    	    . PHPWS_Form::formHidden('IMGLib_op', 'delete_gallery')
    	    . PHPWS_Form::formSubmit($_SESSION['translate']->it('Yes'), 'IMGLib_yes') 
    	    . '&nbsp;&nbsp;'
    	    . PHPWS_Form::formSubmit($_SESSION['translate']->it('No'), 'IMGLib_no');

    	$GLOBALS[$this->_block]['title'] = $_SESSION['translate']->it('Image Library')
    	      .' - '. $_SESSION['translate']->it('Confirm Action').'!';
    	$GLOBALS[$this->_block]['content'] = '<br /><br />'
    	  . $_SESSION['translate']->it('Are you sure you want to <b>[var1] "[var2]"</b>?'
    	    , $_SESSION['translate']->it('delete'), $this->_galleries[$this->_current_view]) 
    	  . PHPWS_Form::makeForm('IMGLib_confirm_delete', 'index.php', $myform, 'post', 0, 0)
    	  . '<br /><br />';
    }
  }// END FUNC delete_gallery()

  /**
	* Renames an image gallery.
  *
  * @author Eloi George <eloi@NOSPAM.bygeorgeware.com>
  * @param  none
  * @return none
  * @access public
  */
  function rename_gallery () {
    $this->_galleries[$this->_current_view] = stripslashes($_POST['IMGLib_new_gallery']);
    if ($this->update_settings()) 
  		$this->view_gallery();
  	else {
    	$GLOBALS[$this->_block]['title'] = $_SESSION['translate']->it('Image Library').' - '. $str1 .'!';
    	$GLOBALS[$this->_block]['content'] = $_SESSION['translate']->it('The [var1] <b>"[var2]"</b> could not be [var3]'
              , $_SESSION['translate']->it('gallery'), $this->_galleries[$this->_current_view]
              , '<b>'.$_SESSION['translate']->it('renamed').'</b>!')
    	    . '<br /><br /><center>'. $this->gallery_button().'</center>';
  	}
  }// END FUNC rename_gallery()

  /**
	* Uploads an image to the current image gallery. 
  *
  * @author Eloi George <eloi@NOSPAM.bygeorgeware.com>
  * @param  none
  * @return none
  * @access public
  */
  function upload_image () {
    $image = EZform::saveImage('IMGLib_loaded_image'
          , $this->_library_path.$this->_current_view.'/'
          , $this->_max_image_width
          , $this->_max_image_height
          , $this->_max_image_size*1024);
    if (PHPWS_Error::isError($image))
      $image->message($this->_block, $_SESSION['translate']->it('Image Upload Failed'));
 		$this->view_gallery();
  }// END FUNC upload_image()

  /**
	* Deletes an image from the current image gallery. 
  *
  * @author Eloi George <eloi@NOSPAM.bygeorgeware.com>
  * @param  none
  * @return none
  * @access public
  */
  function delete_image () {
    if(isset($_POST['IMGLib_yes'])) {
      foreach($_POST['IMGLib_selected_image'] as $f) 
        $status = @unlink($this->_library_path.$this->_current_view.'/'.$f);
      if ($status) {
      	$str1 = $_SESSION['translate']->it('Gallery Deleted');
      	$str2 = 'has successfully been';
        $this->_done = true;
      }
      else {
      	$str1 = $_SESSION['translate']->it('ERROR');
      	$str2 = 'could not be';
      }
    	$GLOBALS[$this->_block]['title'] = $_SESSION['translate']->it('Image Library').' - '. $str1 .'!';
    	$content = $_SESSION['translate']->it('The [var1] <b>"[var2]"</b> '.$str2.' [var3]'
    	            , $this->_image_type, implode('" & "', $_POST['IMGLib_selected_image'])
    	            , '<b>'.$_SESSION['translate']->it('deleted').'</b>!')
    	    . '<br /><br />';
    	$GLOBALS[$this->_block]['content'] = $content .'<br /><br /><center>'. $this->gallery_button().'</center>';
    }

    elseif(isset($_POST['IMGLib_no'])) {
  		$this->view_gallery();
    }

    else {
    	$myform[0] = $this->post_class_vars()
  	    . PHPWS_Form::formHidden('IMGLib_op', 'delete_image')
  	    . $this->post_array('IMGLib_selected_image', $_POST['IMGLib_selected_image'])
  	    . PHPWS_Form::formSubmit($_SESSION['translate']->it('Yes'), 'IMGLib_yes') 
  	    . '&nbsp;&nbsp;'
  	    . PHPWS_Form::formSubmit($_SESSION['translate']->it('No'), 'IMGLib_no');

    	$GLOBALS[$this->_block]['title'] = $_SESSION['translate']->it('Image Library')
    	      .' - '. $_SESSION['translate']->it('Confirm Action').'!';
    	$GLOBALS[$this->_block]['content'] = '<br /><br />'
    	  . $_SESSION['translate']->it('Are you sure you want to [var1] "[var2]"?'
    	    , $_SESSION['translate']->it('delete'), implode('" & "', $_POST['IMGLib_selected_image'])) 
    	  . PHPWS_Form::makeForm('IMGLib_confirm_delete', 'index.php', $myform, 'post', 0, 0)
    	  . '<br /><br />';
    }
  }// END FUNC delete_image()

  /**
	* Moves an image to another image gallery. 
  *
  * @author Eloi George <eloi@NOSPAM.bygeorgeware.com>
  * @param  none
  * @return none
  * @access public
  */
  function move_image () {
    if(isset($_POST['IMGLib_yes'])) {
      if (!isset($_POST['IMGLib_selected_gallery']))
        return;
      $status = true;
      $from = $this->_library_path.$this->_current_view.'/';
      $to = $this->_library_path.$_POST['IMGLib_selected_gallery'].'/';
      foreach($_POST['IMGLib_selected_image'] as $f) 
        /* If file copies OK, erase it */
        if ($status && $status = PHPWS_File::fileCopy($from.$f, $to, $f, true, true)) {
          $status = @unlink($from.$f); 
        }

      if ($status) {
      	$str1 = $_SESSION['translate']->it('File Move Complete');
      	$str2 = 'has successfully been';
        $this->_done = true;
      }
      else {
      	$str1 = $_SESSION['translate']->it('ERROR');
      	$str2 = 'could not be';
      }
    	$GLOBALS[$this->_block]['title'] = $_SESSION['translate']->it('Image Library').' - '. $str1 .'!';
    	$content = $_SESSION['translate']->it('The [var1] <b>"[var2]"</b> '.$str2.' [var3]'
  	            , $this->_image_type, implode('" & "', $_POST['IMGLib_selected_image'])
  	            , '<b>'.$_SESSION['translate']->it('moved').'</b>!')
    	    . '<br /><br />';
    	$GLOBALS[$this->_block]['content'] = $content .'<br /><br /><center>'. $this->gallery_button().'</center>';
    }

    elseif(isset($_POST['IMGLib_no'])) {
  		$this->view_gallery();
    }

    else {
    	$myform[0] = $this->post_class_vars()
  	    . PHPWS_Form::formHidden('IMGLib_op', 'move_image')
  	    . $this->post_array('IMGLib_selected_image', $_POST['IMGLib_selected_image'])
  	    . PHPWS_Form::formSelect('IMGLib_selected_gallery', $this->_galleries, $this->_galleries[$this->_current_view])
  	    . PHPWS_Form::formSubmit($_SESSION['translate']->it('Move'), 'IMGLib_yes') 
  	    . '&nbsp;&nbsp;'
  	    . PHPWS_Form::formSubmit($_SESSION['translate']->it('Cancel'), 'IMGLib_no');

    	$GLOBALS[$this->_block]['title'] = $_SESSION['translate']->it('Image Library')
    	      .' - '. $_SESSION['translate']->it('Confirm Action').'!';
    	$GLOBALS[$this->_block]['content'] = '<br /><br />'
    	  . $_SESSION['translate']->it('Where do you want to move "[var1]" to?'
    	    , implode('" & "', $_POST['IMGLib_selected_image'])) . '<br />'
    	  . PHPWS_Form::makeForm('IMGLib_confirm_move', 'index.php', $myform, 'post', 0, 0)
    	  . '<br /><br />';
    }
  }// END FUNC move_image()

  /**
	* Prepares class variables to be passed via $_POST. 
  *
  * @author Eloi George <eloi@NOSPAM.bygeorgeware.com>
  * @param  none
  * @return string  HTML for all class variables to be POSTed
  * @access private
  */
  function post_class_vars () {
    return PHPWS_Form::formHidden('module', $this->_module) 
    . PHPWS_Form::formHidden('IMGLib_can_select_images', $this->_can_select_images)
    . PHPWS_Form::formHidden('IMGLib_return_data', $this->_return_data)
    . PHPWS_Form::formHidden('IMGLib_current_image', $this->_current_image)
    . PHPWS_Form::formHidden('IMGLib_current_gallery', $this->_current_gallery)
    . PHPWS_Form::formHidden('PAGER_limit', $this->_pager_limit)
    . PHPWS_Form::formHidden('IMGLib_selected_view', $this->_current_view);
  }// END FUNC post_class_vars()

  /**
	* Prepares an array to be passed via $_POST. 
  *
  * @author Eloi George <eloi@NOSPAM.bygeorgeware.com>
  * @param  string  $name Name to give POSTed variable
  * @param  array   Array of variables to post
  * @return string  HTML for hidden array to be POSTed
  * @access private
  */
  function post_array ($name, $array) {
    if (!is_array($array))
      return;
    foreach($array as $key=>$value) 
      $p .= PHPWS_Form::formHidden($name.'['.$key.']', $value);
    return $p;
  }// END FUNC post_class_vars()

  /**
	* Returns a form with a "View Gallery" button and class variables. 
  *
  * @author Eloi George <eloi@NOSPAM.bygeorgeware.com>
  * @param  string  $label    Button label text
  * @param  string  $action   Operation to perform
  * @param  string  $in_form  Whether this is included in a larger form
  * @return HTML for a centered button
  * @access private
  */
  function gallery_button ($label=null, $action=null, $in_form=false) {
    if (!$label)
      $label = $_SESSION['translate']->it('View Gallery');
    if (!$action)
      $action = 'view_gallery';
  	$myform[0] = $this->post_class_vars()
  	    . PHPWS_Form::formHidden('IMGLib_op', $action)
  	    . PHPWS_Form::formSubmit($label, 'IMGLib_btn'); 
  	if ($in_form)
  	  return $myform[0];
  	else 
  	  return PHPWS_Form::makeForm('IMGLib_button', 'index.php', $myform, 'post', 0, 0);
  }// END FUNC gallery_button()


  /**
	* Creates all files needed for a module's image library. 
  *
  * @author Eloi George <eloi@NOSPAM.bygeorgeware.com>
  * @param  None.  
  * @return bool  Success or Failiure.
  * @access private
  */
  function create_library () {
    /* Create the main library directory */
    $the_path = substr($this->_library_path, 0, -1);
    if(!is_dir($the_path)) { 
      $a='';
      foreach(explode('/',$the_path) AS $k) {
        $a.=$k.'/';
        if(!is_dir($a)) { 
	    PHPWS_File::makeDir($a);
        }
      } 
    }
    if(!is_dir($the_path)) 
      return false;
    else 
      /* If the settings file & general gallery are successfully created.. */
      if ($this->_created = $this->update_settings('.General Images')) {
        /* Copy any extraneous files that may be in the base directory to the general gallery */
        if ($filelist = PHPWS_File::readDirectory($this->_library_path,false,true)) {
          foreach($filelist as $f) { 
            if ($f != 'config.php' 
                && PHPWS_File::fileCopy($this->_library_path.$f, $this->_library_path.$this->_created.'/', $f, true, true))
              @unlink($this->_library_path.$f);
          }
        }
        return true;
      }
  }// END FUNC post_class_vars()
}//END CLASS: PHPWS_IMGLib
?>