<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/PHPWS_ResourceNode.php');
require_once(PHPWS_SOURCE_DIR . 'mod/basic.php');

class SlipMgr {
  protected static $allSnips = array();

  static function printSnip() {
	  print_r(SlipMgr::$allSnips);
  }

  static function loadSnip($flowid) {
	  $slips = $GLOBALS['core']->sqlSelect("snips", array('flowid'=>$flowid));
	  if ($slips) foreach ($slips as $d) {
		  if ($d['finalized'] == 2) $d['quantity'] = $d['propqty'];
		  elseif ($d['finalized'] == 1) $d['propose'] = $d['propqty'];
		  $d['dirty'] = false;
		  SlipMgr::$allSnips[$d['id']] = $d;
	  }
  }

  static function saveSnip($flowid) {
	  $sql = "REPLACE INTO supply_snips (id, flowid, slip, plan, propqty, price, finalized, packaging, packages) VALUES ";
	  $g = ''; $n = 0;
	  foreach (SlipMgr::$allSnips as $id=>$v) {
		  if ($v['flowid'] != $flowid || !$v['dirty']) continue;
		  $u = "($id, $flowid, {$v['slip']}, {$v['plan']}, ";
		  if ($v['finalized'] == 2) $u .= $v['quantity'];
		  elseif ($v['finalized'] == 1) $u .= $v['propose'];
		  else $u .= "0";
		  $u .= ", " . (isset($v['price'])?$v['price'] : 0) . "," . $v['finalized'];
		  $u .= ", " . (isset($v['packaging'])?$v['packaging'] : 0);
		  $u .= ", " . (isset($v['packages'])?$v['packages'] : 0) . ")";
		  $sql .= $g . $u; $g = ","; $n++;
		  SlipMgr::$allSnips[$id]['dirty'] = false;
	  }
	  if ($n) $GLOBALS['core']->query($sql, false);
  }

  static function newSnip($flowid, $slip) {
	  $id = $GLOBALS['core']->sqlInsert(array('flowid'=>$flowid, 'slip'=>$slip), "snips", false, true);
	  SlipMgr::$allSnips[$id] = array('flowid'=>$flowid, 'slip'=>$slip, 'plan'=>0, 'finalized'=>0, 'dirty'=>false);
	  SlipMgr::$allSnips[$id]['id'] = $id;
	  return SlipMgr::$allSnips[$id];
  }

  static function getSnip($id) {
	  if (!isset(SlipMgr::$allSnips[$id])) {
		  $slips = $GLOBALS['core']->sqlSelect("snips", array('id'=>$id));
		  if ($slips) {
			  $d = $slips[0];
			  if ($d['finalized'] == 2) $d['quantity'] = $d['propqty'];
			  elseif ($d['finalized'] == 1) $d['propose'] = $d['propqty'];
			  $d['dirty'] = false;
			  SlipMgr::$allSnips[$id] = $d;
		  }
	  }

	  return SlipMgr::$allSnips[$id];
  }

  static function updateSnip($data) {
	  if (isset($data['quantity'])) $data['finalized'] = 2;
	  elseif (isset($data['propose'])) $data['finalized'] = 1;
	  $data['dirty'] = true;
	  SlipMgr::$allSnips[$data['id']] = $data;
  }

  static function deleteSnips($flowid, $slip) {
	  $todel = false;
	  foreach (SlipMgr::$allSnips as $id=>$v)
		  if ($v['flowid'] == $flowid && $v['slip'] == $slip) {
			  unset(SlipMgr::$allSnips[$id]);
			  $todel = true;
		  }
	  if ($todel) $GLOBALS['core']->sqlDelete("snips", array('flowid'=>$flowid, 'slip'=>$slip));
  }

  static function deleteOneSnip($id) {
	  if (isset(SlipMgr::$allSnips[$id])) {
		  $GLOBALS['core']->sqlDelete("snips", array('id'=>$id));
		  unset(SlipMgr::$allSnips[$id]);
	  }
  }
}

class PHPWS_GoodsNode extends PHPWS_ResourceNode {
  protected $_product = 0;					// 0x1024	-- edit position flag
  protected $_category = 0;					// 0x2048
  protected $_unit = NULL;					// 0x4096
  protected $_quantity = 0;					// 0x4096
  protected $_weight = 0;					// 0x16384
  protected $_volumn = 0;					// 0x16384
  protected $_price = 0;					// 0x16384
  protected $_freight = 0;					// 0x16384
  protected $_goodtil = 0;					// 0x16384
  protected $_attribs = array();			// 0x32768

  function PHPWS_GoodsNode() {
  }

  function __construct() {
	  $this->_processType = GOODS_NODE;
	  parent::__construct();
  }

  function bestProdName() {
	  return $this->ExtraVal('goods')?goodsCache($this->ExtraVal('goods')) : prodCache($this->product());
  }

  function hasacceptance() {
	  if (sizeof($this->_extra['acceptance']) > 1) return true;
	  if (sizeof($this->_extra['acceptance']) == 0) return false;
	  return !isset($this->_extra['acceptance']['plan']);
  }

  function acceptance($lable) {
	  $ex = $this->_extra['acceptance'];
	  if (!isset($ex[$lable])) return NULL;
	  return SlipMgr::getSnip($ex[$lable]);
  }

  function accepted($lable) {
	  return isset($this->_extra['acceptance'][$lable]);
  }

  function acceptconfirm($lable) {
	  $d = $this->acceptance($lable);

	  if (!isset($d['propose'])) return;
	  $d['quantity'] = $d['propose'];
	  unset($d['propose']);
	  SlipMgr::updateSnip($d);
  }

  function acceptdeconfirm($lable) {
	  $d = $this->acceptance($lable);

	  if (!isset($d['propose'])) return;
	  $d['propose'] = $d['quantity'];
	  $d['finalized'] = 1;
	  unset($d['quantity']);
	  SlipMgr::updateSnip($d);
  }

  function unaccept($lable) {
	  if (isset($this->_extra['acceptance'][$lable])) {
		  unset($this->_extra['acceptance'][$lable]);
		  return true;
	  }
	  return false;
  }

  function addslip($lable, $id) {
	  $this->_extra['acceptance'][$lable] = $id;
	  $this->saved = false;
  }

  function accept($lable, $quantity, $price = NULL, $as = 'quantity', $packages = NULL, $packaging = NULL) {
	  if ($this->workstatus() >= FLOW_EXECUTED) return false;

	  $this->chgStatus(FLOW_EXECUTING + 1);

	  if (!$this->_extra['acceptance']) {
		  $this->_extra['acceptance'] = array('plan'=>array('quantity'=>$this->_quantity, 'price'=>$this->_price));
  		  $this->saved = false;
	  }

	  $d = $this->acceptance($lable);

	  if (!$d) {
		  $d = SlipMgr::newSnip($this->flowid, $lable);
		  $this->_extra['acceptance'][$lable] = $d['id'];
		  $this->saved = false;
	  }

	  $d[$as] = $quantity;
	  if ($price !== NULL) $d['price'] = round($price * 100);
	  if ($packaging && $packages) {
		  $d['packages'] = $packages;
		  $d['packaging'] = $packaging;
	  }
	  SlipMgr::updateSnip($d);

	  return $d['id'];
  }

  function setCode($code) {
	  if ($this->_extra['code'] != $code) {
		  $prods = $GLOBALS['core']->query("SELECT * FROM goodsonsale WHERE code='$code' AND " . goodsIncCond(), true);
		  $prod = $prods->fetchRow();

		  if (!$prod) return false;
		  if ($prods->fetchRow()) return false;

//		  $prod['price'] /= 100;

		  $this->setGoods($prod['id']);
	  }
	  return true;
  }
  function setGoods($goods) {
	  if ($this->_extra['goods'] != $goods) {
		  $prod = $GLOBALS['core']->sqlSelect("goodsonsale", 'id', $goods);
		  $prod = $prod[0];
		  if ($prod['name']) {
			  $this->_extra['name'] = $prod['name'];
			  $this->_name = $prod['name'];
		  }
		  if ($this->_product != $prod['product'] || !$this->_name) {
			  $this->_product = $prod['product'];
			  $cat = $GLOBALS['core']->sqlSelect("mod_product", 'id', $prod['product']);
			  $this->_category = $cat[0]['category'];
			  if (!$prod['name']) $this->_name = $cat[0]['name'];
		  }
		  $this->_attribs = unserialize($prod['attribs']);
		  if ($prod['unit']) $this->_unit = $prod['unit'];
		  $this->_extra['goods'] = $goods;
		  $this->_extra['code'] = $prod['custid'];
		  $this->saved = false;
	  }
  }
  function removeFrom($flow) {
	  if ($this->_innerid) {
		  unset($flow->goods[$this->_innerid]);
		  $flow->saved = false;
	  }
	  if ($this->_id) $GLOBALS['core']->sqlDelete($flow->flowTable, 'id', $this->_id);
  }
  function whatChanged($changes) {
	  static $changeWhat = array(1024=>1, 2048=>1, 4096=>5, 16384=>5, 32768=>0);
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
  function loadData($f) {
	  parent::loadData($f);
	  $this->_category = $f['category'];
	  $this->_unit = $f['unit'];
	  $this->_product = $f['product'];
	  $this->_quantity = $f['quantity'];
	  $this->_weight = $f['weight'];
	  $this->_price = $f['unitprice'];
	  $this->_freight = $f['freight'];
	  $this->_volumn = $f['volumn'];
	  $this->_goodtil = $f['goodtil'];
	  $this->_attribs = $f['attribs']?unserialize($f['attribs']) : array();
  }
  function copy($f) {
	  parent::copy($f);
	  $this->_category = $f->_category;
	  $this->_unit = $f->_unit;
	  $this->_product = $f->_product;
	  $this->_quantity = $f->_quantity;
	  $this->_weight = $f->_weight;
	  $this->_price = $f->_price;
	  $this->_weight = $f->_weight;
	  $this->_freight = $f->_freight;
	  $this->_attribs = $f->_attribs;
	  $this->_goodtil = $f->_goodtil;
	  $this->flowid = $f->flowid;
	  $this->saved = false;
  }
  function weakcopy($f) {
	  parent::weakcopy($f);
	  $this->_category = $f->_category;
	  $this->_unit = $f->_unit;
	  $this->_product = $f->_product;
	  $this->_quantity = $f->_quantity;
	  $this->_weight = $f->_weight;
	  $this->_price = $f->_price;
	  $this->_weight = $f->_weight;
	  $this->_freight = $f->_freight;
	  $this->_attribs = $f->_attribs;
	  $this->_goodtil = $f->_goodtil;
	  $this->flowid = $f->flowid;
	  $this->saved = false;
  }
  function littlecopy($f) {
	  parent::weakcopy($f);
	  $this->_category = $f->_category;
	  $this->_unit = $f->_unit;
	  $this->_product = $f->_product;
	  $this->_goodtil = $f->_goodtil;
	  $this->flowid = $f->flowid;
	  $this->saved = false;
  }
  function saveData() {
	  $f = parent::saveData();
	  $f['category'] = $this->_category;
	  $f['unit'] = $this->_unit;
	  $f['product'] = $this->_product;
	  $f['quantity'] = $this->_quantity;
	  $f['weight'] = $this->_weight;
	  $f['unitprice'] = $this->_price;
	  $f['freight'] = $this->_freight;
	  $f['volumn'] = $this->_volumn;
	  $f['goodtil'] = $this->_goodtil;
	  $f['attribs'] = $this->_attribs?$this->_attribs : array();
	  return $f;
  }
  function dataForm($show = 0xFFFFFFFF, $edit = 0xFFFFFFFF) {
	  $maychg = true;
	  if (in_array($this->_status & 0xF, array(FLOW_COMPLETED, FLOW_EXECUTED, FLOW_CANCELLED))) $maychg = false;
	  if ($show & 1024) {
		  if (!$this->_product && ($edit & 1024)) {
			  $c = $GLOBALS['core']->sqlSelect('mod_category', 'parentid', $this->_category);
			  $cat = array(0=>'');
			  if ($c) foreach ($c as $cs) {
				  $cat[$cs['id']] = $cs['name'];
			  }

			  $c = $GLOBALS['core']->sqlSelect('mod_product', 'category', $this->_category);
			  $products = array(0=>'');
			  if ($c) foreach ($c as $cs) {
				  $products[$cs['id']] = $cs['name'];
			  }

			  if (sizeof($cat) > 1 || sizeof($products) > 1)
				  $s .= "<tr><td>产品：</td><td id=showgoods><div id=cats></div>" . (sizeof($cat) > 1?PHPWS_Form::formSelect("category", $cat, $this->_category, false, true, 'catChg(this);') : '') . PHPWS_Form::formSelect("product", $products, $this->_product, false, true) . "</td></tr>";
		  }
		  elseif ($this->_product) {
			  $s .= "<tr><td>产品：</td><td>" . prodCache($this->_product) . "</td></tr>";
		  }
		  else {
			  $s .= "<tr><td>产品：</td><td></td></tr>";
		  }
	  }

	  $us = array_merge(array(0=>''), Attributes::$units);
	  if ($show & 4096) {
		  $s .= "<tr><td>数量：</td><td>";
		  if ($maychg && ($edit & 4096)) $s .= PHPWS_Form::formTextField("quantity", $this->_quantity) . PHPWS_Form::formSelect("unit", $us, $this->_unit, true, false);
		  else $s .= $this->_quantity . $this->_unit;
		  $s .= "</td></tr>";
		  $s .= "<tr><td>价格：</td><td>";
		  if ($maychg && ($edit & 4096)) $s .= PHPWS_Form::formTextField("price", $this->_price / 100.0);
		  else $s .= $this->_price / 100.0;
		  $s .= "</td></tr>";
	  }
	  if ($show & 16384) {
		  $s .= "<tr><td>总重量：</td><td>";
		  if ($maychg && ($edit & 16384)) $s .= PHPWS_Form::formTextField("weight", $this->_weight, 4);
		  else $s .= $this->_weight;
		  $s .= "公斤</td></tr>";
		  $s .= "<tr><td>总体积：</td><td>";
		  if ($maychg && ($edit & 16384))  $s .= PHPWS_Form::formTextField("volumn", $this->_volumn, 4);
		  else $s .= $this->_volumn;
		  $s .= "升</td></tr>";
	  }

	  $s .= parent::dataForm($show & (8192 | 16 | 4 | 1), $edit);

	  if ($this->_attribs) {
		  $s .= "<tr><td>规格：</td><td><pre>" . print_r($this->_attribs, true) . "</pre></td></tr>";
	  }

	  if ($GLOBALS['WXMODE']) {
		  $s .= "<tr><td colspan=2 align=center>";
//		  if ($this->_srcode) $s .= "二维码<br><img with=128 src=./{$GLOBALS['SCRIPT']}?module=accessories&MOD_op=getSrcode&for=mod_flow&id={$this->_id}>";
//		  elseif ($this->_product && $this->_quantity && $this->_id)
//			  $s .= "<div id=srcode><a href=# onclick='gensrcode({$this->_id});'>生成二维码</a> | <a href=# onclick='scansrcode();'>扫二维码赋予该物品</a> | <a href=# onclick='scanbarcode();'>扫条码赋予该物品</a></div>";
		  $s .= "</td></tr>";

		  require_once (PHPWS_SOURCE_DIR . "core/jssdk.php");

		  $sdk = new JSSDK(AppId, AppSecret);
		  $pkg = $sdk->getSignPackage();

		  $s .= "<script>
			wx.config({
				debug: false, // 开启调试模式,调用的所有api的返回值会在客户端alert出来，若要查看传入的参数，可以在pc端打开，参数信息会通过log打出，仅在pc端时才会打印。
				appId: '{$pkg['appId']}',
				timestamp: '{$pkg['timestamp']}',
				nonceStr: '{$pkg['nonceStr']}',
				signature: '{$pkg['signature']}',
				jsApiList: ['scanQRCode'] // 必填，需要使用的JS接口列表，所有JS接口列表见附录2
			});

			function scansrcode() {
				wx.ready(function () {
					wx.scanQRCode({
						needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
						scanType: ['qrCode'], // 可以指定扫二维码还是一维码，默认二者都有
						desc: '扫一扫二维码，赋予该商品',
						success: function (res) {
							var result = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
							document.getElementById('srcode').innerHTML = '二维码<br><img with=128 src=./wxlink.php?module=work&MOD_op=assignsrcode&id={$this->_id}&type=srcode&srcode=' + result + '>';
						}
					});
				});
			}

			function scanbarcode() {
				wx.ready(function () {
					wx.scanQRCode({
						needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
						scanType: ['barCode'], // 可以指定扫二维码还是一维码，默认二者都有
						desc: '扫一扫二维码，赋予该商品',
						success: function (res) {
							var result = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
							document.getElementById('srcode').innerHTML = '<img with=128 src=./wxlink.php?module=work&MOD_op=assignsrcode&id={$this->_id}&type=barcode&srcode=' + result + '>';
						}
					});
				});
			}
			</script>";
	  }
	  else {
		  $s .= "<tr><td colspan=2 align=center>";
		  if ($this->_srcode) $s .= "二维码<br><a href=./{$GLOBALS['SCRIPT']}?module=accessories&MOD_op=printsrcode&for=mod_flow&id={$this->_id}&label=" . URLEncode($this->_quantity . $this->_unit . prodCache($this->_product)) . " target=_blank><img with=128 src=./{$GLOBALS['SCRIPT']}?module=accessories&MOD_op=getSrcode&for=mod_flow&id={$this->_id}></a>";
//		  elseif ($this->_product && $this->_quantity && $this->_unit && $this->_id)
//			  $s .= "<div id=srcode><a href=# onclick='gensrcode({$this->_id});'>生成二维码</a></div>";
		  $s .= "</td></tr>";
	  }
	  $_SESSION['OBJ_layout']->extraHead("<script>
				function gensrcode(id) {
					$.ajax({
						url:'./{$GLOBALS['SCRIPT']}?module=work&MOD_op=gensrcode&for=mod_flow&id=' + id,
						type:'get',
						dataType:'json',
						success: function(resp) {
							document.getElementById('srcode').innerHTML = '二维码<br><img with=128 src=./{$GLOBALS['SCRIPT']}?module=accessories&MOD_op=getSrcode&for=mod_flow&id={$this->_id}>';
						}
					});
				}
		  </script>");

	  return $s;
  }
  function changeData() {
	  extract($_REQUEST);
	  extract($_POST);

	  $changes = 0;
	  if ($category && $this->_category != $category) {
		  $this->_category = $category;
		  $changes |= 2048;
		  if (($this->_status & 0xF) < FLOW_EXECUTING) {
			  $this->_status = ($this->_status & ~0xF) | 0x2;
			  $changes |= 1;
		  }
	  }
	  if ($product && $this->_product != $product) {
		  $this->_product = $product;
		  $changes |= 1024;
		  if (($this->_status & 0xF) < FLOW_EXECUTING) {
			  $this->_status = ($this->_status & ~0xF) | 0x2;
			  $changes |= 1;
		  }
	  }

	  if ($quantity && $this->_quantity != $quantity) {
		  $this->_quantity = $quantity + 0;
		  $changes |= 4096;
		  if (($this->_status & 0xF) < FLOW_EXECUTING) {
			  $this->_status = ($this->_status & ~0xF0000) | 0x20000;
			  $changes |= 1;
		  }
	  }
	  if ($unit && $this->_unit != $unit) {
		  $this->_unit = $unit;
		  if ($this->_quantity) {
			  $changes |= 4096;
			  if (($this->_status & 0xF) < FLOW_EXECUTING) {
				  $this->_status = ($this->_status & ~0xF0000) | 0x20000;
				  $changes |= 1;
			  }
		  }
	  }
	  if ($weight && $this->_weight != $weight) {
		  $this->_weight = $weight + 0;
		  $changes |= 16384;
		  if (($this->_status & 0xF) < FLOW_EXECUTING) {
			  $this->_status = ($this->_status & ~0xF0000) | 0x20000;
			  $changes |= 1;
		  }
	  }
	  if ($volumn && $this->_volumn != $volumn) {
		  $this->_volumn = $volumn + 0;
		  $changes |= 16384;
		  if (($this->_status & 0xF) < FLOW_EXECUTING) {
			  $this->_status = ($this->_status & ~0xF0000) | 0x20000;
			  $changes |= 1;
		  }
	  }
	  if ($changes) $this->saved = false;

	  $changes |= parent::changeData();
	  return $changes;
  }
  function insertBefore($flow, $procType, $goodsType = 0, $copydata = NULL) {
	  if (!$goodsType && $this->_src) return NULL;
	  if ($goodsType) {
		  $p2 = PHPWS_Node::newNode($goodsType);
		  if ($copydata) {
			  $p2->copy($copydata);
			  $p2->SetConnects(array());
			  $p2->SetId(0);
			  $p2->ChgStatus(1);
			  $p2->SetDest($p2->SetSrc(0));
		  }
		  $p2->SetInnerid($flow->goodsid);
		  $flow->goods[$flow->goodsid++] = $p2;
	  }
	  $d = PHPWS_Node::newNode($procType);
	  $d->SetTaskname(PHPWS_Flow::$allProcessType[$procType]);
	  $d->SetInnerid($flow->processorsid);
	  $d->SetOrg($_SESSION['OBJ_user']->org);
	  $flow->processors[$flow->processorsid++] = $d;
	  $d->SetOutput(array($this->_innerid));
	  if ($p2) {
		  $d->SetInput(array($p2->innerid()));
		  $p2->SetDest($d->innerid()); $p2->SetSrc($this->_src);
	  }
	  else $d->SetInput(array());
	  if ($this->_src > 0) {
		  $flow->processors[$this->_src]->SetOutput(array_diff($flow->processors[$this->_src]->output(), array($this->_innerid)));
		  $flow->processors[$this->_src]->output()[] = $p2->innerid();
	  }
	  $this->_src = $d->innerid();
	  $this->saved = false;
	  return $d;
  }
  function & insertAfter($flow, $procType, $goodsType = 0, $copydata = NULL) {
	  if (!$goodsType && $this->_dest) return NULL;
	  if ($goodsType) {
		  $p2 = PHPWS_Node::newNode($goodsType);
		  if ($copydata) {
			  $p2->copy($copydata);
			  $p2->SetConnects(array());
			  $p2->SetId(0);
			  $p2->ChgStatus(1);
			  $p2->SetDest($p2->SetSrc(0));
		  }
		  $p2->SetInnerid($flow->goodsid);
		  $flow->goods[$flow->goodsid++] = $p2;
	  }
	  $d = PHPWS_Node::newNode($procType);
	  $d->SetTaskname(PHPWS_Flow::$allProcessType[$procType]);
	  $d->SetInnerid($flow->processorsid);
	  $d->SetOrg($_SESSION['OBJ_user']->org);
	  $flow->processors[$flow->processorsid++] = $d;
	  $d->SetInput(array($this->_innerid));

	  if ($p2) {
		  $d->SetOutput(array($p2->innerid()));
		  $p2->SetDest($this->_dest); $p2->SetSrc($d->innerid());
	  }
	  else $d->SetOutput(array());
	  if ($this->_dest > 0) {
		  $flow->processors[$this->_dest]->SetInput(array_diff($flow->processors[$this->_dest]->input(), array($this->_innerid)));
		  $flow->processors[$this->_dest]->input()[] = $p2->innerid();
	  }
	  $this->_dest = $d->innerid();
	  $this->saved = false;
	  return $d;
  }

  function sameProduct($p) {
	  if ($p->_product != $this->_product) return false;
	  if (!$this->_attribs && !$p->_attribs) return true;
	  if (!$p->_attribs || !$this->_attribs) return false;
	  if (!is_array($p->_attribs)) $p->_attribs = unserialize($attribs);
	  foreach ($this->_attribs as $i=>$v)
		  if ($attribs[$i] != $v) return false;
	  foreach ($attribs as $i=>$v)
		  if ($this->_attribs[$i] != $v) return false;
	  return true;
  }
  function convertUnit($u) {
	  if ($u == $this->_unit) return $this->_quantity;
	  if (Attributes::$unitsConv[$this->_unit][$u])
		  return $this->_quantity * Attributes::$unitsConv[$this->_unit][$u];

	  if (!is_array($u)) return 0;
	  if (isset($u['单位']) && isset($u['数量'])) {
		  if (isset($u[$u['单位']])) return $this->convertUnit($u[$u['单位']]) * $u['数量'];
		  elseif (Attributes::$unitsConv[$u['单位']][$u])
			  return $this->_quantity * Attributes::$unitsConv[$u['单位']][$u] * $u['数量'];
	  }

	  return 0;
  }
  function convertPrice($u) {
	  if ($u == $this->_unit) return $this->_price;
	  if (Attributes::$unitsConv[$u][$this->_unit])
		  return $this->_price * Attributes::$unitsConv[$u][$this->_unit];

	  if (!is_array($u)) return 0;
	  if (isset($u['单位']) && isset($u['数量'])) {
		  if (isset($u[$u['单位']])) return $this->convertUnit($u[$u['单位']]) / $u['数量'];
		  elseif (Attributes::$unitsConv[$u['单位']][$u])
			  return $this->_price / (Attributes::$unitsConv[$u['单位']][$u] * $u['数量']);
	  }

	  return 0;
  }

  function executedSum($committed = false) {
	  $sum = 0;
	  if ($this->_extra['acceptance']) {
		  foreach ($this->_extra['acceptance'] as $i=>$p) {
			  if ($i == 'plan') continue;
			  $p = $this->acceptance($p);
			  if (isset($p['quantity']) && $p['quantity']) $sum += $p['quantity'];
			  elseif (!$committed && isset($p['propose']) && $p['propose']) $sum += $p['propose'];
		  }
	  }
	  return $sum;
  }

  function quantity($type = 'plan', $slip = NULL) {
	  if ($slip) $d = $this->acceptance($slip);
	  switch ($type) {
		  case 'propose':
		  case 'executed':
			  if ($slip) {
				  if (isset($d['quantity'])) return $d['quantity'];
				  elseif (isset($d['propose']) && $type == 'propose') return $d['propose'];
				  return 0;
			  }
			  return $this->executedSum();
		  case 'plan':
			  if ($slip) return $d['plan'];
		  default:
			  return $this->_quantity;
	  }
  }
  function price($type = 'plan', $slip = NULL) {
	  switch ($type) {
		  case 'executed':
			  if (!$slip) {
				  $ex = $this->_extra['acceptance'];
				  $s = 0; $q = 0;
				  if ($ex) {
					  foreach ($ex as $i=>$p) {
						  if ($i == 'plan') continue;
						  $p = $this->acceptance($p);
						  if (isset($p['quantity'])) {
							  $q += $p['quantity'];
							  $s += $p['quantity'] * (isset($p['price'])?$p['price'] : $this->_price);
						  }
					  }
					  return round($s / $q / 100.0, 2);
				  }
			  }
		  case 'propose':
			  if ($slip) {
				  $p = $this->acceptance($slip);
				  if ($p['price']) return $p['price'] / 100.0;
			  }
		  default:
			  return $this->_price / 100.0;
	  }
  }
  function product() { return $this->_product; }
  function category() { return $this->_category; }
  function unit() { return $this->_unit; }
  function planquantity() { return $this->_extra['plan']?$this->_extra['plan']['quantity'] : $this->_quantity; }
  function weight() { return $this->_weight; }
  function volumn() { return $this->_volumn; }
  function freight() { return $this->_freight; }
  function goodtil() { return $this->_goodtil; }
  function goodtill() { return $this->_goodtil?date("Y-m-d", $this->_goodtil * 24 * 3600) : NULL; }
  function & attribs() { return $this->_attribs; }

  function SetGoodtil($val) { if ($this->_goodtil != $val) { $this->_goodtil = $val; $this->saved = false; } return $val; }
  function SetProduct($val) { if ($this->_product != $val) { $this->_product = $val; $this->saved = false; } return $val; }
  function SetProductCategory($val) {
	  if ($this->_product != $val) {
		  $this->_product = $val; $this->saved = false;
		  $c = $GLOBALS['core']->sqlSelect('mod_product', 'id', $this->_product);
		  $this->_category = $c[0]['category'];
		  unset($this->_extra['goods']);
		  unset($this->_extra['code']);
	  }
	  return $val;
  }
  function SetCategory($val) { if ($this->_category != $val) { $this->_category = $val; $this->saved = false; } return $val; }
  function SetUnit($val) { if ($this->_unit != $val) { $this->_unit = $val; $this->saved = false; } return $val; }
  function SetQuantity($val) { if ($this->_quantity != $val) { $this->_quantity = $val; $this->saved = false; } return $val; }
  function IncQuantity($val) { $this->_quantity += $val;  $this->_totalcost += $val * $this->_price; $this->saved = false; return $this->_quantity; }
  function SetWeight($val) { if ($this->_weight != $val) { $this->_weight = $val; $this->saved = false; } return $val; }
  function SetVolumn($val) { if ($this->_volumn != $val) { $this->_volumn = $val; $this->saved = false; } return $val; }
  function SetPrice($val) { if ($this->_price != $val) { $this->_price = round($val * 100, 0); $this->saved = false; } return $val; }
  function SetFreight($val) { if ($this->_freight != $val) { $this->_freight = $val; $this->saved = false; } return $val; }
  function SetAttribs($val) { $this->_attribs = $val; $this->saved = false; return $val; }
}

?>