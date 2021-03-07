<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'addnode');

  function params() {
	  extract($_POST);
	  
	  $s .= "操作：" . PHPWS_Form::formSelect("op", array("add", "remove", "onetry"), "add", true, false) . "<br>";
	  $s .= "节点地址：" . PHPWS_Form::formTextField("host") . "<br>";
	  $s .= PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formSubmit("提交");
	  $s .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($s), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_POST);

	  return array($host, $op);
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }

	  return $res;
  }

?>
