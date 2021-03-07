<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'getminerblock');

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

	  $raw = strtolower($res->result);
	  $r = new Reader($raw);
	  $r->StrToByte();

	  $msg = array();

	  $msg['Version'] = $r->readInt32();
	  $msg['PrevBlock'] = $r->readHash();
	  $msg['BestBlock'] = $r->readHash();
	  $msg['Timestamp'] = $r->readInt32();
	  $msg['Timestamp'] .= " (" . date("Y-m-d H:i:s", $msg['Timestamp']) . ")";
	  $msg['Bits'] = $r->readInt32();
	  $msg['Nonce'] = $r->readInt32();
	  $msg['Miner'] = $r->readScript();
	  $msg['Connection'] = $r->readText();

	  $n = $r->ReadVarInt();
	  if ($n == 0) return $msg;

	  if ($msg['Version'] + 0 >= 0x20000) {
		  $msg['Utxo'] = $r->readHash() . ":" . $r->readInt32();

		  $msg['ViolationReport'] = array();
		  $n = $r->ReadVarInt();
		  for ($i = 0; $i < $n; $i++) {
			  $msg['ViolationReport'][] = readVioldations($r);
		  }

		  $msg['Collateral'] = $r->ReadVarInt();
		  $msg['MeanTPH'] = $r->ReadVarInt();

		  $n = $r->ReadVarInt();
		  $msg['TphReports'] = array();
		  for ($i = 0; $i < $n; $i++) 
			  $msg['TphReports'][] =  $r->ReadVarInt();
		  $msg['ContractLimit'] = $r->readInt64();
	  }

	  return $msg;
  }

  function readVioldations($r) {
	  $v = array();
	  $v['Height'] = $r->readInt32();
	  $n = $r->readInt32();
	  $v['MRBlock'] = $r->readHash();

	  $v['Blocks'] = array();

	  for ($i = 0; $i < $n; $i++) {
		  $v['Blocks'][$i] = $r->readHash();
	  }
	  return $v;
  }

?>
