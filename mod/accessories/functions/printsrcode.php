<?php
require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'core/phpqrcode.php');

	  extract($_REQUEST);

	  $params = '';
	  foreach ($_REQUEST as $i=>$v) {
		  if (in_array($i, array('module', 'MOD_op', 'label'))) continue;
		  $params .= "&$i=$v";
	  }

	  exit("<center><img with=128 src=./index.php?module=accessories&MOD_op=getSrcode$params></a><br>" . URLDecode($label) . "</center>");
?>