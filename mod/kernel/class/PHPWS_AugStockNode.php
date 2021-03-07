<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/administration/class/PHPWS_TaskNode.php');

class PHPWS_AugStockNode extends PHPWS_TaskNode {
  function PHPWS_AugStockNode() {
  }
  function __construct() {
	  $this->_processType = AUGSTOCK_NODE;
	  parent::__construct();
	  $this->__correspondMode = 0;	// 1:1
  }

  function summary($flow) {
	  $res = array();
	  foreach ($this->output as $i) {
		  $p = $flow->goods[$i];
		  $g = $flow->processors[$p->src];
		  if ($g->processType != 512) continue;
		  if (!$res[$g->processType]) $res[$g->processType] = 0;
		  $res[$g->processType] += $p->quantity;
	  }
	  return $res;
  }
}

?>