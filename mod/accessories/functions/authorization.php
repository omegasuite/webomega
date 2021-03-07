<?php

require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/cache.php');

function authorized($right, $org, $user, $param = NULL) {
	if ($param)
		$auth = $GLOBALS['core']->sqlSelect('mod_authreq', array('requester'=>$user, 'org'=>$org, 'rights'=>$right, 'param'=>$param));
	else $auth = $GLOBALS['core']->sqlSelect('mod_authreq', array('requester'=>$user, 'org'=>$org, 'rights'=>$right));
	if (!$auth) return false;
	if ($auth[0]['status'] > 0) return true;
	return false;
}

$rightNames = array('deliver'=>'货物交接');

function explainRightRaram($right, $param) {
	if (!$param) return;
	switch ($right) {
		case 'deliver':
			list ($good, $acpt) = each($param);
			$s = $acpt?"验收" : "交付" . '<br>';
			$good = $GLOBALS['core']->sqlSelect('mod_flow', 'id', $good);
			$good = $good[0];
			if (!$good) return $s;
			if ($good['product']) $s .= '产品：' . prodCache($good['product']);
			if ($good['place']) {
				$p =  locCache($good['place']);
				$s .= '预计地点：' . $p['province'] . $p['city'] . $p['name'] . '<br>';
			}
			if ($good[$acpt?'end' : 'start']) $s .= '预计时间：' . $good[$acpt?'end' : 'start'];
			return $s;
			break;
	}
}
?>