<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'makehash');

  function params() {
	  extract($_POST);

	  if ($data) {
		  $s = pack("H*", $data);
		  $h = hash("sha256", $s);
		  exit($h);
	  }

	  $f .= "Hex数据：" . PHPWS_Form::formTextArea("data", "", 5, 60) . "<br>";
	  $f .= PHPWS_Form::formSubmit("Hash");
	  $f .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($f), "post", FALSE, TRUE) . "</center>";
  }

?>
