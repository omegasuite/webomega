<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/basic.php');

	extract($_REQUEST);

	$resp = array('cat'=>array());
	$c = $GLOBALS['core']->query("SELECT c.* FROM supply_mod_watchcats as w JOIN supply_mod_category as c ON (c.ownerOrg=0 OR c.ownerOrg=" . $_SESSION["OBJ_user"]->org . " OR c.rfcperiod<'" . date('Y-m-d') . "') AND w.user_id=" . $_SESSION["OBJ_user"]->user_id . " AND w.category=c.id", false);

	while ($cc = $c->fetchRow()) 
		$resp['cat'][] = array('value'=>$cc['id'], 'text'=>$cc['name']);

	exit(json_encode($resp));
?>