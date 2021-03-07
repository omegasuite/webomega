<?php
require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');

	  extract($_REQUEST);
	  if ($checked) {
		  $u = $GLOBALS['core']->query("UPDATE mod_menu SET app=app | $right WHERE id=$id", true);
	  }
	  else {
		  $u = $GLOBALS['core']->query("UPDATE mod_menu SET app=app & ~$right WHERE id=$id", true);
	  }

	  exit();
?>