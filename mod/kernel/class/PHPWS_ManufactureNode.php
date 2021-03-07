<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_CapacityNode.php');

class PHPWS_ManufactureNode extends PHPWS_CapacityNode {
  function PHPWS_ManufactureNode() {
  }

  function __construct() {
	  $this->_processType = MANUFACTURE_NODE;
	  parent::__construct();
  }
  static function validate($goods, &$input, &$output, &$rules) {
	  if (sizeof($input)) return "<span style='color:red'>生产任务不能有输入。需要输入的可以改用加工任务。</span>";
	  if (sizeof($output)) foreach ($output as $t)
		  if ($goods[$t]->_resourceType != GOODS_NODE) return "<span style='color:red'>生产任务的输出必须是货物。</span>";
  }
  function SetOrg($org) {
	  $this->addOrg($org);
	  parent::SetOrg($org);
  }
}

?>