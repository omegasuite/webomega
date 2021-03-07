<?php
require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/accessories/class/formoptions.php');

	  extract($_REQUEST);

	  $requests = explode(",", $requests);
	  $res = array();
	  exit(json_encode(array('status'=>"OK", 'result'=>$res)));
?>