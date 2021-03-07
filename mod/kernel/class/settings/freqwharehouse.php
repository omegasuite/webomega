<?php
require_once(PHPWS_SOURCE_DIR . 'mod/accessories/class/formoptions.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/cache.php');

  function freqwharehouse() {
	extract($_REQUEST);
	extract($_POST);

	  return PHPWS_Form::makeForm('changeprod', $GLOBALS['SCRIPT'], array($s), "post", FALSE, TRUE); 
  }

?>