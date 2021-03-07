<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/administration/class/PHPWS_TaskNode.php');

class PHPWS_Aggregate_Node extends PHPWS_TaskNode {
  function PHPWS_Aggregate_Node() {
  }
  function __construct() {
	  $this->_processType = AGGREGATE_NODE;
	  parent::__construct();
	  $this->__correspondMode = 1;	// 1:N
  }
}

?>