<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/product/class/product.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/flow.php');

require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_ResourceNode.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_GoodsNode.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_StopNode.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_TaskNode.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_ReturnNode.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_TruckNode.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_WharehouseNode.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_RouteNode.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_PurchaseNode.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_SaleNode.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_SumpupNode.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_ProcessorNode.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_CapacityNode.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_ManufactureNode.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_PackerNode.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_TransportNode.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_StockNode.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_WasteNode.php');

class PHPWS_Node {
  public static $type2class = array(32=>'PHPWS_GoodsNode', 1024=>'PHPWS_RouteNode', 2048=>'PHPWS_TruckNode', 4096=>'PHPWS_StopNode', 1=>'PHPWS_ManufactureNode', 2=>'PHPWS_TransportNode', 4=>'PHPWS_ProcessorNode', 8=>'PHPWS_SaleNode', 16=>'PHPWS_WholesaleNode', 64=>'PHPWS_PackerNode', 128=>'PHPWS_SumpupNode', 256=>'PHPWS_DividerNode', 512=>'PHPWS_StockNode', 8192=>'PHPWS_WasteNode', 16384=>'PHPWS_ConsumerNode', 32768=>'PHPWS_WharehouseNode', 131072=>'PHPWS_PurchaseNode', 262144=>'PHPWS_ReturnNode');

  var $flowTable = "mod_flow";
  protected  $_status = 0;				// 0x1 -- edit position flags
			// status rule: 4 sections for each type data (only when status is 1 or 2)
			// product (what): 0xF
			// start time: 0xF0
			// end time: 0xF00
			// place: 0xF000
			// quantity: 0xF0000
			// cost: 0xF00000
			// meaning of value: 0 - template, 1 - planning, 2 - set by user, 4 - executable, 8 - executing, 0xF - done, 0xE - cancel
  var  $flowid = 0;
  protected  $_id = 0;
  protected  $_org = 0;					// 0x2
  protected  $_province = NULL;			// 0x4
  protected  $_city = NULL;				// 0x4
  protected  $_connects = NULL;			// 0x8
  protected  $_place = 0;				// 0x4
  protected  $_start = NULL;			// 0x16
  protected  $_end = NULL;				// 0x16
  protected  $_totalcost = 0;			// 0x8192
  protected  $_duration = 1;			// 0x32
  protected  $_innerid = 1;
  protected  $_orderNum = NULL;
  protected  $_creator = NULL;
  protected  $_created = NULL;
  protected  $_extra = NULL;
  protected  $_srcode = 0;
  protected  $_barcode = 0;
  protected  $_rootOrg = 0;
  protected  $_pendingaddorgs = array();

  protected  $saved = false;

  function PHPWS_Node() {
  }

  function __construct() {
	  $this->SetOrg($_SESSION['OBJ_user']->org);
	  $this->_created = date("Y-m-d H:i:s");
	  $this->saved = false;
  }

  function workStatus() { return $this->_status & FLOW_STATUS_TASKMASK; }
  function financeStatus() { return $this->_status & FLOW_STATUS_MONEYMASK; }
  function statusNotPlan() { return $this->_status > 0; }
  function statusExecuted() { return ($this->_status & FLOW_STATUS_TASKMASK) == FLOW_EXECUTED; }
  function statusToBeExecuted() { return $this->_status > 0 && ($this->_status & FLOW_STATUS_TASKMASK) < FLOW_EXECUTED; }
  function statusWorkDone() { return ($this->_status  & FLOW_FULLWORK) == FLOW_COMPLETED; }
  function statusNothingToDo() { return ($this->_status & FLOW_STATUS_TASKMASK) == FLOW_EXECUTED || $this->_status < 0; }

  function dirty() { $this->saved = false; }

  function rootOrg() {
	  if ($this->_rootOrg) return $this->_rootOrg;
	  $g = $GLOBALS['core']->sqlSelect('mod_org', 'id', $this->_org);
	  $this->_rootOrg = $g[0]['rootOrg'];
	  return $this->_rootOrg;
  }

  function whatChanged($changes) {
	  static $changeWhat = array(4=>4, 16=>2, 32=>2, 8192=>6);
	  $res = array();
	  foreach ($changeWhat as $i=>$t) {
		  if (!($changes & $i)) continue;
		  $changes &= ~$i;
		  if (!$t) continue;
		  $res[$t] = $i;
	  }
	  return array_flip($res);
  }
  function loadData($f) {
	  $this->_status = $f['status'];
	  $this->_id = $f['id'];
	  $this->_org = $f['org'];
	  $this->_province = $f['province'];
	  $this->_city = $f['city'];
	  $this->_place = $f['place'];
	  $this->_start = $f['start'];
	  $this->_totalcost = $f['totalcost'];
	  $this->_end = $f['end'];
	  $this->_creator = $f['creator'];
	  $this->_created = $f['created'];
	  $this->_orderNum = $f['orderNum'];
	  $this->_extra = $f['extra']?unserialize($f['extra']) : NULL;
	  $this->_innerid = $f['innerid'];
	  $this->_connects = $f['connects']?unserialize($f['connects']) : array();
	  $this->_duration = $f['duration'];
	  $this->_srcode = $f['srcode'];
	  $this->_barcode = $f['barcode'];
  }
  function copy($f) {
	  $this->_status = $f->_status;
	  $this->_id = $f->_id;
	  $this->SetOrg($f->_org);
	  $this->_province = $f->_province;
	  $this->_city = $f->_city;
	  $this->_place = $f->_place;
	  $this->_start = $f->_start;
	  $this->_totalcost = $f->_totalcost;
	  $this->_end = $f->_end;
	  $this->_orderNum = $f->_orderNum;
	  $this->_extra = $f->_extra;
	  $this->_innerid = $f->_innerid;
	  $this->_created = $f->_created;
	  $this->_connects = $f->_connects;
	  $this->_duration = $f->_duration;
	  $this->saved = false;
	  $this->_srcode = $f->_srcode;
	  $this->_barcode = $f->_barcode;
  }

  function weakcopy($f) {
	  $this->_status = $f->_status;
	  $this->SetOrg($f->_org);
	  $this->_totalcost = $f->_totalcost;
	  $this->_extra = $f->_extra;
	  $this->saved = false;
  }

  function littlecopy($f) {
	  $this->_status = $f->_status;
	  $this->SetOrg($f->_org);
	  $this->_totalcost = $f->_totalcost;
	  $this->saved = false;
  }

  static function load($f) {  
//echo 'loading ' . $f['resourceType'] . '<pre>';
	  $cls = PHPWS_Node::$type2class[$f['resourceType']];
	  $c = new $cls();
	  $c->loadData($f);

	  $c->saved = true;
//print_r($c);
	  return $c;
  }

  static function newNode($resourceType) {
	  $cls = PHPWS_Node::$type2class[$resourceType];
	  $c = new $cls();
	  $c->setNodeType($resourceType);
	  $c->saved = false;
	  return $c;
  }

  function save($del = true) {
	  if ($this->saved) return;

	  $d = $this->saveData();
	  if ($del || !$this->_id) {
		  $this->_creator = $d['creator'] = $_SESSION["OBJ_user"]->user_id;
		  $d['created'] = date("Y-m-d H:i:s");
		  $this->_id = $GLOBALS['core']->sqlInsert($d, $this->flowTable, false, true);

		  if ($this->_pendingaddorgs) $this->commitOrgs();
	  }
	  elseif ($this->flowTable != 'mod_flow') {
		  $d['id'] = $this->_id;
		  $GLOBALS['core']->sqlReplace($d, $this->flowTable);
	  }
	  else $GLOBALS['core']->sqlUpdate($d, $this->flowTable, 'id', $this->_id);
	  $this->saved = true;
  }
  function saveData() {
	  $f = array();
	  $f['status'] = $this->_status;
	  $f['org'] = $this->_org;
	  $f['province'] = $this->_province;
	  $f['connects'] = $this->_connects;
	  $f['city'] = $this->_city;
	  $f['place'] = $this->_place?$this->_place : 0;
	  $f['start'] = $this->_start;
	  $f['totalcost'] = $this->_totalcost;
	  $f['end'] = $this->_end;
	  $f['orderNum'] = $this->_orderNum;
	  $f['extra'] = $this->_extra;
	  $f['innerid'] = $this->_innerid;
	  $f['flowid'] = $this->flowid;
	  $f['duration'] = $this->_duration?$this->_duration : 1;
	  $f['srcode'] = $this->_srcode;
	  $f['barcode'] = $this->_barcode;
	  return $f;
  }
  function setStatus($flow, $status) {
	  if ($this->_status == $status) return;
	  $flow->saved = false;
	  $this->_status = $status;
	  $this->propagateStatus($flow);
	  $this->saved = false;
  }
  function changeData() {
	  extract($_REQUEST);
	  extract($_POST);

	  $changes = 0;

	  if ($place && $this->_place != $place) {
		  $this->_place = $place;
		  $loc = locCache($place);
		  $this->_province = $loc['province'];
		  $this->_city = $loc['city'];
		  $changes |= 4;
		  if (($this->_status & 0xF) < 4) $this->_status = ($this->_status & ~0xF000) | 0x2000;
	  }
	  elseif (!$place && ($this->_province != $province || $this->_city != $city)) {
		  if ($province) $this->_province = $province;
		  if ($city) $this->_city = $city;
		  $changes |= 4;
		  if (($this->_status & 0xF) < 4) $this->_status = ($this->_status & ~0xF000) | 0x2000;
	  }

	  if ($start) {
		  $this->_start = $start;
		  $changes |= 16;
		  if (($this->_status & 0xF) < 4) $this->_status = ($this->_status & ~0xF0) | 0x20;
	  }
	  if ($end) {
		  $this->_end = $end;
//		  $changes |= 16;
		  if (($this->_status & 0xF) < 4) $this->_status = ($this->_status & ~0xF00) | 0x200;
	  }
	  if ($totalcost && $this->_totalcost + 0 != $totalcost + 0) {
		  $this->_totalcost = $totalcost;
		  $changes |= 8192;
		  if (($this->_status & 0xF) < 4) $this->_status = ($this->_status & ~0xF00) | 0x200;
	  }

	  if ($status && $this->_status != $status) {
		  $changes |= 1;
		  $this->_status = $status;
	  }
	  if ($changes) $this->saved = false;

	  return $changes;
  }
  function dataForm($show = 0xFFFFFFFF, $edit = 0xFFFFFFFF) {
	  $maychg = true;
	  if (in_array($this->_status, array(0, -1, 8))) $maychg = false;

	  if ($show & 8192) {
		  $s .= "<tr><td>总成本：</td><td>";
		  if ($maychg && ($edit & 8192)) $s .= PHPWS_Form::formTextField("totalcost", $this->_totalcost) . "元</td></tr>";
		  else $s .= $this->_totalcost . "元</td></tr>";
	  }

	  if ($show & 16) {
		  $s .= "<tr><td>就绪时间：</td><td>";
		  if ($maychg && ($edit & 16)) $s .= PHPWS_Form::formTextField("start", $this->_start);
		  else $s .= $this->_start;
		  $s .= "</td></tr>";
		  if ($maychg && ($edit & 16)) $s .= "<tr><td>结束时间：</td><td>" . PHPWS_Form::formTextField("end", $this->_end) . "</td></tr>";
		  elseif ($this->_end) $s .= "<tr><td>结束时间：</td><td>{$this->_end}</td></tr>";
	  }

	  if ($show & 4) {
		  $ps = $GLOBALS['core']->query("SELECT * FROM supply_mod_locations WHERE province='{$this->_province}'" . ($this->_city?" AND city='{$this->_city}'" : ''), false);
		  $places = array(0=>'');
		  while ($p = $ps->fetchRow()) $places[$p['id']] = $p['name'];

		  $cities = array(0=>'');
		  if ($this->_province) {
			  $ps = $GLOBALS['core']->query("SELECT DISTINCT city FROM supply_mod_locations WHERE province='{$this->_province}'", false);
			  while ($p = $ps->fetchRow()) $cities[] = $p['city'];
		  }

		  if ($this->_place) {
			  $pl = locCache($this->_place);
			  $pl = $pl['name'] . "<br>" ;
		  }

		  $s .= "<tr><td>地点：</td><td>" . $pl;
		  if ($maychg && ($edit & 4)) $s .= PHPWS_Form::formSelect("province", $GLOBALS['provinces'], $this->_province, true, false, "chgProvince(this, 'city', 'place');") . PHPWS_Form::formSelect("city", $cities, $this->_city, true, false, "chgCity(this, 'province', 'place');") . PHPWS_Form::formSelect("place", $places, $this->_place, false, true);
		  $s .= "</td></tr>";
	  }

	  if ($show & 1) {
		  $statuses = PHPWS_Flow::$flowstatus;
		  unset($statuses[0]);
		  if ($this->_status > 0)
			  $s .= "<tr><td>状态：</td><td>" . $statuses[$this->_status & 0xF] /* PHPWS_Form::formSelect("status", $statuses, $this->_status, false, true)*/ . "</td></tr>";
	  }

	  if ($this->_extra) {
		  foreach ($this->_extra as $n=>$v) {
			  $s .= "<tr><td>{$n}：</td><td><pre>" . print_r($v, true) . "</pre></td></tr>";
		  }
	  }

	  return $s;
  }
  function mergeResult($r1, $r2) {
	  echo "obsolete function";
	  if (is_object($r2))
		  return array('result'=>array_merge($r1['result'], $r2->result), 'effect'=>array_merge($r1['effect'], $r2->effect));
	  return array('result'=>array_merge($r1['result'], $r2['result']), 'effect'=>array_merge($r1['effect'], $r2['effect']));
  }

  function status() { return $this->_status; }
  function id() { return $this->_id; }
  function org() { return $this->_org; }
  function province() { return $this->_province; }
  function city() { return $this->_city; }
  function connects() { return $this->_connects; }
  function place() { return $this->_place; }
  function start() { return $this->_start; }
  function end() { return $this->_end; }
  function totalcost() { return $this->_totalcost / 100.0; }
  function duration() { return $this->_duration; }
  function innerid() { return $this->_innerid; }
  function orderNum() { return $this->_orderNum; }
  function creator() { return $this->_creator; }
  function created() { return $this->_created; }
  function & extra() { return $this->_extra; }
  function srcode($data = NULL) { return $this->GenSrcode($data); }
  function barcode() { return $this->_barcode; }

  function GenSrcode($data = NULL) {
	  if ($data) {
		  $srcode = $GLOBALS['core']->sqlSelect('srcodes', array('tablename'=>$this->flowTable, 'tablerow'=>$this->_id, 'data'=>$data));
		  if ($srcode) $srcode = $srcode[0];
	  }
	  else {
		  $srcode = $GLOBALS['core']->query("SELECT * FROM srcodes WHERE tablename='{$this->flowTable}' AND tablerow={$this->_id} AND data IS NULL", true);
		  $srcode = $srcode->fetchRow();
	  }

	  if (!$srcode) {
		  $srcodeid = $GLOBALS['core']->sqlInsert(array('tablename'=>$this->flowTable, 'tablerow'=>$this->_id, 'data'=>$data), 'srcodes', false, true);
		  $srcode = urlencode("module=accessories&MOD_op=onsrcode&for=srcodes&codeid=$srcodeid");
		  $GLOBALS['core']->sqlUpdate(array('srcode'=>$srcode), 'srcodes', 'id', $srcodeid);
	  }
	  else $srcode = $srcode['srcode'];

	  return $srcode;
  }
  function SetId($val) { if ($this->_id != $val) $this->saved = false; $this->_id = $val; return $val; }
  function SetOrg($val) { if ($this->_org != $val) $this->saved = false; $this->_org = $val; return $val; }
  function SetProvince($val) { if ($this->_province != $val) $this->saved = false; $this->_province = $val; return $val; }
  function SetCity($val) { if ($this->_city != $val) $this->saved = false; $this->_city = $val; return $val; }
  function SetConnects($val) { $this->_connects = $val; $this->saved = false; return $val; }
  function SetPlace($val) { if ($this->_place != $val) $this->saved = false; $this->_place = $val; return $val; }
  function SetStart($val) { if ($this->_start != $val) $this->saved = false; $this->_start = $val; return $val; }
  function SetEnd($val) { if ($this->_end != $val) $this->saved = false; $this->_end = $val; return $val; }
  function SetTotalcost($val) { $val = round($val * 100, 0); if ($this->_totalcost != $val) $this->saved = false; $this->_totalcost = $val; return $this->_totalcost / 100; }
  function IncTotalcost($val) { $val = round($val * 100, 0); $this->_totalcost += $val; $this->saved = false; return $this->_totalcost / 100; }
  function SetDuration($val) { if ($this->_duration != $val) $this->saved = false; $this->_duration = $val; return $val; }
  function SetInnerid($val) { if ($this->_innerid != $val) $this->saved = false; $this->_innerid = $val; return $val; }
  function SetOrderNum($val) { if ($this->_orderNum != $val) $this->saved = false; $this->_orderNum = $val; return $val; }
  function SetSrcode($val) { if ($this->_srcode != $val) { $this->_srcode = $val; $this->saved = false; } return $val; }
  function SetBarcode($val) { if ($this->_barcode != $val) { $this->_barcode = $val; $this->saved = false; } return $val; }
  function SetCreator($val) { if ($this->_creator != $val) $this->saved = false; $this->_creator = $val; return $val; }
  function ClearExtra() { $this->_extra = array(); $this->saved = false; }
  function SetExtra($val) {
	  foreach ($val as $n=>$v) {
		  if (!isset($this->_extra[$n]) || $this->_extra[$n] != $v) {
			  $this->saved = false;
			  $this->_extra[$n] = $v;
		  }
	  }
	  return $val;
  }
  function SetExtraVal($n, $v) {
	  if (!isset($this->_extra[$n]) || $this->_extra[$n] != $v) {
		  $this->_extra[$n] = $v;
		  $this->saved = false;
	  }
	  return $v;
  }
  function ExtraVal($n) {  return $this->_extra[$n];  }
  function ChgFullStatus($val) { if ($this->_status != $val) { $this->saved = false; $this->_status = $val; } return $val; }
  function ChgFinStatus($val) { if (($this->_status & FLOW_STATUS_MONEYMASK) != ($val & FLOW_STATUS_MONEYMASK)) { $this->saved = false; $this->_status = ($this->_status & ~FLOW_STATUS_MONEYMASK) | ($val & FLOW_STATUS_MONEYMASK); } return $val; }
  function ChgStatus($val) { if (($this->_status & FLOW_STATUS_TASKMASK) != ($val & FLOW_STATUS_TASKMASK)) { $this->saved = false; $this->_status = ($this->_status & ~FLOW_STATUS_TASKMASK) | ($val & FLOW_STATUS_TASKMASK); } return $val; }
}

?>