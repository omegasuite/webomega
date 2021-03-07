<?php
require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');

	  extract($_REQUEST);
	  $i = 1;
	  if (!$loc) exit();

	  $GLOBALS['core']->sqlUpdate(array('lat'=>$lat, 'lng'=>$lng), "mod_locations", 'id', $loc);

	  exit();
?>