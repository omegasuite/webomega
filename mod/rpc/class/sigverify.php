<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'sigverify');

  function params() {
	  extract($_POST);

	  $s = "区块开始高度：" . PHPWS_Form::formTextField("start") . "<br>";
	  $s .= "区块结束高度：" . PHPWS_Form::formTextField("end") . "<br>";
	  
	  $s .= PHPWS_Form::formHidden("directexec", 1) . PHPWS_Form::formSubmit("检查区块签名");
	  $s .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($s), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_REQUEST);
	  extract($_POST);

	  $result = "<tr><th>Block height</th><th>Nonce</th><th>Signature</th></tr>";

	  if (!$end) $end = $start;

	  for ($i = $start + 0; $i <= $end; $i++) {
		  $param = array("jsonrpc"=>"1.0", 'method'=>'getblockhash', 'params'=>array($i), 'id'=>$_SESSION['SES_RPC']->id);
		  $ret = $_SESSION['SES_RPC']->post(json_encode($param));
		  $rdata = json_decode($ret);
		  $_SESSION['SES_RPC']->id++;

		  $param = array("jsonrpc"=>"1.0", 'method'=>'getblock', 'params'=>array($rdata->result, false), 'id'=>$_SESSION['SES_RPC']->id);

		  $ret = $_SESSION['SES_RPC']->post(json_encode($param));
		  $rdata = showResult(json_decode($ret));

		  $status = "OK";
		  if ($rdata['Header']['Nonce'] < 0) {
			  if (sizeof($rdata['Transactions'][0]->SignatureScripts) < 3) {
				  $status = "number of Signatures insuffcient: " . sizeof($rdata['Transactions'][0]->SignatureScripts);
			  } else if (strlen($rdata['Transactions'][0]->SignatureScripts[1]) < 66) {
				  $status = "Signature 1 is invalid: " . $rdata['Transactions'][0]->SignatureScripts[1];
			  }
		  }
		  
		  $result .= "<tr><td>$i</td><td>{$rdata['Header']['Nonce']}</td><td>$status</td></tr>";
		  $_SESSION['SES_RPC']->id++;
	  }
	  return "<table width=100%><tr><td width=20%>" . params() . "</td><td width=80%><table cellpadding=5 cellspacing=5>$result</table></td></tr></table>";
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
