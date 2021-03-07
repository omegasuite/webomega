<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_ResourceNode.php');

class PHPWS_StopNode extends PHPWS_ResourceNode {
  function PHPWS_StopNode() {
  }

  function __construct() {
	  $this->_processType = STOP_NODE;
	  parent::__construct();
  }
  function dataForm($show = 0xFFFFFFFF, $edit = 0xFFFFFFFF) {
	  $s = parent::dataForm(4);
	  return $s;
  }
  function changeData() {
	  $changes = parent::changeData();
	  if (($changes & 4) && $this->_status != -1) {
		  $this->_status = 4;
		  $changes |= 1;
	  }
	  if ($changes) $this->saved = false;

	  return $changes;
  }
}

?>