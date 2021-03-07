<?php
require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');

	  extract($_REQUEST);
	  $i = 1;
	  if (!$province) {
		  $ps = $GLOBALS['core']->query("SELECT DISTINCT province FROM supply_mod_locations", false);
		  $pv = array();
		  while ($p = $ps->fetchRow()) 
			  if ($p['province']) $pv[] = array('text'=>$p['province'], 'value'=>$p['province']);

		  exit(json_encode(array('provinces'=>$pv)));
	  }
	  if (!$city) {
		  $ps = $GLOBALS['core']->query("SELECT DISTINCT city FROM supply_mod_locations WHERE province='$province'", false);
		  $pv = array();
		  while ($p = $ps->fetchRow()) 
			  if ($p['city']) $pv[] = array('text'=>$p['city'], 'value'=>$p['city']);

		  $pl = array();
		  if (!$pv) {
			  $ps = $GLOBALS['core']->query("SELECT * FROM supply_mod_locations WHERE province='$province'", false);
			  while ($p = $ps->fetchRow()) 
				  if ($p['name']) $pl[] = array('text'=>$p['name'], 'value'=>$p['id']);
		  }

		  exit(json_encode(array('cities'=>$pv, 'places'=>$pl)));
	  }

	  $ps = $GLOBALS['core']->query("SELECT * FROM supply_mod_locations WHERE province='$province'" . ($city?" AND city='$city'" : ''), false);
	  $pl = array();
	  while ($p = $ps->fetchRow()) $pl[] = array('text'=>$p['name'], 'value'=>$p['id']);

	  exit(json_encode(array('places'=>$pl)));
?>