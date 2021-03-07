<?php
require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'core/phpqrcode.php');

	  $cat = myCategories();
	  
	  $category = array();
	  foreach (myCategories() as $id=>$c) $category[] = array('id'=>$id, 'name'=>$c);

	  exit(json_encode(array('status'=>'OK', 'result'=>$category)));
?>