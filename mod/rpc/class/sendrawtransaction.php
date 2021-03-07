<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'sendrawtransaction');

  function params() {
	  extract($_POST);

//	  if ($_SESSION['SES_RPC']->tx && !$data && !$_SESSION['SES_RPC']->tx->SignatureScripts) {
//		  return "您需要签署原始交易" . print_r($_SESSION['SES_RPC']->tx, true);
//	  }
	  if ($_SESSION['SES_RPC']->tx) unset($_SESSION['SES_RPC']->tx->rawtx);

	  $f = PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formTextArea("data", "", 10, 80) . "<br>" . PHPWS_Form::formSubmit("提交原始交易");
	  $f .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($f), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_POST);

	  if (!$_SESSION['SES_RPC']->tx && !$data) return "您需要先创建原始交易";

	  if (!$data && $_SESSION['SES_RPC']->tx) $s = bin2hex($_SESSION['SES_RPC']->tx->BtcEncode(true));
	  elseif ($data) $s = $data;
	  else return array();

	  return array($s, true, 15);
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }

	  $raw = strtolower($res->result);

	  $hash = substr($raw, 0, 64);
	  if (strlen($raw) > 64) {
		  $x = substr($raw, 64);
		  if (substr($x, 0, 5) == "txhex") {
			  $x = substr($x, 5);
			  $r = new Reader($x);
			  $r->StrToByte();
			  $tx = new MsgTx;
			  $tx->BtcDecode($r);
			  $tx->rawtx = $x;
			  $extra = "<hr>" . print_r($tx, true);
		  } else $extra = "<hr>" . $x;
	  }

	  if ($raw) return "交易收据(哈希值)：" . $hash . $extra;
	  else return $res;
  }
?>
