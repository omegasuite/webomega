<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'createmultisig');

  function params() {
	  extract($_POST);

	  $s = "私钥：" . PHPWS_Form::formTextField("privkey") . "<br>";
	  $s = "私钥：" . PHPWS_Form::formTextField("privkey") . "<br>";
	  $s = "私钥：" . PHPWS_Form::formTextField("privkey") . "<br>";
	  
	  $s .= PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formSubmit("导入私钥");
	  $s .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($s), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_POST);

	  return array($privkey, false);
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
		  $msg['Transactions'][] = $tx;
	  }

	  return $msg;
  }
?>
