<?php
require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'core/phpqrcode.php');

	  extract($_REQUEST);
	  
	  $GLOBALS['core']->sqlUpdate(array('allowneg'=>$allow?1 : 0), 'mod_wharehouse', 'id', $wh);
	  exit("$wh allowneg to $allow");

?>