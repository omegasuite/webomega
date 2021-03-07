<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_PurchSaleNode.php');

class PHPWS_SaleNode extends PHPWS_PurchSaleNode {
  function PHPWS_SaleNode() {
  }

  function __construct() {
	  $this->_processType = SALE_NODE;
	  parent::__construct();
	  $this->__correspondMode = 1;	// 1:N
  }
  static function validate($goods, &$input, &$output, &$rules) {
	  $tc = array();
	  if (sizeof($output)) return "<span style='color:red'>销售任务不能有输出。</span>";
	  if (sizeof($input)) foreach ($input as $t) {
		  if (!in_array($goods[$t]->_resourceType, array(GOODS_NODE))) return "<span style='color:red'>销售任务的输入只能是货物。</span>";
	  }
  }

/*
  function total($flow) {
	  $sum = 0;
	  if (($this->_status & 0xF) >= 8) {
		  foreach ($this->_output as $i) {
			  $g = &$flow->goods[$i];
			  if ($g->_resourceType == GOODS_NODE)
//				  $sum += $g->_price * $g->_quantity;
				  $sum += $g->_totalcost;
		  }
		  $sum += $this->_totalcost;
	  }
	  else {
		  foreach ($this->_input as $i) {
			  $g = &$flow->goods[$i];
			  if ($g->_resourceType == GOODS_NODE)
				  $sum += $g->_totalcost;
//				  $sum += $g->_price * $g->_quantity;
		  }
		  $sum += $this->_totalcost;
	  }
	  return $sum;
  }
*/

  function execute($flow) {
	  return "operation obsolete";

	  if (!$this->_input) return;
	  foreach ($this->_input as $i)
		  if ($flow->goods[$i]->_status > 0 && $flow->goods[$i]->_status < FLOW_EXECUTING) return;
	  foreach ($this->_input as $i) {
		  $g = &$flow->goods[$i];
		  if ($g->_status != 4) continue;
		  $GLOBALS['core']->query("INSERT INTO mod_sales (product, quantity, unit, price, org, soldby, sellto,	buyer, flownode) VALUES ({$g->_product}, {$g->_quantity}, '{$g->_unit}', {$g->_totalcost}, {$g->_org}, {$flow->_creator}, '" . ($_SESSION['OBJ_user']->realname?$_SESSION['OBJ_user']->realname : $_SESSION['OBJ_user']->username) . "', {$_SESSION['OBJ_user']->user_id}, {$g->_id})", true);
		  if (!$g->_start) $g->_start = date("Y-m-d H:i:s");
		  $g->_end = date("Y-m-d H:i:s");
		  $g->ChgStatus(-1);
	  }
	  unset($g);

	  $this->setStatus($flow, -1);
	  $flow->saved = false;

	  $completed = true;
	  foreach ($flow->processors as $g)
		  if ($g->_status > 0) $completed = false;
	  foreach ($flow->goods as $g)
		  if ($g->_dest <= 0 && $g->_status > 0 && $g->_status & 0xF != FLOW_EXECUTED) $completed = false;
	  if ($completed) {
		  $flow->status = -1;
		  $GLOBALS['core']->sqlDelete('mod_planadjusts', 'flow', $this->_id);
	  }

	  $flow->saveflow(false);
	  return "货已提走，销售完成。";
  }
}

?>