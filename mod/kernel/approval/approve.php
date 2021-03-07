<?php
require_once(PHPWS_SOURCE_DIR . 'mod/basic.php');

  function approvekernel($module, $flow, $g, $override = false) {
	  extract ($_REQUEST);
	  extract ($_POST);

	  $status = $g->workstatus();

	  $formname = $module . "approval";

	  foreach (formoptions($formname) as $f) {
		  $s = $f['colname'];
		  extract(array($f['colname']=>$f['optin']));
		  extract(array($f['colname'] . "val"=>$f['defaultval']));
	  }

	  $rightDir	= PHPWS_SOURCE_DIR . "mod/$module/conf/module_rights.txt";
	  if (file_exists($rightDir)) {
		  if ($rows = file($rightDir)) {
			  foreach ($rows as	$rights) {
				  $subright = explode("::", $rights);
				  extract(array($subright[0] . "name"=>$subright[1]));
			  }
		  }
	  }

	  $modulenames = array('sale'=>'销售', 'purchase'=>'采购', 'production'=>'生产');
	  $modulename = $modulenames[$module];

	  $ordernum = $g->orderNum()?$g->orderNum() : $g->id();

	  switch ($status) {
		  case FLOW_PLAN:
			  if (!$_SESSION["OBJ_user"]->checkUserPermission($module, "enterplan") && $_SESSION['OBJ_user']->org == $g->org())
					return array('code'=>2001, "msg"=>"您没有提交" . $enterplanname . "订单的权限·。");

			  if (!$override) {
				  $products = array();
				  foreach ($g->input() as $i) {
					  $p = $flow->goods[$i];
					  if ($p->resourceType() != GOODS_NODE) continue;
					  $products[] = $p->product();
				  }
			  }

			  $pu = array();
			  if (!$override && ($autominpricevet || $automaxpricevet)) {
				  $match = array();
				  if ($autominpricevet) $match[] = "tobuy=0";
				  if ($automaxpricevet) $match[] = "tobuy=1";
				  $check = $GLOBALS['core']->query("SELECT * FROM minsaleprice WHERE product IN (" . implode(",", $products) . ") AND (" . implode(" OR ", $match) . ")", true);

				  while ($c = $check->fetchRow()) {
					  $c['attribs'] = $c['attribs']?unserialize($c['attribs']) : array();
					  if (Attributes::$basicUnits[$c['unit']]) $c['attribs']['单位'] = $c['unit'];
					  $pu[] = $c;
				  }
			  }
			  if (!$override && ($autominstockvet || $automaxstockvet)) {
				  $match = array();
				  if ($autominstockvet) $match[] = "(minmax=0 AND wharehouse=$autominstockvetval)";
				  if ($automaxstockvet) $match[] = "(minmax=1 AND wharehouse=$automaxstockvetval)";
				  $check = $GLOBALS['core']->query("SELECT * FROM minstorelevel WHERE product IN (" . implode(",", $products) . ") AND (" . implode(" OR ", $match) . ")", true);

				  while ($c = $check->fetchRow()) {
					  $c['attribs'] = $c['attribs']?unserialize($c['attribs']) : array();
					  if (Attributes::$basicUnits[$c['unit']]) $c['attribs']['单位'] = $c['unit'];
					  $pu[] = $c;
				  }
			  }

			  if (!$override && ($autominpricevet || $automaxpricevet || $autominstockvet || $automaxstockvet)) {
				  foreach ($g->input() as $i) {
					  $p = $flow->goods[$i];
					  if ($p->resourceType() != GOODS_NODE) continue;

					  // find best match of product and attributes
					  $u = $p->attribs();
					  if (Attributes::$basicUnits[$p->unit()]) $u['单位'] = $p->unit();
					  $score = -1000; $selu = NULL;
					  foreach ($pu as $c) {
						  if ($c['product'] != $p->product()) continue;
						  if (!matchAttribs($c['attribs'], $u)) continue;
						  $t = closeness($u, $c['attribs']);
						  if ($t < 0) continue;
						  if ($t > $score) { $score = $t; $selu = $c; }
					  }

					  if (!$selu) continue;		// no restriction for this product

					  $bu1 = Attributes::$basicUnits[$p->unit()]?array('单位'=>$p->unit(), '数量'=>1) : basicUnit($p->attribs());
					  $bu2 = Attributes::$basicUnits[$selu['unit']]?array('单位'=>$selu['unit'], '数量'=>1) : basicUnit($selu['attribs']);

					  if (!Attributes::$unitsConv[$bu1['单位']][$bu2['单位']])
						  return array('code'=>1002, "msg"=>"无法对价格受限制的产品进行单位转换。");

					  if (isset($selu['tobuy'])) {
						  if ($selu['tobuy']) {
							  if ($p->price() > $selu['price'] * Attributes::$unitsConv[$bu1['单位']][$bu2['单位']] * $bu1['数量'] / $bu2['数量']) 
								  return array('code'=>1000, "msg"=>"产品价格高于价格限制。");
						  }
						  else {
							  if ($p->price() < $selu['price'] * Attributes::$unitsConv[$bu1['单位']][$bu2['单位']] * $bu1['数量'] / $bu2['数量']) 
								  return array('code'=>1000, "msg"=>"产品价格低于价格限制。");
						  }
					  }

					  if (isset($selu['minmax'])) {
						  $wh = $GLOBALS['core']->query("SELECT * FROM mod_stocked_goods WHERE wharehouse={$selu['wharehouse']} AND productCat=" . $p->product() . " AND status>=0 AND quantity!=0", true);
						  $qsum = 0;
						  while ($h = $wh->fetchRow()) {
							  $h['attribs'] = $h['attribs']?unserialize($h['attribs']) : array();
							  if (!matchAttribs($selu['attribs'], $h['attribs'])) continue;
							  $bu3 = Attributes::$basicUnits[$h['unit']]?array('单位'=>$h['unit'], '数量'=>1) : basicUnit($h['attribs']);
							  if (!Attributes::$unitsConv[$bu3['单位']][$bu2['单位']]) continue;
							  $qsum += $h['quantity'] * Attributes::$unitsConv[$bu3['单位']][$bu2['单位']] * $bu3['数量'];
						  }
						  if ($selu['minmax']) {
							  if (($p->quantity()  * Attributes::$unitsConv[$bu1['单位']][$bu2['单位']] * $bu1['数量']) + $qsum > $selu['quantity'] * $bu2['数量']) 
								  return array('code'=>1000, "msg"=>"产品库存高于库存上限。");
						  }
						  else {
							  if ($qsum - ($p->quantity() * Attributes::$unitsConv[$bu1['单位']][$bu2['单位']] * $bu1['数量'] ) < $selu['quantity'] * $bu2['数量']) 
								  return array('code'=>1000, "msg"=>"产品库存低于库存下限。");
						  }
					  }
				  }
			  }
			  if ($checklevelsval == 0) {
				  $g->ChgStatus(FLOW_EXECUTING);
				  PHPWS_Notice::broadcastwork(NOTICE_APPROVALMSG, $g->org(), $g->id(), $ordernum . "号{$modulename}订单开始执行", "approveplan");
			  }
			  else {
				  $g->ChgStatus(FLOW_FIRM);
				  PHPWS_Notice::broadcastwork(NOTICE_APPROVALMSG, $g->org(), $g->id(), $ordernum . "号{$modulename}订单提交审批", "approveplan");
			  }
			  break;
		  case FLOW_FIRM:
			  if (!$_SESSION["OBJ_user"]->checkUserPermission($module, "checkplan") && in_array($g->org(), suborgs($_SESSION['OBJ_user']->org, false, true)))
					return array('code'=>2001, "msg"=>"您没有审查" . $enterplanname . "订单的权限。");

			  if ($checklevelsval == 1) {
				  $g->ChgStatus(FLOW_EXECUTING);
				  $g->SetExtra(array('approval'=>$_SESSION['OBJ_user']->user_id));
				  if ($transfer && $transferval) {
					  $g->Setorg($transferval);
					  PHPWS_Notice::broadcastwork(NOTICE_APPROVALMSG, $g->org(), $g->id(), $ordernum . "号{$modulename}订单移交执行", "approveplan");
				  }
				  else PHPWS_Notice::broadcastwork(NOTICE_APPROVALMSG, $g->org(), $g->id(), $ordernum . "号{$modulename}订单通过审批，开始执行", "approveplan");
			  }
			  else {
				  $g->ChgStatus(FLOW_CHECKED);
				  PHPWS_Notice::broadcastwork(NOTICE_APPROVALMSG, $g->org(), $g->id(), $ordernum . "号{$modulename}订单通过审核", "approveplan");
			  }
			  break;
		  case FLOW_CHECKED:
			  if (!$_SESSION["OBJ_user"]->checkUserPermission($module, "approveplan") && in_array($g->org(), suborgs($_SESSION['OBJ_user']->org, false, true)))
					return array('code'=>2001, "msg"=>"您没有批准" . $enterplanname . "订单的权限。");

			  $g->ChgStatus(FLOW_EXECUTING);
			  $g->SetExtra(array('approval'=>$_SESSION['OBJ_user']->user_id));
			  if ($transfer && $transferval) {
				  $g->Setorg($transferval);
				  PHPWS_Notice::broadcastwork(NOTICE_APPROVALMSG, $g->org(), $g->id(), $ordernum . "号{$modulename}订单移交执行", "approveplan");
			  }
			  else PHPWS_Notice::broadcastwork(NOTICE_APPROVALMSG, $g->org(), $g->id(), $ordernum . "号{$modulename}订单通过审批，开始执行", "approveplan");
			  break;
	  }

	  $flow->saved = false;
	  $flow->saveflow(false);

	  return NULL;
  }
?>