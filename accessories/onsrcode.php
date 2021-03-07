<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/consts.php');

	$platform = 'web';

	extract($_REQUEST);

	if ($codeid) $c = $GLOBALS['core']->sqlSelect($for, 'id', $codeid);

	if ($c) exit(processor($platform, $for, $c));

	if ($code) {
		$res = $GLOBALS['core']->query("SELECT * FROM srcodes WHERE srcode='$code' ORDER BY id DESC LIMIT 0,1", true);
		$k = $res->fetchRow();
		if (!$k) exit(json_encode(array('status'=>'ERROR', 'msg'=>'未知二维码')));
		$d = $GLOBALS['core']->sqlSelect($k['tablename'], 'id', $k['tablerow']);
		exit(array('status'=>'OK', 'result'=>processor($platform, $k['tablename'], $d)));
	}

	$CNT_user["content"] = '未知二维码';

function processor($platform, $for, $c) {
	switch ($for) {
		case 'mod_flow':
			$c = $c[0];
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
					switch ($platform) {
						case 'weixin':
							break;
						case 'android':
							break;
						case 'iphone':
							break;
						default:
							$s = "./index.php?module=production&MOD_op=production&order=$codeid";
							header("location: $s");
							break;
					}
					break;
				case SALE_NODE:
					switch ($platform) {
						case 'weixin':
							break;
						case 'android':
							break;
						case 'iphone':
							break;
						default:
							$s = "./index.php?module=sale&MOD_op=sale&order=$codeid";
							header("location: $s");
							break;
					}
					break;
				case TRANSPORT_NODE:
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
				case STOCK_NODE:
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
				case PURCHASE_NODE:
					switch ($platform) {
						case 'weixin':
							break;
						case 'android':
							break;
						case 'iphone':
							break;
						default:
							$s = "./index.php?module=purchase&MOD_op=purchase&order=$codeid";
							header("location: $s");
							break;
					}
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
			}
			break;
	}
	return $s;
}
?>