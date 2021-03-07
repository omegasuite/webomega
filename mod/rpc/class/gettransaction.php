<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'gettransaction');

  function params() {
	  extract($_POST);

	  $f = "交易哈希：" . PHPWS_Form::formTextField("hash") . "<br>";

	  $f .= PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formHidden("data", "") . PHPWS_Form::formSubmit("提交查询");
	  $f .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($f), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_POST);

	  $hash = strtolower($hash);
	  return array($hash);
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }

	  return $res->result;

  }
?>
