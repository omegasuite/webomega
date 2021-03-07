<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_PurchSaleNode.php');

class PHPWS_PurchaseNode extends PHPWS_PurchSaleNode {
  function PHPWS_PurchaseNode() {
  }

  function __construct() {
	  $this->_processType = PURCHASE_NODE;
	  parent::__construct();
	  $this->__correspondMode = 1;	// 1:N
  }

  static function validate($goods, &$input, &$output, &$rules) {
	  $tc = array();
	  if (sizeof($input)) return "<span style='color:red'>采购任务不能有输入。</span>";
	  if (sizeof($output)) foreach ($output as $t) {
		  if (!in_array($goods[$t]->_resourceType, array(GOODS_NODE))) return "<span style='color:red'>采购任务的输出只能是货物。</span>";
	  }
  }
/*
  function total($flow) {
	  $sum = 0;
//	  if (($this->status() & 0xF) >= 8) {
		  foreach ($this->output() as $i) {
			  $g = &$flow->goods[$i];
			  if ($g->_resourceType == GOODS_NODE)
//				  $sum += $g->_price * $g->_quantity;
				  $sum += $g->_totalcost;
		  }
		  $sum += $this->_totalcost;
/*
	  }
	  else {
		  foreach ($this->output() as $i) {
			  $g = &$flow->goods[$i];
			  if ($g->_resourceType == GOODS_NODE && ($g->_dest <= 0 || $flow->processors[$g->dest]->_processType != 262144))
				  $sum += $g->_price * $g->_quantity;
		  }
		  $sum += $this->_totalcost;
	  }
* /
	  return $sum;
  }
*/
  function shipped($flow, $param) {
	  $this->_status = FLOW_EXECUTED;
	  $now = date("Y-m-d H:i:s");
	  foreach ($this->_input as $i) 
		  if ($flow->goods[$i]->_resourceType == GOODS_NODE) {
				if (!$flow->goods[$i]->_start) $flow->goods[$i]->_start = $now;
				$flow->goods[$i]->_end = $now;
				$flow->goods[$i]->_status = FLOW_EXECUTED;
		  }
	  $this->_end = $now;
	  $flow->saved = false;
  }

  function receivefrom($flow, $s) {
	  return;	// not working yet. correspond could return an array
	  if ($this->correspond($s)) {
		  $g = $flow->goods[$this->correspond($s)];
		  $g->_product = $s->_product;
		  $g->_quantity = $s->_quantity;
		  $g->_unit = $s->_unit;
		  $g->_start = $s->_end = date("Y-m-d H:i:s");
		  if ($s->_barcode && !$g->_barcode) $g->_barcode = $s->_barcode;
		  if ($s->_srcode && !$g->_srcode) $g->_srcode = $s->_srcode;
	  }
  }

  function execute($flow) {
	  $s = parent::execute($flow);
	  if ($s) return $s;

	  return $this->listing($this->taskname() . "结果", 'output', $flow);
  }

  function SetOrg($org) {
	  $this->addOrg($org);
	  parent::SetOrg($org);
  }
}

?>