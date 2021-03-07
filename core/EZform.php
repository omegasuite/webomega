<?php

/* These are the default values for various form elements */
define("DFLT_ROWS", 5);
define("DFLT_COLS", 40);
define("DFLT_TEXT_SIZE", 20);
define("DFLT_MAX_SIZE", 255);
define("DFLT_MAX_SELECT", 4);
define("MAX_IMAGE_WIDTH", 640);
define("MAX_IMAGE_HEIGHT", 480);
define("MAX_IMAGE_SIZE", 80000);

require_once PHPWS_SOURCE_DIR . "core/EZelement.php";
require_once("Date.php");

/**
 * Creates HTML form elements and/or an entire form
 *
 * This class is stand alone. You must construct an object within your
 * function to get it to work:
 * $form = new EZform;
 *
 * This class allows you to easily create a form and then fetch elements of
 * that form. It also allows you to export a template of the form that you
 * can use within phpWebSite.
 *
 * Once the object is created just start adding elements to it with the add function.
 * Example:
 * $form->add("testarea");
 *
 * This would create a form element named 'testarea'. You can set the type and value via
 * the setType and setValue functions or you can just include them in the add.
 * Example:
 * $form->add("testarea", "textarea", "something something");
 *
 * For many form elements, that may be all you need.
 *
 * @version $Id: EZform.php,v 1.77 2005/08/17 12:43:29 matt Exp $
 * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
 * @author Don Seiler <don@NOSPAM.seiler.us>
 * @author Steven Levin <steven@NOSPAM.tux.appstate.edu>
 * @package Core
 *
 */
class EZform {
  /**
   * Optional name of form
   * @var string
   * @access private
   */
  var $_formName;

  
  /**
   * Array of form elements
   * @var    array
   * @access private
   */
  var $_elements;

  /**
   * Directory destination of submitted form.
   * Note: if none is provided, getTemplate will try to use the core
   * home_http directory
   * 
   * @var    string
   * @access private
   */
  var $_action;

  /**
   * How the form is sent.
   * @var    string
   * @access private
   */
  var $_method;

  /**
   * Tells whether to multipart encode the form
   * @var    string
   * @access private
   */
  var $_encode;


  /**
   * Error message
   * @var    string
   * @access public
   */
  var $error;

  /**
   * Constructor for class
   */
  function EZform($formName=NULL){
    $this->_formName = $formName;
    $this->_elements = array();
    $this->_action   = NULL;
    $this->_method   = 'method="post"';
    $this->_encode   = NULL;
    $this->error     = NULL;
  }

  /**
   * Adds a form element to the class
   *
   * The type and value parameters are optional, though it is a timesaver.
   * See setType for the form types.
   * See setValue for value information.
   *
   * @author             Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param string name  The name of the form element
   * @param string type  The type of form element (text, check, radio, etc)
   * @param mixed  value The default value of the form element
   */
  function add($name, $type=NULL, $value=NULL){
    if (preg_match("/[^\[\]a-z0-9_]/i", $name)){
      $this->error = "You may not use <b>$name</b> as a form element name.<br />\n";
      return FALSE;
    }

    if (isset($this->_elements[$name]))
      $this->error = "Warning: The element name <b>$name</b> is already in use.";

    $this->_elements[$name] = new EZelement;
    $this->_elements[$name]->name = $name;

    if ($type)
      $this->setType($name, $type);

    $this->setValue($name, $value);
    return TRUE;
  }


  /**
   * Removes a form element from the class
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   */
  function drop($name){
    unset($this->_elements[$name]);
  }

  /**
   * Returns the value of the error variable
   *
   * @author            Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @return string     Current error
   */
  function getError(){
    return $this->error;
  }

  /**
   * Sets form element type
   *
   * The types are restricted are:
   * radio
   * hidden
   * file
   * text
   * password
   * textarea
   * checkbox
   * select or dropbox
   * multiple
   * submit
   * button
   * reset
   *
   * Select and dropbox are for a drop-down selection box and multiple is
   * for a multiple selection drop box.
   *
   * @author            Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param string name Name of element to set the type
   * @param string type Type of form to set to the element
   */
  function setType($name, $type){
    if (!$this->testName($name))
      return FALSE;

    if ($type == "file")
      $this->_encode = " enctype=\"multipart/form-data\"";

    if (!$this->_elements[$name]->_setType($type))
      $this->error = $this->_elements[$name]->error;
  }

    /**
     * Allows you to set the id attribute to an element.
     *
     * If $id is not passed then $id will be set to $name
     *
     * @author            Steven Levin <steven@NOSPAM.tux.appstate.edu>
     * @param string name Name of element to set the type
     * @param string id   Identifier for the element
     */
    function setId($name, $id=null){
	if (!$this->testName($name))
	    return FALSE;
	
	if(!isset($id)) {
	    $id = $name;
	}
	
	if (!$this->_elements[$name]->_setId($id))
	    $this->error = $this->_elements[$name]->error;
    }
    
  /**
   * Allows you to enter extra information to an element.
   *
   * This is useful for style components, javascript, etc.
   *
   * @author            Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param string name Name of element to set the type
   * @param string type Extra text to add to element
   */
  function setExtra($name, $extra){
    if (!$this->testName($name))
      return FALSE;

    if (!$this->_elements[$name]->_setExtra($extra))
      $this->error = $this->_elements[$name]->error;
  }

  /**
   * Lets you enter a width style to a text field or area
   *
   * Instead of setting a column number, you may prefer applying
   * a style width. The width will size itself depending on side of
   * or its contained (i.e. a table cell)
   *
   * @author             Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param string name  Name of element to set the type
   * @param string width Percentage of width wanted on element
   */
  function setWidth($name, $width){
    if (!$this->testName($name))
      return FALSE;

    if (!$this->_elements[$name]->_setWidth($width))
      $this->error = $this->_elements[$name]->error;
  }

  /**
   * Lets you enter a height style to a text field or area
   *
   * Instead of setting a row number for a textarea, you may prefer
   * applying a style width. The width will size itself depending on
   * side of or its contained (i.e. a table cell).
   *
   * Note: You can set the height of a text field, but it will look
   * strange and it has no real functionality.
   *
   * @author              Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param string name   Name of element to set the type
   * @param string height Percentage of height wanted on element
   */
  function setHeight($name, $height){
    if (!$this->testName($name))
      return FALSE;

    if (!$this->_elements[$name]->_setHeight($height))
      $this->error = $this->_elements[$name]->error;
  }

  /**
   * Allows you to set the numbers of rows for a textarea
   *
   * Rows must be more than 1 and less than 100
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param string name Name of element to set the rows
   * @param string rows Number rows to use in a textarea
   */
  function setRows($name, $rows){
    if (!$this->testName($name))
      return FALSE;

      if (!$this->_elements[$name]->_setRows($rows))
	$this->error = $this->_elements[$name]->error;
  }

  /**
   * Allows you to set the numbers of columns for a textarea
   *
   * Columns must be more than 10 and less than 500
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param string name Name of element to set the rows
   * @param string rows Number columns to use in a textarea
   */
  function setCols($name, $cols){
    if (!$this->testName($name))
      return FALSE;

      if (!$this->_elements[$name]->_setCols($cols))
	$this->error = $this->_elements[$name]->error;
  }


  /**
   * Sets the tabindex for a form element
   *
   * Tab indexing allows use of the tab key to move among
   * form elements (like Windows). Just give the name of the element
   * and what order you want it in. EZform does not check your settings
   * so be careful you don't use the same number more than once.
   *
   * @author               Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string  name  Name of element to set the type
   * @param  integer order Numeric order of tab queue
   */
  function setTab($name, $order){
    if (!$this->testName($name))
      return FALSE;

    if (!$this->_elements[$name]->_setTab($order))
      $this->error = $this->_elements[$name]->error;
  }

  /**
   * Sets the number of characters for text boxes, number of rows
   * for select boxes
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param string name Name of element to set the type
   * @param string size Size to make the element
   */
  function setSize($name, $size){
    if (!$this->testName($name))
      return FALSE;

    $this->_elements[$name]->_setSize($size);
  }

  /**
   * Sets the display of the password
   *
   * If you set an element showPass to TRUE, then the symbols
   * will appear in the password field. The password will NOT
   * appear in the source BUT the number of characters will be indicated.
   * Be careful turning this on.
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param string name Name of element to set the type
   * @param string show Set to TRUE to make the field have characters, FALSE otherwise.
   */
  function showPass($name, $show){
    if ($show)
      $this->_elements[$name]->showPass=TRUE;
    else
      $this->_elements[$name]->showPass=FALSE;
  }


  /**
   * Changed the template tag name for the form element
   *
   * Should be used if you do not want to use the name of the post
   * variable for your template. A good function to use to convert
   * old templates. 
   *
   * @author                Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param string name     Name of element to set the type
   * @param string template Name of template tag to print for this element
   */
  function setTag($name, $tag){
    if (!$this->testName($name))
      return FALSE;

    if (!$this->_elements[$name]->_setTag($tag))
      $this->error = $this->_elements[$name]->error;
  }

  /**
   * Sets the value of an element
   *
   * This does different things depending on the element.
   * For text and textarea, the value will appear in the field.
   *
   * For a radio button, these are the options. So you would send an array of 
   * different choices:
   * $form->setValue("myChoice", array("yes", "no");
   * This would create two radio buttons (myChoice_1, myChoice_2) with values "yes" and "no"
   * respectively.
   * 
   * You will also send an array to select and multiple. The key of the array cooresponds to
   * the value of the option and the value of the array would be the text in the drop down box.
   *
   * If you want to send a list, and want what appears to be the value, you will need to copy the value
   * to the index or run reindexValue afterwards.
   * Example:
   * 
   * $form->setValue("mySelect", array("red", "blue", "green");
   * This would create
   * <input value="0">red</input>
   * <input value="1">blue</input>
   * etc.
   * So you would send array("red"=>"red", "blue"=>"blue", "green"=>"green") instead or
   * $form->reindexValue("mySelect");
   *
   * @author             Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param string name  Name of element to set the value
   * @param mixed  value Value to set to the element
   */
  function setValue($name, $value){
    if (!$this->testName($name))
      return FALSE;

    if (!empty($value))
      $this->_elements[$name]->_setValue($value);
    else {
        switch($this->_elements[$name]->getType()) {
            case "checkbox":
                $this->_elements[$name]->_setValue(1);
                break;
            case "select":
            case "multiple":
                $this->_elements[$name]->_setValue(array());
                break;
            case "text":
            case "textarea":
                $this->_elements[$name]->_setValue($value);
                break;
        }
    }
  }

  /**
   * Reindexes the value. Sets the value of the array equal to the index.
   *
   * Use this function after setting the match.
   *
   * Example: 
   * $list = array('apple', 'orange', 'peach', 'banana');
   * $form = new EZform;
   * $form->add("testing", "multiple", $list);
   * $form->reindexValue('testing');
   * $form->setMatch('testing', array('orange', 'banana'));
   *
   * This would change the index array to array('apple'=>'apple', 'orange'=>'orange', etc.
   *
   * @author                    Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param string name         Name of element to set the type
   */
  function reindexValue($name){
    if (!$this->testName($name)
	|| ($this->_elements[$name]->type != "multiple" && $this->_elements[$name]->type != "select")
	|| !is_array($this->_elements[$name]->value))
      return FALSE;

    $oldValueArray = $this->_elements[$name]->value;
    foreach ($oldValueArray as $value)
      $newValueArray[(string)$value] = $value;

    $this->_elements[$name]->value = $newValueArray;

  }


  /**
   * Sets the match value of an element
   *
   * Used when you want a radio button, checkbox, or drop down box
   * to be defaultly selected.
   * In most cases this is just a string, however there is a special circumstance
   * for multiple select forms. The match must be an array. Even if there is just one
   * match, for it to register, it must come as an array.
   *
   * Also, match will ONLY match to the VALUE of a select box unless you set
   * optionMatch to TRUE.
   *
   * @author                    Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param string name         Name of element to set the type
   * @param string match        Value to match against the element's value
   * @param boolean optionMatch If TRUE, then a select box will try to match
   *                            the value to the option not the value
   */
  function setMatch($name, $match, $optionMatch=FALSE){
    if (!$this->testName($name))
      return FALSE;

    if ($this->_elements[$name]->getType() == "multiple" && !is_array($match)){
      $this->error = "The match for <b>$name</b> must be an array to match a multiple form";
      return FALSE;
    }

    $this->_elements[$name]->_setMatch($match);
    $this->_elements[$name]->optionMatch = $optionMatch;
  }

  /**
   * Allows you to set optgroup options in a select field so the items 
   * will not be selectable.
   *
   * @author            Darren Greene <dg49379@NOSPAM.tux.appstate.edu>
   * @param string name      Name of element to set the optgroups
   * @param array  optgroups The values of options that should be optgroups
   */
  function setOptgroups($name, $optgroups){
    if (!$this->testName($name))
      return FALSE;

    $this->_elements[$name]->_setOptgroups($optgroups);
  }

  /**
   * Sets the max text size for a text, password, file element
   *
   * @author Matthew McNaney<matt@NOSPAM.tux.appstate.edu>
   * @param string  name Name of element to set the maxsize
   * @param integer maxsize The max number of characters allowed in the element's field
   */
  function setMaxSize($name, $maxsize){
    if (!$this->testName($name))
      return FALSE;

    $this->_elements[$name]->_setMaxSize($maxsize);
  }


  /**
   * Allows you to change the name of an element
   *
   * @author Matthew McNaney<matt@NOSPAM.tux.appstate.edu>
   * @param string  oldName Name of element to change
   * @param string  newName Name to change the oldName to
   */
  function changeName($oldName, $newName){
    if (isset($this->elements[$newName])){
      $this->error = "Cannot change name. Already in use.";
      return FALSE;
    }

    $this->_elements[$newName] = $this->elements[$oldName];
    $this->drop($oldName);
  }

  /**
   * Indicates whether an element exists
   *
   * @author         Matthew McNaney<matt@NOSPAM.tux.appstate.edu>
   * @param  string  name Name to check if exists
   * @return boolean TRUE if the element exists, FALSE otherwise
   */
  function testName($name){
    if (!isset($this->_elements[$name])){
      $this->error = "No element named <b>$name</b>.";
      return FALSE;
    } else
      return TRUE;
  }

  /**
   * Indicates whether an element's type is set
   *
   * @author         Matthew McNaney<matt@NOSPAM.tux.appstate.edu>
   * @param  string  name Name to check if type exists
   * @return boolean TRUE if the element's type exists, FALSE otherwise
   */
  function testType($name){
    if (!isset($this->_elements[$name])){
      $this->error = "Element <b>$name</b> type is not set.";
      return FALSE;
    } else
      return TRUE;
  }

  /**
   * retrieves a HTML form element
   *
   * This returns just the element asked for. It is not in a template format.
   * If there is problem, it will be registered to the error variable.
   *
   * @author              Matthew McNaney<matt@NOSPAM.tux.appstate.edu>
   * @param  string  name Name to retrieve
   * @return string       HTML form element
   */
  function get($name){
    if (!$this->testName($name) || !$this->testType($name))
      return FALSE;

    $element =  $this->_elements[$name]->_getInput();
    if ($element === FALSE){
      $this->error = $this->_elements[$name]->error;
      return FALSE;
    }

    if (is_array($element)){
      foreach ($element as $data)
	$formElements[] = $data;
      return implode("", $formElements);
    } else 
      return $element;
  }

  /**
   * sets the 'action' or destination directory for a form
   *
   * If you are using this class in phpWebSite, the default directory will be
   * phpwebsite's home address/index.php
   *
   * If you need to send the form elsewhere, set the directory here.
   *
   * @author                   Matthew McNaney<matt@NOSPAM.tux.appstate.edu>
   * @param  string  directory Directory that a form will post to
   */
  function setAction($directory){
    $this->_action = $directory;
  }

  function setEncode($flag) {
    if(is_bool($flag) && $flag) {
      $this->_encode = " enctype=\"multipart/form-data\"";
      return TRUE;
    } else {
      $this->_encode = NULL;
    }
  }

  function getStart(){
    if (!isset($this->_action)){
      if (defined('PHPWS_HOME_HTTP'))
	$this->_action = $GLOBALS['SCRIPT'];
      else
	$this->error = "A form directory has not entered and the phpWebSite home directory cannot be found.";
    }

    if (isset($this->_formName))
      $formName = 'name="' . $this->_formName . '" id="' . $this->_formName . '" ';
    else
      $formName = NULL;

    if (isset($this->_action))
      return "<form " . $formName . "action=\"" . $this->_action . "\" " . $this->_method . $this->_encode . ">\n";
    
  }


  /**
   * Returns all the elements of a form in a template array
   *
   * This is the fruit of your labor. After calling this you will get an associative array
   * of all you form elements. The keys of the template are the capitalized names of the elements.
   * The template also includes START_FORM and END_FORM tags to make creating the form easier.
   * Hidden variables will AUTOMATICALLY be added to the START_FORM tag. If helperTags == FALSE
   * they will be placed in a tag named HIDDEN.
   * It will also create a DEFAULT_SUBMIT button.
   * 
   * Hidden variables will be added on to START_FORM. They will NOT have their own template tag.
   *
   * @author                     Matthew McNaney<matt@NOSPAM.tux.appstate.edu>
   * @param  boolean phpws       If TRUE and the action is missing, phpWebSite will attempt to use your directory settings instead
   * @param  boolean helperTags  If TRUE START and END_FORM tags will be created, otherwise they will not
   * @param  array   template    If a current template is supplied, form will add to it.
   * @return array   template    Array of completed form
   */
  function getTemplate($phpws=TRUE, $helperTags=TRUE, $template=NULL){
    if (count($this->_elements) < 1){
      $this->error = "No form elements have been created.";
      return FALSE;
    }

    if (!is_null($template) && !is_array($template)){
      $this->error = "The template submitted to getTemplate is not an array";
      return FALSE;
    }

    if ($helperTags)
      $template["START_FORM"] = $this->getStart();

    $template["HIDDEN"] = NULL;
    foreach ($this->_elements as $elementName=>$element){
      $count = 0;
      $formElement = $element->_getInput();

      if (!is_null($element->tag))
	$elementName = $element->tag;

      if ($element->type == "hidden"){
	($helperTags == TRUE) ? $template["START_FORM"] .= $formElement : $template["HIDDEN"] .= $formElement;
	continue;
      }

      if ($formElement == FALSE){
	$this->error = $element->error;
	return FALSE;
      }

      if (is_array($formElement)){
	foreach ($formElement as $data){
	  $count++;
	  $template[strtoupper($elementName) . "_$count"] = $data;
	}
      } else
	$template[strtoupper($elementName)] = $formElement;
    }
    $template["DEFAULT_SUBMIT"] = "<input type=\"submit\" value=\"" . $_SESSION["translate"]->it("Submit") . "\" />\n";
    $template["DEFAULT_RESET"] = "<input type=\"reset\" value=\"" . $_SESSION["translate"]->it("Reset") . "\" />\n";
    if (isset($this->_action))
      $template["END_FORM"]   = "</form>\n";

    return $template;
  }

  /**
   * Creates a templated file box for images
   *
   * This function will return a image template for phpWebSite.
   * The template includes the image file box and a drop down box of current images.
   * It is up to the module developer to catch the data.
   *
   * If 'name' is not supplied, the form will use 'IMAGE'.
   * If the image_directory is not supplied, the function will try and use the core
   * value. If the image directory is not correct or not writable, FALSE is returned.
   *
   * The file box will be named NEW_ + the name (ie NEW_myModImage, or by default NEW_IMAGE).
   * The select box will be named CURRENT_ + the name.
   *
   * If you have a current image you want to match in the select box, send it to the match
   * parameter and it will be highlighted in the select box.
   *
   * If a user wants to dump an image from an element, they can choose <None>. Make sure you
   * are checking for that.
   *
   * There is also a REMOVE button sent back to the template. Whether you allow them to delete
   * images is up to you.
   * 
   * @author                         Matthew McNaney<matt@NOSPAM.tux.appstate.edu>
   * @param  string name             Name of image variable
   * @param  string image_directory  Directory path of current images
   * @param  string match            Name of the image to select by default
   * @return array                   Template of image form
   */
  function imageForm($name=NULL, $image_directory=NULL, $match=NULL){
    if (is_null($image_directory))
      $image_directory = PHPWS_HOME_DIR . "images/" . $GLOBALS["core"]->current_mod;

    if (is_null($name))
      $name = "IMAGE";

    if (!is_dir($image_directory) || !is_writable($image_directory)){
      $this->error = "Directory <b>$image_directory</b> is not writable.";
      return FALSE;
    }

    if ($current_images = PHPWS_File::readDirectory($image_directory, FALSE, TRUE)){
      $imageList = array();
      foreach ($current_images as $imageName){
	if (preg_match("/\.+(jpg|png|gif)$/i", $imageName))
	  $imageList[$imageName] = $imageName;
      }
	  
      if (sizeof($imageList) > 0)
	$current_images = array("none"=>"&lt;".$_SESSION["translate"]->it("None")."&gt;") + $imageList;
      else
	$current_images = array("none"=>"&lt;".$_SESSION["translate"]->it("None")."&gt;");
    }

    $this->add("NEW_".$name, "file");
    $this->setSize("NEW_".$name, 30);
    if ($current_images){
      $this->add("CURRENT_".$name, "select", $current_images);
      $this->setMatch("CURRENT_".$name, $match);
      $this->add("REMOVE_".$name, "submit", $_SESSION["translate"]->it("Remove Image"));
    }
    return TRUE;
  }

  /**
   * Saves an image uploaded via a form
   *
   * saveImage uses the _FILES array to save an uploaded image file to your
   * server. Here is an example of it being used in a function.
   * The name of the file is sample.jpg with a 534 width and 300 height.
   *
   * if ($_FILES["NEW_IMAGE"]["name"]){
   *   $image = EZform::saveImage("NEW_IMAGE", $image_directory, 1024, 768, 100000);
   *   if (is_array($image))
   *	$this->image = $image;
   *   else {
   *	$GLOBALS["CNT_Module_Var"]["content"] .= $image;
   *   }
   * }
   *
   * In this example, it is using the imageForm standard of NEW_IMAGE as the
   * post variable name. But it could obviously be whatever you name it. Since
   * it exists, I send it to the saveImage method. I tell the function the name
   * of the post variable, what directory to save it to, the maximum width and height,
   * and finally the maximum file size. These are set by default to 640x480x80000.
   *
   * I then catch the result. If the result is an array, it was successful. The array
   * looks like so:
   * $image['name'] = "sample.jpg";
   * $image['width'] = 534;
   * $image[['height'] = 300;
   *
   * I can then save these values however.
   *
   * If the result is a string, then there was an error and I am posting it to my module's
   * content variable.
   *
   * @author                         Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string postVar          Name of the file form variable
   * @param  string image_directory  Path where the file is to get saved
   * @param  int    widthLimit       Maximum allowed image width
   * @param  int    heightLimit      Maximum allowed image height
   * @param  int    sizeLimit        Maximum number of bytes allowed for the image.
   * @param  array  allowedImages    Array of images allowed. Defaults to all known.
   * @return mixed                   Array with image info if successful, error string if not
   */
  function saveImage($postVar, $image_directory, $widthLimit=NULL, $heightLimit=NULL, $sizeLimit=NULL, $allowedImages=NULL, $autoIncrement=TRUE){
    $loop = 0;
    $fileTypes = NULL;
    if (is_null($widthLimit))
      $widthLimit = MAX_IMAGE_WIDTH;

    if (is_null($heightLimit))
      $heightLimit = MAX_IMAGE_HEIGHT;

    if (is_null($sizeLimit))
      $sizeLimit = MAX_IMAGE_SIZE;

    if (!($filename = $_FILES[$postVar]['name'])){
      $error = new PHPWS_Error("EZform", "saveImage", "<b>$postVar</b> not found in _FILES array.");
      return $error->errorMessage();
    } else {
      $filename = preg_replace("/[^a-z0-9\._\s]/i", "", $filename);
      $filename = str_replace (" ", "_", $filename);
      if (is_file($image_directory . $filename)){
	if ($autoIncrement){
	  for($i=1;$i < 1000; $i++){
	    $tempNameArray = explode(".", $filename);
	    $tempNameArray[0] = $tempNameArray[0] . "_" . $i;
	    $checkFile = implode(".", $tempNameArray);
	    if (is_file($image_directory . $checkFile))
	      continue;
	    else {
	      $filename = $checkFile;
	      break;
	    }
	  }
	} else {
	  $error = new PHPWS_Error("EZform", "saveImage", "Image file already exists. Try another filename.");
	  return $error;
	}
      }
    }

    $fileSize = $_FILES[$postVar]['size'];

    if (!is_dir($image_directory) || !is_writable($image_directory)){
      $error = new PHPWS_Error("EZform", "saveImage", "Upload directory does not exist or is not writable:<br />$image_directory");
      return $error;
    }

    if ($fileSize > $sizeLimit){
      $error = new PHPWS_Error("EZform", "saveImage", "Submitted image was larger than $sizeLimit: <b>$fileSize</b>.");
      return $error;
    }

    if (is_null($allowedImages) || !is_array($allowedImages)) {
      include(PHPWS_SOURCE_DIR.'conf/allowedImageTypes.php');
      $allowedImages = $allowedImageTypes;
    }

    $fileTypes = implode(", ", $allowedImages);

    if (!in_array($_FILES[$postVar]["type"], $allowedImages)){
      $error = new PHPWS_Error("EZform", "saveImage", "Submitted image must be $fileTypes file.");
      return $error;
    }

    $tmp_file = $_FILES[$postVar]['tmp_name'];

    $file = $image_directory . $filename;


    if (move_uploaded_file($tmp_file, $file)){
      chmod($image_directory . $filename, 0644);
    } else {
      $error = new PHPWS_Error("EZform", "saveImage", "An unknown error occurred when trying to save your image.");
      return $error;
    }

    $imageSize = getimagesize($file);

    if ($widthLimit && ($imageSize[0] > $widthLimit)){
      @unlink($file);
      $error = new PHPWS_Error("EZform", "saveImage", "Submitted image width was larger than $widthLimit pixels.");
      return $error;
    }
       
    if ($heightLimit && ($imageSize[1] > $heightLimit)){
      @unlink($file);
      $error = new PHPWS_Error("EZform", "saveImage", "Submitted image height was larger than $heightLimit pixels.");
      return $error;
    }

    $image["name"] = $filename;
    $image["width"] = $imageSize[0];
    $image["height"] = $imageSize[1];
    
    return $image;
  }
  

    /**
    * Creates templated select boxes for dates
    *
    * This function will return a templte of select form elements
    * for phpWebSite.
    *
    * The template includes a drop down box for each of year, month, day.
    * It is up to the module developer to catch the data.
    *
    * If 'name' is not supplied, the form will use 'DATE'.
    *
    * The year box will be named the name + _YEAR.
    * The month box will be named the name + _MONTH.
    * The day box will be named the name + _DAY.
    *
    * If you have a current date you want to match in the select
    * boxes, send it to the match parameter and it will be
    * highlighted in the select boxes.
    *
    * @author                         Don Seiler <don@NOSPAM.seiler.us>
    * @param  string name             Name to label each form select box
    * @param  integer match           Unix timestamp of date
    * @param  integer yearStart       Date to start the year list
    * @param  integer yearEnd         Date to end the year list
    * @param  boolean useBlanks       Bit on whether or not to use blank options
    * @param  boolean textYear        Bit on whether or not to use text field for year
    *                                 (default is select box)
    * @return array                   Template of date form
    */
    function dateForm($name=NULL, $match=NULL, $yearStart=NULL, $yearEnd=NULL, $useBlanks=FALSE, $textYear=FALSE, $textMonth=FALSE, $textDay=FALSE){
        if (is_null($name))
            $name = "DATE";
	
        $match_y = NULL;
        $match_m = NULL;
        $match_d = NULL;
        if (is_null($match) && !$useBlanks) {
            $mdate = new Date();
            $match_y = $mdate->format("%Y");
            $match_m = $mdate->format("%m");
            $match_d = $mdate->format("%d");
        } elseif (is_null($match) && $useBlanks) {
            $match_y = NULL;
            $match_m = NULL;
            $match_d = NULL;
        } else {
            if(empty($match))
                $mdate = new Date();
            else
                $mdate = new Date($match);

            $match_y = $mdate->format("%Y");
            $match_m = $mdate->format("%m");
            $match_d = $mdate->format("%d");
        }

        if(!$textYear) {
            if (is_numeric($yearStart) && is_numeric($yearEnd))
                $length = $yearEnd - $yearStart;
            elseif (is_numeric($yearStart) && ($yearStart < date("Y")))
                $length = 10;
            elseif (($yearStart - (int)date("Y")) > 10) {
                $length = $yearStart - (int)date("Y") + 3;
                $yearStart = (int)date("Y");
            }
    
            if(isset($length) && $length > 0 && $length < 1000)
                $years = $GLOBALS["core"]->datetime->yearArray($yearStart, $length);
            else
                $years = $GLOBALS["core"]->datetime->yearArray();
        }

        $months = $GLOBALS["core"]->datetime->monthArray();
        $days = $GLOBALS["core"]->datetime->dayArray();

        if($useBlanks) {
            if(!$textYear) 
                array_unshift($years, NULL);
            array_unshift($months, NULL);
            array_unshift($days, NULL);
        }

        if($textYear) {
            $this->add($name . "_YEAR", "text", $match_y);
            $this->setSize($name . "_YEAR", 4);
            $this->setMaxSize($name . "_YEAR", 4);
        } else {
            $this->add($name . "_YEAR", "select", $years);
            $this->reindexValue($name . "_YEAR");
            $this->setMatch($name . "_YEAR", $match_y);
        }

	if($textMonth) {
	    $this->add($name . "_MONTH", "text", $match_m);
	    $this->setSize($name . "_MONTH", 2);
	    $this->setMaxSize($name . "_MONTH", 2);
	} else {
	    $this->add($name . "_MONTH", "select", $months);
	    $this->reindexValue($name . "_MONTH");
	    $this->setMatch($name . "_MONTH", $match_m);
	}

	if($textDay) {
	    $this->add($name . "_DAY", "text", $match_d);
	    $this->setSize($name . "_DAY", 2);
	    $this->setMaxSize($name . "_DAY", 2);
	} else {
	    $this->add($name . "_DAY", "select", $days);
	    $this->reindexValue($name . "_DAY");
	    $this->setMatch($name . "_DAY", $match_d);
	}

        return TRUE;
    }// END FUNC dateForm
    

    /**
    * Creates templated select boxes for times
    *
    * This function will return a templte of select form elements
    * for phpWebSite.
    *
    * The template includes a drop down box for each of hour, minute, am/pm.
    * It is up to the module developer to catch the data.
    *
    * If 'name' is not supplied, the form will use 'TIME'.
    *
    * The hour box will be named the name + _HOUR.
    * The minute box will be named the name + _MINUTE.
    * The am/pm box will be named the name + _AMPM.
    *
    * If you have a current time you want to match in the select
    * boxes, send it to the match parameter and it will be
    * highlighted in the select boxes.
    *
    * @author                         Don Seiler <don@NOSPAM.seiler.us>
    * @param  string name             Name to label each form select box
    * @param  integer match           Unix timestamp of date
    * @param  integer increment       How many minutes to increment
    * @return array                   Template of date form
    */
    function timeForm($name=NULL, $match=NULL, $increment=15){
        if (is_null($name))
            $name = "DATE";

        $hours = array();
        $military = FALSE;
        if (preg_match("/g/", PHPWS_TIME_FORMAT))
            $hours = PHPWS_Array::interval(12, 1);
        elseif (preg_match("/G/", PHPWS_TIME_FORMAT)){
            $hours= PHPWS_Array::interval(23, 0);
            $military = TRUE;
        }
        elseif (preg_match("/h/", PHPWS_TIME_FORMAT)){
            $hours = PHPWS_Array::interval(12, 1);
            foreach ($hours as $key=>$old_hour){
                if ((int)$old_hour < 10)
                    $hours[$key] = "0".(string)$old_hour;
            }
        } elseif (preg_match("/H/", PHPWS_TIME_FORMAT)){
            $hours = PHPWS_Array::interval(23, 0);
            $military = TRUE;
            foreach ($hours as $key=>$old_hour){
                if ((int)$old_hour < 10)
                    $hours[$key] = "0".(string)$old_hour;
            }
        }

        $match_h = NULL;
        $match_m = NULL;
        $match_ampm = NULL;
        if(empty($match))
            $mtime = new Date();
        else
            $mtime = new Date($match);

        if($military)
            $match_h = $mtime->format("%H");
        else
            $match_h = $mtime->format("%I");
        $match_m = $mtime->format("%M");
        $match_ampm = $mtime->format("%P");

        $ampm = array(0=>"AM", 1=>"PM");
        if($match_ampm == "AM")
            $match_ampm = 0;
        else
            $match_ampm = 1;

        $minutes = PHPWS_Array::interval(59,0,$increment);
        $m = NULL;
        foreach ($minutes as $key=>$old_min) {
            if((int)$old_min < 10)
                $minutes[$key] = "0" . (string)$old_min;

            if(is_null($m) && ($old_min >= $match_m)) {
                $match_m = $old_min;
                $m = 1;
            }
        }

        $this->add($name . "_HOUR", "select", $hours);
        $this->reindexValue($name . "_HOUR");
        $this->setMatch($name . "_HOUR", $match_h);
        $this->add($name . "_MINUTE", "select", $minutes);
        $this->reindexValue($name . "_MINUTE");
        $this->setMatch($name . "_MINUTE", $match_m);
        if(!$military) {
            $this->add($name . "_AMPM", "select", $ampm);
            //$this->reindexValue($name . "_AMPM");
            $this->setMatch($name . "_AMPM", $match_ampm);
        }
        return TRUE;
    }// END FUNC timeForm
}

?>