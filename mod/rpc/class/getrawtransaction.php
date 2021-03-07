<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'getrawtransaction');

  function params() {
	  extract($_POST);
	  
	  $s = "交易哈希：" . PHPWS_Form::formTextField("utxo") . "<br>";

	  $s .= PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formSubmit("获取UTXO信息");
	  $s .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($s), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_POST);
	  return array($utxo);
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }

	  if ($res->result->errors) {
		  return "Op " . $res->result->id . " returned width error: " . print_r($res->result->errors, true);
	  }

	  $raw = strtolower($res->result);

	  if ($_SESSION['SES_RPC']->useraw) $tx = $_SESSION['SES_RPC']->tx = $res->result;
	  else {
		  $r = new Reader($raw);
		  $r->StrToByte();

		  $tx = new MsgTx;
		  $tx->BtcDecode($r);

		  if ($tx) $_SESSION['SES_RPC']->tx = $tx;
	  }

//	  $tx->visual = "<a href=./index.php?module=rpc&MOD_op=visual&in=" . json_encode($tx->TxIn) . "&out=" . json_encode($tx->TxOut) . " target=_blank>交易图解</a>";

	  return $tx;
  }

?>
