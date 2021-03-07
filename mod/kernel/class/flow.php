<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/consts.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/cache.php');
require_once(PHPWS_SOURCE_DIR . 'mod/product/class/product.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/nodes.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/result.php');

class PHPWS_Flow {
  public static $flowstatus = array(0=>'模板', 1=>'计划', 2=>'确定', 4=>'可执行', 8=>'执行完毕', 0xE=>'取消', 0xF=>'完成', -1=>'完成', -2=>'取消', -3=>"");
  public static $resourceType = array(32=>GOODS, 1024=>ROUTE, 2048=>TRUCK, 4096=>STOPS, 32768=>WHAREHOUSE);
  public static $allProcessType = array(1=>ORG_MANUFACTURER, 2=>ORG_TRANSPORTER, 4=>ORG_PROCESSOR, 8=>ORG_RETAILER, 16=>ORG_WHOLESALER, 64=>ORG_PACKER, 512=>STOCK, 8192=>WASTED, 16384=>COMSUMED, 128=>SUMUP, 256=>DIVIDER, 131072=>ORG_PURCHASE, 262144=>RETURNGOODS);
  public static $processType = array(1=>ORG_MANUFACTURER, 2=>ORG_TRANSPORTER, 4=>ORG_PROCESSOR, 64=>ORG_PACKER, 512=>STOCK, 131072=>ORG_PURCHASE, 8=>ORG_RETAILER, 262144=>RETURNGOODS);
  public static $inernalProcessType = array(8192=>WASTED, 16384=>COMSUMED, 128=>SUMUP, 16=>ORG_WHOLESALER, 256=>DIVIDER);

  var $flowTable = "mod_flow";
  var $processors = array();
  var $goods = array();
  var $name = NULL;
  var $id = NULL;
  var $goodsid = 1;
  var $processorsid = 1;
  var $alltypes = 0;
  var $status = 0;
  var $org = NULL;
  var $creator = NULL;
  var $saved = false;
  var $merged = false;

  var $altFlow = NULL;

  function PHPWS_Flow() {
  }

  function __construct() {
  }

  function changeTable($table) {
	  $this->flowTable = $table;
	  $this->saved = false;
	  foreach ($this->processors as &$c) {
		  $c->flowTable = $table;
	  }
	  foreach ($this->goods as &$c) {
		  $c->flowTable = $table;
	  }
  }

  function insertTask($procType) {
	  $d = PHPWS_Node::newNode($procType);
	  $this->alltypes |= $procType;
	  $d->SetTaskname(PHPWS_Flow::$allProcessType[$procType]);
	  $d->SetInnerid($this->processorsid);
	  $d->SetOrg($_SESSION['OBJ_user']->org);
	  $this->processors[$this->processorsid++] = $d;
	  $this->saved = false;
	  return $d;
  }

  function insertGoods($procType, $copydata = NULL) {
	  $d = PHPWS_Node::newNode($procType);
	  $this->alltypes |= $procType;
	  if ($copydata) {
		  $d->copy($copydata);
		  $d->SetConnects(array());
		  $d->SetId(0);
		  $d->ChgStatus(1);
		  $d->SetDest(0);
		  $d->SetSrc(0);
	  }
	  else {
		  $d->SetName(PHPWS_Flow::$resourceType[$procType]);
		  $d->SetOrg($_SESSION['OBJ_user']->org);
	  }
	  $d->SetInnerid($this->goodsid);
	  $this->goods[$this->goodsid++] = $d;
	  $this->saved = false;
	  return $d;
  }

  function copy($f) {
	  $this->processors = array();
	  foreach ($f->processors as $i=>$g) {
		  $t = PHPWS_Node::newNode($g->processType());
		  $t->copy($g);
		  $this->processors[$i] = $t;
	  }
	  $this->goods = array();
	  foreach ($f->goods as $i=>$g) {
		  $t = PHPWS_Node::newNode($g->resourceType());
		  $t->copy($g);
		  $this->goods[$i] = $t;
	  }
	  $this->name = $f->name;
	  $this->id = $f->id;
	  $this->goodsid = $f->goodsid;
	  $this->processorsid = $f->processorsid;
	  $this->alltypes = $f->alltypes;
	  $this->status = $f->status;
	  $this->saved = $f->saved;
	  $this->altFlow = NULL;
	  $this->org = NULL;
  }

  function load_flow($id) {
	  $f = $GLOBALS['core']->sqlSelect($this->flowTable, 'id', $id);
	  $this->loadflow($f[0]['flowid']);

	  SlipMgr::loadSnip($f[0]['flowid']);
  }

  function loadflow($id) {
	  $flow = $GLOBALS['core']->sqlSelect($this->flowTable, 'flowid', $id, 'id asc');

	  if (!$flow) return NULL;

	  $this->id = $id;
	  $this->processors = array();
	  $this->goods = array();
	  $this->goodsid = 1;
	  $this->processorsid = 1;
	  $this->alltypes = 0;
	  $this->status = 0;
	  $this->saved = true;
	  $this->altFlow = NULL;
//echo '<pre>';
	  foreach ($flow as $f) {
//print_r($f);
		  if ($f['flowname']) {
			  $this->name = $f['flowname'];
			  $this->alltypes = $f['resourceType'];
			  $this->status = $f['status'];
			  $this->start = $f['start'];
			  $this->created = $f['created'];
			  $this->org = $f['org'];
			  $this->creator = $f['creator'];
		  }
		  elseif (isset(PHPWS_Flow::$resourceType[$f['resourceType']])) {
			  $this->goods[$f['innerid']] = PHPWS_Node::load($f);
			  $this->goods[$f['innerid']]->flowid = $id;
			  $this->goods[$f['innerid']]->flowTable = $this->flowTable;
			  $this->goodsid = max($this->goodsid, $f['innerid']);
		  }
		  else {
//echo 'processor ' . $f['innerid'] . ' loaded';
			  $this->processors[$f['innerid']] = PHPWS_Node::load($f);
			  $this->processors[$f['innerid']]->flowid = $id;
			  $this->processors[$f['innerid']]->flowTable = $this->flowTable;
			  $this->processorsid = max($this->processorsid, $f['innerid']);
		  }
	  }
	  $this->goodsid++;
	  $this->processorsid++;
	  if ($this->goods) foreach ($this->goods as $i=>$g) {
		  if ($g->src() > 0) $this->processors[$g->src()]->AddOutput($g);
		  if ($g->dest() > 0) $this->processors[$g->dest()]->AddInput($g);
	  }

	  SlipMgr::loadSnip($id);
//print_r($this->processors);
//echo '</pre>';
  }

  function saveflow($del = true) {
//echo "<pre>"; print_r($this);echo "</pre>";
	  if ($del) {
		  $this->status = max($this->status, 1);
		  $this->saved = false;
		  $this->id = 0;
	  }

	  if ($this->saved || $this->merged) return;
	  $this->alltypes = 0;
	  foreach ($this->processors as &$val) {
		  $this->alltypes |= $val->processType();
		  $val->flowTable = $this->flowTable;
		  if ($del) {
			  $val->ChgStatus(max($val->status(), 1));
			  $val->SetId(0);
		  }
	  }
	  foreach ($this->goods as &$val) {
		  $this->alltypes |= $val->resourceType();
		  $val->flowTable = $this->flowTable;
		  if ($del) {
			  $val->ChgStatus(max($val->status(), 1));
			  $val->SetId(0);
		  }
	  }
	  unset($val);

	  if ($this->id) {
		  if ($this->flowTable == 'mod_flow')
			  $GLOBALS['core']->sqlUpdate(array('flowname'=>$this->name, 'multiple'=>0, 'status'=>$this->status, 'resourceType'=>$this->alltypes, 'start'=>date("Y-m-d H:i:s")), $this->flowTable, 'id', $this->id);
		  else {
			  $rep = array('id'=>$this->id, 'flowname'=>$this->name, 'multiple'=>0, 'status'=>$this->status, 'resourceType'=>$this->alltypes, 'start'=>date("Y-m-d H:i:s"));
			  if ($this->created) $rep['created'] = $this->created;
			  if ($this->org) $rep['org'] = $this->org;
			  if ($this->creator) $rep['creator'] = $this->creator;
			  $GLOBALS['core']->sqlReplace($rep, $this->flowTable);
		  }
		  if ($del && $this->id) {
			  $GLOBALS['core']->query("DELETE FROM {$this->flowTable} WHERE flowid={$this->id} AND flowname is NULL", true);
		  }
	  }
	  else {
		  $d['creator'] = $_SESSION["OBJ_user"]->user_id;
		  $d['org'] = $this->org?$this->org : $_SESSION["OBJ_user"]->org;
		  $d['flowname'] = $this->name;
		  $d['created'] = $d['start'] = date("Y-m-d H:i:s");
		  $d['status'] = $this->status;
		  $d['resourceType'] = $this->alltypes;
		  $this->id = $GLOBALS['core']->sqlInsert($d, $this->flowTable, false, true);
		  $GLOBALS['core']->sqlUpdate(array('flowid'=>$this->id), $this->flowTable, 'id', $this->id);
		  if ($this->processors) foreach ($this->processors as $innerid=>$val) $this->processors[$innerid]->SetId(0);
		  if ($this->goods) foreach ($this->goods as $innerid=>$val) $this->goods[$innerid]->SetId(0);
	  }
	  foreach ($this->processors as $innerid=>$val) {
		  if ($val->org()) {
			  if (!$val->place()) {
				  $loc = $GLOBALS['core']->sqlSelect('mod_org', 'id', $val->org());
				  $this->processors[$innerid]->SetPlace($loc[0]['location']);
			  }
			  if (!$val->province() && $this->processors[$innerid]->place()) {
				  $tp = locCache($this->processors[$innerid]->place());
				  $this->processors[$innerid]->SetProvince($tp['province']);
				  $this->processors[$innerid]->SetCity($tp['city']);
			  }
		  }
		  $val->flowid = $this->id;
		  $val->save($del);
//		  $val->save(array('innerid'=>$innerid, 'flowid'=>$this->id), $del);
		  if ($this->flowTable == 'mod_flow' && in_array($val->processType(), array(PROCESSOR_NODE, SALE_NODE, PURCHASE_NODE, STOCK_NODE, RETURN_NODE, WASTE_NODE, RETURN_NODE, AUGSTOCK_NODE))) {
			  $GLOBALS['core']->query("REPLACE INTO supply_orgtasks (org, task, processType) VALUES (" . $val->org() . ", " . $val->id() . ", " . $val->processType() . ")", false);
		  }
	  }

	  foreach ($this->goods as $innerid=>$val) {
		  if ($val->src() > 0) $val->SetFromtasktype($this->processors[$val->src()]->processType());
		  if ($val->dest() > 0) $val->SetTotasktype($this->processors[$val->dest()]->processType());
		  $val->flowid = $this->id;
		  $val->save($del);
//		  $val->save(array('innerid'=>$innerid, 'flowid'=>$this->id), $del);
	  }

	  $this->saved = true;

	  SlipMgr::saveSnip($this->id);
  }

  function newflow($name = NULL) {
	  extract($_REQUEST);
	  extract($_POST);

	  if ($op == 'newflow') {
		  if ($GLOBALS['core']->sqlSelect($this->flowTable, array('flowname'=>$name, 'org'=>$_SESSION["OBJ_user"]->org))) {
			  $s .= "<span style='color:red'>{$name}已存在</span><br>";
			  $makenew = true;
		  }
		  else {
			  $this->name = $name;
			  $this->saved = FALSE;
		  }
	  }

	  if ($makenew || (!$this->name && !$name)) {
		  $this->processors = array();
		  $this->goods = array();
		  $this->id = NULL;
		  $this->name = NULL;
		  $this->goodsid = 1;
		  $this->processorsid = 1;
		  $this->alltypes = 0;
		  $this->status = 0;
		  $this->saved = FALSE;
		  $this->org = $_SESSION["OBJ_user"]->org;
		  
		  $s .= "流程名称：" . PHPWS_Form::formTextField("name", $name) . "<br>";
		  $s .= "<br>" . PHPWS_Form::formSubmit("提交");
		  $s .= PHPWS_Form::formHidden("module", $module) . PHPWS_Form::formHidden("op", 'newflow') . PHPWS_Form::formHidden("MOD_op", 'newflow');
		  return PHPWS_Form::makeForm('addProduct', $GLOBALS['SCRIPT'], array($s), "post", FALSE, TRUE);
	  }

	  if ($op == 'addprod') {
		  $d = PHPWS_Node::newNode($resourceType);
		  $d->SetName($name);
		  if ($resourceType == 32) {
			  $d->SetCategory($category?$category : 0);
			  $d->SetProduct($product?$product : 0);
		  }
//		  $d->org = $owner;
		  $d->SetMultiple($multiple?$multiple : 0);
		  $this->goods[$this->goodsid++] = $d;
		  $this->saved = FALSE;
//echo '<pre>'; print_r($this); echo '</pre>'; flush();
	  }

	  if ($op == 'addproc') {
		  $res = '';
//echo 'addproc'; flush();
		  $d = PHPWS_Node::newNode($processType);
		  if ($srproduct) {
			  foreach ($srproduct as $i=>$smt)
				  if ($smt != $destproduct[$i])
					  $d->rules()[] = array('type'=>'product', 'cause'=>$smt, 'target'=>$destproduct[$i]);
		  }
		  if ($org && $srcamt) {
			  foreach ($srcamt as $i=>$smt)
				  $d->rules()[] = array('type'=>'ratio', 'src'=>array('item'=>$srcratio[$i], 'unit'=>$srcunit[$i], 'amount'=>$smt), 'dest'=>array('item'=>$destratio[$i], 'unit'=>$destunit[$i], 'amount'=>$destamt[$i]));
		  }
		  if ($org && $desttime) {
			  foreach ($desttime as $i=>$smt)
				  if ($smt == '当前任务') $desttime[$i] = $taskname;
			  foreach ($srctime as $i=>$smt)
				  if ($smt == '当前任务') $srctime[$i] = $taskname;
			  foreach ($srcduration as $i=>$smt)
				  if ($smt == '当前任务') $srcduration[$i] = $taskname;
			  foreach ($destduration as $i=>$smt)
				  if ($smt == '当前任务') $destduration[$i] = $taskname;

			  foreach ($desttime as $i=>$smt)
				  if ($smt != $srctime[$i] || $delaytime[$i] != 0)
					  $d->rules()[] = array('type'=>'time', 'target'=>$smt, 'cause'=>$smt != $srctime[$i]?$srctime[$i] : NULL, 'delay'=>$delaytime[$i], 'unit'=>$delayunit[$i]);
			  foreach ($destduration as $i=>$smt)
				  if ($smt != $srcduration[$i] || $duration[$i] != 0)
					  $d->rules()[] = array('type'=>'duration', 'target'=>$smt, 'cause'=>$smt != $srcduration[$i]?$srcduration[$i] : NULL, 'delay'=>$duration[$i], 'unit'=>$durationunit[$i]);
		  }
		  if ($org && $destplace) {
			  foreach ($destplace as $i=>$smt)
				  if ($smt == '当前任务') $destplace[$i] = $taskname;
			  foreach ($srcplace as $i=>$smt)
				  if ($smt == '当前任务') $srcplace[$i] = $taskname;
			  foreach ($destplace as $i=>$smt)
				  if ($smt != $srcplace[$i])
					  $d->rules()[] = array('type'=>'place', 'target'=>$smt, 'cause'=>$srcplace[$i]);
		  }
//echo 'addproc2'; flush();

		  if (in_array($processType, array(1, 2, 4, 64, 512))) 
			  $res = $d->validate($this->goods, $input, $output, $d->rules());
/*
			  case 1:	// ORG_MANUFACTURER
				  $res = PHPWS_ManufactureNode::validate($this->goods, $input, $output, $d->rules());
				  break;
			  case 2:	// ORG_TRANSPORTER
				  $res = PHPWS_TransportNode::validate($this->goods, $input, $output, $d->rules());
				  break;
			  case 4:	// ORG_PROCESSOR
//echo 'PHPWS_ProcessorNode'; flush();
				  $res = PHPWS_ProcessorNode::validate($this->goods, $input, $output, $d->rules());
				  break;
			  case 64:	// ORG_PACKER
				  $res = PHPWS_PackerNode::validate($this->goods, $input, $output, $d->rules());
				  break;
			  case 512:	// STOCK
				  $res = PHPWS_StockNode::validate($this->goods, $input, $output, $d->rules());
				  break;
		  }
*/
//echo 'addproc3'; flush();

		  $err = NULL;
		  if ($res) { $input = $output = array(); $s .= $res; }
		  else {
			  if ($input) foreach ($input as $i)
				  if ($this->goods[$i]->dest()) $err .= "<span style='color:red'>" . $this->goods[$i]->name() . "去向重复定义</span><br>";
			  if ($output) foreach ($output as $i)
				  if ($this->goods[$i]->src()) $err .= "<span style='color:red'>" . $this->goods[$i]->name() . "来源重复定义</span><br>";
			  if ($err) $s .= $err;
			  else {
				  if ($input) foreach ($input as $i)
					  $this->goods[$i]->SetDest($this->processorsid);
				  if ($output) foreach ($output as $i)
					  $this->goods[$i]->SetSrc($this->processorsid);

				  $d->SetTaskname($taskname);
				  
				  if ($timeneed) {
					  $durationsec = array("分钟"=>60, "小时"=>3600, "天"=>3600 * 24, "周"=>3600 * 24 * 7, "月"=>(strtotime("+$timeneed month") - time()) / $timeneed);	  
					  if ($timeneed) $timeneed = $timeneed * $durationsec[$duraunit];
				  }
				  $d->SetDuration($timeneed);
				  $d->SetInput($input);
				  $d->SetOutput($output);
				  $d->SetOrg($org?$org : 0);

			  }
			  if (!$err) {
				  $this->processors[$this->processorsid++] = $d;
				  $this->saved = FALSE;
				  $processType = $taskname = $input = $output= NULL;
			  }
		  }
//echo 'addproc4'; flush();
	  }

	  if ($op == 'delres') {
		  $js = array();
		  if ($this->goods[$id]->dest()) 
			  $this->processors[$this->goods[$id]->dest()]->SetInput(array_diff($this->processors[$this->goods[$id]->dest()]->input(), array($id)));
		  if ($this->goods[$id]->src())
			  $this->processors[$this->goods[$id]->src()]->SetOutput(array_diff($this->processors[$this->goods[$id]->src()]->output(), array($id)));
		  unset($this->goods[$id]);
		  $this->saved = FALSE;
	  }

	  if ($op == 'deltask') {
		  if ($this->processors[$id]->input())
			  foreach ($this->processors[$id]->input() as $i) $this->goods[$i]->SetDest(0);
		  if ($this->processors[$id]->output())
			  foreach ($this->processors[$id]->output() as $i) $this->goods[$i]->SetSrc(0);
		  unset($this->processors[$id]);
		  $this->saved = FALSE;
	  }
// echo $op;
	  if ($op == 'saveflow') {
// echo 'saveflow';
		  $this->saveflow();
	  }

	  $s .= "<center>" . $this->name() . "（" . ($this->saved?"已" : "未") . "保存）</center><hr>";

	  $prods = "资源<hr>";
	  $res = array();
	  $resall = array(-$this->processorsid=>'当前任务');
	  if (sizeof($this->goods)) {
		  $prods .= "<table cellspacing=3 cellpadding=3 border=1><tr><th>品名</th><th>类型</th><th>来源</th><th>去向</th></tr>";
		  foreach ($this->goods as $i=>$g) {
			  $resall[$i] = "资源：" . $g->name();
			  $prods .= "<tr><td><a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=setresparam&itemid=$i>" . $g->name() . "</a></td><td>" . PHPWS_Flow::$resourceType[$g->resourceType()] . "</td><td>" . ($g->src()?$this->processors[$g->src()]->taskname() : '') . "</td><td>" . ($g->dest()?$this->processors[$g->dest()]->taskname() : '') . "</td><td><a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=$MOD_op&op=delres&id=$i>删除</a></td></tr>";
		  }
		  $prods .= "</table>";
	  }
	  $prods .= "<table><tr><th align=center colspan=2>增加资源</th></tr>";
	  $prods .= "<tr><td>资源类型：</td><td>" . PHPWS_Form::formSelect("resourceType", PHPWS_Flow::$resourceType, NULL, false, true, 'resChg(this);') . "</td></tr>";
	  $prods .= "<tr><td>资源名称：</td><td>" . PHPWS_Form::formTextField("name", '') . PHPWS_Form::formCheckBox("multiple", 1, $multiple) . "可有多项</td></tr>";
/*
	  $asignee = array(0=>'外单位', $_SESSION['OBJ_user']->org=>'本单位');
	  $as = $GLOBALS['core']->query("SELECT b.* FROM supply_mod_org as a JOIN supply_mod_org as b ON a.id={$_SESSION['OBJ_user']->org} AND (b.parent=a.id OR (b.parent=a.parent AND a.parent!=0)) AND b.id!=a.id", false);
	  while ($e = $as->fetchRow()) $asignee[$e['id']] = $e['name'];
	  $orgs = $GLOBALS['core']->sqlSelect('mod_org', 'id', $_SESSION['OBJ_user']->org);
	  $orgs = unserialize($orgs[0]['anciester']);
	  if ($orgs && is_array($orgs))
		  foreach ($orgs as &$og) $og = '<a onclick="' . "chgorg($og, 'orgsA', 'owner');" . '">' . orgCache($og) . "</a>";
	  $prods .= "<tr><td>资源所有者：</td><td><div id=orgsA>" . ($orgs?implode(" => ", $orgs) : '') . "</div>" . PHPWS_Form::formSelect("owner", $asignee, $_SESSION['OBJ_user']->org, false, true, "chgorg(this.options[this.selectedIndex].value, 'orgsA', 'owner');") . "</td></tr>";
*/
	  $cat = myCategories();

	  $prods .= "<tr><td>产品：</td><td id=showgoods><div id=cats></div>" . PHPWS_Form::formSelect("category", $cat, NULL, false, true, 'catChg(this);') . PHPWS_Form::formSelect("product", array(), NULL, false, true) . "</td></tr>";

	  $prods .= "<tr><td align=center colspan=2>" . PHPWS_Form::formSubmit("提交新资源") . "</td></tr></table>";
	  $prods .= PHPWS_Form::formHidden("module", $module) . PHPWS_Form::formHidden("MOD_op", $MOD_op) . PHPWS_Form::formHidden("op", "addprod");
	  $prods = PHPWS_Form::makeForm('addprod', $GLOBALS['SCRIPT'], array($prods), "post", FALSE, TRUE);

	  $process = "任务环节<hr>";
	  $delayunit =  array("小时", "天", "周", "月");
	  if (sizeof($this->processors)) {
		  $process .= "<table cellspacing=3 cellpadding=3 border=1><tr><th>名称</th><th>任务</th><th>单位</th><th>输入</th><th>输出</th><th>物品</th><th>比例</th></tr>";

		  foreach ($this->processors as $j=>$g) {
			  $resall[-$j] = "任务：" . $g->taskname();

			  $in = ''; $glue = '';
			  if ($g->input()) foreach ($g->input() as $i) {
				  $in .= $glue . $this->goods[$i]->name();
				  $glue .= "<br>";
			  }
			  $out = ''; $glue = '';
			  if ($g->output()) foreach ($g->output() as $i) {
				  $out .= $glue . $this->goods[$i]->name();
				  $glue .= "<br>";
			  }

			  $things = $ratio = $delay = $location = NULL;

			  if ($g->rules()) foreach ($g->rules() as $rule) {
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

			  $lorg = orgCache($g->org());

			  $process .= "<tr><td><a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=settaskparam&itemid=$j>" . ($g->org()?"" : "<span style='color:red'>") . $g->taskname() . ($g->org()?"" : "</span>") . "</a></td><td>" . PHPWS_Flow::$allProcessType[$g->processType()] . "</td><td>" . $lorg . "</td><td>" . $in . "</td><td>" . $out . "</td><td>" . $things . "</td><td>" . $ratio . "</td><td><a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=$MOD_op&op=deltask&id=$j>删除</a></td></tr>";
		  }
		  $process .= "</table>";
	  }
	  $process .= "<table><tr><th align=center colspan=2><a name=a></a>增加任务</th></tr>";
	  
	  $units = Attributes::$units;
/* leave transport to professionals
		  if ($op == 'setliners' && $liner > 0) {
			  $ls = $GLOBALS['core']->query("SELECT *,l.name as stationname FROM supply_mod_liner as v JOIN supply_mod_locations as l ON lineid=$liner AND v.station=l.id ORDER BY seq ASC", false);
			  $out = NULL; $con = NULL;
			  if ($vehicle) {
				  $v = PHPWS_Node::newNode(2048);
				  $v->name = "运输工具";
				  $v->org = $_SESSION["OBJ_user"]->org;
				  $v->multiple = 0;
				  $vid = $this->goodsid;
				  $this->goods[$this->goodsid++] = $v;
			  }
			  $in = NULL; $arrival = NULL; $pcon = NULL;
			  $stations = array();
			  while ($l = $ls->fetchRow()) $stations[] = $l;

			  foreach ($stations as $i=>$l) {
				  if ($i == sizeof($stations) - 1) continue;
				  $in = PHPWS_Node::newNode(32);
				  $in->name = $l['stationname'] . ".收货";
				  $in->province = $l['province'];
				  $in->city = $l['city'];
				  $in->place = $l['station'];
				  $in->org = $_SESSION["OBJ_user"]->org;
				  $in->multiple = 1;
				  $in->dest = $this->processorsid;
				  $inid = $this->goodsid;
				  $this->goods[$this->goodsid++] = $in;

				  $dest = PHPWS_Node::newNode(4096);
				  $dest->name = $l['stationname'] . ".目的地";
				  $dest->org = $_SESSION["OBJ_user"]->org;
				  $dest->multiple = 0;
				  $dest->dest = $this->processorsid;
				  $destid = $this->goodsid;
				  $this->goods[$this->goodsid++] = $dest;

				  $arrival = PHPWS_Node::newNode(32);
				  $arrival->name = $stations[$i+1]['stationname'] . ".到站";
				  $arrival->province = $stations[$i+1]['province'];
				  $arrival->city = $stations[$i+1]['city'];
				  $arrival->place = $stations[$i+1]['station'];
				  $arrival->org = $_SESSION["OBJ_user"]->org;
				  $arrival->multiple = 1;
				  $arrival->src = $this->processorsid;
				  $arrivalid = $this->goodsid;
				  $this->goods[$this->goodsid++] = $arrival;

				  if ($i < sizeof($stations) - 2) {
					  $cont = PHPWS_Node::newNode(32);
					  $cont->name = $stations[$i+1]['stationname'] . ".中继";
					  $cont->province = $stations[$i+1]['province'];
					  $cont->city = $stations[$i+1]['city'];
					  $cont->place = $stations[$i+1]['station'];
					  $cont->org = $_SESSION["OBJ_user"]->org;
					  $cont->src = $this->processorsid;
					  $cont->dest = $this->processorsid + 1;
					  $cont->multiple = 1;
					  $contid = $this->goodsid;
					  $this->goods[$this->goodsid++] = $cont;
				  }
				  else $cont = NULL;

				  $l['staytime'] = explode(":", $l['staytime']);
				  $l['departure'] = explode(":", $l['departure']);
				  $l['arrival'] = explode(":", $stations[$i+1]['arrival']);
				  $timeneed = ($l['staytime'][0] + $l['arrival'][0] - $l['departure'][0]) * 3600 + ($l['staytime'][1] + $l['arrival'][1] - $l['departure'][1]) * 60 + ($l['staytime'][2] + $l['arrival'][2] - $l['departure'][2]);
				  $timeneed /= 3600.0;

				  if ($v) $v->dest = $this->processorsid;
				  $d = PHPWS_Node::newNode(2);
				  $d->taskname = $l['stationname'];
				  $d->input = array($inid, $destid);
				  $d->province = $l['province'];
				  $d->city = $l['city'];
				  $d->place = $l['station'];
				  if ($v) $d->input[] = $vid;
				  if ($pcon) $d->input[] = $pconid;
				  $d->duration = round($timeneed) . ":" . round(($timeneed - round($timeneed)) * 60) . ":01";

				  $d->output() = array($arrivalid);
				  if ($cont) $d->output()[] = $contid;
				  $d->org = $_SESSION["OBJ_user"]->org;
				  $this->processors[$this->processorsid++] = $d;

				  $v = NULL;
				  $pcon = $cont; $pconid = $contid;
			  }
			  $this->saved = FALSE;
		  }
		  elseif ($processType == 2 && !$liner) {	// transport
			  $liners = array(-1=>'非班线运输');
			  $ls = $GLOBALS['core']->query("SELECT DISTINCT lineid,name FROM supply_mod_liner WHERE ownerOrg={$_SESSION['OBJ_user']->org}", false);
			  while ($l = $ls->fetchRow()) $liners[$l['lineid']] = $l['name'];
			  $process .= "<tr><td>选择班线：</td><td>" . PHPWS_Form::formSelect("liner", $liners) . "自动生成。包括交通工具:" . PHPWS_Form::formCheckbox("vehicle", 1) . "</td></tr>";

			  $process .= "<tr><td align=center colspan=2>" . PHPWS_Form::formSubmit("下一步") . "</td></tr></table>";
			  $process .= PHPWS_Form::formHidden("op", "setliners");
		  }
		  else {
*/

	  if (!$processType) {
		  $process .= "<tr><td>增加任务请选择任务类型：</td><td>" . PHPWS_Form::formSelect("processType", PHPWS_Flow::$processType, NULL, false, true) . PHPWS_Form::formSubmit("下一步") . "</td></tr></table>";
		  $process .= PHPWS_Form::formHidden("op", "setprocessType");
	  }
	  else {
		  $process .= "<tr><td>任务类型：</td><td>" . PHPWS_Form::formHidden("processType", $processType) . PHPWS_Flow::$processType[$processType] . "</td></tr>";
		  if (!$taskname) {
			  $process .= "<tr><td>任务名称：</td><td>" . PHPWS_Form::formTextField("taskname", $taskname) . "</td></tr>";
			  $asignee = array(0=>'外单位', $_SESSION['OBJ_user']->org=>'本单位');
			  $as = $GLOBALS['core']->query("SELECT b.* FROM supply_mod_org as a JOIN supply_mod_org as b ON a.id={$_SESSION['OBJ_user']->org} AND (b.parent=a.id OR (b.parent=a.parent AND a.parent!=0)) AND b.id!=a.id", false);
			  while ($e = $as->fetchRow()) $asignee[$e['id']] = $e['name'];
			  $orgs = $GLOBALS['core']->sqlSelect('mod_org', 'id', $_SESSION['OBJ_user']->org);
			  $orgs = unserialize($orgs[0]['anciester']);
			  if ($orgs && is_array($orgs))
				  foreach ($orgs as &$og) $og = '<a onclick="' . "chgorg($og, 'orgs', 'org');" . '">' . orgCache($og) . "</a>";
			  $process .= "<tr><td>执行单位：</td><td><div id=orgs>" . ($orgs?implode(" => ", $orgs) : '') . "</div>" . PHPWS_Form::formSelect("org", $asignee, $_SESSION['OBJ_user']->org, false, true, "chgorg(this.options[this.selectedIndex].value, 'orgs', 'org');") . "</td></tr>";
			  $process .= "<tr><td align=center colspan=2>" . PHPWS_Form::formSubmit("下一步") . "</td></tr></table>";
			  $process .= PHPWS_Form::formHidden("op", "setprocessType") . PHPWS_Form::formHidden("stops", $stops);
		  }
		  else {
			  $process .= "<tr><td>任务名称：</td><td>" . PHPWS_Form::formHidden("taskname", $taskname) . $taskname . "</td></tr>";
			  $process .= "<tr><td>执行单位：</td><td>" . PHPWS_Form::formHidden("org", $org) . ($org?orgCache($org) : '外单位') . "</td></tr>";
			  if ($processType == 2 && !$input && !$org) {	// transport
				  $process .= "<tr><td>待运货物：</td><td>";
				  foreach ($this->goods as $i=>$g) {
					  if ($g->dest() || $g->resourceType() != 32) continue;
					  $process .= PHPWS_Form::formCheckBox("input[]", $i) . $g->name() . " | ";
				  }
				  $process .= "</td></tr>";
				  $dest = '';
				  foreach ($this->goods as $i=>$g) {
					  if ($g->dest() || $g->resourceType() != 4096) continue;
					  $dest .= PHPWS_Form::formRadio("dest", $i) . $g->name() . " | ";
				  }
				  if ($dest) $process .= "<tr><td>目的地：</td><td>" . $dest . PHPWS_Form::formRadio("dest", 0) . "不选</td></tr>";
				  $dest = '';
				  foreach ($this->goods as $i=>$g) {
					  if ($g->dest() || $g->resourceType() != 2048) continue;
					  $dest .= PHPWS_Form::formRadio("truck", $i) . $g->name() . " | ";
				  }
				  if ($dest) $process .= "<tr><td>运输工具：</td><td>" . $dest . PHPWS_Form::formRadio("truck", 0) . "不选</td></tr>";
				  $dest = '';
				  foreach ($this->goods as $i=>$g) {
					  if ($g->dest() || $g->resourceType() != 1024) continue;
					  $dest .= PHPWS_Form::formRadio("liner", $i) . $g->name() . " | ";
				  }
				  if ($dest) $process .= "<tr><td>班线：</td><td>" . $dest . PHPWS_Form::formRadio("liner", 0) . "不选</td></tr>";
				  $process .= "<tr><td align=center colspan=2>" . PHPWS_Form::formSubmit("下一步") . "</td></tr></table>";
				  $process .= PHPWS_Form::formHidden("op", "setprocessType");
			  }
			  elseif ($processType == 2 && !$org) {	// transport
				  $rd = floor($timeneed / 60); $ru = "分钟";
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
				  $process .= "<tr><td>任务耗时：</td><td>" . PHPWS_Form::formTextField("timeneed", $rd) . PHPWS_Form::formSelect("duraunit", array("分钟", "小时", "天", "周", "月"), $ru, true, false) . "</td></tr>";

				  if ($dest) $input[] = $dest;
				  if ($truck) $input[] = $truck;
				  if ($liner) $input[] = $liner;

				  $glue = "<tr><td>输入：</td><td>"; $glue2 = "<tr><td>输出：</td><td>"; $op = '';
				  if ($input) foreach ($input as $in) {
					  $process .= PHPWS_Form::formHidden("input[]", $in) . $glue . $this->goods[$in]->name();
					  if ($this->goods[$in]->resourceType() == 32) {
						  $target = NULL;
						  $process .= PHPWS_Form::formHidden("srproduct[]", $this->goods[$in]->name());
						  foreach ($this->goods as $j=>$g)
							  if ($g->name() == $this->goods[$in]->name() . ".运抵") {
								  $target = $g;
								  $op .= PHPWS_Form::formHidden("destproduct[]", $target->name()) . PHPWS_Form::formHidden("output[]", $j) . $glue2 . $target->name();
							  }
						  if (!$target) {
//							  $this->goods[$in]->SetExtra(array('correspondto'=>$this->goodsid));
							  $d = PHPWS_Node::newNode(32);
							  $d->copy($this->goods[$in]);
							  $d->SetSrc(0); $d->SetDest(0);
							  $d->SetName($d->name() . ".运抵");
							  $op .= PHPWS_Form::formHidden("destproduct[]", $d->name()) . PHPWS_Form::formHidden("output[]", $this->goodsid) . $glue2 . $d->name();
							  $this->goods[$this->goodsid++] = $d;
						  }
					  }
					  $glue2 = $glue = "，";
				  }
				  $process .= "</td></tr>" . $op;

				  $process .= "<tr><td align=center colspan=2>" . PHPWS_Form::formSubmit("提交新任务") . "</td></tr></table>";
				  $process .= PHPWS_Form::formHidden("op", "addproc");
		//		  }
			  }
			  else {
				  $rd = floor($timeneed / 60); $ru = "分钟";
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

				  $process .= "<tr><td>任务耗时：</td><td>" . PHPWS_Form::formTextField("timeneed", $rd) . PHPWS_Form::formSelect("duraunit", array("分钟", "小时", "天", "周", "月"), $ru, true, false) . "</td></tr>";

				  if ($processType != 1 && ($processType != 2 || $org)) {
						$process .= "<tr><td>输入：</td><td>";
						foreach ($this->goods as $i=>$g) {
							if ($g->dest()) continue;
							$res[$i] = $g->name();
							$process .= PHPWS_Form::formCheckBox("input[]", $i) . $g->name();
						}
						$process .= "</td></tr>";
				  }
				  if ($processType != 8) {
					  $process .= "<tr><td>输出：</td><td>";
					  foreach ($this->goods as $i=>$g) {
						  if ($g->src() || ($g->resourceType() != 32 && $g->resourceType() != 32768)) continue;
						  if (!$res[$i]) $res[$i] = $g->name();
						  $process .= PHPWS_Form::formCheckBox("output[]", $i) . $g->name();
					  }
					  $process .= "</td></tr>";
				  }

				  $hasrules = array(1=>array(), 2=>array(1), 4=>array(2, 3), 8=>array(4), 16=>array(1, 4), 64=>array(2,3,4), 512=>array(), 131072=>array());

//				  if (in_array(1, $hasrules[$processType])) $process .= "<tr><td>物品规则：</td><td name=showrules><div id=prodrule></div><a href=#a onclick='additem(6);'>增加物品规则</a></td></tr>";
				  if (in_array(2, $hasrules[$processType])) $process .= "<tr><td>物品间比例规则：</td><td name=showrules><div id=ratiorule></div><a href=#a onclick='additem(3);'>增加物品间比例规则</a></td></tr>";
		//		  if (in_array(3, $hasrules[$processType])) $process .= "<tr><td>时间规则：</td><td name=showrules><div id=timerule></div><a href=#a onclick='additem(4);'>增加时间规则</a></td></tr>";
		//		  $addrs = 0;
		//		  if ($input) foreach ($input as $in) {
		//			  if ($this->goods[$in]->resourceType == 4096) $addrs++;
		//		  }
		//		  if (in_array(4, $hasrules[$processType]) && $addrs > 1) $process .= "<tr><td>地点规则：</td><td name=showrules><div id=placerule></div><a href=#a onclick='additem(5);'>增加地点规则</a></td></tr>";
				  $process .= "<tr><td align=center colspan=2>" . PHPWS_Form::formSubmit("提交新任务") . "</td></tr></table>" . PHPWS_Form::formHidden("op", "addproc");
			  }
		  }
	  }
	  $process .= PHPWS_Form::formHidden("module", $module) . PHPWS_Form::formHidden("MOD_op", $MOD_op);
	  $process = PHPWS_Form::makeForm('addproc', $GLOBALS['SCRIPT'], array($process), "post", FALSE, TRUE);

	  $s .= $prods . $process;

	  $sv = "<center>" . PHPWS_Form::formSubmit("保存流程") . "</center>";
	  $sv .= PHPWS_Form::formHidden("module", $module) . PHPWS_Form::formHidden("MOD_op", $MOD_op) . PHPWS_Form::formHidden("op", "saveflow");
	  $sv = PHPWS_Form::makeForm('saveflow', $GLOBALS['SCRIPT'], array($sv), "post", FALSE, TRUE);

//				  if (obj > 2 && document.getElementById('org').selectedIndex == 0) alert('只有本单位的任务才可以设定规则');
/*
					  case 1:
						  document.getElementById('inputs').innerHTML += '资源" . str_replace("\n", ' ', PHPWS_Form::formSelect("input[]", $res, NULL, false, true)) . "';
						  break;
					  case 2:
						  document.getElementById('outputs').innerHTML += '资源" . str_replace("\n", ' ', PHPWS_Form::formSelect("output[]", $res, NULL, false, true)) . "';
						  break;
					  case 4:
						  document.getElementById('timerule').innerHTML += '时间之间关系" . str_replace("\n", ' ', PHPWS_Form::formSelect("desttime[]", $resall, NULL, true, false) . "时间 = " . PHPWS_Form::formSelect("srctime[]", $resall, NULL, true, false) . '之后' . PHPWS_Form::formTextField("delaytime[]", "0", 4) . PHPWS_Form::formSelect("delayunit[]", array("小时", "天", "周", "月"), NULL, false, true)) . "<br>时长之间关系" . 
						  str_replace("\n", ' ', PHPWS_Form::formSelect("destduration[]", $resall, NULL, true, false) . "时长 = " . PHPWS_Form::formSelect("srcduration[]", $resall, NULL, true, false) . '时长 + ' . PHPWS_Form::formTextField("duration[]", "0", 4) . PHPWS_Form::formSelect("durationunit[]", array("小时", "天", "周", "月"), NULL, false, true)) . "<br>';
						  break;
					  case 5:
						  document.getElementById('placerule').innerHTML += '地点之间的关系" . str_replace("\n", ' ', PHPWS_Form::formSelect("destplace[]", $resall, NULL, true, false) . "之地点 = " . PHPWS_Form::formSelect("srcplace[]", $resall, NULL, true, false)) . "之地点<br>';
						  break;
					  case 6:
						  document.getElementById('prodrule').innerHTML += '物品之间的关系" . str_replace("\n", ' ', PHPWS_Form::formSelect("destproduct[]", $res, NULL, true, false) . "之物品 = " . PHPWS_Form::formSelect("srproduct[]", $res, NULL, true, false)) . "之物品<br>';
						  break;
*/

	  $_SESSION['OBJ_layout']->extraHead("<script>
			  function additem(obj) {
				  switch (obj) {
					  case 3:
						  document.getElementById('ratiorule').innerHTML += '资源之间比例" . str_replace("\n", ' ', PHPWS_Form::formTextField("srcamt[]", "1", 3) . PHPWS_Form::formSelect("srcunit[]", $units, NULL, true, false) . PHPWS_Form::formSelect("srcratio[]", $res, NULL, true, false) . " ：" . PHPWS_Form::formTextField("destamt[]", "1", 3) . PHPWS_Form::formSelect("destunit[]", $units, NULL, true, false) . PHPWS_Form::formSelect("destratio[]", $res, NULL, true, false)) . "<br>';
						  break;
				  }
			  }

			  function showrule(obj) {
				  var el = document.getElementsByName('showrules');
				  for (var i = 0; i < el.length; i++)
					el[i].style.display = (obj.selectedIndex?'block' : 'none');
			  }

			  function resChg(obj) {
				var show;
				if (obj.options[obj.selectedIndex].text == '" . GOODS . "') show = 'block';
				else show = 'none';
				document.getElementById('showgoods').style.display = show;
			  }

			  function chgorg(orgid, dest, org) {
				$.ajax({
					url:'./{$GLOBALS['SCRIPT']}?module=accessories&MOD_op=getorgs&org=' + orgid + '&orgs=' + dest + '&sel=' + org,
					type:'get',
					dataType:'json',
					success: function(resp) {
						document.getElementById(dest).innerHTML = resp.orghead;
						var obj = document.getElementById(org);
						obj.options.length=1;
						obj.length=1;
						if (resp.orgs.length > 0) {
							for (var i = 0; i < resp.orgs.length; i++) {
								var option=document.createElement('option');
								option.text = resp.orgs[i].text;
								option.value = resp.orgs[i].value;
								obj.add(option,null);
							}
							obj.selectedIndex = 1;
						}
					}
				 });
			  }

			  function catChg(obj) {
				$.ajax({
					url:'./{$GLOBALS['SCRIPT']}?module=accessories&MOD_op=getcatspec&subcat=2&cat=' + obj.options[obj.selectedIndex].value,
					type:'get',
					dataType:'json',
					success: function(resp) {
						document.getElementById('cats').innerHTML += '/' + resp.catname;
						if (resp.cat.length == 0) {
							obj.style.display = 'none';
						}
						else {
							obj.options.length=0;
							obj.length=0;
							for (var i = 0; i < resp.cat.length; i++) {
								var option=document.createElement('option');
								option.text = resp.cat[i].text;
								option.value = resp.cat[i].value;
								obj.add(option,null);
							}
						}
						var prod = document.getElementById('product');
						prod.options.length=0;
						prod.length=0;
						if (resp.products.length > 0) {
							var option=document.createElement('option');
							option.text = '';
							option.value = 0;
							prod.add(option,null);
							for (var i = 0; i < resp.products.length; i++) {
								option=document.createElement('option');
								option.text = resp.products[i].text;
								option.value = resp.products[i].value;
								prod.add(option,null);
							}
						}
					}
				 });
			  }
		  </script>");
	  $chart = $this->flowchart();

	  return $s . $chart['content'] . $sv;
  }

  function flowchart($name = 'name') {
	  $graph = array();
	  $goods = array();
	  $tasks = array();

	  // init
	  if ($this->goods) foreach ($this->goods as $i=>$g) $goods[$i] = array('level'=>0, 'x'=>0, 'obj'=>$g);
	  if ($this->processors) foreach ($this->processors as $i=>$g) $tasks[$i] = array('level'=>0, 'x'=>0, 'obj'=>$g);

	  // set to proper level (y direction)
	  $left = array(0=>1);
	  do {
		  $domore = false;
		  if ($goods) foreach ($goods as &$g) {
			  if ($g['obj']->src() > 0 && $tasks[$g['obj']->src()]['level'] >= $g['level']) {
				  $g['level'] = $tasks[$g['obj']->src()]['level'] + 1;
				  $left[$g['level']] = 1;
				  $domore = true;
			  }
			  if ($g['obj']->dest() > 0 && $tasks[$g['obj']->dest()]['level'] <= $g['level']) {
				  $tasks[$g['obj']->dest()]['level'] = $g['level'] + 1;
				  $left[$g['level'] + 1] = 1;
				  $domore = true;
			  }
		  }
		  unset($g);
	  } while ($domore);

	  if ($goods) foreach ($goods as &$g) {
		  if ($g['obj']->dest() > 0 && $tasks[$g['obj']->dest()]['level'] > $g['level'] + 1) {
			  $g['level'] = $tasks[$g['obj']->dest()]['level'] - 1;
			  $left[$g['level']] = 1;
		  }
	  }
	  unset($g);

	  // by each level, set x position (sequential)
	  $level = 0;
	  do {
		  $domore = false;
		  $queue = array();
		  if ($tasks) foreach ($tasks as &$g) {
			  if ($g['level'] != $level || $g['x']) continue;
			  $domore = true;
			  $g['x'] = $left[$level]++;
			  $que = array($g);
			  for ($i = 0; $i < sizeof($que); $i++) {
				  $f = $que[$i];
				  if (is_subclass_of($f['obj'], 'PHPWS_ResourceNode')) {
					  if ($f['obj']->dest() > 0) {
						  if (!$tasks[$f['obj']->dest()]['x']) 
							  $tasks[$f['obj']->dest()]['x'] = $left[$tasks[$f['obj']->dest()]['level']]++;
						  $que[] = $tasks[$f['obj']->dest()];
					  }
				  }
				  elseif ($f['obj']->output()) foreach ($f['obj']->output() as $h) {
					  if (!$goods[$h]['x']) 
						  $goods[$h]['x'] = $left[$goods[$h]['level']]++;
					  $que[] = $goods[$h];
				  }
			  }
		  }
		  unset($g);

		  if ($goods) foreach ($goods as &$g) {
			  if ($g['level'] != $level || $g['x']) continue;
			  $g['x'] = $left[$level]++;
			  $que = array($g);
			  for ($i = 0; $i < sizeof($que); $i++) {
				  $f = $que[$i];
				  if (is_subclass_of($f['obj'], 'PHPWS_ResourceNode')) {
					  if ($f['obj']->dest() > 0) {
						  if (!$tasks[$f['obj']->dest()]['x']) 
							  $tasks[$f['obj']->dest()]['x'] = $left[$tasks[$f['obj']->dest()]['level']]++;
						  $que[] = $tasks[$f['obj']->dest()];
					  }
				  }
				  elseif ($f['obj']->output()) foreach ($f['obj']->output() as $h) {
					  if (!$goods[$h]['x']) 
						  $goods[$h]['x'] = $left[$goods[$h]['level']]++;
					  $que[] = $goods[$h];
				  }
			  }
		  }
		  unset($g);
	  } while ($domore || ++$level < sizeof($left));
	  $g = NULL;

/*
	  do {
		  $domore = false;
		  $x = 0;
		  if ($tasks) foreach ($tasks as &$g) {
			  if ($g['level'] != $level) continue;
			  $domore = true;
			  $g['x'] = $x + round(max(sizeof($g['obj']->input), sizeof($g['obj']->output())) / 2);
			  $j = $x;
			  if ($g['obj']->input) foreach ($g['obj']->input as $i) $goods[$i]['x'] = $j++;
			  $j = $x;
			  if ($g['obj']->output()) foreach ($g['obj']->output() as $i) $goods[$i]['x'] = $j++;
			  $x += max(sizeof($g['obj']->input), sizeof($g['obj']->output())) + 1;
		  }
		  if ($goods) foreach ($goods as &$g) 
			  if ($g['level'] == $level) $domore = true;
	  } while ($domore && ++$level);
/*
	  $map = array();
	  if ($tasks) foreach ($tasks as &$g) {
		  if (!$map[$g['level']]) $map[$g['level']] = array($g['x']=>1);
		  else $map[$g['level']][$g['x']] = 1;
	  }
	  if ($goods) foreach ($goods as &$g) {
		  if (!$map[$g['level']]) $map[$g['level']] = array($g['x']=>1);
		  else {
			  while ($map[$g['level']][$g['x']]) $g['x']++;
			  $map[$g['level']][$g['x']] = 1;
		  }
	  }
*/
	  $task2 = array();
	  $goods2 = array();
	  $taskmapping = array();

	  $x = 1;
	  do {
		  $domore = false;
		  if ($tasks) foreach ($tasks as $i=>$g) 
			  if ($g['x'] == $x) {
				$taskmapping[$i] = sizeof($task2);
				$task2[] = $g;
				$domore = true;
			  }
		  if ($goods) foreach ($goods as $g) 
			  if ($g['x'] == $x) {
				$goods2[] = $g;
				$domore = true;
			  }
	  } while ($domore && ++$x);

	  $tasks = $task2;
	  $goods = $goods2;

	  $rowheight = 36; $wordgap = 8; $wordwidth = 6; $taskgap = 30; $rowgap = 20; $taskradius = 18;
	  $cntheight = 36;

	  // actual coordinates
	  $level = 0;
	  $width = 0; $height = 0;
	  do {
		  $domore = false;
		  $w = 0;
		  if ($tasks) foreach ($tasks as &$g) {
			  if ($g['level'] != $level) continue;
			  $domore = true;
			  $g['w'] = $w + $taskgap;
			  $g['h'] = $height;
			  $wd = strlen($g['obj']->taskname()) * $wordwidth + $wordgap;
			  $w += $wd + $taskradius * 2 + $taskgap;
		  }
		  unset($g);
		  if ($goods) foreach ($goods as &$g) {
			  if ($g['level'] != $level) continue;
			  $domore = true;
			  $wd = strlen($g['obj']->option($name)) * $wordwidth + $wordgap;
			  $g['w'] = $w;
			  $g['h'] = $height;
			  $w += $wd + $wordgap;
		  }
		  unset($g);
		  $height += $rowgap + $rowheight;
		  $width = max($width, $w);
	  } while ($domore && ++$level);

	  // draw them
	  $myCanvas = 'myCanvas' . ($this->id + rand());
	  $h = '<canvas id="' . $myCanvas . '" width=' . ($width + 40) . ' height=' . $height . ' style="border:1px solid #c3c3c3;"></canvas><script type="text/javascript">
			var canvas=document.getElementById("' . $myCanvas . '");
			var cxt=canvas.getContext("2d");
			var text = null, w;
			cxt.lineWidth = 2;
			cxt.font = "14px serif";';

	  if ($tasks) foreach ($tasks as &$g) {
		  $w = $g['w']; $height = $g['h'];
		  if ($g['obj']->org() != $_SESSION["OBJ_user"]->org) $stroke = 'cxt.strokeStyle = "#FF3300";';
		  else $stroke = 'cxt.strokeStyle = "#003300";';
		  $s .= "
			cxt.beginPath();
			cxt.arc(" . ($w + $taskradius) . ", " . ($height + $taskradius) . ", $taskradius, 0, 2 * Math.PI, false);
			$stroke
			cxt.stroke();
			text = cxt.measureText('" . PHPWS_Flow::$allProcessType[$g['obj']->processType()] . "');
			cxt.fillText('" . PHPWS_Flow::$allProcessType[$g['obj']->processType()] . "', (" . ($w + $taskradius) . " - text.width/2) , " . ($height + 22) . ");
			cxt.fillText('" . $g['obj']->option($name) . "', " . ($w + $taskradius * 2 + $wordgap) . ", " . ($height + 22) . ");";
		  $wd = strlen($g['obj']->option($name)) * $wordwidth + $wordgap;
		  $w += $wd + $taskradius * 2 + $rowgap;
	  }
	  unset($g);
	  if ($goods) foreach ($goods as &$g) {
		  $w = $g['w']; $height = $g['h'];
		  $wd = strlen($g['obj']->option($name)) * $wordwidth + $wordgap;

		  $s .= "
			cxt.moveTo($w,$height);
			cxt.lineTo(" . ($w + $wd) . ", $height);
			cxt.lineTo(" . ($w + $wd) . ", " . ($height + $cntheight) . ");
			cxt.lineTo($w, " . ($height + $cntheight) . ");
			cxt.lineTo($w, $height);
		    cxt.strokeStyle = '#003300';
			cxt.stroke();
			cxt.fillText('" . $g['obj']->option($name) . "', " . ($w + $wordwidth) . ", " . ($height + 22) . ");";
		  if ($g['obj']->src() > 0) {
			  $s .= "cxt.moveTo(" . ($w + $wd/2) . ",$height);";
			  $s .= "cxt.lineTo(" . $tasks[$taskmapping[$g['obj']->src()]]['w'] . " + $taskradius, " . $tasks[$taskmapping[$g['obj']->src()]]['h'] . " + $cntheight); cxt.stroke();";
		  }
		  if ($g['obj']->dest() > 0) {
			  $s .= "cxt.moveTo(" . ($w + $wd/2) . ",$height + $cntheight);";
			  $s .= "cxt.lineTo(" . $tasks[$taskmapping[$g['obj']->dest()]]['w'] . " + $taskradius, " . $tasks[$taskmapping[$g['obj']->dest()]]['h'] . "); cxt.stroke();";
		  }
		  $w += $wd + $rowgap;
	  }
	  unset($g);

	  return array('width'=>$width, 'content'=>$h . $s . "</script>");
  }

  function fullFlowchart($option) {
	  $fls = new PHPWS_Flow();

	  $fls->copy($this);
	  $gmap = array();

	  $loadedflow = array($fls->id);

	  $tq = array();
	  $tbp = array();

	  foreach ($fls->goods as $i=>$f) $gmap[-$f->id()] = $i;

	  do {
		  $update = false;
//		  foreach ($fls->goods as $i=>$g) {
		  foreach ($fls->goods as $i=>&$g) {
			  if ($g->src() < 0)
//				  if (isset($gmap[$g->src()])) $fls->goods[$i]->src() = $fls->goods[$gmap[$g->src()]]->src();
				  if (isset($gmap[$g->src()])) $g->SetSrc($fls->goods[$gmap[$g->src()]]->src());
				  elseif (!in_array($g->src(), $tq)) {
					  $tq[] = $update = $g->src();
					  $tbp[] = $update;
				  }
			  if ($g->dest() < 0)
//				  if (isset($gmap[$g->dest()]))  $fls->goods[$i]->dest() = $fls->goods[$gmap[$g->dest()]]->dest();
				  if (isset($gmap[$g->dest()]))  $g->SetDest($fls->goods[$gmap[$g->dest()]]->dest());
				  elseif (!in_array($g->dest(), $tq)) {
					  $tq[] = $update = $g->dest();
					  $tbp[] = $update;
				  }
		  }
		  unset($g);

		  if ($tbp) {
			  list(,$update) = each($tbp);
			  $tbp = array_diff($tbp, array($update));
		  }

		  if ($update) {
			  $node = $GLOBALS['core']->sqlSelect($this->flowTable, 'id', -$update);
//			  if (in_array($node[0]['flowid'], $loadedflow)) break;
			  $loadedflow[] = $node[0]['flowid'];
			  $tflow = new PHPWS_Flow();
			  $tflow->loadflow($node[0]['flowid']);

//			  foreach ($tflow->processors as $i=>$f) {
//				  if ($f->input) foreach ($f->input as $j=>$inid) $tflow->processors[$i]->input[$j] += $fls->goodsid;
//				  if ($f->output()) foreach ($f->output() as $j=>$outid) $tflow->processors[$i]->output()[$j] += $fls->goodsid;
			  foreach ($tflow->processors as $i=>&$f) {
				  if ($f->input()) foreach ($f->input() as $j=>$inid) $f->input()[$j] += $fls->goodsid;
				  if ($f->output()) foreach ($f->output() as $j=>$outid) $f->output()[$j] += $fls->goodsid;
			  }
			  unset($f);

			  foreach ($tflow->goods as $i=>$f) {
				  $newnd = NULL;
				  assert(!($f->src() < 0 && $f->dest() < 0));
				  if ($f->src() < 0 || $f->dest() < 0) {
					  if ($f->src() < 0 && isset($gmap[$f->src()])) {
						  assert($f->dest() >= 0);
						  if ($f->dest()) {
							  $fls->goods[$gmap[$f->src()]]->SetName($fls->goods[$gmap[$f->src()]]->name() . " - " . $f->name());
							  $newnd = $fls->goods[$gmap[$f->src()]]->SetDest($f->dest() + $fls->processorsid);
							  $tflow->processors[$f->dest()]->SetInput(array_diff($tflow->processors[$f->dest()]->input(), array($i + $fls->goodsid)));
//							  $tflow->processors[$f->dest()]->input[] = $newnd;
						  }
						  else $newnd = $fls->goods[$gmap[$f->src()]]->SetDest(0);
					  }
					  if ($f->dest() < 0 && isset($gmap[$f->dest()])) {
						  assert($f->src() >= 0);
						  if ($f->src()) {
							  $fls->goods[$gmap[$f->dest()]]->SetName($fls->goods[$gmap[$f->dest()]]->name() . " - " . $f->name());
							  $newnd = $fls->goods[$gmap[$f->dest()]]->SetSrc($f->src() + $fls->processorsid);
							  $tflow->processors[$f->src()]->SetOutput(array_diff($tflow->processors[$f->src()]->output(), array($i + $fls->goodsid)));
//							  $tflow->processors[$f->src()]->output()[] = $newnd;
						  }
						  else $newnd = $fls->goods[$gmap[$f->dest()]]->SetSrc(0);
					  }
				  }
				  if ($newnd === NULL) {
					  if ($f->src() > 0) $f->SetSrc($f->src() + $fls->processorsid);
					  if ($f->dest() > 0) $f->SetDest($f->dest() + $fls->processorsid);
					  $gmap[-$f->id()] = $i + $fls->goodsid;
					  $fls->goods[$i + $fls->goodsid] = $f;
				  }
			  }
			  foreach ($tflow->processors as $i=>$f) {
				  $fls->processors[$i + $fls->processorsid] = $f;
			  }
			  $fls->goodsid += $tflow->goodsid;
			  $fls->processorsid += $tflow->processorsid;
		  }
	  } while ($update);

	  return $fls->flowchart($option);
  }

  function viewformat($defpar = false) {
	  extract($_REQUEST);
	  extract($_POST);

	  $table = array(32=>array(array('mod_product', 'id', 'product'), array('mod_category', 'id', 'category')), 1024=>array(array('mod_liner', 'lineid', 'product')), 2048=>array(array('mod_vehicle', 'id', 'vehicle')), 4096=>array(array('mod_locations', 'id', 'place')), 32768=>array(array('mod_wharehouse', 'id', 'wharehouse')));

	  $gap = $GLOBALS['WXMODE']?'<br>' : ' | ';
	  $prods = "资源<hr>";
	  $res = array();

	  if ($viewonly) $defpar = false;

	  if (sizeof($this->goods)) {
		  $prods .= "<table cellspacing=3 cellpadding=3 border=1><tr><th>资源</th><th>值</th><th>来源</th><th>去向</th></tr>";
		  $h = "<a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=setresparam&viewonly=" . ($defpar?0: 1) . "&itemid=";
		  foreach ($this->goods as $i=>$g) {
			  $res[$i] = $i;

			  $tb = $table[$g->resourceType()];
			  $good = NULL;
			  if ($tb) foreach ($tb as $tn)
				  if (!$good) {
						$m = $tn[2];
						$good = $GLOBALS['core']->sqlSelect($tn[0], $tn[1], $g->$m);
				  }

			  if ($good) $good = $good[0]['name'];

			  $prods .= "<tr><td>" . $h . $i . ">" . $g->ccname() . "</a>" . "</td><td>" . $good . "</td><td>" . ($g->src()>0?$this->processors[$g->src()]->taskname() : ($g->src()<0?'其它流程':'')) . "</td><td>" . ($g->dest()>0?$this->processors[$g->dest()]->taskname() : ($g->dest()<0?'其它流程':'')) . "</td>";

			  $extra = ''; $glue = '<td>';
/*
			  if ($defpar && $g->multiple() && $g->status() > 0 && ($g->status() & 0xF) < 8) {
				  $extra .= $glue . "<a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=addresource&itemid=$i>添加此类资源</a>";
				  $glue = $gap;
			  }
			  if ($defpar && $g->resourceType() == 32 && $g->product() && $g->quantity()) {
				  if ($g->dest() == 0) {
					  $extra .= $glue . "<a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=pubresource&itemid=$i>发布产品供应消息</a>";
					  $glue = $gap;
				  }
				  if ($g->src() == 0 && !$g->start() && $g->status() > 0 && ($g->status() & 0xF) < 8) {
					  $extra .= $glue . "<a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=pubresource&itemid=$i>发布产品需求消息</a>";
					  $glue = $gap;
				  }
			  }
*/
			  if ($extra) $prods .= $extra . "</td>";

			  $prods .= "</tr>";
		  }
		  $prods .= "</table>";
//		  if ($defpar)
//			  $prods .= "<a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=connectFlow>连接其它工作流程</a>";
	  }

	  $process = "任务环节<hr>";
	  if (sizeof($this->processors)) {
		  $process .= "<table cellspacing=3 cellpadding=3 border=1><tr><th>任务</th><th>类型</th><th>执行单位</th><th>执行地</th><th>用时</th></tr>";

		  $h = "<a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=settaskparam&viewonly=" . ($defpar?0: 1) . "&itemid=";

		  $canserve = false;
		  foreach ($this->processors as $j=>$g) {
			  $in = locCache($g->place());
			  $in = $in['name'];

			  $org = orgCache($g->org());

			  $process .= "<tr><td>" . $h . $j . ">" . $g->ccname() . "</a>" . "</td><td>" . PHPWS_Flow::$allProcessType[$g->processType()] . "</td><td>" . $org . "</td><td>" . $in . "</td><td>" . floor($g->duration()/60) . "分</td>";

			  if ($defpar) {
				  $process .= "<td>";
/*
				  if ($g->status() > 0 && ($g->status() & 6))
					  $process .= "<a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=executetask&itemid=$j>执行</a>$gap";
				  elseif (($g->status() & 0xF) == 8) 
					  $process .= "<a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=executetask&itemid=$j>执行</a>$gap<a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=taskcomplete&itemid=$j>工作完成</a>$gap";
*/

				  if ($g->status() > 0 && ($g->status() & 0xF) < 8) {
//					  if (!in_array($g->processType(), array(512, 131072, 8)))
//						  $process .= "<a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=pubtask&itemid=$j>发布服务需求消息</a>$gap";
				  }
				  elseif ($g->org() == $_SESSION["OBJ_user"]->org && $g->status() > 0 && ($g->status() & 0xF) < 8) {}
//					  $canserve = true;
				  elseif ($g->status() == -1) $process .= "已完成";
				  elseif ($g->status() == -2) $process .= "已取消";
//				  if ($g->status() > 0) $process .= "<a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=deltask&itemid=$j>删除任务</a>";
				  $process .= "</td>";
			  }

			  $process .= "</tr>";

			  if (($g->status() & 0xF) < 4 && $g->input()) {
				  $wait = array();
				  foreach ($g->input() as $i)
					  if (($this->goods[$i]->status() & 0xF) < 4) $wait[] = $this->goods[$i]->ccname();
				  if ($wait) $process .= "<tr><td></td><td colspan=5>等待" . implode("，", $wait) . "</td></tr>";
			  }
		  }
		  $process .= "</table>";
//		  if (($this->status() & 0xF) < 8)
//			  $process .= "<a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=pubflow>发布服务消息</a><br>";
//		  if ($defpar)
//			  $process .= "<a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=tasksubstitute>代入其它工作流程</a>";
	  }
/*
	  elseif ($defpar && $this->status() == 3 && sizeof($this->goods) == 1) {
		  $process = "接入我的其它工作流程<hr>";

		  $flow = $GLOBALS['core']->query("SELECT * FROM {$this->flowTable} WHERE flowname is not NULL AND resourceType & 32 AND status>0 AND org={$_SESSION['OBJ_user']->org} AND flowid!={$this->id}", true);
		  $fls = array();
		  $fn = array('');

		  foreach($this->goods as $g) $n = $g;

		  while ($n->src() + $n->dest() < 0 && ($f = $flow->fetchRow())) {
			  $fl = new PHPWS_Flow();
			  $fl->loadflow($f['flowid']);
			  foreach ($fl->goods as $i=>$p) {
				  if ($p->org() != $_SESSION['OBJ_user']->org || $p->resourceType() != 32) continue;
				  if ($p->src() && $p->dest()) continue;
				  if ($n->dest() && $p->dest()) continue;
				  if ($n->src() && $p->src()) continue;
				  if ($p->product() && !$n->sameProduct($p)) continue;
				  if (!$p->product() && $p->category()) {
					  $ct = $GLOBALS['core']->sqlSelect('mod_product', 'id', $n->product());
					  $ct = $ct[0];
					  while ($ct['category'] && $ct['category'] != $p->category()) {
						  $ct = $GLOBALS['core']->sqlSelect('mod_category', 'id', $ct[0]['category']);
						  $ct = $ct[0];
						  $ct['category'] = $ct['parentid'];
					  }
					  if (!$ct['category']) continue;
				  }

//				  if ($p->connects) continue;

				  if (!$fls[$f['flowid']]) {
						$fls[$f['flowid']] = array('name'=>$fl->name, 'tasks'=>array());
						$fn[$f['flowid']] = $fl->name;
				  }
				  $fls[$f['flowid']]['tasks'][] = array('value'=>$p->id(), 'text'=>$p->name());
			 }
		 }
		 if ($fls) {
			 $this->matched = $fls;

			 $s .= "接入的任务：" . PHPWS_Form::formSelect("task", $fn, NULL, false, true, "chgTask(this);") . PHPWS_Form::formSelect("seldtask", array(), NULL, true, false);

			 $s .= PHPWS_Form::formSubmit("接入");
			 $s .= PHPWS_Form::formHidden("module", 'work') . PHPWS_Form::formHidden("MOD_op", 'viewworkflow') . PHPWS_Form::formHidden("op", "connectin") . PHPWS_Form::formHidden("flowid", $this->flowid);

			 $process = PHPWS_Form::makeForm('savework', $GLOBALS['SCRIPT'], array($s), "post", FALSE, TRUE);

			 $_SESSION['OBJ_layout']->extraHead("<script>
				  function chgTask(obj) {
					$.ajax({
						url:'./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=gettask&task=' + obj.options[obj.selectedIndex].value,
						type:'get',
						dataType:'json',
						success: function(resp) {
							var cts = document.getElementById('seldtask');
							cts.options.length=1;
							cts.length=1;
							for (var i = 0; i < resp.processor.length; i++) {
								var option=document.createElement('option');
								option.text = resp.processor[i].text;
								option.value = resp.processor[i].value;
								cts.add(option,null);
							}
						}
					 });
				  }
			  </script>");
		 }
		 else $process = "没有其它工作流程可以接入，您需要创建新的工作来处理与其它地方交接的货物。<hr>";
	  }
*/

	  return array('resource'=>$prods, 'process'=>$process);
  }

  function gettask() {
	  extract($_REQUEST);

	  $s = array('processor'=>array());
	  $s['processor'] = $this->matched[$task]['tasks'];

	  exit(json_encode($s));
  }

  function taskcomplete($itemid = NULL) {
	  if (!$itemid) extract($_REQUEST);

	  if ($this->processors[$itemid]->output()) foreach ($this->processors[$itemid]->output() as $i)
		  if (!$this->goods[$i]->product() || !$this->goods[$i]->quantity() || !$this->goods[$i]->unit())
			return "<span style='color:red'>产出货物描述不完整，需要名称、数量、计量单位。</span>" . $this->viewflow(true);

	  $this->processors[$itemid]->SetEnd(date("Y-m-d H:i:s"));
	  $this->processors[$itemid]->setStatus($this, -1);
	  $this->saved = false;

	  $this->saveflow(false);
	  return $this->viewflow(true);
  }

  function executetask($itemid = NULL) {
	  if (!$itemid) {
		  extract($_REQUEST);
		  extract($_POST);
	  }

	  if (!$this->processors[$itemid]->ready()) return "工作条件不具备。";

	  if (($this->status() & 0xF) < 8) {
		  $this->processors[$itemid]->setStatus($this, 8);

		  $this->processors[$itemid]->SetStart(date("Y-m-d H:i:s"));
		  $chg = array();
		  $this->processors[$itemid]->evaluateBeginTimeChanges($this, $chg, NULL, 1);
		  if ($this->processors[$itemid]->place() && $this->processors[$itemid]->output())
			  foreach ($this->processors[$itemid]->output() as $i)
				  if (!$this->goods[$i]->place()) {
						$this->goods[$i]->SetPlace($this->processors[$itemid]->place());
						if ($this->goods[$i]->dest() > 0) 
							$this->processors[$this->goods[$i]->dest()]->evaluatePlaceChanges($this, $this->goods[$i], 1);
				  }

		  $this->saveflow(false);
	  }

	  $s = $this->processors[$itemid]->execute($this);

	  if ($s) {
		  $s .= PHPWS_Form::formHidden("module", $module) . PHPWS_Form::formHidden("itemid", $itemid) . PHPWS_Form::formHidden("MOD_op", $MOD_op);
		  $s = PHPWS_Form::makeForm('addProduct', $GLOBALS['SCRIPT'], array($s), "post", FALSE, TRUE);
		  return $s;
	  }
	  return $this->viewflow(true);
  }

  function savework() {
	  $this->saveflow(false);
	  return $this->viewflow(true);
  }

  function viewflow($defpar = false) {
	  $viewoption = 'name';
	  extract($_REQUEST);

	  if ($id) $this->load_flow($id);

	  $res = $this->viewformat($defpar);

	  $sv = "<table><tr><td>";
	  if ($defpar) {
		  $ss = PHPWS_Form::formSubmit("刷新");
		  $ss .= PHPWS_Form::formHidden("module", $module) . PHPWS_Form::formHidden("MOD_op", 'viewflow') . PHPWS_Form::formHidden("defpar", 1);
		  $sv .= PHPWS_Form::makeForm('refreshview', $GLOBALS['SCRIPT'], array($ss), "post", FALSE, TRUE) . "</td>";
	  }

	  if (!$this->saved) {
		  $sv .= "<td width=40px></td><td>";
//		  $ss = PHPWS_Form::formSubmit("保存");
		  $ss .= PHPWS_Form::formHidden("module", $module) . PHPWS_Form::formHidden("MOD_op", 'savework') . PHPWS_Form::formHidden("op", 'savework');
		  $sv .= PHPWS_Form::makeForm('savework', $GLOBALS['SCRIPT'], array($ss), "post", FALSE, TRUE) . "</td>";
	  }
	  $sv .= "</tr></table>";
	  $sv .= "状态：" . PHPWS_Flow::$flowstatus[$this->status & 0xF];

 // echo "<pre>"; print_r($this); echo "</pre>";
	  $chart = $full?$this->fullFlowchart($viewoption) : $this->flowchart($viewoption);

	  if ($chart['width'] > 500 || $GLOBALS['WXMODE'])
		  return "<table><tr><th colspan=2>" . $this->name . "</th></tr><tr><td align=center>" . $res['process'] . "</td></tr><tr><td align=center>" . $res['resource'] . "</td></tr><tr><td align=center>" . $sv . "</td></tr><tr><td rowspan=3 align=center>" . $chart['content'] . "<br><a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewflow&viewoption=name" . ($full?">局部" : "&full=1>全局") . "图</a><br><a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewflow&viewoption=place&full=$full>地点图</a> | <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewflow&viewoption=start&full=$full>时间图</a> | <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewflow&viewoption=product&full=$full>产品图</a> | <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewflow&viewoption=quantity&full=$full>数量图</a> | <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewflow&viewoption=cost&full=$full>成本图</a> | <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewflow&viewoption=status&full=$full>状态图</a> | <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewflow&viewoption=org&full=$full>单位图</a> | <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewflow&viewoption=id&full=$full>流水图</a></td></tr></table>";
	  else return "<table><tr><th colspan=2>" . $this->name . "</th></tr><tr><td align=center>" . $res['process'] . "</td><td rowspan=3 align=center>" . $chart['content'] . "<br><a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewflow&viewoption=name" . ($full?">局部" : "&full=1>全局") . "图</a><br><a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewflow&viewoption=place&full=$full>地点图</a> | <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewflow&viewoption=start&full=$full>时间图</a> | <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewflow&viewoption=product&full=$full>产品图</a> | <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewflow&viewoption=quantity&full=$full>数量图</a> | <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewflow&viewoption=cost&full=$full>成本图</a> | <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewflow&viewoption=status&full=$full>状态图</a> | <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewflow&viewoption=org&full=$full>单位图</a> | <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewflow&viewoption=id&full=$full>流水图</a></td></tr><tr><td align=center>" . $res['resource'] . "</td></tr><tr><td align=center>" . $sv . "</td></tr></table>";
  }

  function selectFlow() {
	  extract($_REQUEST);
	  extract($_POST);

	  if ($op == 'selected') {
		  $this->altFlow = new PHPWS_Flow();
		  $this->altFlow->loadflow($id);
		  return;
	  }

	  if ($op == 'preview') {
		  $alt = new PHPWS_Flow();
		  $alt->loadflow($id);
		  $chart = $this->flowchart();
		  $altchart = $alt->flowchart();
		  $s = "<table width=100%><tr><th align=center>当前流程</th><th align=center>待选流程</th></tr><tr><td>" . $chart['content'] . "</td><td>" . $altchart['content'] . "</td></tr>";
		  
		  $res1 = $this->viewformat();
		  $res2 = $alt->viewformat();

		  $s .= "<tr><td>" . $res1['resource'] . $res1['process'] . "</td><td>" . $res2['resource'] . $res2['process'] . "</td></tr>";

		  $s .= "<tr><td colspan=2 align=center>";
		  $ss = PHPWS_Form::formSubmit("选中");
		  $ss .= PHPWS_Form::formHidden("module", $module) . PHPWS_Form::formHidden("MOD_op", $MOD_op) . PHPWS_Form::formHidden('op', "selected") . PHPWS_Form::formHidden("id", $id);
		  $s .= PHPWS_Form::makeForm('refreshview', $GLOBALS['SCRIPT'], array($ss), "post", FALSE, TRUE) . "</td>";
		  $s .= "</tr></table>";
		  return $s;
	  }

	  $fls = $GLOBALS['core']->query("SELECT * FROM {$this->flowTable} WHERE flowid!={$this->id} AND flowname is not NULL AND status&0xF=1", TRUE);
	  $cnd = array();
	  $s = "<table><tr><th>流程名称</th><th>创建日期</th><th>操作</th></tr>";
	  while ($c = $fls->fetchRow()) {
		  $s .= "<tr><td>{$c['flowname']}</td><td>{$c['created']}</td><td><a target=_blank href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=$MOD_op&op=preview&id={$c['flowid']}>查看</a></td></tr>";
	  }
	  $s .= "</table>";
	  return $s;
  }

  function checkLoop($left, $right) {
	  $leftmap = array();
	  $rightmap = array();

	  $leftflows = array($left->id=>$left);
	  $rightflows = array($right->id=>$right);

	  foreach ($left->goods as $f) $leftmap[-$f->id()] = 1;
	  foreach ($right->goods as $f) $rightmap[-$f->id()] = 1;

	  do {
		  $update = false;
		  if (list ($lid, $l) = each($leftflows)) foreach ($l->goods as $g) {
			  if ($g->src() < 0 && !isset($leftmap[$g->src()])) {
				  if (isset($rightmap[$g->src()])) return true;	// there is a loop
				  $leftmap[$g->src()] = 1;

				  $node = $GLOBALS['core']->sqlSelect($this->flowTable, 'id', -$g->src());
				  if (isset($rightflows[$node[0]['flowid']])) return true;	// there is a loop
				  if (!isset($leftflows[$node[0]['flowid']])) {
					  $tflow = new PHPWS_Flow();
					  $tflow->loadflow($node[0]['flowid']);
					  $leftflows[$node[0]['flowid']] = $tflow;
					  $update = true;
					  foreach ($tflow->goods as $f) $leftmap[-$f->id()] = 1;
				  }
			  }
		  }

		  if (list ($rid, $r) = each($rightflows)) foreach ($r->goods as $g) {
			  if ($g->dest() < 0 && !isset($rightmap[$g->dest()])) {
				  if (isset($leftmap[$g->dest()])) return true;	// there is a loop
				  $rightmap[$g->dest()] = 1;

				  $node = $GLOBALS['core']->sqlSelect($this->flowTable, 'id', -$g->dest());
				  if (isset($leftflows[$node[0]['flowid']])) return true;	// there is a loop
				  if (!isset($rightflows[$node[0]['flowid']])) {
					  $tflow = new PHPWS_Flow();
					  $tflow->loadflow($node[0]['flowid']);
					  $rightflows[$node[0]['flowid']] = $tflow;
					  $update = true;
					  foreach ($tflow->goods as $f) $rightmap[-$f->id()] = 1;
				  }
			  }
		  }
	  } while ($update);

	  return false;
  }

  function connect($src, $dest, $srcres, $destres) {
	  foreach ($srcres as $i=>$s) {
		  $d = $destres[$i];
		  if (!$s || !$d) continue;
		  // check if the nodes matches: same type, params one includes another, copy data from more specific one to over generic one
		  $src->goods[$s]->SetDest(- $dest->goods[$d]->id());
		  $dest->goods[$d]->SetSrc(- $src->goods[$s]->id());
		  $src->saved = false;
		  $dest->saved = false;
	  }
  }

  function connectFlow() {
	  if (!$this->altFlow) $s = $this->selectFlow();
	  if (!$this->altFlow) return $s;

	  extract($_REQUEST);
	  extract($_POST);

	  if ($op == 'connect') {
		  $sum1 = 0;
		  if ($out2 && $in1) {
			  foreach ($out2 as $d) $sum1 += $d;
			  foreach ($in1 as $d) $sum1 += $d;
		  }
		  $sum2 = 0;
		  if ($out1 && $in2) {
			  foreach ($out1 as $d) $sum2 += $d;
			  foreach ($in2 as $d) $sum2 += $d;
		  }
		  
		  $this->saveflow(false);

		  if ($sum1 * $sum2 != 0) $s = "<span style='color:red'>循环连接</span>";
		  elseif ($sum1) {
			  // check if there is global loop
			  if ($this->checkLoop($this->altFlow, $this)) $s = "<span style='color:red'>循环连接</span>";
			  else $this->connect($this->altFlow, $this, $out2, $in1);
		  }
		  elseif ($sum2) {
			  // check if there is global loop
			  if ($this->checkLoop($this, $this->altFlow)) $s = "<span style='color:red'>循环连接</span>";
			  else $this->connect($this, $this->altFlow, $out1, $in2);
		  }
		  $this->saveflow(false);
		  $this->altFlow->saveflow(false);

		  $chart = $this->fullFlowchart();
		  return $chart['content'];
	  }

	  $chart = $this->flowchart();
	  $altchart = $this->altFlow->flowchart();

	  $s .= "<table width=100%><tr><th align=center>" . $this->name . "</th><th align=center>" . $this->altFlow->name . "</th></tr><tr><td>" . $chart['content'] . "</td><td>" . $altchart['content'] . "</td></tr>";
		  
	  $res1 = $this->viewformat();
	  $res2 = $this->altFlow->viewformat();

	  $s .= "<tr><td>" . $res1['resource'] . "</td><td>" . $res2['resource'] . "</td></tr>";

	  $out1 = array(0=>''); $in1 = array(0=>'');
	  $out2 = array(0=>''); $in2 = array(0=>'');

	  foreach ($this->goods as $i=>$g) {
		  if (!$g->dest()) $out1[$i] = $g->name();
		  elseif (!$g->src()) $in1[$i] = $g->name();
	  }
	  foreach ($this->altFlow->goods as $i=>$g) {
		  if (!$g->dest()) $out2[$i] = $g->name();
		  elseif (!$g->src()) $in2[$i] = $g->name();
	  }

	  $s .= "<tr><td align=center>==&gt;&gt;</td><td align=center>&lt;&lt;==</td></tr>";
	  $s .= "<tr><td>"; $g = '';

	  $sz = min(sizeof($out1), sizeof($in2));
	  $sel = PHPWS_Form::formSelect("out1[]", $out1, NULL, false, true) . PHPWS_Form::formSelect("in2[]", $in2, NULL, false, true);
	  for ($i = 0; $i < $sz; $i++) {
		  $s .= $g . $sel; $g = '<br>';
	  }
	  
	  $s .= "</td><td>";
	  $sz = min(sizeof($out2), sizeof($in1));
	  $sel = PHPWS_Form::formSelect("out2[]", $out2, NULL, false, true) . PHPWS_Form::formSelect("in1[]", $in1, NULL, false, true);
	  for ($i = 0; $i < $sz; $i++) {
		  $s .= $g . $sel; $g = '<br>';
	  }
	  
	  $s .= "</td></tr>";

	  $s .= "<tr><td colspan=2 align=center>";
	  $s .= PHPWS_Form::formSubmit("连结");
	  $s .= PHPWS_Form::formHidden("module", $module) . PHPWS_Form::formHidden("MOD_op", $MOD_op) . PHPWS_Form::formHidden("op", 'connect');
	  $s .= "</td></tr></table>";
	  return PHPWS_Form::makeForm('refreshview', $GLOBALS['SCRIPT'], array($s), "post", FALSE, TRUE);
  }

  function tasksubstitute() {
	  if (!$this->altFlow) $s = $this->selectFlow();
	  if (!$this->altFlow) return $s;

	  extract($_REQUEST);
	  extract($_POST);

	  if ($op == 'substitute') {
		  $inin2 = array();
		  $inin1 = array();
		  foreach ($in2 as $i=>$d) {
			  if (!$in1[$i]) $err = "<span style='color:red'>必须连接所有被替代任务的输入参数</span>";
			  else {
				  $inin1[$in1[$i]] = $in1[$i];
				  $inin2[$d] = $d;
			  }
		  }
		  if (sizeof($inin2) != sizeof($in2) || sizeof($inin1) != sizeof($in2))
			  $err = "<span style='color:red'>必须连接所有被替代任务的输入参数</span>";

		  $outout2 = array();
		  $outout1 = array();
		  foreach ($out2 as $i=>$d) {
			  if (!$out1[$i]) $err = "<span style='color:red'>必须连接所有被替代任务的输出参数</span>";
			  else {
				  $outout1[$out1[$i]] = $out1[$i];
				  $outout2[$d] = $d;
			  }
		  }
		  if (sizeof($outout2) != sizeof($out2) || sizeof($outout1) != sizeof($out2))
			  $err = "<span style='color:red'>必须连接所有被替代任务的输出参数</span>";

		  $this->saveflow(false);
		  $this->altFlow->process[$seldtask]->SetRefflow($this->id);

		  $this->connect($this->altFlow, $this, $in2, $in1);
		  $this->connect($this, $this->altFlow, $out1, $out2);

		  $this->saveflow(false);
		  $this->altFlow->saveflow(false);

		  $chart = $this->fullFlowchart();
		  return $chart['content'];
	  }

	  $chart = $this->flowchart();
	  $altchart = $this->altFlow->flowchart();

	  $s .= "<table width=100%><tr><th align=center>" . $this->name . "</th><th align=center>" . $this->altFlow->name . "</th></tr><tr><td>" . $chart['content'] . "</td><td>" . $altchart['content'] . "</td></tr>";

	  if (!$seldtask) {
		  $s .= "<tr><td colspan=2>选择" . $this->altFlow->name . "中的一项任务予以替代</td></tr>";
		  $s .= "<tr><td colspan=2>";

		  $s .= "<table cellspacing=3 cellpadding=3 border=1><tr><th>名称</th><th>任务</th><th>输入</th><th>输出</th></tr>";

		  $h = "<a target=_blank href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=$MOD_op&seldtask=";

		  foreach ($this->altFlow->processors as $j=>$g) {
			  $in = ''; $glue = '';
			  if ($g->input()) foreach ($g->input() as $i) {
				  $in .= $glue . $this->altFlow->goods[$i]->name();
				  $glue = "<br>";
			  }
			  $out = ''; $glue = '';
			  if ($g->output()) foreach ($g->output() as $i) {
				  $out .= $glue . $this->altFlow->goods[$i]->name();
				  $glue = "<br>";
			  }
			  $s .= "<tr><td>" . $h . $j . ">" . $g->taskname() . "</a>" . "</td><td>" . PHPWS_Flow::$allProcessType[$g->processType()] . "</td><td>" . $in . "</td><td>" . $out . "</td>";
			  
			  $s .= "</tr>";
		  }
		  $s .= "</table>";
		  $s .= "</td></tr></table>";

		  return $s;
	  }

	  $res1 = $this->viewformat();
	  $res2 = $this->altFlow->viewformat();

	  $s .= "<tr><td>" . $res1['resource'] . "</td><td>" . $res2['resource'] . "</td></tr>";

	  $out1 = array(0=>''); $in1 = array(0=>'');
	  $out2 = array(); $in2 = array();

	  foreach ($this->goods as $i=>$g) {
		  if (!$g->dest()) $out1[$i] = $g->name();
		  if (!$g->src()) $in1[$i] = $g->name();
	  }

	  foreach ($this->altFlow->processors[$seldtask]->input() as $i) {
		  $in2[$i] = $this->altFlow->goods[$i]->name();
	  }
	  foreach ($this->altFlow->processors[$seldtask]->output() as $i) {
		  $out2[$i] = $this->altFlow->goods[$i]->name();
	  }

	  if (sizeof($in1) < sizeof($in2)) $err = "<span style='color:red'>可供连接的输入参数不足</span>";
	  if (sizeof($out1) < sizeof($out2)) $err = "<span style='color:red'>可供连接的输出参数不足</span>";

	  $s .= "<tr><td align=center>==&gt;&gt;</td><td align=center>&lt;&lt;==</td></tr>";
	  $s .= "<tr><td>"; $g = '';

	  $sel = PHPWS_Form::formSelect("in1[]", $in1, NULL, false, true);
	  foreach ($in2 as $i=>$lbl) {
		  $s .= $g . $lbl . PHPWS_Form::formHidden("in2[]", $i) . $sel; $g = '<br>';
	  }
	  
	  $s .= "</td><td>";
	  $sel = PHPWS_Form::formSelect("out1[]", $out1, NULL, false, true);
	  foreach ($out2 as $i=>$lbl) {
		  $s .= $g . $lbl . PHPWS_Form::formHidden("out2[]", $i) . $sel; $g = '<br>';
	  }

	  $s .= "</td></tr>";

	  if ($err) return $err . $s . "</table>";

	  $s .= "<tr><td colspan=2 align=center>";
	  $s .= PHPWS_Form::formSubmit("替代");
	  $s .= PHPWS_Form::formHidden("module", $module) . PHPWS_Form::formHidden("MOD_op", $MOD_op) . PHPWS_Form::formHidden("seldtask", $seldtask) . PHPWS_Form::formHidden("op", 'substitute') . PHPWS_Form::formHidden("id", $id);
	  $s .= "</td></tr></table>";
	  return PHPWS_Form::makeForm('refreshview', $GLOBALS['SCRIPT'], array($s), "post", FALSE, TRUE);
  }

  function deltask() {
	  extract($_REQUEST);
	  $obj = $this->processors[$itemid];
	  
	  if ($obj->org() != $_SESSION["OBJ_user"]->org) return "<span style='color:red'>您没有删除此任务的权限</span>";

	  return $this->viewflow(true);
  }

  function addresource() {
	  extract($_REQUEST);

	  $obj = PHPWS_Node::newNode($this->goods[$itemid]->resourceType());
	  $obj->copy($this->goods[$itemid]);
/*
	  $auth = false;
	  if ($obj->org() == $_SESSION["OBJ_user"]->org) $auth = true;
	  if ($obj->src && $this->processors[$obj->src]->org() == $_SESSION["OBJ_user"]->org) $auth = true;
	  if ($obj->dest && $this->processors[$obj->dest]->org() == $_SESSION["OBJ_user"]->org) $auth = true;
	  if (!$auth) return "<span style='color:red'>您没有权限</span>";
*/

	  $obj->SetMultiple(0);
	  $obj->SetRefflow(0);
	  $obj->ChgStatus(0x11111111);

	  if ($obj->src()) $this->processors[$obj->src()]->output()[] = $this->goodsid;
	  if ($obj->dest()) $this->processors[$obj->dest()]->input()[] = $this->goodsid;
	  $obj->SetId(0);

	  $this->goods[$this->goodsid++] = $obj;

	  $this->saved = false;

	  return $this->viewflow(true);
  }

  function delivery($type, $task, $obj) {
	  if ($type == 'deliver') $check = 'flowsupplynode';
	  else $check = 'flowconsumenode';
	  
	  $sup = $GLOBALS['core']->query("SELECT * FROM mod_bids WHERE ($check=" . $obj->id() . " OR $check=-" . $task->id() . ")", true);
	  $sup = $sup->fetchRow();
	  if (!$sup) return;
	  if ($sup[$check] == $obj->id()) 
		  $GLOBALS['core']->query("UPDATE mod_bids SET status=max(status, 3), delivertime='" . date('Y-m-d H:i:s') . "' WHERE id=" . $sup['id'], true);
	  elseif ($task->status() == -1) 
		  $GLOBALS['core']->query("UPDATE mod_bids SET status=max(status, 3), delivertime='" . date('Y-m-d H:i:s') . "' WHERE id=" . $sup['id'], true);
  }

  function setresparam($itemid = NULL, $show = 0xFFFFFFFF) {
	  $viewonly = true;
	  extract($_REQUEST);

	  $obj = &$this->goods[$itemid];
/*
	  $maychg = true;
	  if (!$viewonly && $obj->org() && $obj->org() != $_SESSION["OBJ_user"]->org)
		  $viewonly = true;
	  if (($obj->status() & 0xF) >= 4 && $obj->org() != $_SESSION["OBJ_user"]->org)
		  $viewonly = true;
	  if (($obj->status() & 0xF) >= 8 || $obj->status() == -1)
		  $viewonly = true;
	  if ($obj->src() > 0 && $this->processors[$obj->src()]->status() == -1)
		  $viewonly = true;
	  if ($obj->dest() > 0 && $this->processors[$obj->dest()]->status() == -1)
		  $viewonly = true;
	  if (in_array($obj->status() & 0xF, array(0, -1, 8))) $maychg = false;
	  if ($op == 'linkprod') {
		  $ps = $GLOBALS['core']->sqlSelect($this->flowTable, 'id', $linkto);
		  $old = array('id'=>$obj->id(), 'src'=>$obj->src(), 'innerid'=>$obj->innerid(), 'dest'=>$obj->dest());
		  $obj->loadData($ps[0]);

		  $obj->SetId($old['id']); $obj->SetSrc(-$ps[0]['id']); $obj->SetInnerid($old['innerid']);
		  $obj->SetDest($old['dest']);
		  $changes = 1 | 4 | 16 | 8192 | 1024 | 2048 | 4096 | 16384;
		  $this->saved = false;
		  $result = new PHPWS_Result($obj->propagateChanges($this, $changes));
		  $obj->propagateStatus($this);
		  if (!$this->saved) $this->saveflow(false);

		  $s = "完成数据修改。<hr><table border=1>";
		  if ($result) $s .= $result->showchgresult();

		  return $s . "</table>" . $next;
	  }

	  if ($op == 'changeprod') {
		  $changes = $obj->changeData();

		  if (($xchg == "验收货物" || $xchg == "货物流转")  && !($obj->product() && $obj->quantity() && $obj->unit())) 
			  $xchg = '';

		  if ($xchg == "验收货物" && $obj->product() && $obj->quantity() && $obj->unit()) {
			  $obj->SetOrg($_SESSION["OBJ_user"]->org);
			  $obj->SetStart(date('Y-m-d H:i:s'));
			  $changes = 16;
		  }

		  if ($changes) $this->saved = false;
		  if ($changes) $result = new PHPWS_Result($obj->propagateChanges($this, $changes));

		  if ($xchg == "货物流转" && $obj->status() >= 0 && $obj->product() && $obj->quantity() && $obj->unit()) {
			  if ($obj->status() != 8) $changes |= 1;
			  $obj->ChgStatus(8);
			  $this->processors[$obj->src()]->received($this, $obj);
		  }
		  if (($xchg == "验收货物" || $xchg == "货物流转")  && $obj->product() && $obj->quantity() && $obj->unit()) {
			  if ($obj->status() != -1) $changes |= 1;
			  $obj->ChgStatus(8);
			  $multi = $this->processors[$obj->dest()]->received($this, $obj);
/*
			  if (is_array($multi) && sizeof($multi) > 1) {
				  foreach ($multi as $m) {
					  if ($m->id()) continue;
					  if ($m->src) $this->processors[$obj->src]->output()[] = $this->goodsid;
					  if ($m->dest) $this->processors[$obj->dest]->input[] = $this->goodsid;
					  $this->goods[$this->goodsid++] = $m;
					  $this->saved = false;
				  }
			  }
* /
		  }

		  if ($changes & 1) {
			  $obj->propagateStatus($this);
		  }

		  if ($xchg == "验收货物" && $obj->product() && $obj->quantity() && $obj->unit()) $this->delivery('receive', $this->processors[$obj->dest()], $obj);

		  if (($xchg == "验收货物" || $xchg == "货物流转")) {
			  $completed = true;
			  foreach ($this->processors as $g)
				  if ($g->status() > 0) $completed = false;
			  foreach ($this->goods as $g)
				  if ($g->dest() <= 0 && $g->status() > 0 && $g->status() !=8) $completed = false;
			  if ($completed) {
				  $this->ChgStatus(-1);
				  $GLOBALS['core']->sqlDelete('mod_planadjusts', 'flow', $this->id);
			  }
			  if ($obj->dest()) {
				  if ($this->processors[$obj->dest()]->status() == 4)
					  $next = "<br>点这里<a href=./{$GLOBALS['SCRIPT']}?module=work&MOD_op=executetask&itemid=" . $obj->dest() . ">执行" . $this->processors[$obj->dest()]->taskname() . "工作</a>";
			  }
//			  PHPWS_Notice::broadcast(2, 0, "您没有中标", array_flip(array_flip($us)));
		  }

		  if (!$this->saved) $this->saveflow(false);

		  $s = "完成数据修改。<hr><table border=1>";
		  if ($result) $s .= $result->showchgresult();

		  return $s . "</table>" . $next;
	  }
*/

	  if (!$GLOBALS['provinces']) {
		  $ps = $GLOBALS['core']->query("SELECT DISTINCT province FROM supply_mod_locations", false);
		  $GLOBALS['provinces'] = array(0=>'');
		  while ($p = $ps->fetchRow()) $GLOBALS['provinces'][] = $p['province'];
	  }

	  $prods = "<table><tr><th align=center colspan=2>" . ($viewonly?'' : '修改') . $obj->name() . "资源信息</th></tr>";
	  $prods .= $obj->dataForm($show, $viewonly?0 : 0xFFFFFFFF);
/*
	  $btn = array(1=>'设置', 2=>'调整计划', 4=>'调整计划');
	  $lbl = "提交修改";
	  if ($btn[$obj->status() & 0xF]) $lbl = $btn[$obj->status() & 0xF];
	  if ($obj->resourceType() == 32 && $obj->status() > 0 && ($obj->status() & 0xF) < 8) {
		  if ($obj->src() <= 0 && $obj->dest() > 0 && $this->processors[$obj->dest()]->org() == $_SESSION["OBJ_user"]->org)
			  $lbl2 = "验收货物";
		  elseif ($obj->src() > 0 && $obj->dest() > 0) {
			  if ($this->processors[$obj->src()]->org() != $this->processors[$obj->dest()]->org()) {
				  if (!($this->processors[$obj->src()]->org() == $_SESSION["OBJ_user"]->org)) $lbl2 = "验收货物";
			  }
			  elseif ($this->processors[$obj->src()]->org() == $_SESSION["OBJ_user"]->org) $lbl2 = "货物流转";
		  }
	  }
	  if ($obj->status() == 8 || $obj->status() < 0) $lbl = $lbl2 = NULL;
//	  if ($obj->status() < 0) $lbl2 = NULL;
	  if (!$viewonly && $lbl) $lbl = PHPWS_Form::formSubmit($lbl, 'xchg');
	  else $lbl = NULL;
	  if ($lbl2) $lbl2 = PHPWS_Form::formSubmit($lbl2, 'xchg');

	  if ($lbl || $lbl2) {
		  $prods .= "<tr><td align=center colspan=2> {$lbl} {$lbl2} </td></tr></table>";
		  $prods .= PHPWS_Form::formHidden("module", $module) . PHPWS_Form::formHidden("MOD_op", $MOD_op) . PHPWS_Form::formHidden("op", "changeprod") . PHPWS_Form::formHidden("itemid", $itemid); 
		  $prods = PHPWS_Form::makeForm('changeprod', $GLOBALS['SCRIPT'], array($prods), "post", FALSE, TRUE);
	  }
	  else 
*/
	  $prods .= "</table>";
/*
	  if (!$obj->src()) {
		  $lst = "<table><tr><th colspan=3>选择待处理的产品</th></tr><tr><td>单号</td><td>品名</td><td>数量</td></tr>";
		  if (!$pos) $pos = 0;

		  $ps = $GLOBALS['core']->query("SELECT * FROM {$this->flowTable} WHERE org={$_SESSION['OBJ_user']->org} AND status>0 AND status<8 AND totask=0 AND resourceType=32 AND product!=0 ORDER BY id DESC LIMIT $pos,9", true);
		  $n = 0;
		  while ($p = $ps->fetchRow()) {
			  if (++$n > 8) continue;
			  $lst .= "<tr><td><a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=$MOD_op&op=linkprod&linkto={$p['id']}&itemid=$itemid>{$p['flowid']}</a></td><td>" . prodCache($p['product']) . "</td><td>" . $p['quantity'] . $p['unit'] . "</td></tr>";
		  }
		  $lst .= "</table>";
		  if ($pos) $lst .= "<a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=$MOD_op&pos=" . ($pos - 8) . ">上一页</a> | ";
		  if ($n > 8)  $lst .= "<a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=$MOD_op&pos=" . ($pos + 8) . ">下一页</a> | ";
		  if ($n || $pos) $prods = "<table><tr><td>$prods</td><td>$lst</td></tr></table>";
	  }

	  if (!$viewonly)
		  $_SESSION['OBJ_layout']->extraHead("<script>
			  function chgProvince(obj, city, place) {
				$.ajax({
					url:'./{$GLOBALS['SCRIPT']}?module=accessories&MOD_op=getlocation&province=' + obj.options[obj.selectedIndex].text,
					type:'get',
					dataType:'json',
					success: function(resp) {
						if (resp.cities.length > 0) {
							var cts = document.getElementById(city);
							cts.options.length=1;
							cts.length=1;
							for (var i = 0; i < resp.cities.length; i++) {
								var option=document.createElement('option');
								option.text = resp.cities[i].text;
								option.value = resp.cities[i].value;
								cts.add(option,null);
							}
						}
						else {
							var cts = document.getElementById(place);
							cts.options.length=1;
							cts.length=1;
							for (var i = 0; i < resp.places.length; i++) {
								var option=document.createElement('option');
								option.text = resp.places[i].text;
								option.value = resp.places[i].value;
								cts.add(option,null);
							}
						}
					}
				 });
			  }
				  
			  function chgCity(obj, province, place) {
				var province = document.getElementById(province);
				$.ajax({
					url:'./{$GLOBALS['SCRIPT']}?module=accessories&MOD_op=getlocation&province=' + province.options[province.selectedIndex].text + '&city=' + obj.options[obj.selectedIndex].text,
					type:'get',
					dataType:'json',
					success: function(resp) {
						var cts = document.getElementById(place);
						cts.options.length=1;
						cts.length=1;
						for (var i = 0; i < resp.places.length; i++) {
							var option=document.createElement('option');
							option.text = resp.places[i].text;
							option.value = resp.places[i].value;
							cts.add(option,null);
						}
					}
				 });
			  }

			  var places = [0, 0];

			  function chgPlace(obj, place) {
				places[place] = obj.options[obj.selectedIndex].value;
				if (places[0] == 0) return;
				if (places[1] == 0) return;
				$.ajax({
					url:'./{$GLOBALS['SCRIPT']}?module=accessories&MOD_op=getroute&stops=' + places[0] + ',' + places[1],
					type:'get',
					dataType:'json',
					success: function(resp) {
						var cts = document.getElementById('liner');
						cts.options.length=1;
						cts.length=1;
						for (var i = 0; i < resp.liner.length; i++) {
							var option=document.createElement('option');
							option.text = resp.liner[i].text;
							option.value = resp.liner[i].value;
							cts.add(option,null);
						}
						cts = document.getElementById('linegroup');
						if (cts && resp.linegroup) {
							cts.options.length=1;
							cts.length=1;
							for (var i = 0; i < resp.linegroup.length; i++) {
								var option=document.createElement('option');
								option.text = resp.linegroup[i].text;
								option.value = resp.linegroup[i].value;
								cts.add(option,null);
							}
						}
					}
				 });
			  }

			  function catChg(obj) {
				$.ajax({
					url:'./{$GLOBALS['SCRIPT']}?module=accessories&MOD_op=getcatspec&subcat=2&cat=' + obj.options[obj.selectedIndex].value,
					type:'get',
					dataType:'json',
					success: function(resp) {
						document.getElementById('cats').innerHTML += '/' + resp.catname;
						if (resp.cat.length == 0) {
							obj.style.display = 'none';
						}
						else {
							obj.options.length=0;
							obj.length=0;
							for (var i = 0; i < resp.cat.length; i++) {
								var option=document.createElement('option');
								option.text = resp.cat[i].text;
								option.value = resp.cat[i].value;
								obj.add(option,null);
							}
						}
						var prod = document.getElementById('product');
						prod.options.length=0;
						prod.length=0;
						if (resp.products.length > 0) {
							var option=document.createElement('option');
							option.text = '';
							option.value = 0;
							prod.add(option,null);
							for (var i = 0; i < resp.products.length; i++) {
								option=document.createElement('option');
								option.text = resp.products[i].text;
								option.value = resp.products[i].value;
								prod.add(option,null);
							}
						}
					}
				 });
			  }
		  </script>");
*/
	  return $prods;
  }

  function settaskparam() {
	  $viewonly = false;

	  extract($_REQUEST);

	  $obj = &$this->processors[$itemid];

	  if (in_array($obj->status(), array(0, -1, 8))) $viewonly = true;
	  elseif ($obj->org() && $obj->org() != $_SESSION["OBJ_user"]->org) $viewonly = true;

//	  if (in_array($obj->status(), array(0, -1, 4))) return "<span style='color:red'>您没有权限</span>" . $this->viewflow(true);

//	  $auth = false;
//	  if (!$obj->org() || $obj->org() == $_SESSION["OBJ_user"]->org) $auth = true;
//		return "<span style='color:red'>您没有权限</span>" . $this->viewflow(true);
/*
	  if ($op == 'changeproc') {
		  $result = new PHPWS_Result();

		  $changes = $obj->changeData();
		  if ($changes) $result->mergeResult($obj->propagateTaskChanges($this, $changes));

		  $this->saved = false;

		  if ($xchg == "完成任务") {
			  foreach ($quantity as $i=>$q) {
				  if ($this->goods[$i]->quantity() !== $q + 0 || $this->goods[$i]->unit() != $unit[$i]) {
					  $this->goods[$i]->SetQuantity($q + 0);
					  $this->goods[$i]->SetUnit($unit[$i]);
					  $result->mergeResult($this->goods[$i]->propagateChanges($this, 4096));
				  }
			  }

			  $obj->ChgStatus(-1);
			  foreach ($obj->output() as $i) {
				  if ($this->goods[$i]->status() != 4) {
					  $this->goods[$i]->ChgStatus(4);
					  $this->goods[$i]->propagateStatus($this);
				  }
			  }
		  }

		  if (!$this->saved) $this->saveflow(false);

		  $s = "完成数据修改。<hr><table border=1>";
		  if ($result) $s .= $result->showchgresult();

		  return $s . "</table>";
	  }
*/
	  if (!$GLOBALS['provinces']) {
		  $ps = $GLOBALS['core']->query("SELECT DISTINCT province FROM supply_mod_locations", false);
		  $GLOBALS['provinces'] = array(0=>'');
		  while ($p = $ps->fetchRow()) $GLOBALS['provinces'][] = $p['province'];
	  }

	  if ($obj->status() > 0 && ($obj->status() & 0xF) < 8) {
		  $prods .= "<table><tr><th align=center colspan=2>" . ($viewonly?'' : '修改') . $obj->taskname() . "任务参数</th></tr>";
		  $prods .= $obj->dataForm(0xFFFFFFFF, $viewonly?0 : 0xFFFFFFFF);
		  if (!$viewonly) $prods .= "<tr><td align=center colspan=2>" . PHPWS_Form::formSubmit("提交修改", 'xchg') . "</td></tr>";
		  $prods .= "</table>";
	  }
	  elseif (($obj->status() & 0xF) == 8) {
		  $prods .= "<table><tr><th align=center colspan=2>" . $obj->taskname() . "任务参数</th></tr>";
		  $prods .= $obj->dataForm(0xFFFFFFFF, $viewonly?0 : 0xFFFFFFFF);
		  if (!$viewonly) $prods .= "<tr><td align=center colspan=2>" . PHPWS_Form::formSubmit("提交修改", 'xchg') . "</td></tr>";
		  $prods .= "</table>";
/*
		  $pu = false;
		  $rs .= "<table><tr><th align=center colspan=3>{$obj->taskname}任务结果</th></tr>";
		  $rs .= "<tr><th align=center>资源</th><th align=center>产品</th><th align=center>数量</th></tr>";
		  foreach ($obj->output() as $i) {
			  $t = &$this->goods[$i];
			  $rs .= "<tr><th align=center>{$t->name}</th><th align=center>";
			  if (!$t->product()) $pu = true;
			  else {
				  $rs .= prodCache($t->product());
			  }
			  $rs .= "</th><th align=center>";
			  
			  $us = array_merge(array(0=>''), Attributes::$units);
			  $rs .= PHPWS_Form::formTextField("quantity[$i]", $this->quantity) . PHPWS_Form::formSelect("unit[$i]", $us, $this->unit(), true, false);
			  $rs .= "</th></tr>";
		  }
		  if (!$pu)
			  $prods .= $rs . "<tr><td align=center colspan=3>" . PHPWS_Form::formSubmit("完成任务", 'xchg') . "</td></tr></table>";
*/
	  }
	  else {
		  $prods .= "<table><tr><th align=center colspan=2>" . $obj->taskname() . "任务参数</th></tr>";
		  $prods .= $obj->dataForm(0xFFFFFFFF, $viewonly?0 : 0xFFFFFFFF);
		  $prods .= "</table>";
	  }

/*
	  if (!$viewonly) {
		  $prods .= PHPWS_Form::formHidden("module", $module) . PHPWS_Form::formHidden("MOD_op", $MOD_op) . PHPWS_Form::formHidden("op", "changeproc") . PHPWS_Form::formHidden("itemid", $itemid); 
		  $prods = PHPWS_Form::makeForm('changeproc', $GLOBALS['SCRIPT'], array($prods), "post", FALSE, TRUE);

		  $res = array();
		  $resall = array();
	//	  if (sizeof($this->goods)) foreach ($this->goods as $i=>$g) {
		  if ($obj->input()) foreach ($obj->input() as $i) {
			  $res[$i] = $this->goods[$i]->name();
		  }
		  if ($obj->output()) foreach ($obj->output() as $i) {
			  $res[$i] = $this->goods[$i]->name();
		  }
	//	  if (sizeof($this->goods)) foreach ($this->goods as $i=>$g) {
	//		  $res[$i] = $g->name;
	//		  $resall[$i] = "资源：" . $g->name;
	//	  }
	//	  if (sizeof($this->processors)) foreach ($this->processors as $j=>$g) {
	//		  $resall[-$j] = "任务：" . $g->taskname;
	//	  }

		  $_SESSION['OBJ_layout']->extraHead("<script>
			  function chgProvince(obj) {
				$.ajax({
					url:'./{$GLOBALS['SCRIPT']}?module=accessories&MOD_op=getlocation&province=' + obj.options[obj.selectedIndex].text,
					type:'get',
					dataType:'json',
					success: function(resp) {
						if (resp.cities.length > 0) {
							var cts = document.getElementById('city');
							cts.options.length=1;
							cts.length=1;
							for (var i = 0; i < resp.cities.length; i++) {
								var option=document.createElement('option');
								option.text = resp.cities[i].text;
								option.value = resp.cities[i].value;
								cts.add(option,null);
							}
						}
						else {
							var cts = document.getElementById('place');
							cts.options.length=1;
							cts.length=1;
							for (var i = 0; i < resp.places.length; i++) {
								var option=document.createElement('option');
								option.text = resp.places[i].text;
								option.value = resp.places[i].value;
								cts.add(option,null);
							}
						}
					}
				 });
			  }
			  
			  function chgCity(obj) {
				var province = document.getElementById('province');
				$.ajax({
					url:'./{$GLOBALS['SCRIPT']}?module=accessories&MOD_op=getlocation&province=' + province.options[province.selectedIndex].text + '&city=' + obj.options[obj.selectedIndex].text,
					type:'get',
					dataType:'json',
					success: function(resp) {
						var cts = document.getElementById('place');
						cts.options.length=1;
						cts.length=1;
						for (var i = 0; i < resp.places.length; i++) {
							var option=document.createElement('option');
							option.text = resp.places[i].text;
							option.value = resp.places[i].value;
							cts.add(option,null);
						}
					}
				 });
			  }

			  function additem(obj) {
				  switch (obj) {
					  case 3:
						  document.getElementById('ratiorule').innerHTML += '资源之间比例" . str_replace("\n", ' ', PHPWS_Form::formTextField("srcamt[]", "1", 3) . PHPWS_Form::formSelect("srcratio[]", $res, NULL, true, false) . " ：" . PHPWS_Form::formTextField("destamt[]", "1", 3) . PHPWS_Form::formSelect("destratio[]", $res, NULL, true, false)) . "<br>';
						  break;
					  case 4:
						  document.getElementById('timerule').innerHTML += '时间之间关系" . str_replace("\n", ' ', PHPWS_Form::formSelect("desttime[]", $resall, NULL, true, false) . "时间 = " . PHPWS_Form::formSelect("srctime[]", $resall, NULL, true, false) . '之后' . PHPWS_Form::formTextField("delaytime[]", "0", 4) . PHPWS_Form::formSelect("delayunit[]", array("小时", "天", "周", "月"), NULL, false, true)) . "<br>时长之间关系" . 
						  str_replace("\n", ' ', PHPWS_Form::formSelect("destduration[]", $resall, NULL, true, false) . "时长 = " . PHPWS_Form::formSelect("srcduration[]", $resall, NULL, true, false) . '时长 + ' . PHPWS_Form::formTextField("duration[]", "0", 4) . PHPWS_Form::formSelect("durationunit[]", array("小时", "天", "周", "月"), NULL, false, true)) . "<br>';
						  break;
					  case 5:
						  document.getElementById('placerule').innerHTML += '地点之间的关系" . str_replace("\n", ' ', PHPWS_Form::formSelect("destplace[]", $resall, NULL, true, false) . "之地点 = " . PHPWS_Form::formSelect("srcplace[]", $resall, NULL, true, false)) . "之地点<br>';
						  break;
					  case 6:
						  document.getElementById('prodrule').innerHTML += '物品之间的关系" . str_replace("\n", ' ', PHPWS_Form::formSelect("destproduct[]", $res, NULL, true, false) . "之物品 = " . PHPWS_Form::formSelect("srproduct[]", $res, NULL, true, false)) . "之物品<br>';
						  break;
				  }
			  }
		  </script>");
	  }
*/
	  return $prods;
  }

  function excute($id = NULL) {
	  if (!$id) extract($_REQUEST);

	  if ($id) {
		  $this->loadflow($id);

		  $seq = $GLOBALS['core']->sqlSelect($this->flowTable . "_seq");
		  $this->name = date("Y-m-d ") . $this->name . " " . $seq[0]['id'];
		  $this->SetRefflow($this->id);
		  $this->id = 0;
		  $this->ChgStatus(0x11111111);
		  $this->saved = false;
		  $this->org = $_SESSION["OBJ_user"]->org;

		  foreach ($this->processors as &$g) {
			  $g->SetCreator($_SESSION["OBJ_user"]->user_id);
			  $g->ChgStatus(0x11111111);
			  $g->SetRefflow(0);
		  }
		  unset($g);
		  foreach ($this->goods as &$g) $g->ChgStatus(0x11111111);
		  unset($g);

		  foreach ($this->processors as &$g)
			  if (!$g->input()) $g->setStatus($this, 4);

		  unset($_REQUEST['id']);
		  $this->saveflow(false);
	  }

	  return $this->viewflow(true);
  }

  function modifyflow() {
	  extract($_REQUEST);
	  $this->loadflow($id);

	  $_REQUEST['MOD_op'] = 'newflow';

	  return $this->newflow();
  }

  function deleteflow() {
	  extract($_REQUEST);

	  $GLOBALS['core']->sqlDelete($this->flowTable, array('flowid'=>$id, 'status'=>0));

	  return $this->flows();
  }

  function mergeFlow($flow2, $targets, $joints) {
	  // merge flow2 into this flow, good nodes $joints in flow2 are matched to good nodes $targets in this flow.
	  if (!$targets || !$joints || sizeof($targets) != sizeof($joints)) return;

	  $ngd = array(); $npd = array();
	  foreach ($joints as $j=>$tg) {
		  $ngd[$tg->innerid()] = $targets[$j]->innerid();
		  if (!$targets[$j]->src() && $tg->src()) $targets[$j]->SetSrc($tg->src());
		  if (!$targets[$j]->dest() && $tg->dest()) $targets[$j]->SetDest($tg->dest());
	  }

	  foreach ($flow2->goods as $tg)
		  if (!$ngd[$tg->innerid()]) {
			  $ngd[$tg->innerid()] = $this->goodsid;
			  $tg->SetInnerid($this->goodsid);
			  $tg->SetId(0);
			  $this->goods[$this->goodsid++] = $tg;
		  }

	  foreach ($flow2->processors as $tg) {
		  $npd[$tg->innerid()] = $this->processorsid;
		  $tg->SetInnerid($this->processorsid);
		  $tg->SetId(0);

		  foreach ($tg->input() as &$i) {
			  $i = $ngd[$i];
			  $this->goods[$i]->SetDest($tg->innerid());
		  }
		  foreach ($tg->output() as &$i) {
			  $i = $ngd[$i];
			  $this->goods[$i]->SetSrc($tg->innerid());
		  }

		  $this->processors[$this->processorsid++] = $tg;
	  }
	  $this->saved = FALSE;
  }

  function flows() {
	  extract($_REQUEST);

	  if (!$pos) $pos = 0;

	  $fls = $GLOBALS['core']->query("SELECT * FROM {$this->flowTable} WHERE org='{$_SESSION['OBJ_user']->org}' AND flowname is not NULL AND status=0 ORDER BY id DESC LIMIT $pos,21", TRUE);
	  $cnd = array();
	  $n = 0;
	  $s = "<table><tr><th>流程名称</th><th>创建日期</th><th>操作</th></tr>";
	  while ($c = $fls->fetchRow()) {
		  if ($n++ >= 20) continue;
		  $s .= "<tr><td>{$c['flowname']}</td><td>{$c['created']}</td><td><a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewflow&id={$c['flowid']}>查看</a> <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=excute&id={$c['flowid']}>执行</a> <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=modifyflow&id={$c['flowid']}>修改</a> <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=deleteflow&id={$c['flowid']}>删除</a></td></tr>";
	  }
	  $s .= "</table>";
	  if ($pos) {
		  $s .= "<a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=$MOD_op&pos=" . ($pos - 20) . ">上一页</a>";
		  $g = " | ";
	  }
	  if ($n == 21) {
		  $s .= "<a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=$MOD_op&pos=" . ($pos + 20) . ">下一页</a>";
	  }
	  return $s;
  }

  function addprocessor($p) {
	  $p->SetInnerid($this->processorsid);
	  $this->processors[$this->processorsid++] = $p;
  }

  function addgoods($p) {
	  $p->SetInnerid($this->goodsid);
	  $this->goods[$this->goodsid++] = $p;
  }

  function & findprocessor($id) {
	  if (!$this->processors) return NULL;
	  foreach ($this->processors as &$g) {
		  if ($g->id() == $id) {
			  return $g;
		  }
	  }
	  return NULL;
  }
  function & findgoods($id) {
	  if (!$this->goods) return NULL;
	  foreach ($this->goods as &$g)
		  if ($g->id() == $id) return $g;
	  return NULL;
  }
  function deletegoods($innerid) {
	  $p = $this->goods[$innerid];
	  if ($p->src() < 0 || $p->dest() < 0) return false;
	  if ($p->src() > 0) 
		  $this->processors[$p->src()]->SetOutput(array_diff($this->processors[$p->src()]->output(), array($innerid)));
	  if ($p->dest() > 0) 
		  $this->processors[$p->dest()]->SetInput(array_diff($this->processors[$p->dest()]->input(), array($innerid)));
	  if ($p->id()) $GLOBALS['core']->sqlDelete($this->flowTable, 'id', $p->id());
	  unset($this->goods[$innerid]);
	  return true;
  }
  function RmProcessor($g) {
	  if (sizeof($g->input()) || sizeof($g->output())) return;
	  unset($this->processors[$g->innerid()]);
	  if ($g->id()) $GLOBALS['core']->sqlDelete($this->flowTable, 'id', $g->id());
  }
}

?>