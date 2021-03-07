<?php
require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');

	  extract($_REQUEST);
	  
	  $ps = $GLOBALS['core']->query("SELECT w.name, w.id FROM supply_mod_wharehouse as w JOIN supply_mod_locations as p ON w.place=p.id AND w.rootOrg={$_SESSION['OBJ_user']->rootOrg} AND p.province='$province'" . ($city?" AND p.city='$city'" : ''), false);
	  
	  $pv = array();
	  while ($p = $ps->fetchRow()) {
		  $pv[] = array('id'=>$p['id'], 'html'=>PHPWS_Form::formCheckBox("wharehouse[{$p['id']}]", $p['id'], 0) . $p['name']);
	  }

	  exit(json_encode(array('status'=>"OK", 'wharehouses'=>$pv)));
?>