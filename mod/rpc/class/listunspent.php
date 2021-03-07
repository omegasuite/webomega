<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'listunspent');

  function params() {
	  extract($_POST);

	  $f = "地址：" . PHPWS_Form::formTextField("addr") . "<br>";
	  $f .= "最少确认：" . PHPWS_Form::formTextField("confirm", -1) . "<br>";

	  $f .= PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formHidden("data", "") . PHPWS_Form::formSubmit("提交查询");
	  $f .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($f), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_POST);

	  $block = strtolower($block);

	  if ($confirm <= 0) return array(array($block));
	  return array($confirm + 0, 9999999, array($addr));
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }

	  return $res->result;

  }
?>
