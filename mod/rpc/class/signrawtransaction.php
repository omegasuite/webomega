<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'signrawtransaction');

//  $_POST['submiting'] = 1;

  function params() {
	  extract($_REQUEST);
	  extract($_POST);

//	  if (!$_SESSION['SES_RPC']->tx) return "您需要先创建原始交易";

	  $f = "私钥：" . PHPWS_Form::formTextField("privKey") . PHPWS_Form::formHidden("submiting", 1);
	  $f .= "<br>赎回脚本：" . PHPWS_Form::formTextField("redeem", NULL, 80);
	  $f .= "<br>" . PHPWS_Form::formTextArea("data", "", 5, 60) . "<br>";
	  $f .= PHPWS_Form::formRadio("outputs", "NONE", $outputs) . " 任意输出 | ";
	  $f .= PHPWS_Form::formRadio("outputs", "SINGLE", $outputs) . " 单对单 | ";
	  $f .= PHPWS_Form::formRadio("outputs", "DOUBLE", $outputs) . " 双对双 | ";
	  $f .= PHPWS_Form::formRadio("outputs", "TRIPLE", $outputs) . " 三对三 | ";
	  $f .= PHPWS_Form::formRadio("outputs", "QUARDRUPLE", $outputs) . " 四对四";
	  $f .= "<br>任何人可付：" . PHPWS_Form::formCheckBox("anyonecanpay", 1);
	  $f .= "<br>对哈希值签名：" . PHPWS_Form::formCheckBox("isHash", 1) . "<br>";
	  $f .= PHPWS_Form::formSubmit("签署原始交易");
	  $f .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($f), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_POST);

	  if ($data) $s = $data;
	  elseif ($_SESSION['SES_RPC']->tx)
		  $s = bin2hex($_SESSION['SES_RPC']->tx->BtcEncode());
	  else {
		  exit("Can not sign transaction with raw transaction and previously created transaction.");
	  }

	  $redeems = array();
	  if ($redeem) $redeems[] = array('txid'=>"", 'vout'=>0, 'scriptPubKey'=>"", 'redeemScript'=>$redeem);

	  if ($privKey) $privKey = array($privKey);
	  else $privKey = array();

	  if (!$isHash && ($outputs || $anyonecanpay)) {
		  if (!$outputs) $outputs = "ALL";
		  if ($anyonecanpay) $outputs .= "|ANYONECANPAY";
		  return array($s, $redeems, $privKey, false, $outputs);
	  }

	  return array($s, $redeems, $privKey, $isHash?true : false);
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned with error: " . print_r($res->error, true);
	  }
	  if ($res->result->errors) {
		  return "Op " . $res->result->id . " returned with error: " . print_r($res->result->errors, true);
	  }

	  if (!$res->result->hex) return $res;	// "Nothing is returned. Possible reasons: incorrect input hash, input has been spent, unauthorized signer (signer: cQdPVU5KSzLkD1rhvLJztvpWBu9TrVAE2iPxfgEQrzWuS5xLNRX6).";

	  if (strlen($res->result->hex) < 160) {
		  return $res->result;
	  }

	  $raw = strtolower($res->result->hex);
	  $r = new Reader($raw);
	  $r->StrToByte();

	  $tx = new MsgTx;
	  $tx->BtcDecode($r);
	  
	  if ($tx) {
		  $_SESSION['SES_RPC']->tx = $tx;
		  $_SESSION['SES_RPC']->tx->rawtx = $res->result->hex;
	  }

//	  $tx->visual = "<a href=./index.php?module=rpc&MOD_op=visual&in=" . json_encode($tx->TxIn) . "&out=" . json_encode($tx->TxOut) . " target=_blank>交易图解</a>";

	  return $tx;
  }
?>
