<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'trycontract');

//  $_POST['submiting'] = 1;

  function params() {
	  extract($_POST);

//	  if (!$_SESSION['SES_RPC']->tx) return "您需要先创建原始交易";

	  $f = PHPWS_Form::formTextArea("data", "", 5, 60) . "<br>";
	  $f .= PHPWS_Form::formSubmit("Try") . PHPWS_Form::formHidden("submiting", 1);
	  $f .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($f), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_POST);

	  if ($data) $s = $data;
	  elseif ($_SESSION['SES_RPC']->tx)
		  $s = bin2hex($_SESSION['SES_RPC']->tx->BtcEncode());
	  else {
		  exit("Can not try contract without transaction.");
	  }

	  return array($s);
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned with error: " . print_r($res->error, true);
	  }
	  if ($res->result->errors) {
		  return "Op " . $res->result->id . " returned with error: " . print_r($res->result->errors, true);
	  }

	  if (!$res->result) return $res;	// "Nothing is returned. Possible reasons: incorrect input hash, input has been spent, unauthorized signer (signer: cQdPVU5KSzLkD1rhvLJztvpWBu9TrVAE2iPxfgEQrzWuS5xLNRX6).";

	  if (strlen($res->result) < 160) {
		  return $res->result;
	  }

	  $raw = strtolower($res->result);
	  $r = new Reader($raw);
	  $r->StrToByte();

	  $tx = new MsgTx;
	  $tx->BtcDecode($r);
	  
	  if ($tx) {
		  $_SESSION['SES_RPC']->tx = $tx;
		  $_SESSION['SES_RPC']->tx->rawtx = $res->result;
	  }

	  return $tx;
  }
?>
