<?php
require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');

	  extract($_REQUEST);

	  $ps = $GLOBALS['core']->sqlSelect("mod_vehicle", 'id', $truct);
	  $ps = unserialize($ps[0]['drivers']);
	  $pv = array();
	  foreach ($ps as $p) {
		  $u = $GLOBALS['core']->sqlSelect("mod_users", 'user_id', $p);
		  $pv[] = array('text'=>$u[0]['realname']?$u[0]['realname'] : $u[0]['username'], 'value'=>$p);
	  }

	  exit(json_encode(array('drivers'=>$pv)));
?>