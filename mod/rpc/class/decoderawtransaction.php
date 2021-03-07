<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'decoderawtransaction');

  function params() {
	  extract($_POST);
	  
	  $s = "交易原始内容：" . PHPWS_Form::formTextField("utxo") . "<br>";

	  $s .= PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formSubmit("解码交易");
	  $s .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($s), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_POST);
	  $raw = strtolower($utxo);
	  $r = new Reader($raw);
	  $r->StrToByte();

	  return array($r->src);
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }

	  return $res->result;
  }

?>
