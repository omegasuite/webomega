<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_StockNode.php');

class PHPWS_ReturnNode extends PHPWS_StockNode {
  function PHPWS_ReturnNode() {
  }

  function __construct() {
	  parent::__construct();
	  $this->_processType = RETURN_NODE;
	  $this->_name = '退货';
  }

  function SetOrg($org) {
	  $this->addOrg($org);
	  parent::SetOrg($org);
  }

  static function validate($goods, &$input, &$output, &$rules) {
  }
}

?>