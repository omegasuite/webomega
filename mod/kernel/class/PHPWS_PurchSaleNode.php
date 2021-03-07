<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_TaskNode.php');

class PHPWS_PurchSaleNode extends PHPWS_TaskNode {
  function PHPWS_PurchSaleNode() {
  }

  function __construct() {
	  parent::__construct();
	  $this->__correspondMode = 1;	// 1:N
  }

  function addGoods($flow, $pd) {
	  $q = $this->addInGoods($flow, $pd);
	  return $q;
  }

  function addProduct($flow, $pd) {
	  $q = $this->addInProduct($flow, $pd);
	  return $q;
  }

  function deleteGoods($flow, $goods) {
	  // if the goods to be deleted is an input, delete it and corresponding output.
	  // if the goods to be deleted is an output, delete it and keep the input.
	  // because the input end is plan/source, output end is result.
	  if ($goods->dest() == $this->_innerid) {	// this is an input goods
		  $this->_input = array_diff($this->_input, array($goods->innerid()));
		  $goods->SetDest(0);
		  $es = $this->correspond($goods);
		  $this->Untangle($goods);
		  foreach ($es as $e) {
			  $this->deleteGoods($flow, $flow->goods[$e]);
		  }
	  }
	  elseif ($goods->src() == $this->_innerid) {
		  $this->_output = array_diff($this->_output, array($goods->innerid()));
		  $goods->SetSrc(0);
		  $this->Untangle($goods);
	  }
	  $this->saved = false;
	  $goods->removeFrom($flow);
  }
  
  function execute($flow) {
	  extract($_REQUEST);
	  extract($_POST);

	  if ($this->_status == -1) return "任务完成";

	  if ($xchg == "完成任务") {
		  $result = new PHPWS_Result();
		  foreach ($quantity as $i=>$q) {
			  if ($flow->goods[$i]->_quantity != $q || $flow->goods[$i]->_unit != $unit[$i] || $flow->goods[$i]->_product != $product[$i]) {
				  $chg = 4096 | 16 | 8192;
				  if ($category[$i]) {
					  $chg |= 16;
					  $flow->goods[$i]->_category = $category[$i];
					  $flow->goods[$i]->_product = $product[$i];
				  }
				  if (!$flow->goods[$i]->_product) return "产品缺失。";
				  $flow->goods[$i]->_quantity = $q + 0;
				  if ($flow->goods[$i]->_quantity == 0) return "产品数量缺失。";
				  $flow->goods[$i]->_start = date('Y-m-d H:i:s');
				  $flow->goods[$i]->_unit = $unit[$i];
				  $flow->goods[$i]->_totalcost = $price[$i] * ($istotal[$i]? 1 : $q);
				  $result->mergeResult($flow->goods[$i]->propagateChanges($flow, $chg));
				  $flow->goods[$i]->setStatus($flow, 4);
			  }
		  }
		  $this->setStatus($flow, -1);
		  $flow->saved = false;
		  $flow->saveflow(false);
		  return "<table>" . $result->showchgresult() . "</table>任务完成";
	  }
  }
  function evaluateCostChanges(&$flow, $relation = 1) {
	  $effect = array();
	  $sum = 0;
	  $quantity = 0;

	  if ($relation == 1) {
		  if ($this->_input) foreach ($this->_input as $i) {
			  $p = &$flow->goods[$i];
			  if ($this->_output) foreach ($this->_output as $j) {
				  $q = &$flow->goods[$j];
				  if (!$p->sameProduct($q)) continue;
				  $q->_totalcost = $p->_totalcost;
			  }
		  }
	  }
	  elseif ($this->_rules) foreach ($this->_rules as $r) {
		  if ($r['type'] != 'priceaddpoints') continue;
		  $ratio = 1 - $r['points'] / 100.0;
		  if ($this->_input) foreach ($this->_input as $i) {
			  $p = &$flow->goods[$i];
			  $sum = 0;
			  if ($this->_output) foreach ($this->_output as $j) {
				  $q = &$flow->goods[$j];
				  if (!$p->sameProduct($q)) continue;
				  $sum += $q->_totalcost;
			  }
			  $p->_totalcost = $sum * $ratio;
			  $p->_price = $p->_totalcost / $p->_quantity;
		  }
	  }

	  return array('result'=>array(), 'effect'=>$effect);
  }

  function listing($title, $io, $flow) {
	  $this->_status = 8;

	  $rs .= "<table><tr><th align=center colspan=4>$title</th></tr>";
	  $rs .= "<tr><th align=center>资源</th><th align=center>产品</th><th align=center>数量</th><th align=center>价格</th></tr>";
	  $pu = false;

	  $cat = myCategories();

	  if ($t->_category && !$cat[$t->_category]) {
		  $c = $GLOBALS['core']->sqlSelect('mod_category', 'id', $t->_category);
		  $cat[$t->_category] = $c[0]['name'];
	  }
	  $us = array_merge(array(0=>''), Attributes::$units);

	  foreach ($this->$io as $i) {
		  $t = &$flow->goods[$i];
		  $rs .= "<tr><th align=center>" . $t->name() . "</th><th align=center>";
		  if (!$t->_product) {
			  $rs .= "<div id=cats$i></div>" . PHPWS_Form::formSelect("category[$i]", $cat, $t->_category, false, true, "catChg2(this, 'cats$i', 'product[$i]');") . PHPWS_Form::formSelect("product[$i]", array(), NULL, false, true);
		  }
		  else $rs .= prodCache($t->_product) . PHPWS_Form::formHidden("product[$i]", $t->_product);
		  $rs .= "</th><th align=center>";

		  $rs .= PHPWS_Form::formTextField("quantity[$i]", $t->_quantity) . PHPWS_Form::formSelect("unit[$i]", $us, $t->_unit, true, false);
		  $rs .= "</th><th align=center>";
		  $rs .= PHPWS_Form::formTextField("price[$i]", $t->_totalcost) . PHPWS_Form::formCheckBox("istotal[$i]", 1, $t->_totalcost == 0?0 : 1) . "总价";
		  $rs .= "</th></tr>";
	  }
	  $rs .= "<tr><td align=center colspan=4>" . PHPWS_Form::formSubmit("完成任务", 'xchg') . "</td></tr></table>";

	  $_SESSION['OBJ_layout']->extraHead("<script type='text/javascript' src=js/catChg2func.js></script>");

	  return $rs;
  }

  function forcedChange($flow, $origional, $change, $affected) {
	  foreach ($this->_output as $i) {
		  $p = &$flow->goods[$i];
		  if ($p != $affected) continue;
		  $bestmatch = NULL; $qtydiff = 1.0e30;
		  foreach ($this->_input as $i) {
			  $p = &$flow->goods[$i];
			  if ($p->_product != $origional->_product) continue;
			  $diff = abs($origional->_quantity - $p->convertUnit($origional->_unit));
			  if ($diff < $qtydiff) {
				  $qtydiff = $diff;
				  $bestmatch = $p;
			  }
		  }
		  if ($bestmatch) {
			  if ($change & 1024) {
				  $origional->_product = $bestmatch->_product;
				  $bestmatch->_product = $affected->_product;
			  }
			  if ($change & 4096) {
				  $origional->_quantity = $bestmatch->_quantity;
				  $origional->_unit = $bestmatch->_unit;
				  $bestmatch->_quantity = $affected->_quantity;
				  $bestmatch->_unit = $affected->_unit;
			  }
			  if ($change & 16384) {
				  $origional->_price = $bestmatch->_price;
				  $bestmatch->_price = $affected->_price;
			  }
			  if ($bestmatch->src() > 0)
				  $flow->processors[$bestmatch->_src]->forcedChange($flow, $origional, $change, $bestmatch);
		  }
		  return;
	  }
  }
}

?>