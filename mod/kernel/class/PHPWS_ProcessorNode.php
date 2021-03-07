<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_CapacityNode.php');

class PHPWS_ProcessorNode extends PHPWS_CapacityNode {

  function PHPWS_ProcessorNode() {
  }

  function __construct() {
	  $this->_processType = PROCESSOR_NODE;
	  parent::__construct();
  }

  static function validate($goods, &$input, &$output, &$rules) {
	  $tc = array();
	  $mx = 0;
	  if (sizeof($input)) foreach ($input as $t) {
		  if (!in_array($goods[$t]->_resourceType, array(GOODS_NODE, STOP_NODE))) return "<span style='color:red'>加工任务的输入只能是货物或地点。</span>";
		  if (!$tc[$goods[$t]->_resourceType]) $tc[$goods[$t]->_resourceType] = 1;
		  else $tc[$goods[$t]->_resourceType]++;
		  $mx = max($mx, $tc[$goods[$t]->_resourceType]);
	  }
	  if ($tc[STOP_NODE] && $tc[STOP_NODE] > 1) return "<span style='color:red'>加工任务的输入最多只能有一个地点。</span>";
	  if (sizeof($output)) foreach ($output as $t)
		  if ($goods[$t]->_resourceType != GOODS_NODE) return "<span style='color:red'>加工任务的输出必须是货物。</span>";
  }
  function SetOrg($org) {
	  $this->addOrg($org);
	  parent::SetOrg($org);
  }
}

?>