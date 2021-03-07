<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/basic.php');

	extract($_REQUEST);

	$c = $GLOBALS['core']->sqlSelect('mod_category', 'id', $cat);
	$name = $c?$c[0]['name'] : '';
	$c = unserialize($c[0]['spec']);
	$s = '';
	if ($c) foreach ($c as $nm=>$val) {
		$s .= $nm . "：" . PHPWS_Form::formHidden("specname[]", $nm);
		switch ($val['type']) {
			case 'number':
			case 'text':
				$s .= PHPWS_Form::formTextField("specval[]", '');
				break;
			case 'enum':
				$s .= PHPWS_Form::formSelect("specval[]", $val['range'], NULL, true, false);
				break;
		}
		$s .= "<br>";
	}
	if ($subcat) {
		$resp = array('catname'=>$name, 'cat'=>array());
		$cat += 0;
		if ($cat == 0 && $GLOBALS['core']->sqlSelect('mod_watchcats', 'user_id', $_SESSION["OBJ_user"]->user_id)) {
			$c = $GLOBALS['core']->query("SELECT c.* FROM supply_mod_watchcats as w JOIN supply_mod_category as c ON (c.ownerOrg=0 OR c.ownerOrg=" . $_SESSION["OBJ_user"]->org . " OR c.rfcperiod<'" . date('Y-m-d') . "') AND w.user_id=" . $_SESSION["OBJ_user"]->user_id . " AND w.category=c.id", false);
		}
		else {
			$c = $GLOBALS['core']->query("SELECT * FROM mod_category WHERE parentid=" . max($cat, 0) . " AND (ownerOrg=0 OR ownerOrg=" . $_SESSION["OBJ_user"]->org . " OR rfcperiod<'" . date('Y-m-d') . "')", true);
		}

		$head = true;
		while ($cc = $c->fetchRow()) {
			if ($head) $resp['cat'][] = array('value'=>$cat, 'text'=>'');
			$resp['cat'][] = array('value'=>$cc['id'], 'text'=>$cc['name']);
			$head = false;
		}
	}
	switch ($subcat) {
		case 1:
			$resp['spec'] = $s;
			exit(json_encode($resp));
		case 2:
			$resp['products'] = array();
			$c = $GLOBALS['core']->sqlSelect('mod_product', 'category', $cat);
			if ($c) {
				foreach ($c as $cc)
					if ($cc['status'] >= 0) $resp['products'][] = array('value'=>$cc['id'], 'text'=>$cc['name']);
			}
			exit(json_encode($resp));
		case 3:
			$resp['goods'] = array();
			$c = $GLOBALS['core']->query("SELECT g.id as id,g.name as name, p.name as pname FROM supply_goodsonsale as g JOIN supply_mod_product as p ON g.category=$cat AND g." . goodsIncCond() . " AND g.product=p.id", false);
			if ($c) {
				while ($cc = $c->fetchRow())
					if ($cc['status'] >= 0) $resp['goods'][] = array('value'=>$cc['id'], 'text'=>$cc['name']?$cc['name'] : $cc['pname']);
			}
			exit(json_encode($resp));
	}
	exit($s);
?>