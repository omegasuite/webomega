<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');

	extract($_REQUEST);

	$c = $GLOBALS['core']->sqlSelect('mod_product', 'id', $prod);
	if ($c[0]['attribs']) {
		$c = unserialize($c[0]['attribs']);
		$s = ''; $g = '';
		foreach ($c as $h=>$v) {
			$s .= $g . $h . "：" . PHPWS_Form::formTextField("attribs[$handle][$h]", NULL, 5);
			$g = "<br>";
		}
		exit(json_encode(array('status'=>'OK', 'attribs'=>$s)));
	}
	exit(array('status'=>'NONE'));
?>