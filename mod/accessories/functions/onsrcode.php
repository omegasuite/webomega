<?php
require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/consts.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/flow.php');

	$platform = 'web';
	if ($GLOBALS['WXMODE']) $platform = 'weixin';
	extract($_REQUEST);
	if ($codeid) $c = $GLOBALS['core']->sqlSelect($for, 'id', $codeid);

	if ($for == 'mod_flow' && $c) exit(processor($platform, $c[0]));
	if ($for == 'srcodes' && $c) {
		$d = $GLOBALS['core']->sqlSelect($c[0]['tablename'], 'id', $c[0]['tablerow']);
		if ($c[0]['tablename'] == 'mod_flow') exit(processor($platform, $d[0], $c[0]));
	}

	if ($code) {
		$res = $GLOBALS['core']->query("SELECT * FROM supply_srcodes WHERE srcode='$code' OR srcode='" . urlencode($code) . "' ORDER BY id DESC LIMIT 0,1", false);
		$k = $res->fetchRow();
		if (!$k) exit(json_encode(array('status'=>'ERROR', 'msg'=>'未知二维码')));

		if (in_array($k['tablename'], array('stock', 'purchase', 'sale'))) {
			include (PHPWS_SOURCE_DIR . 'mod/' . $k['tablename'] . '/class/onsrcode.php');
			exit(json_encode(array('status'=>'ERROR', 'msg'=>$k['tablename'] . '：未知二维码')));
		}

		$d = $GLOBALS['core']->sqlSelect($k['tablename'], 'id', $k['tablerow']);
		if ($k['tablename'] == 'mod_flow') {
			exit(processor($platform, $d[0], $k['data']));
		}
	}

	exit(json_encode(array('status'=>'ERROR', 'msg'=>'未知二维码')));

function processor($platform, $c, $ext = NULL) {
	$_REQUEST['order'] = $c['id'];
	switch ($c['resourceType']) {
		case GOODS_NODE:
			switch ($platform) {
				case 'weixin':
					break;
				case 'android':
					break;
				case 'iphone':
					break;
				default:
					break;
			}
			break;
		case PROCESSOR_NODE:
			include_once(PHPWS_SOURCE_DIR . 'mod/production/class/onsrcode.php');
			break;
		case SALE_NODE:
			include_once(PHPWS_SOURCE_DIR . 'mod/sale/class/onsrcode.php');
			break;
		case TRANSPORT_NODE:
			break;
		case STOCK_NODE:
			$orderNum = explode("orderNum=", $ext);
			$_REQUEST['orderNum'] = $orderNum[1];
			include_once(PHPWS_SOURCE_DIR . 'mod/stock/class/onsrcode.php');
			break;
		case PURCHASE_NODE:
			include_once(PHPWS_SOURCE_DIR . 'mod/purchase/class/onsrcode.php');
			break;
		case RETURN_NODE:
			switch ($platform) {
				case 'weixin':
					break;
				case 'android':
					break;
				case 'iphone':
					break;
				default:
					break;
			}
			break;
		case WHAREHOUSE_XFER_NODE:
			include_once(PHPWS_SOURCE_DIR . 'mod/stock/class/onsrcode.php');
			break;
		case WASTE_NODE:
			switch ($platform) {
				case 'weixin':
					break;
				case 'android':
					break;
				case 'iphone':
					break;
				default:
					break;
			}
			break;
		case TRUCK_NODE:
			switch ($platform) {
				case 'weixin':
					break;
				case 'android':
					break;
				case 'iphone':
					break;
				default:
					break;
			}
			break;
		case AUGSTOCK_NODE:
			include_once(PHPWS_SOURCE_DIR . 'mod/stock/class/onsrcode.php');
			break;
	}
	return $s;
}
?>