<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'getblock');

  function params() {
	  extract($_POST);

	  $s = "区块哈希：" . PHPWS_Form::formTextField("block") . "<br>";
	  
	  $s .= PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formSubmit("获取区块");
	  $s .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($s), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_POST);
	  $block = strtolower($block);
	  return array($block, false);
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }
	  
	  require_once(PHPWS_SOURCE_DIR . "mod/rpc/class/MsgTx.php");

	  $raw = strtolower($res->result);
	  $r = new Reader($raw);
	  $r->StrToByte();

	  $msg = array();
	  $msg['Header'] = $r->readBlockHeader();
	  $txCount = $r->ReadVarInt($r);
	  $msg['Transactions'] = array();

	  for ($i = 0; $i < $txCount; $i++) {
		  $tx = new MsgTx;
		  $tx->BtcDecode($r);
		  
		  $tx->visual = "<a href=./index.php?module=rpc&MOD_op=visual&in=" . json_encode($tx->TxIn) . "&out=" . json_encode($tx->TxOut) . " target=_blank>交易图解</a>";

		  $msg['Transactions'][] = $tx;
	  }

	  for ($i = 0; $i < $txCount; $i++) {
		  $msg['Transactions'][$i]->SignatureScripts = array();
		  $count = $r->ReadVarInt();
		  for ($j = 0; $j < $count; $j++) {
			  $msg['Transactions'][$i]->SignatureScripts[] = $r->readScript();
		  }
	  }

	  return $msg;
  }
?>
