<?php
require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
error_reporting (E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

	  extract($_REQUEST);

	  $pv['addresses'] = $c;

	  exit(json_encode($pv));
?>
