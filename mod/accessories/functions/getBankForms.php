<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/basic.php');

	extract($_REQUEST);
	

	$resp['forms'] = array();
	$c = $GLOBALS['core']->sqlSelect('credit_forms', 'org', $bank);
	if ($c) {
		foreach ($c as $cc)
			if ($cc['activated']) $resp['forms'][] = array('value'=>$cc['id'], 'text'=>$cc['formname']);
	}
	exit(json_encode($resp));

?>