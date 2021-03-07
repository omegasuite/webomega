<?php
require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');

	  extract($_REQUEST);

	  if (!$CustomerType) $CustomerType = 0;

	  $fls = $GLOBALS['core']->query("SELECT * FROM supply_mod_coop WHERE myorg='{$_SESSION['OBJ_user']->org}' AND issupplier=$CustomerType ORDER BY id ASC", false);

	  $custs = array();
	  while ($g = $fls->fetchRow()) {
		  $d = array('id'=>$g['id'], 'name'=>$g['orgname'], 'relationship'=>$g['coopbiztype'], 'org'=>$g['cooporg'], 'relationship'=>$g['coopbiztype']);
		  if ($g['product'] > 0) $d['product'] = $g['product'];
		  elseif ($g['product'] < 0) $d['category'] = -$g['product'];

		  $custs[] = $d;
	  }

	  exit(json_encode(array('status'=>"OK", 'result'=>$custs)));
?>