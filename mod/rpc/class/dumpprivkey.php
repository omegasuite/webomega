<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'dumpprivkey');

  function params() {
	  extract($_POST);

	  $s = "地址：" . PHPWS_Form::formTextField("address") . "<br>";
	  
	  $s .= PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formSubmit("导出地址WIF");
	  $s .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($s), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_POST);
	  $address = $address;
	  return array($address);
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }
	  
	  return $res->result;
  }
?>
