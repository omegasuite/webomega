<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'getwalletinfo');

  $_POST['submiting'] = 1;

  function params() {
	  extract($_POST);
	  
	  $f = PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formHidden("data", "") . PHPWS_Form::formSubmit("获取钱包信息");
	  $f .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . $s . PHPWS_Form::makeForm('addProduct', "index.php", array($f), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_POST);

	  return array();
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }
	  
	  return $res->result;
  }
?>
