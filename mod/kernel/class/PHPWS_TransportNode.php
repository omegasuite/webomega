<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_TaskNode.php');

class PHPWS_TransportNode extends PHPWS_TaskNode {
  function PHPWS_TransportNode() {
  }

  function __construct() {
	  $this->_processType = TRANSPORT_NODE;
	  parent::__construct();
	  $this->__correspondMode = 0;	// 1:1
  }
  function SetOrg($org) {
	  $this->addOrg($org);
	  parent::SetOrg($org);
  }

  function receivefrom($flow, $s) {
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

  function shipped($flow, $param) {
	  $this->_status = 4;
	  $now = date("Y-m-d H:i:s");
	  $flow->processors[$param['gid']]->_extra['shippingPlans'][$this->_innerid]['shippingdate'] = $this->_start = $now;
	  foreach ($this->_input as $i) 
		  if ($flow->goods[$i]->_resourceType == 32) {
				if (!$flow->goods[$i]->_start) $flow->goods[$i]->_start = $now;
				$flow->goods[$i]->_end = $now;
				$flow->goods[$i]->_status = 8;
		  }
	  $flow->saved = false;
	  $flow->saveflow(false);
  }

  function runtime($flow) {
	  if ($this->_duration) return $this->_duration;

	  if ($this->_place) $station = $this->_place;
	  else foreach ($this->_input as $t) {
		  if ($flow->goods[$t]->_resourceType == 32 && $flow->goods[$t]->_place) $station = $flow->goods[$t]->_place;
	  }
	  if (!$station) return 0;

	  $dest = NULL;

	  foreach ($this->_input as $t)
		  if ($flow->goods[$t]->_resourceType == 4096) $dest = $flow->goods[$t]->_place;
	  if (!$dest) return 0;

	  foreach ($this->_input as $t) {
		  if ($flow->goods[$t]->_resourceType != 1024) continue;
		  require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/liner.php');
		  return PHPWS_Liner::runtime($flow->goods[$t]->_product, $flow->goods[$t]->_category, $station, $dest);
	  }
	  return 0;
  }
  function earliestLine($flow, $start) {
	  if ($this->_start) {
		  if ($start < $this->_start) $start = $this->_start;
		  return $start;
	  }

	  if ($this->_place) $station = $this->_place;
	  else foreach ($this->_input as $t) {
		  if ($flow->goods[$t]->_resourceType == 32 && $flow->goods[$t]->_place) $station = $flow->goods[$t]->_place;
	  }
	  if (!$station) return $start;

	  foreach ($this->_input as $t) {
		  if ($flow->goods[$t]->_resourceType != 1024) continue;
		  require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/liner.php');
		  return PHPWS_Liner::earliestLine($flow->goods[$t]->_product, $flow->goods[$t]->_category, $start, $station);
	  }
	  return $start;
  }
  function latestLine($flow, $start) {
	  if ($this->_start) {
		  if ($start > $this->_start) $start = $this->_start;
		  return $start;
	  }

	  if ($this->_place) $station = $this->_place;
	  else foreach ($this->_input as $t) {
		  if ($flow->goods[$t]->_resourceType == 32 && $flow->goods[$t]->_place) $station = $flow->goods[$t]->_place;
	  }
	  if (!$station) return $start;

	  foreach ($this->_input as $t) {
		  if ($flow->goods[$t]->_resourceType != 1024) continue;
		  require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/liner.php');
		  return PHPWS_Liner::latestLine($flow->goods[$t]->_product, $flow->goods[$t]->_category, $start, $station);
	  }
	  return $start;
  }
  static function validate($goods, &$input, &$output, &$rules) {
	  $tc = array();
	  $mx = 0;
	  if (sizeof($input)) foreach ($input as $i=>$t) {
		  if (!$t) { unset($input[$i]); continue; }
		  if (!in_array($goods[$t]->_resourceType, array(32, 1024, 2048, 4096)))
			  return "<span style='color:red'>运输任务的输入只能" . GOODS . "，" . ROUTE . "，" . TRUCK . "，" . STOPS . "是4种。</span>";
		  if (!$tc[$goods[$t]->_resourceType]) $tc[$goods[$t]->_resourceType] = 1;
		  else $tc[$goods[$t]->_resourceType]++;
		  if ($goods[$t]->_resourceType != 32) $mx = max($mx, $tc[$goods[$t]->_resourceType]);
	  }
	  if ($mx > 1) return "<span style='color:red'>" . ROUTE . "，" . TRUCK . "，" . STOPS . "各不能超过一个。</span>";
	  if ($tc[4096] != 1) return "<span style='color:red'>运输任务必须有一个目的地。</span>";
	  if (sizeof($output)) foreach ($output as $t)
		  if ($goods[$t]->_resourceType != 32) return "<span style='color:red'>运输任务的输出必须是货物。</span>";
	  if (sizeof($output) != $tc[32])  return "<span style='color:red'>运输任务的输出货物必须与输入货物一致。</span>";

	  if (sizeof($output) == 0)  return "<span style='color:red'>运输任务不能没有货物。</span>";
	  if (sizeof($output) == 1) {
		  foreach ($rules as $i=>$r)
			  if ($r['type'] == 'product') unset($rules[$i]);
		  if (sizeof($input)) foreach ($input as $i) if ($goods[$i]->_resourceType == 32) $smt = $i;
		  if (sizeof($output)) foreach ($output as $i) $destproduct = $i;
		  $rules[] = array('type'=>'product', 'cause'=>$goods[$smt]->_name, 'target'=>$goods[$destproduct]->_name);
	  }
	  else {
		  $sh = array();
		  $dh = array();
		  $n = 0;
		  foreach ($rules as $i=>$r)
			  if ($r['type'] == 'product') {
					$sh[$r['cause']] = 1;
					$dh[$r['target']] = 1;
					$n++;
			  }
		  if ($n != sizeof($output) || sizeof($sh) != sizeof($output) || sizeof($dh) != sizeof($output))
				return "<span style='color:red'>没有完全对应输出输入货物。</span>";
	  }
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
			  if ($bestmatch->_src > 0)
				  $flow->processors[$bestmatch->_src]->forcedChange($flow, $origional, $change, $bestmatch);
		  }
		  return;
	  }
  }
}

?>