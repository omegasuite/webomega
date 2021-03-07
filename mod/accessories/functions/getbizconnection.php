<?php
require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/basic.php');

	  extract($_REQUEST);

	  if (!$CustomerType) $CustomerType = 0;

	  $fls = $GLOBALS['core']->query("SELECT DISTINCT cooporg,orgname FROM supply_mod_coop WHERE myorg='{$_SESSION['OBJ_user']->org}' AND issupplier=$CustomerType ORDER BY id ASC", false);

	  $custs = array();
	  while ($g = $fls->fetchRow()) {
		  if ($hasgoods) {
			  $ps = $GLOBALS['core']->query("SELECT * FROM supply_goodsonsale WHERE status>=0 AND " . goodsIncCond($g['cooporg']) . " LIMIT 0,1", false);
			  if (!$ps->fetchRow()) continue;
		  }
		  $d = array('id'=>$g['cooporg'], 'name'=>$g['orgname']);
		  $custs[] = $d;
	  }

	  exit(json_encode(array('status'=>"OK", 'result'=>$custs)));
?>