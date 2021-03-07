<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'getblocks');

  function params() {
	  extract($_REQUEST);
	  extract($_POST);

	  $s = "区块开始高度：" . PHPWS_Form::formTextField("start") . "<br>";
	  $s .= "区块结束高度：" . PHPWS_Form::formTextField("end") . "<br>";
	  if ($start || $end) {
		  if (!$end) $end = $start;
		  if (!$start) $start = $end;
		  $r = $start - $end;
		  $s .= "<a href=./index.php?module=$module&MOD_op=$MOD_op&directexec=1&start=" . ($end+1) . "&end=" . ($end + 1 + $r) . ">下一批</a><br>";
	  }
	  
	  $s .= PHPWS_Form::formHidden("directexec", 1) . PHPWS_Form::formSubmit("获取区块");
	  $s .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($s), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_REQUEST);
	  extract($_POST);

	  $result = '';

	  if (!$end) $end = $start;

	  $head = ($end - $start > 10);

	  for ($i = $start + 0; $i <= $end; $i++) {
		  $param = array("jsonrpc"=>"1.0", 'method'=>'getblockhash', 'params'=>array($i), 'id'=>$_SESSION['SES_RPC']->id);
		  $ret = $_SESSION['SES_RPC']->post(json_encode($param));
		  $rdata = json_decode($ret);
		  $_SESSION['SES_RPC']->id++;

		  $bhash = $rdata->result;

		  if (!$bhash) continue;

		  $param = array("jsonrpc"=>"1.0", 'method'=>'getblock', 'params'=>array($bhash, false), 'id'=>$_SESSION['SES_RPC']->id);

		  $ret = $_SESSION['SES_RPC']->post(json_encode($param));
		  $rdata = showResult(json_decode($ret), $head);

		  $rdata['Hash'] = $bhash;
		  
		  if ($head) {
			  $rdata['Transactions'] = "<a href=./index.php?module=rpc&MOD_op=getblocks&start=$i&end=$i&directexec=1 target=_blank>交易信息</a>";
		  }

		  $result .= "Block height " . $i . "：<br>";

		  $result .= print_r($rdata, true);
		  $result .= "<hr>";

		  $_SESSION['SES_RPC']->id++;
	  }
	  return "<table width=100%><tr><td width=20%>" . params() . "</td><td width=80%><pre>$result</pre></td></tr></table>";
  }

  function showResult($res, $head) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }
	  
	  require_once(PHPWS_SOURCE_DIR . "mod/rpc/class/MsgTx.php");

	  $raw = strtolower($res->result);
	  $r = new Reader($raw);
	  $r->StrToByte();

	  $msg = array();
	  $msg['Header'] = $r->readBlockHeader();

	  if ($head) {
		  return $msg;
	  }

	  $txCount = $r->ReadVarInt($r);
	  $msg['Transactions'] = array();

	  for ($i = 0; $i < $txCount; $i++) {
		  $tx = new MsgTx;
		  $tx->BtcDecode($r);

		  $tx->visual = "<a href=./index.php?module=rpc&MOD_op=visual&in=" . json_encode($tx->TxIn) . "&out=" . json_encode($tx->TxOut) . " target=_blank>交易图解</a>";

		  $msg['Transactions'][] = $tx;
	  }
/*	  
	  for ($i = 0; $i < $txCount; $i++) {
		  $msg['Transactions'][$i]->SignatureScripts = array();
		  $count = $r->ReadVarInt();
		  for ($j = 0; $j < $count; $j++) {
			  $msg['Transactions'][$i]->SignatureScripts[] = $r->readScript();
		  }
	  }
*/
	  return $msg;
  }
?>
