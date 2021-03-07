<?php
require_once(PHPWS_SOURCE_DIR . 'mod/basic.php');

	  extract ($_REQUEST);
	  extract ($_POST);

	  $required = json_decode($required);

	  $data = array();
	  foreach ($required as $r) {
		  switch ($r) {
			  case 'wharehouse':
				  $s = getfreqwharehouse(true, true);

//				  $c = $GLOBALS['core']->sqlSelect('mod_wharehouse', 'ownerOrg', $_SESSION['OBJ_user']->org);
//				  $s = array();
//				  if ($c) foreach ($c as $cs) $s[] = array('id'=>$cs['id'], 'name'=>$cs['name']);
				  break;
			  case 'province':
				  $ps = $GLOBALS['core']->query("SELECT DISTINCT province FROM supply_mod_locations", false);
				  $s = array();
				  while ($p = $ps->fetchRow()) $s[] = $p['province'];
				  break;
		  }
		  $data[$r] = $s;
	  }

	  exit(json_encode(array('status'=>'OK', 'result'=>$data)));

?>