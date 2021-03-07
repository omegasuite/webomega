<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'gettxout');

  function params() {
	  extract($_REQUEST);
	  extract($_POST);
	  
	  $s = "UTXO哈希：" . PHPWS_Form::formTextField("utxo", $utxo) . "<br>";
	  $s .= "UTXO序号：" . PHPWS_Form::formTextField("txseq", $txseq) . "<br>";

	  $s .= PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formSubmit("获取UTXO信息");
	  $s .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($s), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_REQUEST);
	  extract($_POST);
	  return array($utxo, $txseq + 0, false);
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }

	  return $res->result;
  }

?>
