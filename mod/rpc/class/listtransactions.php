<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'listtransactions');

  $_POST['submiting'] = 1;

  function params() {
	  extract($_POST);

	  $f = "列表交易数：" . PHPWS_Form::formTextField("count", 10, 5) . "<br>";
	  $f .= "开始：" . PHPWS_Form::formTextField("from", 0, 5) . "<br>";
	  $f .= PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formHidden("data", "") . PHPWS_Form::formSubmit("获取交易列表");
	  $f .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($f), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_POST);

	  return array("*", $count + 0, $from + 0, false);
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }

	  return $res->result;

  }
?>
