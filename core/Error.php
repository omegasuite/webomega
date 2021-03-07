<?php

/**
 * Basic error class for usage by all modules for displaying and handling errors.
 *
 * This class extends PHPWS_Item to allow for modules such as "security" to log
 * errors easily to the database.
 *
 * @version $Id: Error.php,v 1.22 2005/05/12 14:16:03 darren Exp $
 * @author  Steven Levin <steven@NOSPAM.tux.appstate.edu>
 * @package Core
 */
class PHPWS_Error {

  /**
   * name of module where error occurred
   *
   * @var     string
   * @example $this->_module = "phatform";
   * @access  private
   */
  var $_module;

  /**
   * name of function where error occurred
   *
   * @var     string
   * @example $this->_function = "PHAT_Form::_save()";
   * @access  private
   */
  var $_function;

  /**
   * message for this error
   *
   * @var     string
   * @example $this->_message = "The item was not saved to the database.";
   * @access  private
   */
  var $_message;

  /**
   * status of the error
   *
   * @var     string
   * @example $this->_status = "continue";
   * @access  private
   */
  var $_status;

  /**
   * Print debug information or not (0, 1).
   *
   * If debug mode is on, debugging information is included with
   * $this->_message on display.
   *
   * @var     integer
   * @example $this->_debugMode = 0;
   * @access  private
   */
  var $_debugMode;

  /**
   * Constructor for phpWebsite error handling class
   *
   * @access public
   */
  function PHPWS_Error($module, $function, $message = NULL, $status="continue", $debugMode=0) {
    $this->_module = $module;
    $this->_function = $function;
    $this->_message = $message;
    $this->_status = $status;
    $this->_debugMode = $debugMode;
  }

  /**
   * Prints error message
   *
   * @param  string $contentVar The content variable to display in
   * @access public
   */
  function message($contentVar = NULL, $title = NULL) {
    if($this->_debugMode) {
      $errorTags['MODULE_LABEL'] = $_SESSION['translate']->it("Module");
      $errorTags['MODULE'] = $this->_module;
      $errorTags['FUNCTION_LABEL'] = $_SESSION['translate']->it("Function");
      $errorTags['FUNCTION'] = $this->_function;
      $errorTags['DATE'] = date(PHPWS_DATE_FORMAT . " "  . PHPWS_TIME_FORMAT, time());
    }
     
    if(!isset($title)) {
      $title = $_SESSION['translate']->it("Error");
    }
    $errorTags['TITLE'] = $title;
    $errorTags['MESSAGE'] = $this->_message;

    if($this->_status == "exit") {
      echo PHPWS_Template::processTemplate($errorTags, "core", "error.tpl");
      exit();
    } else if($this->_status == "continue") {
      if(!isset($GLOBALS[$contentVar]['content'])) {
	$GLOBALS[$contentVar]['content'] = NULL;
      }

      $GLOBALS[$contentVar]['content'] .= PHPWS_Template::processTemplate($errorTags, "core", "error.tpl");
    }
  }
  
  /** PATCH FUNCTION **/
  function errorMessage($contentVar = NULL) {
    return $this->message($contentVar);
  }
  /********************/

  /**
   * isError
   *
   * Tell whether a result code from a PHPWS method is an error
   *
   * @param  mixed   whatever is being tested to see if it is an error
   * @return boolean TRUE if it is an error FALSE if not
   * @access public
   */
  function isError($value) {
    if(is_object($value)) {
       return ((strcasecmp(get_class($value), 'PHPWS_Error') == 0) || is_subclass_of($value, 'PHPWS_Error'));	
    } else {
       return false;
    }
  }

  /**
   * getMessage()
   *
   * Gets the textual message for this error
   *
   * @return string the message for the current error
   */
  function getMessage() {
    return $this->_message;
  }

} // END CLASS PHPWS_Error

?>