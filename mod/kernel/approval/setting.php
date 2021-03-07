<?php
require_once(PHPWS_SOURCE_DIR . 'mod/accessories/class/formoptions.php');

  function treeorder($allorgs, $index) {
	  $r = array();
	  foreach ($allorgs[$index] as $p) {
		  $indent = str_pad("", 18 * ($p['level'] - 1), '&nbsp;', STR_PAD_LEFT);
		  $r[$p['id']] = $indent . $p['name'];
		  if (isset($allorgs[$p['id']])) {
			  foreach (treeorder($allorgs, $p['id']) as $i=>$v)
				  $r[$i] = $v;
		  }
	  }
	  return $r;
  }

  function init_formoptions_setting($formname, $settings) {
	  if (!$GLOBALS['core']->sqlSelect('formoptions', 'formname', $formname)) {
		  foreach ($settings as $f) {
			  $GLOBALS['core']->sqlInsert(array('formname'=>$formname, 'user_id'=>0, 'org'=>0, 'colname'=>$f['colname'], 'collable'=>$f['collable'], 'defaultval'=>$f['defaultval'], 'optin'=>$f['optin']), 'formoptions');
		  }
	  }
  }

  function remove_formoptions_setting($module) {
	  $formname = $module . "approval";
	  $GLOBALS['core']->sqlDelete('formoptions', 'formname', $formname);
  }

  function _approvalsetting($module, $permitted = array('autominpricevet', 'automaxpricevet', 'autominstockvet', 'automaxstockvet', 'checklevels', 'transfer')) {
/*
$settings = array(
	'sale'=>array('colname'=>'sale', 'collable'=>'销售订单号', 'defaultval'=>0, 'optin'=>0),
	'purchase'=>array('colname'=>'purchase', 'collable'=>'采购订单号', 'defaultval'=>0, 'optin'=>0),
	'shipping'=>array('colname'=>'shipping', 'collable'=>'发货单号', 'defaultval'=>0, 'optin'=>0),
	'receiving'=>array('colname'=>'receiving', 'collable'=>'验收单号', 'defaultval'=>0, 'optin'=>0),
	'outstock'=>array('colname'=>'outstock', 'collable'=>'出库单号', 'defaultval'=>0, 'optin'=>0),
	'instock'=>array('colname'=>'instock', 'collable'=>'入库单号', 'defaultval'=>0, 'optin'=>0),
	'waste'=>array('colname'=>'waste', 'collable'=>'报损单号', 'defaultval'=>0, 'optin'=>0),
	'augstock'=>array('colname'=>'augstock', 'collable'=>'库存调整单号', 'defaultval'=>0, 'optin'=>0),
	'production'=>array('colname'=>'production', 'collable'=>'生产计划单号', 'defaultval'=>0, 'optin'=>0),
	'return'=>array('colname'=>'return', 'collable'=>'退货单号', 'defaultval'=>0, 'optin'=>0));
init_formoptions_setting('ordernumformats', $settings);
*/
	  extract($_REQUEST);
	  extract($_POST);

	  $modulenames = array('sale'=>'销售', 'purchase'=>'采购', 'production'=>'生产');
	  $modulename = $modulenames[$module];

	  $formname = $module . "approval";
//	  $labels = array('autominpricevet'=>'最低价格', 'automaxpricevet'=>'最高价格', 'autominstockvet'=>'最低库存', 'automaxstockvet'=>'最高库存', 'automaxstockvet'=>'审批层级数', 'automaxstockvet'=>'移交办理部门');

	  return PHPWS_Form::makeForm('changeprod', $GLOBALS['SCRIPT'], array($s), "post", FALSE, TRUE); 
  }

?>