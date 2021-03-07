<?php

/**
 * PHPWS Messaging class
 *
 * @version $Id: Message.php,v 1.4 2004/08/19 19:06:55 steven Exp $
 * @author  Steven Levin <steven@NOSPAM.tux.appstate.edu>
 * @package Core
 */
class PHPWS_Message {

  var $_title = NULL;
  var $_content = NULL;
  var $_contentVar = NULL;

  function PHPWS_Message($content, $contentVar, $title=NULL) {
    $this->_content = $content;
    $this->_contentVar = $contentVar;
    $this->_title = $title;
  }

  function display() {
    $messageTags = array();
    $messageTags['CONTENT'] = $this->_content;

    if(isset($this->_title)) {
      $messageTags['TITLE'] = $this->_title;
    }

    $GLOBALS[$this->_contentVar]['content'] .= PHPWS_Template::processTemplate($messageTags, "core", "message.tpl");
  }
  
  function isMessage($value) {
      return (is_object($value) && (strcasecmp(get_class($value), 'PHPWS_Message') == 0) || is_subclass_of($value, 'PHPWS_Message'));
  }
} // END CLASS PHPWS_Message

?>