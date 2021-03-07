<?php
require_once(PHPWS_SOURCE_DIR . 'mod/accessories/class/formoptions.php');
require_once(PHPWS_SOURCE_DIR . 'mod/basic.php');

  function setings() {
	extract($_REQUEST);
	extract($_POST);

	return $s;
  }

?>