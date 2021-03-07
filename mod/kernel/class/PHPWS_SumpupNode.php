<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_TaskNode.php');

class PHPWS_SumpupNode extends PHPWS_TaskNode {
  function PHPWS_SumpupNode() {
  }

  function __construct() {
	  $this->_processType = SUMUP_NODE;
	  parent::__construct();
  }

  function MarkExecution($slip, $slipt, $flow) {
	  $g = $flow->processors[$flow->goods[$this->_output[0]]->dest()];
	  $g->MarkExecution($slip, $slipt, $flow);
  }
  function EraseExecution($slip, $flow) {
	  $g = $flow->processors[$flow->goods[$this->_output[0]]->dest()];
	  $g->EraseExecution($slip, $flow);
  }

  function evaluateQuantityChanges(&$flow, &$chg, $which, $ratio) {
	  $result = array();
	  $effect = array();
	  $line = array();

	  $sum = 0; $product = NULL;
	  reset($this->_output);
	  list(, $i) = each($this->_output);
	  $out = &$flow->goods[$i];

	  $unit = $out->_unit;
	  foreach ($this->_input as $i) {
		  if (!$unit) $unit = $flow->goods[$i]->_unit;
		  if (!$product) $product = $flow->goods[$i];
		  assert($flow->goods[$i]->_product == $product->_product);
		  $sum += $flow->goods[$i]->convertUnit($unit);
	  }
	  if (!$out->sameProduct($product) || $out->_quantity != $sum || $out->_unit != $unit) {
		  $out->_product = $product->_product; $out->_attribs = $product->_attribs; $out->_quantity = $sum; $out->_unit = $unit;
		  return $flow->processors[$out->_dest]->evaluateQuantityChanges($flow, $chg, $out, 1.0);
	  }

	  return array('result'=>array(), 'effect'=>$effect);
  }
  function evaluateCostChanges(&$flow, $relation = 1) {
	  $effect = array();
	  $sum = 0;
	  $quantity = 0;

	  if ($this->_input) foreach ($this->_input as $i) {
		  $sum += $flow->goods[$i]->_totalcost;
		  $quantity += $flow->goods[$i]->_quantity;
	  }
	  if ($relation == 1) {
		  reset($this->_output);
		  list(, $i) = each($this->_output);
		  $out = &$flow->goods[$i];

		  if ($out->_totalcost != $sum) {
			  $out->_totalcost = $sum;
			  if ($quantity) {
				  $out->_quantity = $quantity;
				  $out->_price = $sum / $quantity;
			  }
			  if ($out->_dest > 0)
				  return $flow->processors[$out->_dest]->evaluateCostChanges($flow);
		  }
	  }
	  else {
		  reset($this->_output);
		  list(, $i) = each($this->_output);
		  $out = &$flow->goods[$i];
		  if ($sum == $out->_totalcost) return array('result'=>array(), 'effect'=>$effect);

		  $pricefactor = $out->_totalcost / $sum;

		  if ($this->_input) foreach ($this->_input as $i) {
			  $flow->goods[$i]->_price *= $pricefactor;
			  $flow->goods[$i]->_totalcost *= $pricefactor;
			  if ($flow->goods[$i]->_src > 0) $flow->processors[$flow->goods[$i]->_src]->evaluateCostChanges($flow, -1);
		  }
	  }

	  return array('result'=>array(), 'effect'=>$effect);
  }
  function evaluateThingChanges(&$flow, $which, $relation) {
	  $result = new PHPWS_Result();
	  return $result;
  }
  function evaluateBeginTimeChanges(&$flow, &$chg, $which, $relation) {
	  if ($this->_input) foreach ($this->_input as $i) {
		  $start = max($start, $flow->goods[$i]->_start);
	  }

	  reset($this->_output);
	  list(, $i) = each($this->_output);
	  $out = &$flow->goods[$i];
	  if ($out->_start != $start) {
		  $out->_start = $start;
		  if ($out->_dest > 0) 
			  return $flow->processors[$out->_dest]->evaluateBeginTimeChanges($flow, $chg, $out, $relation);
	  }

	  return array('result'=>array(), 'effect'=>$effect);
  }

  function evaluatePlaceChanges(&$flow, $which, $relation) {
	  list(, $i) = each($this->_output);
	  $out = &$flow->goods[$i];

	  if ($this->_input) foreach ($this->_input as $i) {
		  if (!$out->_place && $flow->goods[$i]->_place)
			  $out->_place = $flow->goods[$i]->_place;
		  elseif ($flow->goods[$i]->_place)
			  assert($out->_place == $flow->goods[$i]->_place);
	  }

	  return array('result'=>$result, 'effect'=>$effect);
  }

  function mirror($q, $flow, $creatnew = false) {	// find mapping node, optionally create one if not exist
	  if (in_array($q->_innerid, $this->_input))
		  return $flow->goods[$this->_output[0]];
	  return NULL;
  }


  function forcedChange($flow, $origional, $change, $affected) {
	  $qr = $affected->_quantity / $origional->_quantity;
	  $pr = $affected->_price / $origional->_price;
	  
	  $from = PHPWS_Node::newNode(32);

	  foreach ($this->_input as $i) {
		  if ($p->_product != $origional->_product) continue;

		  $from->copy($p);

		  if ($change & 1024) {
			  $p->_product = $affected->_product;
		  }
		  if ($change & 4096) {
			  $p->_quantity = $p->convertUnit($origional->_unit) * $qr;
			  $p->_unit = $affected->_unit;
		  }
		  if ($change & 16384) {
			  $p->_price = $p->convertPrice($origional->_unit) * $pr;
		  }
		  if ($p->_src > 0)
			  $flow->processors[$p->_src]->forcedChange($flow, $from, $change, $p);
	  }
  }
}

?>