<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'getblocktxhashes');

  function params() {
	  extract($_POST);

	  $s = "区块哈希：" . PHPWS_Form::formTextField("block") . "<br>";
	  
	  $s .= PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formSubmit("获取区块交易哈希");
	  $s .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($s), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_POST);
	  $block = strtolower($block);
	  return array($block);
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }

	  return $res->result;
  }
?>
