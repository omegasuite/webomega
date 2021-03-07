<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/attrib.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_TaskNode.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_TransportNode.php');

class PHPWS_StockNode extends PHPWS_TaskNode {
  protected $_wharehouse = 0;	// 0x1024	-- edit position flag
  protected $_name = NULL;
  protected $_type = 0;			// bit 0 - 库存表（按category统计） 1 - frozen 2 - 堆放 （无货架） 3 - 有库位 （一位一货） 4 -- 允许负库存
  protected $_ownerOrg = 0;
  protected $_rivalOrg = 0;		// 对方单位
  protected $_io = 0;			// 0 - checkin / 1 - checkout
  protected $_value = 0;		// value calculation: 0 - no change, 1 - by value
  protected $_xfer = 0;			// whether this is a stock transfer order

  var $uncommitted = array();	// temp data

  function PHPWS_StockNode() {
	  $this->uncommitted = array('fetch'=>array(), 'keys'=>array());
  }

  function __construct() {
	  $this->_processType = STOCK_NODE;
	  $this->_type = 4;
	  parent::__construct();
  }

  function whatChanged($changes) {
	  $changes &= ~1024;
	  if ($changes) return parent::whatChanged($changes);
	  return array();
  }
  function loadData($f) {
	  parent::loadData($f);
	  $this->_name = $f['name'];
	  $this->_type = $f['totask'];
	  $this->_value = $f['fromtask'];
	  $this->_rivalOrg = $f['fromtasktype'];
	  $this->_xfer = $f['totasktype'];
	  $this->_ownerOrg = $f['unit'];
	  $this->_wharehouse = $f['product'];
	  $this->_io = $f['category'];
	  $this->getWdata();
  }
  function copy($f) {
	  parent::copy($f);
	  $this->_type = $f->_type;
	  $this->_ownerOrg = $f->_ownerOrg;
	  $this->_rivalOrg = $f->_rivalOrg;
	  $this->_name = $f->_name;
	  $this->_wharehouse = $f->_wharehouse;
	  $this->_io = $f->_io;
	  $this->_value = $f->_value;
	  $this->_xfer = $f->_xfer;
  }
  function weakcopy($f) {
	  parent::weakcopy($f);
	  $this->_wharehouse = $f->_wharehouse;
	  $this->_io = $f->_io;
	  $this->_value = $f->_value;
	  $this->_rivalOrg = $f->_rivalOrg;
	  $this->_xfer = $f->_xfer;
  }
  function saveData() {
	  $f = parent::saveData();
	  $f['product'] = $this->_wharehouse;
	  $f['fromtask'] = $this->_value;
	  $f['fromtasktype'] = $this->_rivalOrg;
	  $f['category'] = $this->_io;
	  $f['name'] = $this->_name;
	  $f['totask'] = $this->_type;
	  $f['unit'] = $this->_ownerOrg;
	  $f['totasktype'] = $this->_xfer;
	  return $f;
  }
  function dataForm($show = 0xFFFFFFFF, $edit = 0xFFFFFFFF) {
	  $maychg = true;
	  $show &= ~65536; $edit &= ~65536;

	  if (in_array($this->_status, array(0, -1, -2, FLOW_EXECUTED))) $maychg = false;

	  if (!$this->_wharehouse) {
		  $c = $GLOBALS['core']->sqlSelect('mod_wharehouse', 'ownerOrg', $_SESSION['OBJ_user']->org);
		  $wharehouse = array(0=>'');
		  if ($c) foreach ($c as $cs) {
			  $wharehouse[$cs['id']] = $cs['name'];
		  }

		  if (sizeof($wharehouse) > 1)
			  $s .= "<tr><td>仓库：</td><td id=showgoods><div id=cats></div>" . PHPWS_Form::formSelect("wharehouse", $wharehouse, $this->_wharehouse, false, true) . "</td></tr>";
	  }
	  else {
		  $c = $GLOBALS['core']->sqlSelect('mod_wharehouse', 'id', $this->_wharehouse);
		  $s .= "<tr><td>仓库：</td><td>" . $c[0]['name'] . "</td></tr>";
	  }

	  $s .= parent::dataForm(0, $edit);

	  return $s;
  }
  function changeData() {
	  extract($_REQUEST);
	  extract($_POST);

	  $changes = 0;
	  if ($wharehouse && $this->_wharehouse != $wharehouse) {
		  $this->_wharehouse = $wharehouse;
		  $this->getWdata();
		  $changes |= 1024;
		  if ($this->_status != -1 && $this->_status != 4) {
			  $this->_status = 4;
			  $changes |= 1;
		  }
	  }
	  if ($changes) $this->saved = false;

	  $changes |= parent::changeData();
	  return $changes;
  }
  function getWdata() {
	  if ($this->_wharehouse) $c = $GLOBALS['core']->sqlSelect('mod_wharehouse', 'id', $this->_wharehouse);
	  if ($c) {
		  $this->_name = $c[0]['name'];
		  $this->_place = $c[0]['place'];
		  $this->_type = $c[0]['type'];
		  $this->_ownerOrg = $c[0]['ownerOrg'];
	  }
  }

  static function validate($goods, &$input, &$output, &$rules) {
	  $tc = array();
	  if (sizeof($input)) foreach ($input as $t) {
		  if (!in_array($goods[$t]->resourceType(), array(GOODS_NODE))) return "<span style='color:red'>仓储任务的输入只能是货物。</span>";
		  if (!$tc[$goods[$t]->resourceType()]) $tc[$goods[$t]->resourceType()] = 1;
		  else $tc[$goods[$t]->resourceType()]++;
	  }
	  $oc = array();
	  if (sizeof($output)) foreach ($output as $t) {
		  if (!in_array($goods[$t]->resourceType(), array(GOODS_NODE))) return "<span style='color:red'>仓储任务的输出只能是货物。</span>";
		  if (!$oc[$goods[$t]->resourceType()]) $oc[$goods[$t]->resourceType()] = 1;
		  else $oc[$goods[$t]->resourceType()]++;
	  }
//	  if ($tc[32768] != 1) return "<span style='color:red'>仓储任务有且只能有一个仓库。</span>";
  }

  function checkin($g, $quantity, $codes = NULL) {
	  $value = $g->totalcost() * $quantity / $g->quantity();

	  $cs = $GLOBALS['core']->sqlSelect('mod_stocked_goods', array('productCat'=>$g->product(), 'wharehouse'=>$this->_wharehouse, 'status'=>1));
	  foreach ($cs as $c) {
		  if (attribsEq(unserialize($c['attribs']), $g->attribs()) && $c['unit'] == $g->unit()) {
			  $id = $c['id'];
			  $GLOBALS['core']->sqlUpdate(array('quantity'=>$c['quantity'] + $quantity, 'totalcost'=>$c['totalcost'] + $g->totalcost()), 'mod_stocked_goods', 'id', $id);
			  break;
		  }
	  }

	  if (!$id)
		  $id = $GLOBALS['core']->sqlInsert(array('quantity'=>$quantity, 'productCat'=>$g->product(), 'attribs'=>$g->attribs(), 'goodtil'=>date("Y-m-d H:i:s", strtotime("+" . ($g->duration() + 0) . " days")), 'source'=>$g->id(), 'availtime'=>date('Y-m-d H:i:s'), 'owner'=>$g->org(), 'unit'=>$g->unit(), 'totalcost'=>$value, 'handler_uid'=>$_SESSION["OBJ_user"]->user_id, 'wharehouse'=>$this->_wharehouse, 'status'=>1), 'mod_stocked_goods', false, true);

	  if ($codes) foreach ($codes as $key) {
		  $k = $GLOBALS['core']->query("SELECT * FROM srcodes WHERE md5key='$key' AND tablename='mod_flow' AND tablerow=" . $g->id() . " AND tentative=0 ORDER BY id DESC LIMIT 0,1", true);
		  $k = $k->fetchRow();

		  if ($k) unset($k['id']);
		  else {
			  $s = "产品：" . ($g->ExtraVal('goods')?goodsCache($g->ExtraVal('goods')) : prodCache($g->product())) . "<br>";
			  if ($g->attribs()) {
				  $s .= "规格：" . attribName($g->attribs()) . "<br>";
			  }
			  $k = array('data'=>array('text'=>$s, 'goods'=>goodsCache($g->ExtraVal('goods')), 'product'=>$g->product(), 'attribs'=>$g->attribs(), 'time'=>date("Y-m-d H:i:s")), 'srcode'=>$s . $key, 'md5key'=>$key);
		  }

		  $k['tablename'] = 'mod_stocked_goods';
		  $k['tablerow'] = $id;
		  $GLOBALS['core']->sqlInsert($k, 'srcodes');
	  }
  }

  function availibility($product, $attribs) {
	  $avail = array();
	  $tc = $GLOBALS['core']->query("SELECT * FROM mod_stocked_goods WHERE productCat=" . $product . " AND wharehouse=" . $this->_wharehouse . " AND status>0", true);
	  $sid = array();
	  while ($t = $tc->fetchRow()) {
		  $t['attribs'] = unserialize($t['attribs']);
		  if (matchAttribs($attribs, $t['attribs'])) {
			  $matched = false;
			  $sid[] = $t['id'];
			  foreach ($avail as $v) {
				  if (attribsEq($v['attribs'], $t['attribs'])) {
					  if ($t['unit'] == $v['unit']) $v['quantity'] += $t['quantity'];
					  elseif (Attributes::$unitsConv[$t['unit']][$v['unit']])
						  $v['quantity'] += $t['quantity'] * Attributes::$unitsConv[$t['unit']][$v['unit']];
					  else continue;

					  $matched = true;
					  break;
				  }
			  }
			  if (!$matched) {
				  $avail[] = array('attribs'=>$t['attribs'], 'unit'=>$t['unit'], 'quantity'=>$t['quantity']);
			  }
		  }
	  }

	  if ($sid) {
		  $tc = $GLOBALS['core']->query("SELECT * FROM srcodes WHERE tablename='stocked_goods' AND tablerow IN (" . implode(",", $sid) . ") ORDER BY tablerow DESC", true);
		  $srcode = array();
		  while ($t = $tc->fetchRow()) {
			  if ($srcode[$t['tablerow']]) continue;
			  $srcode[$t['tablerow']] = unserialize($t['data'])['text'];
		  }
		  if ($srcode) $avail[] = implode("<br>", $srcode);
	  }

	  return $avail;
  }

  function avail($g, $quantity) {	// if we have enought in stock
	  $stdfmt = array('attribs'=>$g->attribs(), 'unit'=>$g->unit(), 'quantity'=>$quantity);
	  $tc = $GLOBALS['core']->query("SELECT * FROM mod_stocked_goods WHERE productCat=" . $g->product() . " AND wharehouse=" . $this->_wharehouse . " AND status>0", true);
	  while ($stdfmt['quantity'] > 0 && ($t = $tc->fetchRow())) {
		  if (matchAttribs($g->attribs(), unserialize($t['attribs']))) {
			  $s = lessby($stdfmt, $t);
			  if ($s !== NULL) $stdfmt['quantity'] = $s;
		  }
	  }
	  if ($stdfmt['quantity'] > 0) return false;

	  return true;
  }
//	  if (!$this->negStock() && !$override && !$this->avail($g, $quantity)) return "所取货物数量超过库存";

  function checkout($g, $codes, $override = false) {
	  $fetch = $this->uncommitted['fetch'];
	  $keys = $this->uncommitted['keys'];

//	  $this->uncommitted = array('fetch'=>array(), 'keys'=>array());

	  if ($codes) {
		  foreach ($codes as $key) {
			  $k = $GLOBALS['core']->query("SELECT * FROM srcodes WHERE md5key='$key' AND tablename='mod_stocked_goods' AND tentative=0 ORDER BY id DESC LIMIT 0,1", true);
			  $k = $k->fetchRow();
			  if (!$k) {
				  $k = $GLOBALS['core']->query("SELECT * FROM srcodes WHERE md5key='$key' AND tablename='mod_flow' AND tentative=1 ORDER BY id DESC LIMIT 0,1", true);
				  $k = $k->fetchRow();
				  if (!$k && !$override) return array('status'=>'WARN', 'msg'=>$key . " 二维码不存在，或不是仓库中的货物");
				  if (!$k) return array('status'=>'ERROR', 'msg'=>$key . " 二维码不存在");
				  $k['tablerow'] = 0;
			  }

			  $kd = unserialize($k['data']);
			  if ($kd['product'] != $g->product() && !$override)
				  return array('status'=>'WARN', 'msg'=>"产品不一致，继续取货？");

			  if ($fetch[$k['tablerow']]) $c = $fetch[$k['tablerow']];
			  elseif ($k['tablename'] == 'mod_stocked_goods') {
				  $c = $GLOBALS['core']->query("SELECT * FROM mod_stocked_goods WHERE id={$k['tablerow']} AND productCat=" . $g->product() . " AND wharehouse=" . $this->_wharehouse . " AND status>0", true);
				  
				  $c = $c->fetchRow();
				  if (!$c) return array('status'=>'ERROR', 'msg'=>"二维码不存在，或不是仓库中的货物");
				  $c['product'] = $c['productCat'];
				  if ($c['product'] != $g->product() && !$override)
					  return array('status'=>'WARN', 'msg'=>"产品不一致，继续取货？");

				  $c['attribs'] = unserialize($c['attribs']);

				  $fetch[$k['tablerow']] = $c;
			  }
			  else {
				  $cs = $GLOBALS['core']->query("SELECT * FROM mod_stocked_goods WHERE productCat=" . $g->product() . " AND wharehouse=" . $this->_wharehouse . " AND status>0", true);

				  $c = NULL;

				  while ($d = $cs->fetchRow()) {
					  if (!$d['quantity']) continue;
					  $c['product'] = $d['productCat'];
					  $d['attribs'] = unserialize($d['attribs']);

					  if (!matchAttribs($g->attribs(), $d['attribs'])) {
						  continue;
					  }
					  
					  if (!$this->negStock() && ismore($kd, $d) && !$override) continue;
					  if (lessby($d, $kd) === NULL) continue;

					  $c = $d;
					  $k['tablerow'] = $c['id'];
					  $fetch[$k['tablerow']] = $c;
					  break;
				  }
			  }

			  if (!$this->negStock() && ismore($kd, $c)) {
				  if (!$override) return array('status'=>'WARN', 'msg'=>"所取货物数量超过库存，继续取货？");
				  if (!$c['quantity']) {
					  $c['unit'] = $kd['unit'];
					  $c['attribs'] = $kd['attribs'];
					  $c['quantity'] = 0;
				  }
			  }

			  $c['quantity'] = lessby($c, $kd);
			  if (!$this->negStock()) {
				  if ($c['quantity'] === NULL)
					  return array('status'=>'ERROR', 'msg'=>formatAttrib($g->attribs(), "\n") . "货物规格和单位无法转换" . "\n" . print_r($c, true) . print_r($kd, true) . print_r($g, true));
			  }
			  elseif ($c['quantity'] === NULL) {
				  $kd['tablerow'] = $GLOBALS['core']->sqlInsert(array('wharehouse'=>$this->_wharehouse, 'productCat'=>$g->product(), 'attribs'=>$g->attribs(), 'unit'=>$g->unit(), 'quantity'=>0, 'status'=>1, 'totalcost'=>-$kd['quantity'] * $g->price(), 'owner'=>$g->org(), 'handler_uid'=>$_SESSION['OBJ_user']->user_id), 'mod_stocked_goods', false, true);
				  $kd['quantity'] = -$kd['quantity'];
				  $fetch[$k['tablerow']] = $kd;
			  }

			  $fetch[$k['tablerow']] = $c;
			  if ($k) $keys[$key] = $k;
		  }
	  }

	  $this->uncommitted['fetch'] = $fetch;
	  $this->uncommitted['keys'] = $keys;

	  return NULL;
  }

  function commitCheckout() {
	  if ($this->uncommitted['fetch']) foreach ($this->uncommitted['fetch'] as $id=>$c)
		  $GLOBALS['core']->sqlUpdate(array('quantity'=>$c['quantity']), 'mod_stocked_goods', 'id', $id);

	  if ($this->uncommitted['keys']) foreach ($this->uncommitted['keys'] as $key=>$k)
		  $GLOBALS['core']->sqlUpdate(array('tentative'=>0), 'srcodes', 'id', $k['id']);

	  $this->uncommitted = array('fetch'=>array(), 'keys'=>array());
  }

  function store($flow, $on = 0) {
	  extract($_REQUEST);
	  extract($_POST);

	  if ($on) $orderNum = $on;

	  $this->jshead();

	  $order = $GLOBALS['core']->sqlSelect("wharehouse_order", 'orderNum', $orderNum);

	  $prods = array(); $os = array(); 
	  if ($order) foreach ($order as $p) {
		  $pd = $flow->findgoods($p['productOrder']);
		  $prods[$pd->innerid()] = $p['quantity'];
		  $os[$pd->innerid()] = $p['status'];
		  $former = $p['user_id'];
		  $task = $p['task'];
		  $multitask = $p['multitask'];
		  if ($p['flowtrigger']) $flowtrigger = unserialize($p['flowtrigger']);
		  if (!$actor) $actor = $p['actor'];
	  }

	  $wh = $GLOBALS['core']->sqlSelect('mod_wharehouse', 'id', $this->_wharehouse);
	  $wh = $wh[0];
	  $wh['hierachy'] = $wh['hierachy']?unserialize($wh['hierachy']) : array();
	  $wh['shelfspace'] = $wh['shelfspace']?unserialize($wh['shelfspace']) : array();
	  $this->_name = $wh['name'];
	  $this->_place = $wh['place'];
	  $this->_type = $wh['type'];
	  $this->_ownerOrg = $wh['ownerOrg'];
//	  $heap = $wh['type'] & 4;
	  $flow->saved = false;

	  $allocations = array();
	  if ($alloc) foreach ($alloc as $shelf=>$g) {
		  if (!$allocations[$g]) $allocations[$g] = array();
		  $allocations[$g][] = $shelf;
	  }
	  if (!$this->_extra['savedTo']) $this->_extra['savedTo'] = array();

	  $task = $flow->findprocessor($task);
	  if ($xop == "确认") {
		  for ($i = 0; $i < 7; $i++) {
			  $file = uploadfile("receivingProof$i", "data/" . $task->org());
			  if ($file) {
				  if (!$task->_extra) $task->_extra = array('receivingProof'=>array());
				  $task->_extra['receivingProof'][] = $file;
				  $flow->saved = false;
			  }
		  }

		  foreach ($allocations as $gd=>$shelf) {
			  foreach ($flow->goods as $t)
				  if ($t->id == $gd) $g = $t;
				  $id = $GLOBALS['core']->sqlInsert(array('quantity'=>$prods[$g->innerid()], 'productCat'=>$g->product(), 'attribs'=>$g->attribs(), 'goodtil'=>date("Y-m-d H:i:s", strtotime("+" . ($g->duration() + 0) . " days")), 'availtime'=>date('Y-m-d H:i:s'), 'source'=>$g->id(), 'changelog'=>array(array('time'=>date('Y-m-d H:i:s'), 'act'=>'checkin', 'quantity'=>$prods[$g->innerid()])), 'owner'=>$g->org(), 'unit'=>$g->unit(), 'totalcost'=>$g->totalcost(), 'handler_uid'=>$_SESSION["OBJ_user"]->user_id, 'wharehouse'=>$this->_wharehouse, 'status'=>1, 'destination'=>'0:'), 'mod_stocked_goods', false, true);
				  $g->_extra['savedTo'][] = $id;
//			  }

//			  if (!$g->start()) {
				  $g->SetEnd(date('Y-m-d H:i:s'));
				  $flow->saved = false;
//			  }

			  foreach ($shelf as $sh) {
				  $GLOBALS['core']->sqlInsert(array('wharehouse'=>$this->_wharehouse, 'stocked_goods'=>$id, 'shelf'=>$sh), 'mod_shelf_goods');
			  }
		  }
		  $flow->saveflow(false);
	  }

	  $allgood = true;

	  if ($this->_input) {
		  $rs = "<div style='position:relative;width:100%;'><center><div style='position:absolute;'><img height=40 src=" . $org[0]['logo'] . "></div><h2>入 库 单</h2></center></div><br>单号：{$orderNum}<span style='float:right'>仓库：" . wharehouseCache($this->_wharehouse) . "</span>";

		  $rs .= "<table border=1 style='width:100%;'>";
		  $rs .= "<tr><th>产 品</th><th>规 格</th><th>计 划/完 成 数 量</th><th>质 量 验 收</th><th>已 入 库 数 量</th><th>数 量</th><th>批 次</th>" . (($this->_type & 4) == 0?"<th>库 位</th>" : '') . "<th>备 注</th></tr>";

		  $osa = array(0=>'', 1=>'已完成', -1=>'拒绝');

		  foreach ($this->_input as $i) {
			  $g = &$flow->goods[$i];
			  if ($prods && !$prods[$i]) continue;
			  $rs .= "<tr><td align=center>" . prodCache($g->product()) . "</td><td align=center>";
			  if ($g->attribs()) {
				  $glue = '';
				  foreach ($g->attribs() as $at=>$val) {
					  $rs .= $glue . $at . "：" . $val;
					  $glue = "<br>";
				  }
			  }
			  $rs .= "</td><td align=center>" . $g->quantity() . $g->unit() . "</td><td align=center>";
			  if (($g->status() & 0xF) > 4) $rs .= $osa[$os[$g->innerid()]];
			  else $rs .= ($os[$g->innerid()]? ($os[$g->innerid()] == 1?"合格" : '') : PHPWS_Form::formRadio("quality[" . $g->id() ."]", 0, $quality[$g->id()]) . "不合格 " . PHPWS_Form::formRadio("quality[" . $g->id() . "]", 1, $quality[$g->id()]) . "合格");
			  $rs .= "</td><td align=center>";

			  if ($xop == "确认" && $quality[$g->id()] === '0') {
				  $os[$g->innerid()] = -1;
				  $GLOBALS['core']->sqlUpdate(array('status'=>-1, 'doneDate'=>date("Y-m-d H:i:s"), 'actor'=>$_SESSION["OBJ_user"]->user_id), "wharehouse_order", array('orderNum'=>$orderNum, 'productOrder'=>$g->id()));
			  }

			  if ($xop == "确认" && $quality[$g->id()] && $quantity[$g->id()] && !$os[$g->innerid()]) {
				  $os[$g->innerid()] = 1;
//				  $g->_totalcost += $g->price() * $quantity[$g->_id] - $g->price() * $g->quantity();
				  $g->SetTotalcost($g->price() * $quantity[$g->id()] - $g->totalcost());
				  $g->SerQuantity($quantity[$g->id()]);

				  $GLOBALS['core']->sqlUpdate(array('status'=>1, 'doneDate'=>date("Y-m-d H:i:s"), 'actor'=>$_SESSION["OBJ_user"]->user_id, 'quantity'=>$g->quantity()), "wharehouse_order", array('orderNum'=>$orderNum, 'productOrder'=>$g->id()));

				  if ($wh['type'] & 4) {
					  $id = $GLOBALS['core']->sqlInsert(array('quantity'=>$g->quantity(), 'productCat'=>$g->product(), 'attribs'=>$g->attribs(), 'goodtil'=>date("Y-m-d H:i:s", strtotime("+" . ($g->duration() + 0) . " days")), 'source'=>$g->id(), 'availtime'=>date('Y-m-d H:i:s'), 'changelog'=>array(array('time'=>date('Y-m-d H:i:s'), 'act'=>'checkin', 'quantity'=>$quantity[$g->id()])), 'owner'=>$g->org(), 'unit'=>$g->unit(), 'totalcost'=>$g->totalcost(), 'handler_uid'=>$_SESSION["OBJ_user"]->user_id, 'wharehouse'=>$this->_wharehouse, 'status'=>1, 'destination'=>'0:'), 'mod_stocked_goods', false, true);
					  $g->_extra['savedTo'][] = $id;
					  $flow->saved = false;
				  }

				  if ($multitask == 0) {
					  $g->ChgStatus(8);
					  $g->SetEnd(date('Y-m-d H:i:s'));
					  $this->_end = date('Y-m-d H:i:s');
					  $this->_status = 8;
				  }

				  if ($g->src() > 0 && $flow->processors[$g->src()]->processType() == 2) {
					  $ready = true;
					  foreach ($flow->processors[$g->src()]->input() as $i) {
						  if ($flow->goods[$i]->resourceType() != 32) continue;
						  if (($flow->goods[$i]->status() & 0xF) < 8) $ready = false;
					  }
					  if ($ready) {
						  $flow->processors[$g->src()]->SetEnd(date("Y-m-d H:i:s"));
						  $flow->processors[$g->src()]->ChgStatus(8);
					  }
				  }
				  $flow->saved = false;
			  }

			  $allgood &= $os[$g->innerid()] == 1;

			  $isin = $GLOBALS['core']->query("SELECT sum(quantity) FROM supply_wharehouse_order WHERE orderType=0 AND productOrder=" . $g->id() . " AND status=1", false);
			  $isin = $isin->fetchRow();

			  $sum = $isin['sum(quantity)'];
			  $rs .= $sum . "</td><td align=center>";

			  $rs .= (($g->status() & 0xF) > 4?$prods[$i] : ($os[$g->innerid()]? ($os[$g->innerid()] == 1?$prods[$i] : '') : PHPWS_Form::formTextField("quantity[" . $g->id() . "]", $prods[$i], 5))) . $g->unit() . "</td>";

			  $rs .= "<td align=center>" . $g->id() . "</td>";
			  if (($wh['type'] & 4) == 0) {
				  $rs .= "<td align=center>";
				  $stored = $GLOBALS['core']->sqlSelect('mod_stocked_goods', array('owner'=>$g->org(), 'wharehouse'=>$this->_wharehouse, 'source'=>$g->id()));
				  $glue = '';
				  if ($stored) foreach ($stored as $str) {
					  $shelfs = $GLOBALS['core']->sqlSelect('mod_shelf_goods', array('wharehouse'=>$this->_wharehouse, 'stocked_goods'=>$str['id']));
					  if ($shelfs) foreach ($shelfs as $sf) {
						  $rs .= $glue . $sf['shelf']; $glue = "<br>";
					  }
				  }
				  $rs .= "</td>";
			  }
			  $rs .= "<td align=center>" . $g->_extra()['comments'] . "</td>";
			  $rs .= "</tr>";

			  if (($g->status() & 0xF) > 4) continue;
/*
			  if ($xop == "确认" && $multitask == 0) {
				  $g->_end = date('Y-m-d H:i:s');
				  $g->status(8;
			  }
*/

			  if (($wh['type'] & 4) == 0 && !$os[$g->innerid()]) {
//				  if (($g->status() & 0xF) != 8) continue;
				  $rs .= "<tr><td colspan=10>将产品入库到指定位置<hr>";
				  if ($GLOBALS['WXMODE']) $rs .= "<a href=# onclick='scanShelf2Store(" . $g->id() . ");'>扫货架二维码放货</a> 或指定位置放货：<hr>";
				  $rs .= "<pre id=storelocs>" . $this->whexpand($wh['hierachy'], $wh['shelfspace'], $allocations[$g->id()], $g->id()) . "</pre></td></tr>";
			  }

//			  $rs .= "<td>" . (($g->status() == -1 || ($g->status() & 0xF) > 4)?"已完成" : PHPWS_Form::formCheckBox("finish[" . $g->id() . "]", 1) . "计划完成") . "</td></tr>";
			  if (!$quality[$g->id()] || (isset($quantity[$g->id()]) && !$quantity[$g->id()]) || $os[$g->innerid()]) continue;

			  if (!$g->start()) {
				  $g->SetStart(date('Y-m-d H:i:s'));
				  $flow->saved = false;
			  }
		  }
		  if ($xop == "确认" && $multitask == 0 && $flowtrigger) trig($flowtrigger, $flow, $task->id);
		  $flow->saveflow(false);
	  }
	  if ($_SESSION["OBJ_user"]->checkUserPermission($module, "wharehouse")) {
		  if (!$print) {
			  if ($allgood) {
				$rs .= "<tr><td colspan=10 align=center><a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=$MOD_op&op=$op&orderNum=$orderNum&id=$id&print=1>打印</a></td></tr>";
			  }
			  else {
				  $rs .= "<tr><td colspan=10 align=center><table><tr><th>上传收货单：</th><td>" . PHPWS_Form::formFile("receivingProof0") . "</td>";
				  $rs .= "<th>上传收货证据1：</th><td>" . PHPWS_Form::formFile("receivingProof1") . "</td>";
				  $rs .= "<th>上传收货证据2：</th><td>" . PHPWS_Form::formFile("receivingProof2") . "</td></tr><tr>";
				  $rs .= "<th>上传收货证据3：</th><td>" . PHPWS_Form::formFile("receivingProof3") . "</td>";
				  $rs .= "<th>上传收货证据4：</th><td>" . PHPWS_Form::formFile("receivingProof4") . "</td>";
				  $rs .= "<th>上传收货证据5：</th><td>" . PHPWS_Form::formFile("receivingProof5") . "</td></tr><tr>";
				  $rs .= "<th>上传收货证据6：</th><td>" . PHPWS_Form::formFile("receivingProof6") . "<//td></tr></table></td></tr>";
				  $rs .= "<tr><td colspan=10 align=center>" . PHPWS_Form::formSubmit("确认", 'xop') . " | <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=$MOD_op&op=$op&orderNum=$orderNum&id=$id&print=1>打印</a></td></tr>";
			  }
		  }
		  if ($print || $allgood) {
			  if ($task->_extra['receiving'] || $task->_extra['receivingProof']) {
				  $rs .= "<tr><td colspan=10 align=center><table width=100%>";
				  if ($task->_extra['receiving']) foreach ($task->_extra['receiving'] as $receivingslip=>$rcv) {
					  $rs .= "<tr><td>验收单号：</td><td>$receivingslip</td><td>客户签收：</td><td>{$rcv['rcvsigner']}</td><td>验收日期：</td><td>{$rcv['receivingdate']}</td></tr>";
				  }

				  if ($task->_extra['receivingProof']) {
					  $i = 0;
					  foreach ($task->_extra['receivingProof'] as $file) {
						  if (($i++ % 3) == 0) $rs .= "<tr>";
						  $rs .= "<td width=33% colspan=2><img width=100% src=./$file></td>";
						  if (($i % 3) == 0) $rs .= "</tr>";
					  }
					  if (($i % 3) != 0) $rs .= "</tr>";
				  }
				  $rs .= "</table></td></tr>";
			  }
		  }
		  $rs .= "</table>";

		  $u = new PHPWS_User($former);
		  if ($actor) $v = new PHPWS_User($actor);

		  $rs .= "制单：" . ($u->realname?$u->realname : $u->username) . " 仓管：" . ($v?($v->realname?$v->realname : $v->username) : '');
		  $rs .= PHPWS_Form::formHidden("module", $module) . PHPWS_Form::formHidden("MOD_op", $MOD_op) . PHPWS_Form::formHidden("op", $op) . PHPWS_Form::formHidden("orderNum", $orderNum) . PHPWS_Form::formHidden("id", $id);
		  if ($print) exit($rs);

		  $rs = PHPWS_Form::makeForm('addProduct', $GLOBALS['SCRIPT'], array($rs), "post", FALSE, TRUE);
	  }
	  else $rs .= "</table>";
	  return $rs;
  }

  function fetch($flow, $orderNum = 0) {
	  extract($_REQUEST);
	  extract($_POST);

	  $this->jshead();
	  $order = $GLOBALS['core']->sqlSelect("wharehouse_order", 'orderNum', $orderNum);
	  $prods = array(); $os = array(); 
	  if ($order) foreach ($order as $p) {
		  $pd = $flow->findgoods($p['productOrder']);
		  $prods[$pd->innerid()] = $p['quantity'];
		  $os[$pd->innerid()] = $p['status'];
		  $former = $p['user_id'];
		  $task = $p['task'];
		  $multitask = $p['multitask'];
		  if ($p['flowtrigger']) $flowtrigger = unserialize($p['flowtrigger']);
		  if (!$actor) $actor = $p['actor'];
		  if (!$receiver) $receiver = $p['receiver'];
	  }

	  $wh = $GLOBALS['core']->sqlSelect('mod_wharehouse', 'id', $this->_wharehouse);
	  $wh = $wh[0];
	  $wh['hierachy'] = $wh['hierachy']?unserialize($wh['hierachy']) : array();
	  $wh['shelfspace'] = $wh['shelfspace']?unserialize($wh['shelfspace']) : array();
	  $this->_name = $wh['name'];
	  $this->_place = $wh['place'];
	  $this->_type = $wh['type'];
	  $this->_ownerOrg = $wh['ownerOrg'];
	  $flow->saved = false;

	  if (!$this->_extra['fetchFrom']) $this->_extra['fetchFrom'] = array();

	  $shipping = NULL; $d = & $this;
	  while ($d && $d->processType() != TRANSPORT_NODE)
		  if ($d->_output && $flow->goods[$d->_output[0]]->dest() > 0)
				$d = & $flow->processors[$flow->goods[$d->_output[0]]->dest()];
		  else unset($d);
	  if ($d) $shipping = $d;

	  $allocations = array();
	  if ($alloc) foreach ($alloc as $shelf=>$g) {
		  if (!$allocations[$g]) $allocations[$g] = array();
		  $allocations[$g][] = $shelf;
	  }

	  $task = $flow->findprocessor($task);
	  $touched = array();
	  if ($xop == "确认") {
		  for ($i = 0; $i < 7; $i++) {
			  $file = uploadfile("shippingProof$i", "data/" . $task->org());
			  if ($file) {
				  if (!$task->_extra) $task->_extra = array('shippingProof'=>array());
				  $task->_extra['shippingProof'][] = $file;
				  $flow->saved = false;
			  }
		  }

		  foreach ($allocations as $gd=>$shelf) {
			  foreach ($flow->goods as $t)
				  if ($t->id == $gd) $g = $t;
			  $need = $prods[$g->innerid()];
			  if (($this->_type & 4) == 0) {
				  $shs = $GLOBALS['core']->query("SELECT * FROM mod_shelf_goods WHERE id IN (" . implode(",", $shelf) . ")", true);
				  $stk = array(); $del = array();
				  while ($p = $shs->fetchRow()) {
					  if (!in_array($p['stocked_goods'], $stk)) {
						  $stk[] = $p['stocked_goods'];
						  $del[$p['stocked_goods']] = array();
					  }
					  if ($partial[$p['id']]) continue;
					  $del[$p['stocked_goods']][] = $p['id'];
				  }
			  }
			  else $stk = $shelf;

			  $kk = array();
			  $sum = 0;
			  if ($stk) {
				  $ks = $GLOBALS['core']->query("SELECT * FROM mod_stocked_goods WHERE id IN (" . implode(",", $stk) . ")", true);
				  if ($ks) while ($k = $ks->fetchRow()) {
					  if (!$qty[$k['id']]) continue;
					  $k['changelog'] = unserialize($k['changelog']);
					  $kk[] = $k;
					  if ($k['quantity'] * Attributes::$unitsConv[$k['unit']][$g->unit()] * 1.001 < $qty[$k['id']])
						  return "所取的数量（{$qty[$k['id']]}）大于库存量（{$k['quantity']}）。";
					  $sum += $qty[$k['id']];
				  }
			  }

			  if ($sum && abs($sum - $need) > $need * 0.05) return "所取的总量与需求偏差大于5%。";
			  if ($sum) $prods[$g->innerid()] = $sum;
			  $os[$g->innerid()] = 1;
			  $actor = $_SESSION['OBJ_user']->user_id;

			  if ($sum) {
				  $GLOBALS['core']->query("UPDATE wharehouse_order SET quantity=$sum, actor={$_SESSION['OBJ_user']->user_id}, status=1, doneDate='" . date("Y-m-d H:i:s") . "', receiver='$receiver' WHERE orderNum=$orderNum AND productOrder=" . $g->id() . " AND orderType=1", true);
			  }

//			  if (!$g->start()) {
				  $g->SetStart(date('Y-m-d H:i:s'));
				  $flow->saved = false;
//			  }

			  if ($stk) foreach ($kk as $k) {	// mod_stocked_goods
				  $touched[$k['id']] = 1;
				  $pct = $qty[$k['id']] * Attributes::$unitsConv[$g->unit()][$k['unit']] / $k['quantity'];
				  $k['changelog'][] = array('time'=>$g->start(), 'act'=>'checkout', 'quantity'=>$qty[$k['id']] * Attributes::$unitsConv[$g->unit()][$k['unit']], 'destid'=>$g->id());
//echo "{$k['quantity']} < {$qty[$k['id']]} * " . (Attributes::$unitsConv[$g->unit()][$k['unit']] * 0.001);
				  if ($k['quantity'] - $qty[$k['id']] * Attributes::$unitsConv[$g->unit()][$k['unit']] < $qty[$k['id']] * Attributes::$unitsConv[$g->unit()][$k['unit']] * 0.001) {
					  $qty[$k['id']] -= $k['quantity'] * Attributes::$unitsConv[$k['unit']][$g->unit()];
					  $GLOBALS['core']->query("UPDATE mod_stocked_goods SET quantity=0, gonetime='" . $g->start() . "', changelog='" . serialize($k['changelog']) . "', totalcost=0, status=-1,destination=CONCAT(destination,'" . $g->id() . ":') WHERE id={$k['id']}", true);
					  $g->_extra['fetchFrom'][] = $k['id'];
					  $flow->saved = false;
					  if (($this->_type & 4) == 0) {
						  $GLOBALS['core']->query("DELETE FROM mod_shelf_goods WHERE stocked_goods=" . $k['id'], true);
					  }
				  }
				  else {
					  $GLOBALS['core']->query("UPDATE mod_stocked_goods SET quantity=quantity - " . ($qty[$k['id']] * Attributes::$unitsConv[$g->unit()][$k['unit']]) . ", changelog='" . serialize($k['changelog']) . "', status=2, totalcost=totalcost * (1-$pct),destination=CONCAT(destination,'" . $g->id() . ":') WHERE id={$k['id']}", true);
					  $g->_extra['fetchFrom'][] = $k['id'];
					  $flow->saved = false;
					  $qty[$k['id']] = 0;
//					  $GLOBALS['core']->query("UPDATE mod_stocked_goods SET quantity=quantity - " . ($sum * Attributes::$unitsConv[$g->unit()][$k['unit']]) . ", changelog='" . serialize($k['changelog']) . "', status=2, totalcost=totalcost * (1-$pct) WHERE id={$k['id']}", true);
//					  $qty[$k['id']] -= $sum;
					  if (($this->_type & 4) == 0 && $del[$k['id']]) {
						  $GLOBALS['core']->query("DELETE FROM mod_shelf_goods WHERE id IN (" . implode(",", $del[$k['id']]) . ")", true);
					  }
				  }
			  }
		  }

		  foreach ($qty as $kid=>$qt) {
			  if ($touched[$kid]) continue;
			  if (!$qt) continue;
			  $old = $GLOBALS['core']->sqlSelect('mod_stocked_goods', 'id', $kid);
			  unset($g);
			  foreach ($os as $m=>$stu) {
				  if (!$stu) continue;
				  $t = &$flow->goods[$m];
				  if ($t->product() == $old[0]['productCat']) {
					  $g = $t;
				  }
			  }
			  if (!$g) continue;

//			  if (!$g->start()) {
				  $g->SetStart(date('Y-m-d H:i:s'));
				  $g->ChgStatus(4);
				  if ($g->dest() > 0 && $flow->processors[$g->dest()]->processType() == 2) {
					  $ready = true;
					  foreach ($flow->processors[$g->dest()]->input() as $i) {
						  if ($flow->goods[$i]->resourceType() != 32) continue;
						  if (($flow->goods[$i]->status() & 0xF) < 4) $ready = false;
					  }
					  if ($ready) {
						  $flow->processors[$g->dest()]->ChgStatus(4);
						  foreach ($flow->processors[$g->dest()]->input() as $i) {
							  if ($flow->goods[$i]->resourceType() == 32) {
								  $flow->goods[$i]->ChgStatus(8);
								  $flow->goods[$i]->SetEnd(date('Y-m-d H:i:s'));
							  }
						  }
					  }
				  }
				  $flow->saved = false;
//			  }


			  $pct = $qt * Attributes::$unitsConv[$g->unit()][$old[0]['unit']] / $old[0]['quantity'];
			  $qt = min($qt, $old[0]['quantity'] * Attributes::$unitsConv[$old[0]['unit']][$g->unit()]);
			  $prods[$g->innerid()] = $qt;

			  $GLOBALS['core']->query("UPDATE wharehouse_order SET quantity=$qt, actor={$_SESSION['OBJ_user']->user_id}, status=1, doneDate='" . date("Y-m-d H:i:s") . "', receiver='$receiver' WHERE orderNum=$orderNum AND productOrder=" . $g->id() . " AND orderType=1", true);

			  $changelog = array('time'=>$g->start(), 'act'=>'checkout', 'quantity'=>$qty[$kid] * Attributes::$unitsConv[$g->unit()][$old[0]['unit']], 'destid'=>$g->id());
			  if ($old[0]['quantity'] <= $qt * Attributes::$unitsConv[$g->unit()][$old[0]['unit']]) {
				  $GLOBALS['core']->query("UPDATE mod_stocked_goods SET quantity=0, gonetime='" . date("Y-m-d H:i:s") . "', changelog='" . serialize($changelog) . "', totalcost=0, status=-1,destination=CONCAT(destination,'" . $g->id() . ":') WHERE id=$kid", true);
				  $g->_extra['fetchFrom'][] = $kid;
				  $flow->saved = false;
			  }
			  else {
				  $GLOBALS['core']->query("UPDATE mod_stocked_goods SET quantity=quantity - " . ($qt * Attributes::$unitsConv[$g->unit()][$old[0]['unit']]) . ", changelog='" . serialize($changelog) . "', status=2, totalcost=totalcost * (1-$pct),destination=CONCAT(destination,'" . $g->id() . ":') WHERE id=$kid", true);
				  $g->_extra['fetchFrom'][] = $kid;
				  $flow->saved = false;
			  }
		  }

		  if ($packaging) {
			  foreach ($packaging as $pid=>$pkg) {
				  $po = & $flow->processors[$pid];
				  $po->_extra['packaging'] = $pkg;
			  }
			  $flow->saved = false;
		  }
		  if ($packages) {
			  foreach ($packages as $pid=>$pkg) {
				  $po = & $flow->processors[$pid];
				  $po->_extra['packages'] = $pkg;
			  }
			  $flow->saved = false;
		  }
		  unset($po);

		  if ($multitask == 0 && $flowtrigger) trig($flowtrigger, $flow, $task->id());
		  $flow->saveflow(false);
	  }
	  unset($d);

	  $c = $GLOBALS['core']->sqlSelect('mod_wharehouse', 'ownerOrg', $_SESSION['OBJ_user']->org);
	  $wharehouse = array(0=>'');
	  if ($c) foreach ($c as $cs) $wharehouse[$cs['id']] = $cs['name'];

	  if ($this->_output) {
		  if ($shipping) {
			  foreach ($flow->processors as $foo) {
				  if ($foo->processType() == SALE_NODE) $po = $foo;
			  }
		  }

		  $srs = $rs = "<div style='position:relative;width:100%;'><center><div style='position:absolute;'><img height=40 src=" . $org[0]['logo'] . "></div><h2>";

		  if ($shipping)
			  $srs .= "供 货 凭 证</h2><div style='position:absolute;right:0px;bottom:0;'>订单编号：{$po->_orderNum}</div></center></div>";
		  else
			  $srs .= "出 库 单</h2><div style='position:absolute;right:0;bottom:0;'>单号：{$orderNum}</div></center></div>";

		  $rs .= "备 货 单</h2><div style='position:absolute;right:0;bottom:0;'>单号：{$orderNum}</div></center></div>";

		  $srs .= "<table border=1 style='width:100%;'>";
		  $rs .= "<table border=1 style='width:100%;'>";

		  if ($shipping) {
			  $srs .= "<tr><th>收货单位</th><th colspan=2>" . orgCache($po->extra()['customer']) . "</th><th>收货人信息</th><th colspan=3>" . $po->extra()['receipient'] . "</th><th colspan=2>" . $po->extra()['phone'] . "</th><th>订单日期</th><th>" . dayofdate($po->created()) . "</th></tr>";
			  $srs .= "<tr><th>收货地址</th><th colspan=3>" . $po->extra()['address'] . "</th><th colspan=3>出库单号</th><th colspan=2>" . $orderNum . "</th><th>到货期限</th><th>" . dayofdate($po->start()) . "</th></tr>";
			  $srs .= "<tr><th>物流公司</th><th>" . orgCache($shipping->extra()['transporter']) . "</th><th>物流单号</th><th colspan=3>" . $shipping->orderNum() . "</th><th colspan=2>运  费</th><th>" . $shipping->totalcost() . "</th><th>发货仓库</th><th>" . $wharehouse[$this->_wharehouse] . "</th></tr>";
			  $srs .= "<tr><th>商品编码</th><th colspan=2>产品名称</th><th>规 格</th><th>装箱<br>规格</th><th>装箱<br>数量</th><th>单位</th><th>供 价</th><th>数 量</th><th>金 额</th><th>生产日期</th></tr>";
		  }
		  else $srs .= "<tr><th>产 品</th><th>规 格</th><th>单位</th><th>数 量</th><th>备 注</th></tr>";

		  $rs .= "<tr><th>产 品</th><th>规 格</th><th>库 存 量</th><th>待 取<br>总 量</th><th>入 库</th><th>保 质 期</th><th>存 量</th><th>取 货</th></tr>"; // <th>取 货 先 后</th>
		  $osa = array(0=>'', 1=>'已完成', -1=>'拒绝');

		  $asum = 0; $bsum = 0; $totalcost = 0; $bunit = NULL; $insuff = false; $allgood = true;
		  foreach ($this->_output as $i) {
			  if ($prods && !$prods[$i]) continue;
			  $g = $flow->goods[$i];

			  if (!$g->quantity() || !$g->unit()) continue;
			  $action = 0;
			  $avail = $GLOBALS['core']->query("SELECT * FROM mod_stocked_goods WHERE wharehouse={$this->_wharehouse} AND productCat=" . /*($this->_type & 1?$g->_category :*/ $g->product() /*)*/ . " AND status>=0 AND quantity>0 AND availtime<='" . date('Y-m-d H:i:s') . "' ORDER BY " . ($priority[$g->id()] == 2?"goodtil" : "id") . " ASC", true);

			  $shelf = array();
			  $sum = 0; $s0 = ''; $s1 = ''; $rm = $prods[$i]; $nt = 0; $sh = ''; $st = '';
			  $s = &$s0; $pdate = NULL;
			  while ($p = $avail->fetchRow()) {
				  if ($g->attribs() && !matchAttribs($g->attribs(), $p['attribs'])) continue;
				  if (!Attributes::$unitsConv[$p['unit']][$g->unit()]) continue;
				  if (($this->_type & 4) == 0)
					  $shelf = $GLOBALS['core']->sqlSelect('mod_shelf_goods', array('stocked_goods'=>$p['id']));
				  $pqr = $p['quantity'] * Attributes::$unitsConv[$p['unit']][$g->unit()];
				  $s .= $sh . "<td>" . dayofdate($p['availtime']) . '</td><td>' . ($p['goodtil'] > "1970-01-01 08:00:01"?dayofdate($p['goodtil']) : '') . '</td><td>' . $pqr . $g->unit() . "</td><td>";
				  $s .= round($g->totalcost() / $g->quantity(), 2) . "</td><td align=center>";

				  $suggest = min($pqr, ($rm >= $pqr?$pqr : $rm));

				  $s .= (($this->_type & 4) == 0?"从下列位置取" : '') . PHPWS_Form::formTextField("qty[{$p['id']}]", $suggest, 5)  . $g->unit() . "<br>";

				  if ($p['availtime'] > $pdate) $pdate = $p['availtime'];

				  $rm -= ($rm >= $pqr?$pqr : $rm);
				  $sum += $pqr;
				  if ($shelf) {
					  foreach ($shelf as $sh) {
						  $s .= $sh['shelf'] . PHPWS_Form::formCheckBox("alloc[{$sh['id']}]", $g->id(), $suggest > 0?$g->id() : 0) . '部分' . PHPWS_Form::formCheckBox("partial[{$sh['id']}]", 1, $suggest && $suggest<$pqr?1:0) . "<br>";
					  }
					  $s .= '<hr>';
				  }
				  else $s .= PHPWS_Form::formHidden("alloc[{$p['id']}]", $g->id());
				  $s .= '</td>' . $st;
				  $nt++; $sh = '<tr>'; $st = "</tr>"; $s = &$s1;
			  }

			  $atb = formatAttribTable($g->attribs());
/*
			  if ($g->attribs()) {
				  $glue = '';
				  foreach ($g->attribs() as $at=>$val) {
					  $atb .= $glue . $at . "：" . $val;
					  $glue = "<br>";
				  }
			  }
*/
			  if ($shipping) {
				  $srs .= "<tr><td align=center>" . $g->product() . "</td><td colspan=2 align=center>" . prodCache($g->product()) . "</td><td align=center>$atb";
				  $srs .= "</td><td align=center>";
				  if (($g->status() & 0xF) >= 8) $srs .= $po->extra()['packaging'] . "</td><td align=center>{$po->_extra['packages']}";
				  else $srs .= PHPWS_Form::formTextField("packaging[" . $po->id() . "]", $po->extra()['packaging'], 5) . "</td><td align=center>" . PHPWS_Form::formTextField("packages[{$po->id()}]", $po->extra()['packages'], 5);
				  $srs .= "</td><td align=center>{$g->unit()}</td><td align=center>{$g->price()}</td><td align=center>{$prods[$g->innerid()]}</td><td align=center>" . ($g->price() * $prods[$g->innerid()]) . "</td><td align=center>" . dayofdate($pdate) . "</td></tr>";
			  }
			  else {
				  $srs .= "<tr><td align=center>" . prodCache($g->product()) . "</td><td align=center>$atb";
				  $srs .= "</td><td align=center>{$g->unit()}</td><td align=center>" . $prods[$g->innerid()];
				  $srs .= "</td><td align=center>{$g->_extra['comments']}</td></tr>";
			  }
			  if (!$s0) $s0 = "<td></td><td></td><td></td><td></td>";

			  $allgood &= $os[$g->innerid()];

			  if (!$bunit) $bunit = $g->unit();
			  $bsum += $prods[$g->innerid()] * Attributes::$unitsConv[$bunit][$g->unit()];
			  $totalcost += $prods[$g->innerid()] * Attributes::$unitsConv[$bunit][$g->unit()] * $g->price();

			  if ($os[$g->innerid()]) continue;

			  $rs .= "<tr><td align=center rowspan=$nt>" . prodCache($g->product()) . "</td><td rowspan=$nt>$atb</td><td align=center rowspan=$nt>$sum" . $g->unit() . "</td><td align=center rowspan=$nt>" . $prods[$g->innerid()] . $g->unit() . "</td>$s0</tr>" . $s1;

			  if ($sum < $prods[$g->innerid()]) $insuff = true;

			  if (($this->_type & 4) == 0) {	// has shelf
				  if ($GLOBALS['WXMODE']) $rs .= "<tr><td></td><td colspan=4 align=center><a href=# onclick='scanShelf2Store(" . $g->id() . ");'>扫货架二维码取货</a> 或从指定位置取货：<br>$s</td></tr>";
			  }

			  $asum += $sum;
		  }
		  $rs .= "<tr><th colspan=6></th><th>签收：</th><th>" . PHPWS_Form::formTextField("receiver", NULL, 5) . "</th></tr>";
		  $rs .= "</table>";

		  $u = new PHPWS_User($former);
		  if ($actor) $v = new PHPWS_User($actor);
		  if ($shipping) {
			  $d = $po;
//			  else {
//				  $d = $this;
//				  foreach ($flow->processors as $td)
//					  if ($td->processType() == 8) $d = $td;
//			  }
/*
			  while ($d && $d->processType() != 8) {
				  if ($d->_output && $flow->goods[$d->_output[0]]->dest()) {
					  $d = $flow->processors[$flow->goods[$d->_output[0]]->dest()];
				  }
				  elseif ($d->processType() != 8) $d = NULL;
			  }
*/
			  if ($d) {
				  $seller = $GLOBALS['core']->query("SELECT * FROM supply_{$this->flowTable} as f JOIN supply_mod_users as u ON f.id={$d->id()} AND f.creator=u.user_id", false);
				  $seller = $seller->fetchRow();
				  $seller = $seller['realname']?$seller['realname'] : $seller['username'];
			  }

			  $srs .= "<tr><th colspan=8>合 计</td><td align=center>" . $bsum . $bunit . "</td><td align=center>$totalcost</td><td></td></tr></table>";
			  $srs .= "<table width=100%><tr><th>制单：</th><th>" . ($u->realname?$u->realname : $u->username) .  "</th><th>销售人员：</th><th>$seller</th><th>仓管：</th><th>" . ($v?($v->realname?$v->realname : $v->username) : '') . "</th><th>客户签收：</th><th></th></tr>";
			  $srs .= "<tr><th>备注：</th><th>发货日期：</th><th>" . date("Y-m-d") . "</th><th>验收单号</th><th></th><th>验收日期：</th><th></th></tr>";
		  }
		  else $srs .= "</table><table width=100%><tr><th>制单：</th><th>" . ($u->realname?$u->realname : $u->username) .  "</th><th>仓管：</th><th>" . ($v?($v->realname?$v->realname : $v->username) : '') . "</th><th>签收：</th><th>" . $receiver . "</th></tr>";
		  $srs .= "</table>";

		  if ($insuff) return $srs . "<span style='color:red'>库存不足</span>";

		  if ($asum) {
			  $rs .= "<table><tr><th>上传发货单：</th><td>" . PHPWS_Form::formFile("shippingProof0") . "</td>";
			  $rs .= "<th>上传发货证据1：</th><td>" . PHPWS_Form::formFile("shippingProof1") . "</td>";
			  $rs .= "<th>上传发货证据2：</th><td>" . PHPWS_Form::formFile("shippingProof2") . "</td>";
			  $rs .= "<th>上传发货证据3：</th><td>" . PHPWS_Form::formFile("shippingProof3") . "</td></tr><tr>";
			  $rs .= "<th>上传发货证据4：</th><td>" . PHPWS_Form::formFile("shippingProof4") . "</td>";
			  $rs .= "<th>上传发货证据5：</th><td>" . PHPWS_Form::formFile("shippingProof5") . "</td>";
			  $rs .= "<th>上传发货证据6：</th><td>" . PHPWS_Form::formFile("shippingProof6") . "</td></tr></table>";

			  $rs .= "<center>" . PHPWS_Form::formSubmit("确认", 'xop') . " | <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=$MOD_op&op=$op&orderNum=$orderNum&id=$id&print=1>打印</a></center>";
			  $rs .= PHPWS_Form::formHidden("module", 'work') . PHPWS_Form::formHidden("MOD_op", 'wharehouse') . PHPWS_Form::formHidden("op", 'detail') . PHPWS_Form::formHidden("orderNum", $orderNum) . PHPWS_Form::formHidden("id", $id);
			  $rs = PHPWS_Form::makeForm('addProduct', $GLOBALS['SCRIPT'], array($rs), "post", FALSE, TRUE);
		  }
		  else {
			  if ($task->_extra['shippingProof']) {
				  $gs = "<table width=100%>"; $i = 0;
				  foreach ($task->_extra['shippingProof'] as $file) {
					  if (($i++ % 4) == 0) $gs .= "<tr>";
					  $gs .= "<td width=25%><img width=100% src=./$file></td>";
					  if (($i % 4) == 0) $gs .= "</tr>";
				  }
				  if (($i % 4) != 0) $gs .= "</tr>";
				  $gs .= "</table>";
			  }
		  }
	  }
	  if ($print) exit($srs);

	  if ($allgood) $rs = '';

	  return $srs . $rs . $gs;
  }

  function ready() { return $this->_wharehouse? true : false; }

  function jshead() {
	  $_SESSION['OBJ_layout']->extraHead("<script>
			  function expand(obj, loc, goods) {
				$.ajax({
					url:'./{$GLOBALS['SCRIPT']}?module=accessories&MOD_op=expandwh&id={$this->_wharehouse}&loc=' + loc + '&goods=' + goods,
					type:'get',
					dataType:'json',
					success: function(resp) {
						document.getElementById(obj).innerHTML = resp.storelocs;
					}
				 });
			  }

			wx.config({
				debug: false,
				appId: '{$pkg['appId']}',
				timestamp: '{$pkg['timestamp']}',
				nonceStr: '{$pkg['nonceStr']}',
				signature: '{$pkg['signature']}',
				jsApiList: ['scanQRCode']
			});

			var first = true;

			function scanShelf2Store(id) {
				wx.ready(function () {
					wx.scanQRCode({
						needResult: 1,
						scanType: ['qrCode', 'barCode'],
						desc: '扫一扫货架的二维码，确认放货/取货',
						success: function (res) {
							var result = res.resultStr;
							$.ajax({
								url:'./{$GLOBALS['SCRIPT']}?module=work&MOD_op=storeonshelf&id=' + id + '&shelf=' + result,
								type:'get',
								dataType:'json',
								success: function(resp) {
									if (first) document.getElementById('storelocs').innerHTML = '';
									document.getElementById('storelocs').innerHTML += resp.result;
								}
							 });
						}
					});
				});
			}
		  </script>");
  }

  function execute($flow) {
	  extract($_REQUEST);
	  extract($_POST);

	  if (!$this->_wharehouse) return '请设置仓库';

	  if ($op == "确认入库") $action = 1;
	  elseif ($op == "确认出库") $action = 2;
	  elseif (!$action) {
		  $act = 0; $un = false;
		  if ($this->_output) foreach ($this->_output as $i) {
			  if ($flow->goods[$i]->status() == -1 || ($flow->goods[$i]->status() & 0xF) >= 4) continue;
			  $un = true;
			  $act |= 2;
		  }
		  if ($this->_input) foreach ($this->_input as $i) {
			  if ($flow->goods[$i]->status() == -1) continue;
			  $act |= 1;
		  }

		  if (!$un && $act == 0) {
			  $this->setStatus($flow, -1);
			  if ($this->_start) $this->_duration = strtotime(date("Y-m-d H:i:s")) - $this->_start;
			  $flow->saveflow(false);
			  return "任务完成。";
		  }

		  if ($act == 3) 
			  return "<hr><a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=$MOD_op&itemid=$itemid&action=1>入库</a> | <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=$MOD_op&itemid=$itemid&action=2>出库</a>";

		  $action = $act;
	  }

	  if ($GLOBALS['WXMODE']) {
		  require_once (PHPWS_SOURCE_DIR . "core/jssdk.php");

		  $sdk = new JSSDK(AppId, AppSecret);
		  $pkg = $sdk->getSignPackage();
	  }

	  $this->jshead();

	  if ($action & 2) return $this->fetch($flow); //	  . ($act & 1?"<hr><a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=$MOD_op&op=$op&itemid=$itemid&action=1>入库</a>" : '');
	  if ($action & 1) return $this->store($flow);	// . ($act & 2?"<hr><a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=$MOD_op&op=$op&itemid=$itemid&action=2>出库</a>" : '');
  }

  function whexpand($hierachy, $shelfspace, $alloc, $goods, $indent = '', $slot = '') {
	  list ($i, $hd) = each($hierachy);
	  $subhierachy = $hierachy;
	  unset($subhierachy[$i]);
	  $indent .= '  ';
	  foreach ($shelfspace as $h=>$t) {
		  $ms = explode("-", $h);
		  $va = array($ms[0]);
		  if (sizeof($ms) == 2) {
			  if (is_numeric($ms[0])) {
				  for ($i = $ms[0] + 1; $i <= $ms[1]; $i++) $va[] = $i;
			  }
			  else {
				  for ($i = ord($ms[0]) + 1; $i <= ord($ms[1]); $i++) $va[] = chr($i);
			  }
		  }
		  if (sizeof($t) == 0) {
			  $g = $indent;
			  $mm = min(10, round(85 / strlen("$slot$hd"), 0));
			  $usd = array();
			  if (($this->_type & 0x12) == 8) {
				  $used = $GLOBALS['core']->query("SELECT * FROM mod_shelf_goods WHERE shelf like '$slot%' AND wharehouse=" . $this->_wharehouse, true);
				  while ($u = $used->fetchRow()) $usd[$u['shelf']] = 1;
			  }
			  foreach ($va as $v) {
				  $s .= $g . "$slot$v$hd" . ($usd["$slot$v$hd"]?'  ' : preg_replace("/[[:space:]]*$/", " ", PHPWS_Form::formCheckBox("alloc[$slot$v$hd]", $goods)));
				  if ((++$m % 10) == 0) $g = "<br>" . $indent;
				  else $g = " ";
			  }
			  if ($m) $s .= "<br>";
		  }
		  else foreach ($va as $v) {
			  if (isset($alloc[$v]))
				  $s .= $this->whexpand($subhierachy, $shelfspace[$h], $alloc[$v], $goods, $indent, $slot . $v . $hd);
			  else $s .= "<span id='$goods-$slot$v$hd'>$indent<a href=# onclick='expand(\"$goods-$slot$v$hd\", \"$slot$v$hd\", $goods);'>$v$hd</a></span><br>";
		  }
	  }
	  return $s;
  }

  function forcedChange($flow, $origional, $change, $affected) {
	  $st = $GLOBALS['core']->query("SELECT * FROM mod_stocked_goods WHERE id IN(" . implode(",", $affected->extra()['fetchFrom']) . ")", true);
	  $fls = array();
	  $sum = 0;
	  while ($p = $st->fetchRow()) {
		  $fl = new PHPWS_Flow();
		  if (!($q = $GLOBALS['core']->sqlSelect("mod_flow_accounting", 'id', $p['source']))) {
			  $fl->load_flow($p['source']);
			  $fl->changeTable("mod_flow_accounting");
			  $fl->saveflow(false);
		  }
		  elseif ($q['flowid'] != $flow->id) {
			  $fl->changeTable("mod_flow_accounting");
			  $fl->load_flow($p['source']);
		  }
		  else $fl = $flow;
		  $g = $fl->findgoods($p['source']);
		  
		  $sum += $g->convertUnit($origional->unit());

		  $fls[] = array('flow'=>$fl, 'prod'=>$g);
	  }
	  if ($sum == $origional->quantity()) {
	  }


	  $bestmatch = NULL; $qtydiff = 1.0e30;
	  foreach ($this->_input as $i) {
		  $p = &$flow->goods[$i];
		  if ($p->product() != $origional->product()) continue;
		  $diff = abs($origional->quantity() - $p->convertUnit($origional->unit()));
		  if ($diff < $qtydiff) {
			  $qtydiff = $diff;
			  $bestmatch = $p;
		  }
	  }
	  if ($bestmatch) {
		  if ($change & 1024) {
			  $origional->SetProduct($bestmatch->product());
			  $bestmatch->SetProduct($affected->product());
		  }
		  if ($change & 4096) {
			  $origional->SetQuantity($bestmatch->quantity());
			  $origional->SetUnit($bestmatch->unit());
			  $bestmatch->SetQuantity($affected->quantity());
			  $bestmatch->SetUnit($affected->unit());
		  }
		  if ($change & 16384) {
			  $origional->SetPrice($bestmatch->price());
			  $bestmatch->SetPrice($affected->price());
		  }
		  if ($bestmatch->src() > 0)
			  $flow->processors[$bestmatch->src()]->forcedChange($flow, $origional, $change, $bestmatch);
	  }
  }

  function wharehouse() { return $this->_wharehouse; }
  function name() { return $this->_name; }
  function mode() { return $this->_io; }
  function type() { return $this->_type; }
  function valuation() { return $this->_value; }
  function ownerOrg() { return $this->_ownerOrg; }
  function rivalOrg() { return $this->_rivalOrg; }
  function xfer() { return $this->_xfer; }

  function negStock() { return ($this->_type & 16)?true : false; }
  function frozen() { return ($this->_type & 2)?true : false; }
  function heap() { return ($this->_type & 4)?true : false; }
  function shelfed() { return ($this->_type & 4)?false : true; }
  function uniqueSpot() { return ($this->_type & 8)?true : false; }

  function SetRivalOrg($org) {
	  $this->_rivalOrg = $org;
	  $this->addOrg($org);
  }
  function SetOrg($org) {
	  $this->addOrg($org);
	  parent::SetOrg($org);
  }
  function SetMode($m) { $this->_io = $m?1 : 0; $this->saved = false; }
  function SetXfer($m) { $this->_xfer = $m?1 : 0; $this->saved = false; }
  function SetValuation($m) { $this->_value = $m?1 : 0; $this->saved = false; }

  function SetWharehouse($val, $mode = 0) {
	  if ($this->_wharehouse != $val) {
		  $w = $GLOBALS['core']->sqlSelect("mod_wharehouse", 'id', $val);
		  if ($w) {
			  $this->_wharehouse = $val;
			  $this->_io = $mode;
			  $this->_name = $w[0]['name'];
			  $this->_type = $w[0]['type'];
			  $this->_ownerOrg = $w[0]['ownerOrg'];
			  $this->SetOrg($this->_ownerOrg);
			  $this->saved = false;
		  }
	  }
	  return $val;
  }
}

function trig($flowtrigger, $flow, $task) {
	  switch ($flowtrigger['type']) {
		  case 'func':
			  $f = $flowtrigger['func'];
			  $flow->processors[$flowtrigger['task']]->$f($flow, $flowtrigger['param']);
			  break;
		  case 'url':
			  header("location: " . $flowtrigger['url']);
			  exit();
			  break;
		  default:
			  $GLOBALS['core']->sqlUpdate(array('status'=>8, 'end'=>date("Y-m-d H:i:s")), $this->flowTable, array('id'=>$task));
			  break;
	  }
}

?>