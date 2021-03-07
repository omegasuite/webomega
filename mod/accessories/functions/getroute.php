<?php
require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');

	  extract($_REQUEST);
	  $i = 1;
	  if (!$stops) exit();

	  $stops = explode(",", $stops);
	  $ps = $GLOBALS['core']->query("SELECT DISTINCT a.linegroup,a.lineid,a.name FROM supply_mod_liner as a JOIN supply_mod_liner as b ON a.lineid=b.lineid AND a.station={$stops[0]} AND b.station={$stops[1]}", false);

	  $pv = array();
	  $groups = array();
	  while ($p = $ps->fetchRow()) {
		  if ($p['lineid']) {
			  $pv[] = array('text'=>$p['name'], 'value'=>$p['lineid']);
			  $groups[$p['linegroup']] = array('text'=>$p['name'], 'value'=>$p['linegroup']);
		  }
	  }

	  exit(json_encode(array('liner'=>$pv, 'linegroup'=>$groups)));
?>