<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/nodes.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/flow.php');

class PHPWS_ResourceNode extends PHPWS_Node {
  var $_src = 0;				// 0x64	-- edit position flag
  var $_dest = 0;			// 0x64
  var $_multiple = 0;		// 0x128
  var $_resourceType = 0;
  var $_name = NULL;			// 0x256
  var $_name2 = NULL;		// 0x256
  var $_fromtasktype = 0;
  var $_totasktype = 0;

  public static $nameColorCode = array(0=>'blue', 1=>'blue', 2=>'orange', 4=>'green', 8=>'black', 10=>'red');

  function PHPWS_ResourceNode() {
  }

  function __construct() {
	  parent::__construct();
	  $this->_name = $GLOBALS['_NODE_NAMES'][$this->_resourceType];
  }

  function loadData($f) {
	  parent::loadData($f);
	  $this->_src = $f['fromtask'];
	  $this->_dest = $f['totask'];
	  $this->_multiple = $f['multiple'];
	  $this->_resourceType = $f['resourceType'];
	  $this->_name = $f['name'];
	  $this->_name2 = $f['name2'];
	  $this->_fromtasktype = $f['fromtasktype'];
	  $this->_totasktype = $f['totasktype'];
  }
  function copy($f) {
	  parent::copy($f);
	  $this->_src = $f->_src;
	  $this->_dest = $f->_dest;
	  $this->_multiple = $f->_multiple;
	  $this->_resourceType = $f->_resourceType;
	  $this->_name = $f->_name;
	  $this->_name2 = $f->_name2;
	  $this->_fromtasktype = $f->_fromtasktype;
	  $this->_totasktype = $f->_totasktype;
  }
  function weakcopy($f) {
	  parent::weakcopy($f);
	  $this->_multiple = $f->_multiple;
	  $this->_resourceType = $f->_resourceType;
	  $this->_name = $f->_name;
	  $this->_name2 = $f->_name2;
  }
  function saveData() {
	  $f = parent::saveData();
	  $f['fromtask'] = $this->_src;
	  $f['totask'] = $this->_dest;
	  $f['multiple'] = $this->_multiple;
	  $f['resourceType'] = $this->_resourceType;
	  $f['name'] = $this->_name;
	  $f['name2'] = $this->_name2;
	  if ($this->_fromtasktype) $f['fromtasktype'] = $this->_fromtasktype;
	  if ($this->_totasktype) $f['totasktype'] = $this->_totasktype;
	  return $f;
  }
  function setNodeType($resourceType) {
	  $this->_resourceType = $resourceType;
  }
  function option($name = 'name') {
	  switch ($name) {
		  case 'place': 
			  $tp = locCache($this->_place);
			  return $this->_name . "：" . $tp['name'];
		  case 'start': return $this->_name . "：" . $this->_start;
		  case 'end': return $this->_name . "：" . $this->_end;
		  case 'product':
			  return $this->_name . "：" . prodCache($this->_product);
		  case 'cost': return $this->_name . "：" . $this->_totalcost;
		  case 'status': return $this->_name . "：" . ($this->_status & 0xF);
		  case 'quantity': return $this->_name . "：" . round($this->_quantity, 3) . $this->_unit;
		  case 'org': return orgCache($this->_org);
		  case 'id': return $this->_name . "：" . $this->_id;
		  case 'name':
		  default:
			  return $this->_name; 
	  }
  }
  function propagateChanges(&$flow, $changes) {
	  $result = new PHPWS_Result();
	  if (!$changes) return $result;

	  if ($this->_src > 0) {
		  $result = $flow->processors[$this->_src]->propagateChanges($flow, $this, -1, $changes);
	  }
	  if ($this->_dest > 0) {
		  $b = $flow->processors[$this->_dest]->propagateChanges($flow, $this, 1, $changes);
	  }
	  if ($b) 
		  $result = $result->mergeResult($b);

	  return $result;
  }
  function propagateStatus($flow) {
	  $status = $this->_status;
	  if (($status & 0xF) == 4 && !$this->_start) $this->_start = date("Y-m-d H:i:s");
	  if ($status == -1 && !$this->_end) $this->_end = date("Y-m-d H:i:s");
	  if (($status == -1 || ($status & 0xF) >= 4) && $this->_dest > 0 && $flow->processors[$this->_dest]->_status > 0 && ($flow->processors[$this->_dest]->status() & 0xF) < 4) {
		  if ($flow->processors[$this->_dest]->input()) foreach ($flow->processors[$this->_dest]->_input as $i) {
			  if ($flow->goods[$i]->_status > 0) $status = min($flow->goods[$i]->_status & 0xF, $status);
			  elseif ($flow->goods[$i]->_status == -2) $status = -2;
		  }
		  if ($status > ($flow->processors[$this->_dest]->_status & 0xF)) $flow->processors[$this->_dest]->setStatus($flow, $status);
	  }

	  if ($status > 4 || $status < 0) return;

	  $flow->saveflow(false);

	  if ($this->_src < 0 || $this->_dest < 0) {
		  $sid = $this->_src < 0?$this->_src : $this->_dest;
		  $src = $GLOBALS['core']->sqlSelect($this->flowTable, 'id', -$sid);
		  if ($src[0]['status'] != $this->_status) {
			  $f = new PHPWS_Flow();
			  $f->loadflow($src[0]['flowid']);
			  foreach ($f->goods as $i=>$t) {
				  if ($t->_id == -$sid) $g = $t;
			  }
			  $g->_status = $this->_status;
			  $g->propagateStatus($f);
			  $f->saved = false;
			  $f->saveflow(false);
		  }
	  }

	  $ps = array();
	  if ($this->_src > 0 && $flow->processors[$this->_src]->_connects) {
		  $ps[] = $flow->processors[$this->_src];
	  }
	  if ($this->_dest > 0 && $flow->processors[$this->_dest]->_connects) {
		  $ps[] = $flow->processors[$this->_dest];
	  }
	  if ($ps) foreach ($ps as $p) {
		  $g = NULL;
		  $f = new PHPWS_Flow();
		  $one = '1'; $two = '2';
		  if ($p->_connects['flow1'] != $flow->id) {
			  $one = '2'; $two = '1';
		  }
		  $f->loadflow($p->_connects['flow' . $two]);
		  if ($p->_connects['in' . $one])
			  foreach ($p->_connects['in' . $one] as $i=>$in1) {
					if ($flow->goods[$in1]->name() == $this->_name) {
						$g = $f->goods[$p->_connects['in' . $two][$i]];
					}
			  }
		  if ($p->_connects['out' . $one])
			  foreach ($p->_connects['out' . $one] as $i=>$in1) {
					if ($flow->goods[$in1]->name() == $this->_name) {
						$g = $f->goods[$p->_connects['out' . $two][$i]];
					}
			  }
		  if ($g && $g->status() != $this->_status) {
			  $g->_status = $this->_status;
			  $g->propagateStatus($f);
			  $f->saved = false;
			  $f->saveflow(false);
		  }
	  }
  }
  function dataForm($show = 0xFFFFFFFF, $edit = 0xFFFFFFFF) {
	  $s = parent::dataForm($show, $edit);
	  return $s;
  }
  function ccname() {
	  // status color coded name: black - 0/1, 2 - orange, 4 - blue, 8 - green, red - 10 (only in tracing mode)
	  return "<span style='color:" . PHPWS_ResourceNode::$nameColorCode[$this->_status & 0xF] . "'>{$this->_name}</span>";
  }
  function remove(&$flow) {
	  if ($this->_src > 0)
		  $flow->processors[$this->_src]->_output = array_diff($flow->processors[$this->_src]->_output, array($this->_innerid));
	  if ($this->_dest > 0)
		  $flow->processors[$this->_dest]->_input = array_diff($flow->processors[$this->_dest]->_input, array($this->_innerid));
	  unset($flow->goods[$this->_innerid]);
	  $GLOBALS['core']->sqlDelete($this->flowTable, 'id', $this->_id);
  }

  function src() { return $this->_src; }
  function dest() { return $this->_dest; }
  function multiple() { return $this->_multiple; }
  function resourceType() { return $this->_resourceType; }
  function name() { return $this->_name; }
  function name2() { return $this->_name2; }
  function fromtasktype() { return $this->_fromtasktype; }
  function totasktype() { return $this->_totasktype; }

  function SetSrc($val) { if ($this->_src != $val) { $this->_src = $val; $this->saved = false; } return $val; }
  function SetDest($val) { if ($this->_dest != $val) { $this->_dest = $val; $this->saved = false; } return $val; }
  function SetMultiple($val) { if ($this->_multiple != $val) { $this->_multiple = $val; $this->saved = false; } return $val; }
  function SetResourceType($val) { if ($this->_resourceType != $val) { $this->_resourceType = $val; $this->saved = false; } return $val; }
  function SetName($val) { if ($this->_name != $val) { $this->_name = $val; $this->saved = false; } return $val; }
  function SetName2($val) { if ($this->_name2 != $val) { $this->_name2 = $val; $this->saved = false; } return $val; }
  function SetFromtasktype($val) { if ($this->_fromtasktype != $val) { $this->_fromtasktype = $val; $this->saved = false; } return $val; }
  function SetTotasktype($val) { if ($this->_totasktype != $val) { $this->_totasktype = $val; $this->saved = false; } return $val; }
}

?>