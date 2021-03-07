<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_TaskNode.php');

class PHPWS_WasteNode extends PHPWS_TaskNode {
  function PHPWS_WasteNode() {
  }
  function __construct() {
	  $this->_processType = WASTE_NODE;
	  parent::__construct();
	  $this->__correspondMode = 2;	// N:1
  }

  function summary($flow, $type = 1 | 2 | 4 | 8 | 131072 | 512) {
	  $res = array();
	  foreach ($this->input as $i) {
		  $p = $flow->goods[$i];
		  $g = $flow->processors[$p->src];
		  if (($g->processType & $type) == 0) continue;
		  if (!$res[$g->processType]) $res[$g->processType] = 0;
		  $res[$g->processType] += $p->quantity;
	  }
	  return $res;
  }
}

?>