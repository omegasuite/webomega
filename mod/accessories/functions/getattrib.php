<?php
require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'core/phpqrcode.php');

	  extract($_REQUEST);
	  
	  $t = $GLOBALS['core']->sqlSelect('attribs','id', $id);
	  $t = $t[0];
	  unset($t['rootOrg']);
	  $t['attribs'] = convformattrib(unserialize($t['attribs']));

	  exit(json_encode(array('status'=>'OK', 'result'=>$t)));

	  function convformattrib($attrib) {
		  $s = array();
		  if (is_array($attrib)) foreach ($attrib as $name=>$val) {
			  if (is_array($val)) $s[] = array('name'=>$name, 'val'=>convformattrib($val));
			  else $s[] = array('name'=>$name, 'val'=>$val);
		  }
		  return $s;
	  }
?>