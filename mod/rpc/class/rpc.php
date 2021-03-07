<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/WizardBag.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");
require_once(PHPWS_SOURCE_DIR . "mod/rpc/class/MsgTx.php");

/**
 * Controls the announcement forms, saving, and display 
 *
 * Patch - 2 Mar 2005 Thomas Gordon - sticky announcements
 * 
 * @version $Id: Announcement.php,v 1.93 2005/05/12 13:01:40 darren Exp $
 * @author Adam Morton
 * @modified Steven Levin
 * @modified Matt McNaney
 */

define('CoordPrecision', 0x200000);

class PHPWS_RPCClient {
  var $Host = "localhost:8789";
  var $User = "admin";
  var $Pass = "FFh5rL";
  var $chain = "mainnet";
  var $HTTPPostMode = true;
  var $DisableTLS = true;
  var $id = 0;
  var $timeout = 600;
  var $tx = NULL;
  var $generate = true;
  var $useraw = false;

  function PHPWS_RPCClient() {
  }

  function post($s) {
	$protocol = "http";
	if (!$this->DisableTLS) {
		$protocol = "https";
	}
	$url = $protocol . "://" . $this->Host;

	$ch = curl_init();
	curl_setopt($ch , CURLOPT_TIMEOUT, $this->timeout);

	if($this->DisableTLS) curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 
	curl_setopt($ch, CURLOPT_POST, TRUE); 
	curl_setopt($ch, CURLOPT_POSTFIELDS, $s); 
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Authorization: ' . "Basic " . base64_encode($this->User . ":" . $this->Pass)));
    $ret = curl_exec($ch);

    curl_close($ch);
    return $ret;
  }

  function execute($op) {
	  extract($_REQUEST);
	  extract($_POST);

	  if ($submiting) {
		  $param = array("jsonrpc"=>"1.0", 'method'=>$op, 'params'=>execute(), 'id'=>$this->id);
		  $ret = $this->post(json_encode($param));

		  $rdata = showResult(json_decode($ret));

		  $res = print_r($rdata, true);
		  $raw = "->" . json_encode($param);
		  $this->id++;

		  return "<table width=100%><tr><td width=20%>" . params() . "</td><td width=80%>$raw<br><pre>$res</pre></td></tr></table>";
	  }

	  if ($directexec) {
		  return execute();
	  }

	  return params();
  }
}// END CLASS PHPWS_RPCClient

function writeVarInt($val) {
	if ($val < 0xfd) {
		return pack("C", $val);
	}

	if ($val <= 0xFFFF) {
		return pack("C", 0xFD) . pack("v", $val);
	}

	if ($val <= 0xFFFFFFFF) {
		return pack("C", 0xFE) . pack("V", $val);
	}
	return pack("C", 0xFF) . pack("P", $val);
}

function hashreverse($h) {
	$s = '';
	for ($i = 0; $i < 64; $i += 2) {
		$s = $h{$i} . $h{$i + 1} . $s;
	}
	return $s;
}

function hash2string($h) {
	$rawnib = array('0'=>0, '1'=>1, '2'=>2, '3'=>3, '4'=>4, '5'=>5, '6'=>6, '7'=>7, '8'=>8, '9'=>9, 'a'=>10, 'b'=>11, 'c'=>12, 'd'=>13, 'e'=>14, 'f'=>15);
	$s = '';
	for ($i = 0; $i < 32; $i++) {
		$s .= rawnib[$h{$i} >> 4] . rawnib[$h{$i} & 0xF];
	}
	return $s;
}

class Reader {
	var $src;
	var $pos;
	var $len;
	var $nib;


	function EOF() {
		return $this->pos >= $this->len;
	}

	function Reader($s) {
		$this->src = $s;
		$this->pos = 0;
		$this->len = strlen($s);
		$this->rawnib = array('0'=>0, '1'=>1, '2'=>2, '3'=>3, '4'=>4, '5'=>5, '6'=>6, '7'=>7, '8'=>8, '9'=>9, 'a'=>10, 'b'=>11, 'c'=>12, 'd'=>13, 'e'=>14, 'f'=>15);
		$this->nib = array_flip(array('0'=>0, '1'=>1, '2'=>2, '3'=>3, '4'=>4, '5'=>5, '6'=>6, '7'=>7, '8'=>8, '9'=>9, 'a'=>10, 'b'=>11, 'c'=>12, 'd'=>13, 'e'=>14, 'f'=>15));
	}

	function SetBytes($s) {
		$this->src = $s;
		$this->pos = 0;
		$this->len = sizeof($s);
	}

	function StrToByte() {
	  $sb = array_fill(0, strlen($this->src) / 2, 0);
	  for ($i = 0; $i < strlen($this->src); $i += 2) {
		  $sb[$i / 2] = ($this->rawnib[$this->src{$i}] << 4) + $this->rawnib[$this->src{$i + 1}];
	  }

	  $this->src = $sb;
	  $this->pos = 0;
	  $this->len = sizeof($this->src);
	}

	function readBlockHeader() {
		$Version = $this->readInt32();
		$PrevBlock = $this->readHash();
		$MerkleRoot = $this->readHash();
		$Timestamp = $this->readInt32();
		$ContractExec = $this->readInt64();
		$Nonce = $this->readInt32();

		return array('Version'=>$Version, 'PrevBlock'=>$PrevBlock, 'MerkleRoot'=>$MerkleRoot, 'Timestamp'=>$Timestamp . " (" . date("Y-m-d H:i:s", $Timestamp) . ")", 'ContractExec'=>$ContractExec, 'Nonce'=>$Nonce);
	}

	function read($n) {
		$h = array();
		for ($i = 0; $i < $n; $i++) {
			if ($this->pos >= $this->len) return $h;
			$h[] = $this->src[$this->pos++];
		}
		return $h;
	}

	function readInt32() {
		$sum = 0;
		for ($i = 0; $i < 4; $i++) {
			if ($this->pos >= $this->len) return $sum;
			$sum += $this->src[$this->pos] << ($i * 8);
			$this->pos++;
		}

		if ($sum & 0x80000000) {
			$sum |= (-1) << 32;
		}

		return $sum;
	}

	function readInt64() {
		$sum = 0;
		for ($i = 0; $i < 8; $i++) {
			if ($this->pos >= $this->len) return $sum;
			$d = ($this->src[$this->pos]) * (1 << ($i * 8));
			$sum += $d;
			$this->pos++;
		}
		return $sum;
	}

	function readScript() {
		$count = $this->ReadVarInt();
		$t = $this->read($count);
		$s = '';
		foreach ($t as $c) {
			$s .= $this->nib[($c >> 4) & 0xF] . $this->nib[$c & 0xF];
		}
		return $s;
	}

	function readText() {
		$count = $this->ReadVarInt();
		$t = $this->read($count);
		$s = '';
		foreach ($t as $c) {
			$s .= chr($c);
		}
		return $s;
	}

	function readOutPoint() {
		$res['Hash'] = $this->readHash();
		$res['Index'] = $this->readInt32();
		return $res;
	}

	function readHash() {
		$t = $this->read(32);
		$s = '';
		foreach ($t as $c) {
			$s = $this->nib[($c >> 4) & 0xF] . $this->nib[$c & 0xF] . $s;
		}
		return $s;
	}

	function ReadVarInt() {
		$ln = array(0xff=>8, 0xfe=>4, 0xfd=>2);
		$discriminant = $this->src[$this->pos];

		if (!isset($ln[$discriminant])) {
			$this->pos++;
			return $discriminant;
		}

		$this->pos++;

		$sum = 0;
		for ($i = 0; $i < $ln[$discriminant]; $i++) {
			if ($this->pos >= $this->len) {
				return $sum;
			}
			$sum += ($this->src[$this->pos]) << ($i * 8);
			$this->pos++;
		}
		return $sum;
	}
}

?>
