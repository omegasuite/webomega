<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/nodes.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/flow.php');

class PHPWS_TaskNode extends PHPWS_Node {
  protected $_input = array();				// 0x64	-- edit position flag
  protected $_output = array();			// 0x64
  protected $_refflow = 0;					// 0x128
  protected $_processType = 0;
  protected $_correspond = array();
//  var $_product = 0;					// 1024
//  var $_category = 0;				// 0x2048
  protected $_taskname = NULL;				// 0x256
  protected $_name2 = NULL;
  protected $_rules = array();				// 0x512
  protected $_workinghourstart = 0;
  protected $_workinghourend = 0;
  protected $__correspondMode = 0;		// mode according to task,
										// input:output, bit 0 => output, bit 1 => input. val 0 => single, val 1 => multiple
										// 0 = 1:1, 1 = 1:N, 2 = N:1
										// in 1:1 mode, records both sides
										// in 1:N or N:1 modes, records the multiple side
										// N:M mapping is not allowed 

  function PHPWS_TaskNode() {
  }

  function __construct() {
	  parent::__construct();
	  $this->_taskname = $GLOBALS['_NODE_NAMES'][$this->_processType];
//	  $this->_extra['correspondto'] = array();
  }

  function MarkExecution($slip, $slipt, $flow) {
	  $slipno = $this->ExtraVal('orderNums');

	  if (!$slipno) $slipno = array();
	  if (!$slipno[$slip]) $slipno[$slip] = $slipt;
	  else foreach ($slipt as $i=>$v) $slipno[$slip][$i] = $v;

	  $this->SetExtra(array('orderNums'=>$slipno));
  }
  function EraseExecution($slip, $flow) {
	  $slipno = $this->ExtraVal('orderNums');
	  unset($slipno[$slip]);
	  $this->SetExtra(array('orderNums'=>$slipno));
  }
  function executed($slip, $flow) {
	  $res = array();
	  $slipno = $this->ExtraVal('orderNums');
	  if (!$slipno[$slip]) return $res;

	  $slipt = $slipno[$slip]['slip'];

	  foreach ($this->input as $i) {
		  $p = $flow->goods[$i];
		  if (!$p->accepted($slipt)) {
			  if ($p->src() > 0 && $flow->processors[$p->src()]->processType() == SUMUP_NODE)
				  $res = array_merge($res, $flow->processors[$p->src()]->executed($slip, $flow));
			  continue;
		  }
		  $res[] = $p;
	  }
	  foreach ($this->output as $i) {
		  $p = $flow->goods[$i];
		  if (!$p->accepted($slipt)) continue;
		  $res[] = $p;
	  }
	  return $res;
  }

  function addOutGoods($flow, $pd) {
	  $q = $this->insertAfter($flow, GOODS_NODE);
	  $q->ChgStatus(FLOW_PLAN);
	  if ($this->place) $q->SetPlace($this->place);
	  $q->SetStart($this->start);
	  if ($this->ExtraVal('consignment')) {
		  $q->SetExtraVal('consignment', $this->ExtraVal('consignment'));
		  $q->SetOrg($this->ExtraVal('customer'));
	  }
	  if ($pd) $this->updateGoods($flow, $q, $pd);
	  return $q;
  }
  function addInGoods($flow, $pd) {
	  $q = $this->insertBefore($flow, GOODS_NODE);
	  $q->ChgStatus(FLOW_PLAN);
	  if ($this->place) $q->SetPlace($this->place);
	  $q->SetStart($this->start);
	  if ($this->ExtraVal('consignment')) {
		  $q->SetExtraVal('consignment', $this->ExtraVal('consignment'));
		  $q->SetOrg($this->ExtraVal('customer'));
	  }
	  if ($pd) $this->updateGoods($flow, $q, $pd);
	  return $q;
  }

  function addInProduct($flow, $pd) {
	  $q = $this->insertBefore($flow, GOODS_NODE);
	  $q->ChgStatus(FLOW_PLAN);
	  if ($this->place) $q->SetPlace($this->place);
	  $q->SetStart($this->start);
	  if ($this->ExtraVal('consignment')) {
		  $q->SetExtraVal('consignment', $this->ExtraVal('consignment'));
		  $q->SetOrg($this->ExtraVal('customer'));
	  }
	  if ($pd) $this->updateProduct($flow, $q, $pd);
	  return $q;
  }

  function updateProduct($flow, $q, $pd) {	// $pd money numbers are in cents!
	  $q->SetProductCategory($pd->product);
	  $this->_updateGoods($flow, $q, $pd);
  }
  function updateGoods($flow, $q, $pd) {	// $pd money numbers are in cents!
	  $q->setGoods($pd->product);
	  $this->_updateGoods($flow, $q, $pd);
  }
  function _updateGoods($flow, $q, $pd) {	// $pd money numbers are in cents!
	  $q->SetQuantity($pd->quantity);
	  $q->SetUnit($pd->unit);
	  $q->SetPrice($pd->price / 100);

	  $discount = 0; $m = $q->price() * $q->quantity();
	  if (preg_match("/^[0-9.]+%$/", $pd->discount)) $discount = $m * floatval($pd->discount) / 100;
	  else $discount = floatval($pd->discount) / 100;
	  $q->SetTotalcost($m - $discount);

	  $q->SetExtraVal('discount', $pd->discount);
	  $q->SetExtraVal('taxrate', $pd->taxrate);
	  $q->SetExtraVal('comment', $pd->comment);

	  if ($pd->delivered) {
		  if (!$this->correspond($q)) {
			  $r = $this->insertAfter($flow, GOODS_NODE);
			  $r->weakcopy($q);
			  $this->SetCorrespondence($q, $r);
			  $r->SetQuantity($pd->delivered);
		  }
		  else {
			  $rs = $this->correspond($q);
			  assert(sizeof($rs) <= 1);
			  foreach ($rs as $i) {
				  $r = $flow->goods[$i];
				  $r->SetQuantity($pd->delivered);
			  }
		  }
	  }
  }

  function loadData($f) {
	  parent::loadData($f);
	  $this->_processType = $f['resourceType'];
	  $this->_refflow = $f['refflow'];
	  $this->_taskname = $f['name'];
	  $this->_name2 = $f['name2'];
//	  $this->_product = $f['product'];
//	  $this->_category = $f['category'];
	  $this->_workinghourstart = $f['fromtask'];
	  $this->_correspond = unserialize($f['correspond']);
	  $this->_workinghourend = $f['totask'];
	  $this->_rules = unserialize($f['attribs']);
  }
  function copy($f) {
	  parent::copy($f);
	  $this->_processType = $f->_processType;
	  $this->_refflow = $f->_refflow;
	  $this->_taskname = $f->_taskname;
	  $this->_name2 = $f->_name2;
	  $this->_rules = $f->_rules;
	  $this->_correspond = $f->_correspond;
//	  $this->_product = $f->_product;
//	  $this->_category = $f->_category;
	  $this->_workinghourstart = $f->_workinghourstart;
	  $this->_workinghourend = $f->_workinghourend;
  }
  function weakcopy($f) {
	  parent::weakcopy($f);
	  $this->_processType = $f->_processType;
	  $this->_refflow = $f->_refflow;
	  $this->_taskname = $f->_taskname;
	  $this->_name2 = $f->_name2;
	  $this->_correspond = $f->_correspond;
	  $this->_rules = $f->_rules;
//	  $this->_product = $f->_product;
//	  $this->_category = $f->_category;
	  $this->_workinghourstart = $f->_workinghourstart;
	  $this->_workinghourend = $f->_workinghourend;
  }
  function saveData() {
	  $f = parent::saveData();
	  $f['resourceType'] = $this->_processType;
	  $f['refflow'] = $this->_refflow;
	  $f['name'] = $this->_taskname;
	  $f['name2'] = $this->_name2;
//	  $f['product'] = $this->_product;
//	  $f['category'] = $this->_category;
	  $f['attribs'] = $this->_rules;
	  $f['correspond'] = $this->_correspond;
	  $f['fromtask'] = $this->_workinghourstart;
	  $f['totask'] = $this->_workinghourend;
	  return $f;
  }
  function setNodeType($resourceType) {
	  $this->_processType = $resourceType;
  }
  function ready() { return true; }
  function sendto($flow, $g) { }
  function receivefrom($flow, $g) { }
  function evaluateQuantityChanges(&$flow, &$chg, $which, $ratio) {
	  $result = array();
	  $effect = array();
// print_r($which);
	  $que = array(array($which, $ratio));
	  $line = array();
//echo "<pre>";

	  while (list (, $item) = each($que)) {
		  $which = $item[0]; $ratio = $item[1];
		  $whichname = $which->_name;
		  if ($which->_name2 && $which->_dest && $flow->processors[$which->_dest]->_id == $this->_id)
			  $whichname = $which->_name2;

		  foreach ($this->_rules as $rule) {
			  if ($rule['type'] == 'ratio') {
				  if ($rule['dest']['item'] == $whichname && !isset($chg[$rule['src']['item']])) {
					  $src = 'src'; $dest = 'dest';
				  }
				  elseif ($rule['src']['item'] == $whichname && !isset($chg[$rule['dest']['item']])) {
					  $src = 'dest'; $dest = 'src';
				  }
				  else continue;
				  $r = $ratio * $rule[$src]['amount'] / $rule[$dest]['amount'];
				  if ($which->_unit != $rule[$dest]['unit'])
					  $r *= Attributes::$unitsConv[$which->_unit][$rule[$dest]['unit']];
				  $obj = $rule[$src]['item']; $unit = $rule[$src]['unit']; $cunit = NULL;
			  }
			  elseif ($rule['type'] == 'product') {
				  $unit = NULL;
				  if ($rule['target'] == $whichname && !isset($chg[$rule['cause']])) $obj = $rule['cause'];
				  elseif ($rule['cause'] == $whichname && !isset($chg[$rule['target']])) $obj = $rule['target'];
				  else continue;
				  $r = $ratio; $cunit = $which->_unit;
			  }
			  foreach ($flow->goods as $g) {
				  $gname = $g->_name;
				  if ($g->_name2 && $g->_dest && $flow->processors[$g->_dest]->_id == $this->_id)
					  $gname = $g->_name2;
				  if ($gname == $obj) {
					  if (!$g->_unit) $g->_unit = $unit;
					  if ($unit && $unit != $g->_unit) $r *= Attributes::$unitsConv[$unit][$g->_unit];
					  $chg[$obj] = 1;
					  if (($g->_status & 0xF0000) != 0x10000) {
						  $result[] = array('flow'=>$flow->name, 'item'=>$g->_name, 'flowid'=>$flow->id, 'itemid'=>$g->_id, 'quantity'=>array($g->_quantity . $g->_unit, $r . $g->_unit));
						  continue;
					  }
					  if ($cunit) $g->_unit = $cunit;
					  $effect[] = array('flow'=>$flow->name, 'item'=>$g->_name, 'quantity'=>array($g->_quantity . $g->_unit, $r . $g->_unit));
					  $g->_quantity = $r;
					  if ($g->_src && $g->_dest) $line[] = $g;
					  $que[] = array($g, $r);
				  }
			  }
		  }
	  }

	  foreach ($line as $g) {
		  if ($flow->processors[$g->_src]->_taskname == $this->_taskname) {
			  if ($g->_dest > 0)
				$e = $flow->processors[$g->_dest]->evaluateQuantityChanges($flow, $chg, $g, $r);
		  }
		  elseif ($g->src() > 0) $e = $flow->processors[$g->_src]->evaluateQuantityChanges($flow, $chg, $g, $r);
		  if ($e) {
			  $effect = array_merge($effect, $e['effect']);
			  $result = array_merge($result, $e['result']);
		  }
	  }
//echo "</pre>";
	  return array('result'=>$result, 'effect'=>$effect);
  }
  function evaluateCostChanges(&$flow, $relation = 1) {
	  if ($relation != 1) {
		  return array('result'=>array(), 'effect'=>$effect);
	  }

	  $effect = array();
	  $sum = $this->_totalcost;

	  if ($this->_input) foreach ($this->_input as $i) $sum += $flow->goods[$i]->_totalcost;

	  if ($this->_output) {
		  $sum /= sizeof($this->_output);
		  foreach ($this->_output as $i) {
			  $g = &$flow->goods[$i];
			  if ($g->_resourceType != 32 || $g->_totalcost == $sum) continue;
			  else {
				  $effect[] = array('flow'=>$flow->name, 'item'=>$g->_name, 'cost'=>array($g->_totalcost, $sum));
				  $g->_totalcost = $sum;
				  $g->_price = $sum / $g->_quantity;
				  if ($g->_dest) {
					  $e = $flow->processors[$g->_dest]->evaluateCostChanges($flow);
					  $effect = array_merge($effect, $e['effect']);
				  }
				  $flow->saved = false;
			  }
		  }
	  }

	  return array('result'=>array(), 'effect'=>$effect);
  }
  function evaluateThingChanges(&$flow, $which, $relation) {
	  $result = array();
	  $effect = array();

	  $whichname = $which->_name;
	  if ($which->_name2 && $which->_dest && $flow->processors[$which->_dest]->_id == $this->_id)
		  $whichname = $which->_name2;

	  $eq = array();
	  foreach ($this->_rules as $r) {
		  if ($r['type'] != 'product') continue;
		  if ($r['target'] == $whichname) $eq[] = $r['cause'];
		  if ($r['cause'] == $whichname) $eq[] = $r['target'];
	  }

	  if ($relation == 1) {
		  $io = '_output'; $to = '_dest';
	  }
	  else {
		  $io = '_input'; $to = '_src';
	  }
	  if ($this->$io) foreach ($this->$io as $i) {
		  $g = &$flow->goods[$i];
		  $gname = $g->_name;
		  if ($g->_name2 && $g->_dest && $flow->processors[$g->_dest]->id == $this->_id)
			  $gname = $g->_name2;
		  if (in_array($gname, $eq)) {
			  if ($g->_id == $which->_id) continue;
			  if (($g->_status & 0xF) == 0x1 || !$g->_product) {
				  $effect[] = array('flow'=>$flow->name, 'item'=>$g->_name, 'product'=>array($g->_product, $which->_product));
				  if ($g->_quantity . $g->_unit != $which->_quantity . $which->_unit) 
					  $effect[] = array('flow'=>$flow->name, 'item'=>$g->_name, 'quantity'=>array($g->_quantity . $g->_unit, $which->_quantity . $which->_unit));
				  $g->_product = $which->_product;
				  $g->_category = $which->_category;
				  $g->_quantity = $which->_quantity;
				  $g->_unit = $which->_unit;
				  $g->_weight = $which->_weight;
				  $g->_volumn = $which->_volumn;
				  $g->_srcode = $which->_srcode;
				  $g->_barcode = $which->_barcode;
				  if ($g->$to > 0) {
					  $e = $flow->processors[$g->$to]->evaluateThingChanges($flow, $g, $relation);
					  $effect = array_merge($effect, $e['effect']);
					  $result = array_merge($result, $e['result']);
				  }
			  }
			  elseif (!$g->sameProduct($which)) 
				  $result[] = array('flow'=>$flow->name, 'item'=>$g->_name, 'flowid'=>$flow->id, 'itemid'=>$g->_id, 'product'=>array($g->_product, $which->_product));
			  elseif ($g->_quantity != $which->_quantity || $g->_unit != $which->_unit) {
				  if (($g->_status & 0xF0000) == 0x10000) {
					  $effect[] = array('flow'=>$flow->name, 'item'=>$g->_name, 'quantity'=>array($g->_quantity . $g->_unit, $which->_quantity . $which->_unit));
					  $g->_quantity = $which->_quantity;
					  $g->_unit = $which->_unit;
					  $g->_weight = $which->_weight;
					  $g->_volumn = $which->_volumn;
					  $g->_srcode = $which->_srcode;
					  $g->_barcode = $which->_barcode;
					  if ($g->$to) {
						  $e = $flow->processors[$g->$to]->evaluateQuantityChanges($flow, $g);
						  $effect = array_merge($effect, $e['effect']);
						  $result = array_merge($result, $e['result']);
					  }
				  }
				  else $result[] = array('flow'=>$flow->name, 'item'=>$g->_name, 'flowid'=>$flow->id, 'itemid'=>$g->_id, 'quantity'=>array($g->_quantity . $g->_unit, $which->_quantity . $which->_unit));
			  }
		  }
	  }

	  return array('result'=>$result, 'effect'=>$effect);
  }
  function evaluateBeginTimeChanges(&$flow, &$chg, $which, $relation) {
	  $result = array();
	  $effect = array();

	  if ($which && !$which->_start)
		  return array('result'=>$result, 'effect'=>$effect);

	  $start = $which?$which->_start : 0;

	  if ($this->_workinghourend - $this->_workinghourstart == 0) {
		  $this->_workinghourend = 3600 * 24;
		  $this->_workinghourstart = 0;
	  }
// print_r($which);
//echo "<pre>";
	  $day = strtotime(date("Y-m-d", strtotime($start)) . " 00:00:00");
	  $time = strtotime($start) - $day;

	  if ($this->_processType == 2) $d = $this->runtime($flow);
	  else $d = $this->_duration;

	  if ($relation == 1) {
		  $io = '_output'; $dest = '_dest';

		  if ($this->_processType == 2) {
			  // transport. if liner, start time is defined by schedule
			  $start = $this->earliestLine($flow, $start);
		  }
		  elseif ($this->_start && $start < $this->_start) {
			  $day = strtotime(date("Y-m-d", strtotime($this->_start)) . " 00:00:00");
			  $time = strtotime($this->_start) - $day;
		  }
		  if ($this->_workinghourend <= 3600 * 24) {
			  if ($time < $this->_workinghourstart) $time = $this->_workinghourstart;
			  elseif ($time > $this->_workinghourend) {
				  $time = $this->_workinghourstart;
				  $day += 3600 * 24;
			  }
		  }
		  elseif ($time < $this->_workinghourstart && $time >= $this->_workinghourend - 3600 *24)
			  $time = $this->_workinghourstart;
		  elseif ($time < $this->_workinghourend - 3600 *24) {
			  $d += $time + 3600 * 24 - $this->_workinghourstart;
			  $time = $this->_workinghourstart;
			  $day -= 3600 * 24;
		  }

		  $d += $time - $this->_workinghourstart;
		  $time = $this->_workinghourstart;

		  $day += floor($d / ($this->_workinghourend - $this->_workinghourstart)) * 3600 * 24;
		  $time += $d % ($this->_workinghourend - $this->_workinghourstart);
		  $time = $day + $time;
	  }
	  else {
		  $io = '_input'; $dest = '_src';

		  if ($this->_workinghourend <= 3600 * 24) {
			  if ($time > $this->_workinghourend) $time = $this->_workinghourend;
			  elseif ($time < $this->_workinghourstart) {
				  $time = $this->_workinghourend;
				  $day -= 3600 * 24;
			  }
		  }
		  elseif ($time < $this->_workinghourstart && $time >= $this->_workinghourend - 3600 *24) {
			  $time = $this->_workinghourend;
			  $day -= 3600 * 24;
		  }
		  elseif ($time < $this->_workinghourend - 3600 *24) {
			  $d += $this->_workinghourend - 3600 *24 - $time;
			  $time = $this->_workinghourend;
			  $day -= 3600 * 24;
		  }

		  $d += $this->_workinghourend - $time;
		  $time = $this->_workinghourend;

		  $day -= floor($d / ($this->_workinghourend - $this->_workinghourstart)) * 3600 * 24;
		  $time -= $d % ($this->_workinghourend - $this->_workinghourstart);
		  $time = $day + $time;

		  if ($this->_processType == 2) {
			  // transport. if liner, start time is defined by schedule
			  $time = strtotime($this->latestLine($flow, date("Y-m-d H:i:s", $time)));
		  }
		  elseif ($this->_start && date("Y-m-d H:i:s", $time + 600) < $this->_start)	// allow 10 min error
			  return array('result'=>array(array('flow'=>$flow->name, 'item'=>$this->_taskname, 'flowid'=>$flow->id, 'itemid'=>$this->_id, 'time'=>array($this->_start, date("Y-m-d H:i:s", $time)))), 'effect'=>$effect);
	  }

	  foreach ($this->$io as $i) {
		  if (($time - strtotime($flow->goods[$i]->_start)) * $relation <= 0 || isset($chg[$flow->goods[$i]->_name])) continue;
		  $chg[$flow->goods[$i]->_name] = 1;
		  $dtime = date("Y-m-d H:i:s", $time);
		  if (($flow->goods[$i]->_status & 0xF0) != 0x10) {
			  $result[] = array('flow'=>$flow->name, 'item'=>$flow->goods[$i]->_name, 'flowid'=>$flow->id, 'itemid'=>$flow->goods[$i]->_id, 'time'=>array($flow->goods[$i]->_start, $dtime));
			  continue;
		  }
		  $effect[] = array('flow'=>$flow->name, 'item'=>$flow->goods[$i]->_name, 'time'=>array($flow->goods[$i]->_start, $dtime));
		  $flow->goods[$i]->_start = $dtime;
		  if ($flow->goods[$i]->$dest > 0) {
			  $e = $flow->processors[$flow->goods[$i]->$dest]->evaluateBeginTimeChanges($flow, $chg, $flow->goods[$i], $relation);
			  $effect = array_merge($effect, $e['effect']);
			  $result = array_merge($result, $e['result']);
		  }
	  }

//echo "</pre>";
	  return array('result'=>$result, 'effect'=>$effect);
  }

  function evaluatePlaceChanges(&$flow, $which, $relation) {
	  $result = array();
	  $effect = array();

	  $wm = locCache($which->_place);
	  $wm = $wm['province'] . $wm['city'] . $wm['name'];
	  if ($relation == 1)
		  if ($which->_resourceType != 4096) return array('result'=>array(), 'effect'=>array());	// 站点
		  else foreach ($this->_output as $i) {
			  $g = &$flow->goods[$i];
			  if ($g->_place == $which->_place || (!$which->_place && $g->_province == $which->_province && $g->_city == $which->_city))
					continue;
			  else {
				  $gm = locCache($g->_place);
				  $gm = $gm['province'] . $gm['city'] . $gm['name'];
				  if ((!$g->_place && (($g->_city == $which->_city && $g->_province == $which->_province) || (!$g->_city && ($g->_province == $which->_province || !$g->_province)))) || ($g->_status & 0xF000) == 0x1000) {
					  $effect[] = array('flow'=>$flow->name, 'item'=>$g->_name, 'place'=>array($gm, $wm));
					  $g->_place = $which->_place;
					  $g->_province = $which->_province;
					  $g->_city = $which->_city;
					  $flow->saved = false;
				  }
				  else $result[] = array('flow'=>$flow->name, 'item'=>$g->_name, 'flowid'=>$flow->id, 'itemid'=>$g->_id, 'place'=>array($gm, $wm));
			  }
		  }
	  else {
		  $hasdestnode = false;
		  if ($this->_input) foreach ($this->_input as $i) {
			  $g = &$flow->goods[$i];
			  if ($g->_resourceType == 4096) {
				$hasdestnode = true;
				$gm = locCache($g->_place);
				$gm = $gm['province'] . $gm['city'] . $gm['name'];
				if (($g->_status & 0xF000) == 0x1000) {
					if (!($g->_place == $which->_place || (!$which->_place && $g->_province == $which->_province && $g->_city == $which->_city))) {
						$effect[] = array('flow'=>$flow->name, 'item'=>$g->_name, 'place'=>array($gm, $wm));
						$g->_place = $which->_place;
						$g->_province = $which->_province;
						$g->_city = $which->_city;
						$flow->saved = false;

						$result = $this->evaluatePlaceChanges($flow, $g, 1);
						$result['effect'] = array_merge($effect, $result['effect']);
						return $result;
					}
				}
				else $result[] = array('flow'=>$flow->name, 'item'=>$g->_name, 'flowid'=>$flow->id, 'itemid'=>$g->_id, 'place'=>array($gm, $wm));
			  }
		  }

		  if ($hasdestnode) return array('result'=>$result, 'effect'=>$effect);

		  if (!$this->_place) {
			  $loc = $GLOBALS['core']->sqlSelect('mod_org', 'id', $val->org);
			  $this->_place = $loc[0]['location'];
		  }
//		  cachePlace($this->_place);

		  if ($this->_place == $which->_place) {
			  foreach ($this->_output as $i) {
				$g = &$flow->goods[$i];
				$g->_place = $which->_place;
				$g->_province = $which->_province;
				$g->_city = $which->_city;
				$flow->saved = false;
			  }
			  return array('result'=>$result, 'effect'=>$effect);
		  }
	  }
	  return array('result'=>$result, 'effect'=>$effect);
  }
  function propagateChanges(&$flow, $which, $relation, $changes, $back = false) {
	  $result = new PHPWS_Result();
	  if (!is_array($changes)) $changes = $which->whatChanged($changes);

	  $whichname = $which->_name;
	  if ($which->_name2 && $which->_dest && $flow->processors[$which->_dest]->_id == $this->_id)
		  $whichname = $which->_name2;

	  $g = NULL;
	  if (($which->_src < 0 || $which->_dest < 0) && !$back) {
		  $sid = $which->_src < 0?$which->_src < 0 : $which->_dest;
		  $src = $GLOBALS['core']->sqlSelect($this->flowTable, 'id', -$sid);
		  $f = new PHPWS_Flow();
		  $f->loadflow($src[0]['flowid']);
		  foreach ($f->goods as $i=>$t) {
			  if ($t->id == -$sid) $g = $t;
		  }
	  }
	  if ($this->_connects && isset($this->_connects['flow1'])) {
		  $f = new PHPWS_Flow();
		  $f->loadflow($this->_connects['flow1']);
		  if ($this->_connects['in2'])
			  foreach ($this->_connects['in2'] as $i=>$in1) {
					if ($flow->goods[$in1]->_name == $whichname) {
						$g = $f->goods[$this->_connects['in1'][$i]];
					}
			  }
		  if ($this->_connects['out2'])
			  foreach ($this->_connects['out2'] as $i=>$in1) {
					if ($flow->goods[$in1]->_name == $whichname) {
						$g = $f->goods[$this->_connects['out1'][$i]];
					}
			  }
	  }
	  if ($g && ($g->_status & 0xF) == 1) {
		  $gchange = 0;
		  if ($which->_product || $which->_category) {
			  $g->_product = $which->_product;
			  $g->_category = $which->_category;
			  $gchange |= 1024;
		  }
		  if ($which->_quantity || $which->_unit) {
			  $g->_quantity = $which->_quantity;
			  $g->_unit = $which->_unit;
			  $gchange |= 4096;
		  }
		  if ($which->_place || $which->_province || $which->_city) {
			  $g->_place = $which->_place;
			  $g->_province = $which->_province;
			  $g->_city = $which->_city;
			  $gchange |= 4;
		  }
		  if ($which->_start) {
			  $g->_start = $which->_start;
			  $gchange |= 16;
		  }
		  if ($gchange) {
			  $f->processors[$g->_dest>0?$g->_dest : $g->_src]->propagateChanges($f, $g, $g->_dest>0? 1 : -1, $gchange, true);
			  $f->saved = false;
			  $f->saveflow(false);
		  }
	  }

	  if ($changes) foreach ($changes as $c) {
		  switch ($c) {
			  case 1:	// things
				  $result->mergeResult($this->evaluateThingChanges($flow, $which, $relation));
				  break;
			  case 2:	// begin
				  $chg = array($which->_name=>1);
				  if ($which->_name2) $chg[$which->_name2] = 1;
				  $result->mergeResult($this->evaluateBeginTimeChanges($flow, $chg, $which, $relation));
				  break;
			  case 3:	// end
				  $result->mergeResult($this->evaluateEndTimeChanges($flow, $which, $relation));
				  break;
			  case 4:	// where
				  $result->mergeResult($$this->evaluatePlaceChanges($flow, $which, $relation));
				  break;
			  case 5:	// quantity
				  $chg = array($which->_name=>1);
				  if ($which->_name2) $chg[$which->_name2] = 1;
				  $result->mergeResult($this->evaluateQuantityChanges($flow, $chg, $which, $which->_quantity));
				  break;
			  case 6:	// cost
				  if ($relation == 1) $result->mergeResult($this->evaluateCostChanges($flow));
				  break;
		  }
	  }

	  return $result;
  }

  function propagateTaskChanges(&$flow, $changes) {
	  $result = new PHPWS_Result();
	  if (!is_array($changes)) $changes = $this->whatChanged($changes);

	  if ($changes) foreach ($changes as $c) {
		  switch ($c) {
			  case 1:	// things
				  foreach ($this->_rules as $rule) {
					  if ($rule['type'] != 'product') continue;
					  foreach ($flow->goods as $which) {
						  $whichname = $which->_name;
						  if ($which->_name2 && $which->_dest && $flow->processors[$which->_dest]->_id == $this->_id)
							  $whichname = $which->_name2;

						  if ($rule['cause'] != $whichname && $rule['target'] != $whichname) continue;
						  if ($which->_src > 0 && $flow->precessors[$which->_src]->_taskname == $this->_taskname)
							  $result->mergeResult($this->evaluateThingChanges($flow, $which, -1));
						  if ($which->_dest > 0 && $flow->precessors[$which->_dest]->_taskname == $this->_taskname)
							  $result->mergeResult($this->evaluateThingChanges($flow, $which, 1));
					  }
				  }
				  break;
			  case 2:	// begin
				  if ($this->_input) foreach ($this->_input as $in) {
					  if ($flow->goods[$in]->resourceType != 32) continue;
					  if (!$flow->goods[$in]->start()) return $result;
					  if ($this->_start && $flow->goods[$in]->_start > $this->_start) 
						  $result->mergeResult(array('result'=>array(array('flow'=>$flow->name, 'item'=>$this->_taskname, 'flowid'=>$flow->id, 'itemid'=>$flow->goods[$in]->_id, 'time'=>array($this->_start, $flow->goods[$in]->_start))), 'effect'=>array()));
					  $chg = array($flow->goods[$in]->_name=>1);
					  $result->mergeResult($this->evaluateBeginTimeChanges($flow, $chg, $flow->goods[$in], 1));
				  }
				  elseif ($this->_start) {
					  $chg = array();
					  $result->mergeResult($this->evaluateBeginTimeChanges($flow, $chg, NULL, 1));
				  }
				  break;
			  case 4:	// where
				  if ($this->_input) foreach ($this->_input as $in) {
					  if ($flow->goods[$in]->_resourceType == 4096) return $result;
					  if ($flow->goods[$in]->_resourceType == 32) $which = $flow->goods[$in];
				  }
				  $result->mergeResult($this->evaluatePlaceChanges($flow, $which, $relation));
				  break;
			  case 5:	// quantity
				  $chg = array();
				  $cause = array();
				  foreach ($this->_rules as $rule) {
					  if ($rule['type'] != 'ratio') continue;
					  foreach ($flow->goods as $which) {
						  $whichname = $which->_name;
						  if ($which->_name2 && $which->_dest && $flow->processors[$which->dest()]->_id == $this->_id)
							  $whichname = $which->_name2;

						  if ($rule['src']['item'] != $whichname && $rule['dest']['item'] != $whichname) continue;
						  $this->searchCause($flow, $which, $chg, $cause);
					  }
				  }
				  foreach ($cause as $i) {
					  $chg = array($flow->goods[$i]->_name=>1);
					  $result->mergeResult($this->evaluateQuantityChanges($flow, $chg, $flow->goods[$i], $flow->goods[$i]->_quantity));
				  }
				  break;
			  case 6:	// cost
				  $result->mergeResult($this->evaluateCostChanges($flow));
				  break;
		  }
	  }

	  return $result;
  }
  function searchCause(&$flow, $which, &$chg, &$cause) {
	  if ($chg[$which->_name]) return;
	  $chg[$which->_name] = 1;
	  if ($which->_name2) $chg[$which->_name2] = 1;
	  $whichname = $which->_name;
	  if ($which->_name2 && $which->_dest && $flow->processors[$which->_dest]->_id == $this->_id)
		  $whichname = $which->_name2;

	  if (($which->_status & ~0xF0000) == 0x10000) $cause[$which->_name] = 1;

	  foreach ($this->_rules as $rule) {
		  if ($rule['type'] != 'ratio') continue;
		  if ($rule['src']['item'] != $whichname && $rule['dest']['item'] != $whichname) continue;
		  else {
			  if ($which->_dest > 0 && $flow->processors[$which->_dest]->_taskname != $this->_taskname)
				  $flow->processors[$which->_dest]->searchCause($flow, $which, $chg, $cause);
			  if ($which->src() >0 && $flow->processors[$which->_src]->_taskname != $this->_taskname)
				  $flow->processors[$which->_src]->searchCause($flow, $which, $chg, $cause);
			  $oth = $rule['dest']['item'] != $which->_name?$rule['dest']['item'] : $rule['src']['item'];
			  foreach ($flow->goods as $g) {
				  $gname = $g->_name;
				  if ($g->_name2 && $g->_dest && $flow->processors[$g->_dest]->_id == $this->_id)
					  $gname = $g->_name2;

				  if ($gname == $oth && !$chg[$gname]) 
					$this->searchCause($flow, $g, $chg, $cause);
			  }
		  }
	  }
  }

  function propagateStatus($flow) {
	  $status = $this->_status;
	  if (($status  & 0xF) < 4 && $status > 0) {
		  if ($this->_input) {
			  $ready = true;
			  foreach ($this->_input as $in) {
				  if ($flow->goods[$in]->_status < 4 && $flow->goods[$in]->_status > 0)
					  $ready = false;
			  }
			  if ($ready) $status = $this->_status = 4;
		  }
		  else $status = $this->_status = 4;
	  }

	  if (($status & 0xF) == 4 && !$this->_start) $this->_start = date("Y-m-d H:i:s");
	  if ($status == -1 && !$this->_end) $this->_end = date("Y-m-d H:i:s");
	  if ($status == 8) {
		  if ($this->_input) foreach ($this->_input as $i)
			  if ($flow->goods[$i]->_status == 4) $flow->goods[$i]->setStatus($flow, 8);
//		  if ($this->_output) foreach ($this->_output as $i) {
//			  $flow->goods[$i]->org = $this->_org;
//			  $flow->goods[$i]->setStatus($flow, 4);
//		  }
	  }

	  if ($status == -1) {
		  if ($this->_input) foreach ($this->_input as $i) $flow->goods[$i]->setStatus($flow, -1);
		  if ($this->_output) foreach ($this->_output as $i)
			  if ($flow->goods[$i]->_status > 0 && ($flow->goods[$i]->_status & 0xF) < 4)
					$flow->goods[$i]->setStatus($flow, 4);
	  }
  	  if ($flow->status != -1) {
		  if ($status == 8 && $flow->status != 8)
			  $flow->status = 8;
		  elseif ($status == -1) {
			  if ($flow->processors)
				  foreach ($flow->processors as $g) if ($g->_status != -1) $status = 0;
			  if ($flow->goods)
				  foreach ($flow->goods as $g) if ($g->_dest <= 0 && $g->_status > 0 && $g->_status !=8) $status = 0;
			  if ($status == -1) {
				  $flow->status = -1;
				  $GLOBALS['core']->sqlDelete('mod_planadjusts', 'flow', $flow->id);
			  }
		  }
	  }
  }
  function received($flow, $obj) {
	  $done = true;
	  foreach ($this->_input as $in)
		  if (($flow->goods[$in]->_status & 0xF) < 4) $done = false;
	  if ($done && ($this->_status & 0xF) < 4) $this->setStatus($flow, 4);
  }
/*
  function delivered($flow, $obj) {
	  $done = true;
	  foreach ($this->_output as $out)
		  if (($flow->goods[$out]->status() & 0xF) < 4) $done = false;
	  if ($done) $this->setStatus($flow, -1);
  }
*/
  function execute($flow) {
	  extract($_POST);

	  if ($xchg == "完成任务") {
		  $result = new PHPWS_Result();
		  foreach ($quantity as $i=>$q) {
			  if ($flow->goods[$i]->_quantity != $q || $flow->goods[$i]->_unit != $unit[$i]) {
				  $flow->goods[$i]->_quantity = $q;
				  $flow->goods[$i]->_unit = $unit[$i];
				  $flow->goods[$i]->_start = date('Y-m-d H:i:s');
				  $result->mergeResult($flow->goods[$i]->propagateChanges($flow, 4096 | 16));
				  $flow->goods[$i]->setStatus($flow, 4);
			  }
		  }
		  $this->setStatus($flow, -1);
		  return "<table>" . $result->showchgresult() . "</table>" . $flow->viewflow(true);
	  }
	  else {
		  $rs .= "<table><tr><th align=center colspan=3>{$this->_taskname}任务结果</th></tr>";
		  $rs .= "<tr><th align=center>资源</th><th align=center>产品</th><th align=center>数量</th></tr>";
		  $pu = false;
		  foreach ($this->_output as $i) {
			  $t = &$flow->goods[$i];
			  $rs .= "<tr><th align=center>{$t->_name}</th><th align=center>";
			  if (!$t->_product) $pu = true;
			  else {
				  $rs .= prodCache($t->_product);
			  }
			  $rs .= "</th><th align=center>";
				  
			  $us = array_merge(array(0=>''), Attributes::$units);
			  $rs .= PHPWS_Form::formTextField("quantity[$i]", $t->_quantity) . PHPWS_Form::formSelect("unit[$i]", $us, $t->_unit, true, false);
			  $rs .= "</th></tr>";
		  }
		  if (!$pu) {
			  $rs .= "<tr><td align=center colspan=3>" . PHPWS_Form::formSubmit("完成任务", 'xchg') . "</td></tr></table>";
			  return $rs;
		  }
	  }
  }
  function dataForm($show = 0xFFFFFFFF, $edit = 0xFFFFFFFF) {
	  $maychg = true;
	  if (in_array($this->_status, array(0, -1, 8))) $maychg = false;
	  elseif ($this->_org && $this->_org != $_SESSION["OBJ_user"]->org) $maychg = false;

	  $s .= parent::dataForm($show, $edit & ~4);

	  if ($this->_rules) {
		  $s .= "<tr><td>规则：</td><td><pre>" . print_r($this->_rules, true) . "</pre></td></tr>";
	  }

	  if ($this->_correspond) {
		  $cormode = array(0=>'1:1', 1=>'1:N', 2=>'N:1', 3=>'illegal');
		  $s .= "<tr><td>对应关系：</td><td>对应类型：{$cormode[$this->__correspondMode]}<br><pre>" . print_r($this->_correspond, true) . "</pre></td></tr>";
	  }

	  if ($this->_org) {
		  $s .= "<tr><td>上班时间：</td><td>";
		  $t = str_pad(floor($this->_workinghourstart / 3600), 2, '0', STR_PAD_LEFT) . ":" . str_pad(floor(($this->_workinghourstart % 3600) / 60), 2, '0', STR_PAD_LEFT);
		  if ($maychg) $s .= PHPWS_Form::formTextField("workinghourstart", $t) . "（24小时时制，如：8:30）";
		  else $s .= $t;
		  $s .= "</td></tr>";

		  $s .= "<tr><td>下班时间：</td><td>";
		  $t = str_pad(floor($this->_workinghourend / 3600), 2, '0', STR_PAD_LEFT) . ":" . str_pad(floor(($this->_workinghourend % 3600) / 60), 2, '0', STR_PAD_LEFT);
		  if ($maychg) $s .= PHPWS_Form::formTextField("workinghourend", $t) . "（24小时时制，如：25:30表示次日凌晨1:30）";
		  else $s .= $t;
		  $s .= "</td></tr>";
	  }

	  $rd = floor($this->_duration / 60); $ru = "分钟";

	  if ($this->_org || !$this->_end) {
		  if ($rd > 60 && $rd <= 60 * 24) {
			  if ($rd % 60 == 0) {
				  $rd = $rd / 60; $ru = "小时";
			  }
		  }
		  elseif ($rd > 60 * 24) {
			  if ($rd % (60 * 24) == 0) {
				  $rd = $rd / (60 * 24); $ru = "天";
			  }
			  else { $rd = floor($rd / 60); $ru = "小时"; }
		  }
		  if ($maychg) $s .= "<tr><td>任务用时：</td><td>" . PHPWS_Form::formTextField("workduration", $rd) . PHPWS_Form::formSelect("duraunit", array("分钟", "小时", "天", "周", "月"), $ru, true, false) . "</td></tr>";
		  else $s .= "<tr><td>任务用时：</td><td>" . $rd . $ru . "</td></tr>";
	  }

	  $s .= "<tr><td>工作部门：</td><td>";
	  if ($this->_org) {
		  $s .= orgCache($this->_org) . "</td></tr>";
	  }
	  else $s .= "外包单位待定</td><td>";

	  $units = Attributes::$units;

	  $ratio = $delay = $location = NULL;
	  if ($this->_rules) foreach ($this->_rules as $rule) {
		  if ($rule['type'] == 'ratio') {
			  $ratio .= $rule['src']['amount'] . $rule['src']['unit'] . $rule['src']['item'] . " ：" . $rule['dest']['amount'] . $rule['dest']['unit'] . $rule['dest']['item'] . "<br>";
		  }
		  elseif ($rule['type'] == 'time') {
			  $delay .= $rule['target'] . "的时间 = ";
			  if ($rule['cause']) 
				  $delay .= $rule['cause'] . "的时间之后" . $rule['delay'] . $delayunit[$rule['unit']];
			  else $delay .= $rule['delay'];
			  $delay .= "<br>";
		  }
		  elseif ($rule['type'] == 'duration') {
			  $delay .= $rule['target'] . "的时长 = ";
			  if ($rule['cause']) {
				  $delay .= $rule['cause'] . "的时长";
				  if ($rule['delay'] > 0) 
					  $delay .= " + " . $rule['delay'] . $delayunit[$rule['unit']];
				  elseif ($rule['delay'] < 0) 
					  $delay .= " - " . (-$rule['delay']) . $delayunit[$rule['unit']];
			  }
			  else $delay .= $rule['delay'] . $delayunit[$rule['unit']];
			  $delay .= "<br>";
		  }
		  elseif ($rule['type'] == 'place') {
			  $location .= $rule['target'] . "的地点 = ";
			  $location .= $rule['cause'] . "的地点";
			  $location .= "<br>";
		  }
		  elseif ($rule['type'] == 'product') {
			  $things .= $rule['target'] . "的物品 = ";
			  $things .= $rule['cause'] . "的物品";
			  $things .= "<br>";
		  }
	  }

	  if ($this->_org == $_SESSION["OBJ_user"]->org && ($edit & 65536)) {
		  $s .= "<tr><td>物品规则：</td><td name=showrules><div id=prodrule>$things</div><a href=#a onclick='additem(6);'>增加物品规则</a></td></tr>";
		  $s .= "<tr><td>数量规则：</td><td name=showrules><div id=ratiorule>$ratio</div><a href=#a onclick='additem(3);'>增加数量规则</a></td></tr>";
	  }
	  else {
		  if ($things) $s .= "<tr><td>物品规则：</td><td name=showrules><div id=prodrule>$things</div></td></tr>";
		  if ($ratio) $s .= "<tr><td>数量规则：</td><td name=showrules><div id=ratiorule>$ratio</div></td></tr>";
	  }
//	  $prods .= "<tr><td>时间规则：</td><td name=showrules><div id=timerule>$delay</div><a href=#a onclick='additem(4);'>增加时间规则</a></td></tr>";
//	  $prods .= "<tr><td>地点规则：</td><td name=showrules><div id=placerule>$location</div><a href=#a onclick='additem(5);'>增加地点规则</a></td></tr>";

//	  $statuses = PHPWS_Flow::$flowstatus;
//	  unset($statuses[0]);
//	  $prods .= "<tr><td>状态：</td><td>" . PHPWS_Form::formSelect("status", $statuses, $obj->status(), false, true) . "</td></tr>";

	  return $s;
  }
  function changeData() {
	  extract($_REQUEST);
	  extract($_POST);

	  $changes = 0;

	  if ($srproduct) {
		  foreach ($srproduct as $i=>$smt) {
			  if ($smt != $destproduct[$i]) {
				  $changes |= 1024;
				  $obj->_rules[] = array('type'=>'product', 'cause'=>$smt, 'target'=>$destproduct[$i]);
			  }
		  }
	  }
	  if ($srcamt) {
		  foreach ($srcamt as $i=>$smt) {
			  $changes |= 4096;
			  $obj->_rules[] = array('type'=>'ratio', 'src'=>array('unit'=>$srcratiounit[$i], 'item'=>$srcratio[$i], 'amount'=>$smt), 'dest'=>array('unit'=>$destratiounit[$i], 'item'=>$destratio[$i], 'amount'=>$destamt[$i]));
		  }
	  }
	  if ($desttime) {
		  foreach ($desttime as $i=>$smt)
			  if ($smt != $srctime[$i] || $delaytime[$i] != 0)
				  $obj->_rules[] = array('type'=>'time', 'target'=>$smt, 'cause'=>$smt != $srctime[$i]?$srctime[$i] : NULL, 'delay'=>$delaytime[$i], 'unit'=>$delayunit[$i]);
		  foreach ($destduration as $i=>$smt)
			  if ($smt != $srcduration[$i] || $duration[$i] != 0)
				  $obj->_rules[] = array('type'=>'duration', 'target'=>$smt, 'cause'=>$smt != $srcduration[$i]?$srcduration[$i] : NULL, 'delay'=>$duration[$i], 'unit'=>$durationunit[$i]);
	  }
	  if ($destplace) {
		  foreach ($destplace as $i=>$smt)
			  if ($smt != $srcplace[$i])
				  $obj->_rules[] = array('type'=>'place', 'target'=>$smt, 'cause'=>$srcplace[$i]);
	  }

	  if ($workduration) {
		  $durationsec = array("分钟"=>60, "小时"=>3600, "天"=>3600 * 24, "周"=>3600 * 24 * 7, "月"=>(strtotime("+$workduration month") - time()) / $workduration);
		  if ($workduration * $durationsec[$duraunit] != $this->_duration) {
			  $this->_duration = $workduration * $durationsec[$duraunit];
			  $changes |= 16;
		  }
	  }
	  else $this->_duration = $workduration;

	  if ($workinghourstart) {
		  $workinghourstart = explode(":", $workinghourstart);
		  if ($this->_workinghourstart != $workinghourstart[0] * 3600 + $workinghourstart[1] * 60) {
			  $this->_workinghourstart = $workinghourstart[0] * 3600 + $workinghourstart[1] * 60;
			  $changes |= 16;
		  }
	  }

	  if ($workinghourend) {
		  $workinghourend = explode(":", $workinghourend);
		  if ($this->_workinghourend != $workinghourend[0] * 3600 + $workinghourend[1] * 60) {
			  $this->_workinghourend = $workinghourend[0] * 3600 + $workinghourend[1] * 60;
			  if ($this->_workinghourend < $this->_workinghourstart) $this->_workinghourend += 3600 * 24;
			  $changes |= 16;
		  }
	  }
	  if ($changes) $this->saved = false;

	  $changes |= parent::changeData();

	  if ($this->_start && $this->_end) $this->_duration = $this->_end - $this->_start;
	  return $changes;
  }
  function whatChanged($changes) {
	  static $changeWhat = array(1024=>1, 4096=>5);
	  $res = array();
	  foreach ($changeWhat as $i=>$t) {
		  if (!($changes & $i)) continue;
		  $changes &= ~$i;
		  if (!$t) continue;
		  $res[$t] = $i;
	  }
	  $res = array_flip($res);
	  if ($changes) $res = array_merge($res, parent::whatChanged($changes));
	  return $res;
  }
  function ccname() {
	  // status color coded name: black - 0/1, 2 - orange, 4 - blue, 8 - green, red - 10 (only in tracing mode)
	  return "<span style='color:" . PHPWS_ResourceNode::$nameColorCode[$this->_status & 0xF] . "'>{$this->_taskname}</span>";
  }
  function option($name = 'name') {
	  switch ($name) {
		  case 'place': 
			  $tp = locCache($this->_place);
			  return $this->_taskname . "：" . $tp['name'];
		  case 'start': return $this->_taskname . "：" . $this->_start;
		  case 'end': return $this->_taskname . "：" . $this->_end;
		  case 'cost': return $this->_taskname . "：" . $this->_totalcost;
		  case 'status': return $this->_taskname . "：" . ($this->_status & 0xF);
		  case 'quantity': return $this->_taskname . "：" . round($this->_quantity, 3) . $this->_unit;
		  case 'org': return orgCache($this->_org);
		  case 'id': return $this->_taskname . "：" . $this->_id;
		  case 'name':
		  case 'product':
		  default:
			  return $this->_taskname; 
	  }
  }

  function forcedChange($flow, $origional, $change, $affected) {
  }

  function insertBefore($flow, $goodsType, $copydata = NULL) {
	  $p2 = PHPWS_Node::newNode($goodsType);
	  if ($copydata) {
		  $p2->copy($copydata);
		  $p2->_connects = array();
		  $p2->_id = 0;
		  $p2->_src = 0;
		  $p2->_status = 1;
	  }
	  $p2->flowid = $flow->id;
	  $p2->_innerid = $flow->goodsid;
	  $flow->goods[$flow->goodsid++] = $p2;
	  $p2->_dest = $this->_innerid;
	  $this->_input[] = $p2->_innerid;
	  return $p2;
  }
  function & insertAfter($flow, $goodsType, $copydata = NULL) {
	  $p2 = PHPWS_Node::newNode($goodsType);
	  if ($copydata) {
		  $p2->copy($copydata);
		  $p2->_connects = array();
		  $p2->_id = 0;
		  $p2->_dest = 0;
		  $p2->_status = 1;
	  }
	  $p2->flowid = $flow->id;
	  $p2->_innerid = $flow->goodsid;
	  $flow->goods[$flow->goodsid++] = $p2;
	  $p2->_src = $this->_innerid;
	  $this->_output[] = $p2->_innerid;
	  return $p2;
  }
  function remove(&$flow) {
	  if ($this->_output) foreach ($this->_output as $i)
		  $flow->goods[$i]->_src = 0;
	  if ($this->_input) foreach ($this->_input as $i)
		  $flow->goods[$i]->_dest = 0;
	  unset($flow->processors[$this->_innerid]);
	  $GLOBALS['core']->sqlDelete($this->flowTable, 'id', $this->_id);
  }
  function addOrg($org) {
	  if (!$this->_id) $this->_pendingaddorgs[] = $org;
	  else $GLOBALS['core']->query("REPLACE INTO supply_orgtasks (org, task, processType) VALUES ({$org}, {$this->_id}, {$this->_processType})", false);
  }
  function commitOrgs() {
	  if ($this->_pendingaddorgs) {
		  foreach ($this->_pendingaddorgs as $org)
			  $GLOBALS['core']->query("REPLACE INTO supply_orgtasks (org, task, processType) VALUES ({$org}, {$this->_id}, {$this->_processType})", false);
		  $this->_pendingaddorgs = array();
	  }
  }

  function correspond($p) {
	  if (!is_numeric($p)) $p = $p->innerid();

	  switch ($this->__correspondMode) {
		  case 0: return $this->_correspond[$p];
		  case 1: if (in_array($p, $this->_output)) return $this->_correspond[$p];
				$res = array();
				foreach ($this->_correspond as $i=>$v)
					if ($v == $p) $res[] = $i;
				return $res;
		  case 2: if (in_array($p, $this->_input)) return $this->_correspond[$p];
				$res = array();
				foreach ($this->_correspond as $i=>$v)
					if ($v == $p) $res[] = $i;
				return $res;
	  }
  }
  function SetCorrespondence($p, $q) {		// $p - input side, $q - output side
	  switch ($this->__correspondMode) {
		  case 0:
			  $this->_correspond[$p->innerid()] = $q->innerid();
			  $this->_correspond[$q->innerid()] = $p->innerid();
			  break;
		  case 1:
			  $this->_correspond[$q->innerid()] = $p->innerid();
			  break;
		  case 2:
			  $this->_correspond[$p->innerid()] = $q->innerid();
			  break;
	  }
  }

  function Untangle($p) {
	  unset($this->_correspond[$p->innerid()]);

	  foreach ($this->_correspond as $i=>$v)
		  if ($v == $p->innerid()) unset($this->_correspond[$i]);
  }

  function & input() { return $this->_input; }
  function & output() { return $this->_output; }
  function refflow() { return $this->_refflow; }
  function processType() { return $this->_processType; }
  function taskname() { return $this->_taskname; }
  function & rules() { return $this->_rules; }
  function workinghourstart() { return $this->_workinghourstart; }
  function workinghourend() { return $this->_workinghourend; }
  function getRule($val) { foreach ($this->_rules as $r) if ($r['type'] == $val) return $r; }
  function name2() { return $this->_name2; }

  function mirror($q, $flow, $creatnew = false) {	// find mapping node, optionally create one if not exist
	  switch ($this->__correspondMode) {
		  case 0:
		  case 2:
			  if ($this->_correspond[$q->innerid()]) return $flow->goods[$this->_correspond[$q->innerid()]];
			  break;
		  case 1:
			  $bm = NULL;
			  foreach ($this->_correspond as $i=>$j)
					if ($q->innerid() == $j) {
						if (!$bm) $bm = $flow->goods[$i];
						elseif ($flow->goods[$i]->dest() == 0) $bm = $flow->goods[$i];
						elseif ($flow->goods[$i]->dest() < 0 && $bm->dest() != 0) $bm = $flow->goods[$i];
					}
			  if ($bm) return $bm;
			  break;
	  }
	  if ($creatnew) {
		  $r = $this->insertAfter($flow, $q->resourceType(), $q);
		  $this->SetCorrespondence($q, $r);
		  return $r;
	  }
	  return NULL;
  }

  function SetInput($val) { $this->_input = $val; $this->saved = false; return $val; }
  function AddInput($val) {
	  $this->_input[] = $val->innerid();
	  $val->SetDest($this->_innerid);
	  $this->saved = false;
	  return $val;
  }
  function SetOutput($val) { $this->_output = $val; $this->saved = false; return $val; }
  function AddOutput($val) {
	  $this->_output[] = $val->innerid();
	  $val->SetSrc($this->_innerid);
	  $this->saved = false;
	  return $val;
  }
  function RmInput($flow, $g) {
	  $this->Untangle($g);

	  $this->_input = array_diff($this->_input, array($g->innerid()));
	  if (!$g->src()) {
		  if ($g->id()) $GLOBALS['core']->sqlDelete($flow->flowTable, 'id', $g->id());
		  unset($flow->goods[$g->innerid()]);
	  }
	  $g->SetDest(0);

	  $this->saved = false;
  }
  function RmOutput($flow, $g) {
	  $this->Untangle($g);

	  $this->_output = array_diff($this->_output, array($g->innerid()));
	  if (!$g->dest()) {
		  if ($g->id()) $GLOBALS['core']->sqlDelete($flow->flowTable, 'id', $g->id());
		  unset($flow->goods[$g->innerid()]);
	  }
	  $g->SetSrc(0);

	  $this->saved = false;
  }

  function DelInput($flow, $g) {
	  $this->RmInput($flow, $g);
	  if ($g->src() > 0) {
		  $p = $flow->processors[$g->src()];
		  $p->RmOutput($flow, $g);
		  if (sizeof($p->input()) == 0 && sizeof($p->output()) == 0) {
			  if ($p->id()) $GLOBALS['core']->sqlDelete($flow->flowTable, 'id', $p->id());
			  unset($flow->processors[$p->innerid()]);
		  }
	  }
  }
  function DelOutput($flow, $g) {
	  $this->RmOutput($flow, $g);
	  if ($g->dest() > 0) {
		  $p = $flow->processors[$g->dest()];
		  $p->RmInput($flow, $g);
		  if (sizeof($p->input()) == 0 && sizeof($p->output()) == 0) {
			  if ($p->id()) $GLOBALS['core']->sqlDelete($flow->flowTable, 'id', $p->id());
			  unset($flow->processors[$p->innerid()]);
		  }
	  }
  }

  function SetRefflow($val) { $this->_refflow = $val; $this->saved = false; return $val; }
  function SetProcessType($val) { if ($this->_processType != $val) { $this->_processType = $val; $this->saved = false; } return $val; }
  function SetTaskname($val) { if ($this->_taskname != $val) { $this->_taskname = $val; $this->saved = false; } return $val; }
  function SetName2($val) { if ($this->_name2 != $val) { $this->_name2 = $val; $this->saved = false; } return $val; }
  function SetRules($val) { $this->_rules = $val; $this->saved = false; return $val; }
  function AddRule($val) { $this->_rules[] = $val; $this->saved = false; return $val; }
  function SetWorkinghourstart($val) { if ($this->_workinghourstart != $val) { $this->_workinghourstart = $val; $this->saved = false; } return $val; }
  function SetWorkinghourend($val) { if ($this->_workinghourend != $val) { $this->_workinghourend = $val; $this->saved = false; } return $val; }
}

?>