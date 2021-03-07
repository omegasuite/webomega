<?php
require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/cache.php');

	  extract($_REQUEST);

	  $result = array('orghead'=>$orgsd?implode(" => ", $orgsd) : '', 'orgs'=>$olist);
	  exit(json_encode($result));
?>