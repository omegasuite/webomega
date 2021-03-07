<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

class VertexDef {
	var $Lat;
	var $Lng;
	var $Alt;

	function type() {
		return 'vertex';
	}
	function read($r) {
		$this->Lat =  $r->readInt32();
		$this->Lng =  $r->readInt32();
		$this->Alt =  $r->readInt32();
	}
	function write() {
		$s = pack("V*", $this->Lat);
		$s .= pack("V*", $this->Lng);
		$s .= pack("V*", $this->Alt);

		return $s;
	}
	function hashval() {
		return $this->write();
	}
}

class BorderDef {
	var $Father;
	var $Begin;
	var $End;

	function type() {
		return 'border';
	}
	function read($r) {
		$this->Father = $r->readHash();
		$this->Begin = new VertexDef;
		$this->Begin->read($r);
		$this->End = new VertexDef;
		$this->End->read($r);
	}
	function write() {
		$s = pack("c*", 1) . $this->write0();
		return $s;
	}
	function write0() {
		if (!$this->Father) {
			for ($i = 0; $i < 32; $i++) $s .= chr(0);
		}
		else $s .= pack("H*", hashreverse($this->Father));
		$s .= $this->Begin->write();
		$s .= $this->End->write();
		return $s;
	}
	function hashval() {
		$h = $this->write0();
		$h = pack("H*", hash("sha256", $h));
		$h{0} = chr(ord($h{0}) & 0xFE);
		return hashreverse(bin2hex($h));
	}
}

class PolygonDef {
	var $Loops;

	function type() {
		return 'polygon';
	}
	function read($r) {
		$this->Loops = array();

		$nloops = $r->ReadVarInt();

		while ($nloops > 0) {
			$borders = $r->ReadVarInt();

			$loop = array();
			while ($borders > 0) {
				$loop[] = $r->readHash();
				$borders--;
			}
			$this->Loops[] = $loop;
			$nloops--;
		}
	}
	function write() {
		$s .= pack("c*", 2);
		$s .= writeVarInt(sizeof($this->Loops));
		foreach ($this->Loops as $loop) {
			$s .= writeVarInt(sizeof($loop));
			foreach ($loop as $b) {
				$s .= pack("H*", hashreverse($b));
			}
		}
		return $s;
	}
	function hashval() {
		foreach ($this->Loops as $loop) {
			foreach ($loop as $b) {
				$s .= pack("H*", hashreverse($b));
			}
		}
		return hashreverse(hash("sha256", $s));
	}
}

class RightDef {
	var $Father;
	var $Desc;
	var $Attrib;

	function type() {
		return 'right';
	}
	function read($r) {
		$this->Father = $r->readHash();

		$n = $r->ReadVarInt();

		$s = $r->read($n);
		$this->Desc = '';
		foreach ($s as $c) $this->Desc .= chr($c);
		$this->Attrib = $r->read(1);
		$this->Attrib = $this->Attrib[0];
	}
	function write() {
		$s .= pack("c*", 4);
		$s .= pack("H*", hashreverse($this->Father));
		$s .= writeVarInt(strlen($this->Desc));
		$s .= $this->Desc;
		if (is_array($this->Attrib)) $this->Attrib = $this->Attrib[0];
		$s .= pack("c", $this->Attrib);
		return $s;
	}
	function hashval() {
		return hashreverse(hash("sha256", $this->write()));
	}
}

class MsgTx {
	var $Version;
	var $TxDef;
	var $TxIn;
	var $TxOut;
	var $SignatureScripts;
	var $LockTime;

  function BtcEncode($signature = false) {
	  $s = '';
	  $s .= pack("V", $this->Version);
	  
	  if (($this->Version & 0x20) == 0) {
		  $s .= writeVarInt(sizeof($this->TxDef));

		  foreach ($this->TxDef as $d) {
			  $s .= $d->write();
		  }
	  }

	  $s .= writeVarInt(sizeof($this->TxIn));
	  foreach ($this->TxIn as $d) {
		  $s .= $this->writeTxIn($d);
	  }

	  $s .= writeVarInt(sizeof($this->TxOut));
	  foreach ($this->TxOut as $d) {
		  $s .= $this->writeTxOut($d);
	  }

//	  $s .= writeVarInt(sizeof($this->SignatureScripts));
//	  foreach ($this->SignatureScripts as $d) {
//		  $s .= writeVarInt(strlen($d) / 2);
//		  if (strlen($d)) $s .= pack("H*", $d);
//	  }

	  if (($this->Version & 0x10) == 0)
		  $s .= pack("V", $this->LockTime);

	  if ($signature) {
		  $s .= writeVarInt(sizeof($this->SignatureScripts));
		  foreach ($this->SignatureScripts as $d) {
			  $s .= writeVarInt(strlen($d) / 2);
			  if (strlen($d)) $s .= pack("H*", $d);
		  }
	  } else {
		  $s .= writeVarInt(0);
	  }

	  return $s;
  }
  
  function hashval() {
	return hashreverse(hash("sha256", $this->BtcEncode()));
  }

  function writeTxIn($d) {
	  $s .= pack("H*", hashreverse($d['PreviousOutPoint']['Hash']));
	  $s .= pack("V", $d['PreviousOutPoint']['Index']);

//	  $s .= writeVarInt($d['SignatureIndex']);
	  $s .= pack("V", $d['SignatureIndex']);
	  $s .= pack("V", $d['Sequence']);
	  return $s;
  }

  function writeTxOut($d) {
	  $s .= writeVarInt($d['TokenType']);
	  if (($d['TokenType'] & 1) == 0) 
		  $s .= pack("P", $d['Value']);
	  else $s .= pack("H*", hashreverse($d['Value']));

	  if (($d['TokenType'] & 2) != 0) {
		  $s .= pack("H*", hashreverse($d['Rights']));
	  }
	  $s .= writeVarInt(strlen($d['PkScript']) / 2);
	  if (strlen($d['PkScript']))
		  $s .= pack("H*", $d['PkScript']);

	  return $s;
  }

  function RawDecode($raw) {
	  $raw = strtolower($raw);
	  $r = new Reader($raw);
	  $r->StrToByte();

	  $this->BtcDecode($r);
  }

  function BtcDecode($r) {
	$this->Version = $r->readInt32();
	// definitions

	if (($this->Version & 0x20) == 0) {
		$dcount = $r->ReadVarInt();
		$this->TxDef = array();

		for ($i = 0; $i < $dcount; $i++) {
			$this->TxDef[] = $this->readDefinition($r);
		}
	}

	// TxIn
	$count = $r->ReadVarInt();

	// Deserialize the inputs.
	$this->TxIn = array();
	for ($i = 0; $i < $count; $i++) {
		$this->TxIn[] = $this->readTxIn($r);
	}

	$count = $r->ReadVarInt();

	// Deserialize the outputs.
	$this->TxOut = array();
	for ($i = 0; $i < $count; $i++) {
		$this->TxOut[] = $this->readTxOut($r);
	}
	
	if (($this->Version & 0x10) == 0)
		$this->LockTime = $r->readInt32();

	$count = $r->ReadVarInt();
	for ($i = 0; $i < $count; $i++) {
		$this->SignatureScripts[] = $r->readScript();
	}
  }

  function readTxIn($r) {
	  $res = array();
	  $res['PreviousOutPoint'] = $r->readOutPoint();
	  $res['SignatureIndex'] = $r->readInt32();
	  $res['Sequence'] = $r->readInt32();
	  return $res;
  }

  function readTxOut($r) {
	  $t = $r->ReadVarInt();

	  $to = array();
	  $to['TokenType'] = $t;

	  if ($t > 0x1000000) {
		  $to['TokenType'] .= " (" . chr(($t >> 8) & 0xFF) . chr(($t >> 16) & 0xFF) . chr(($t >> 24) & 0xFF) . ")";
	  }


	  if ($t != 252) {
		  if (($t & 1) == 1) {
			  $to['Value'] = $r->readHash();
		  } else {
			  $to['Value'] = $r->readInt64();
		  }

		  if (($to['TokenType'] & 2) != 0) {
			  $to['Rights'] = $r->readHash();
		  }
	  }
	  
	  $to['PkScript'] = $r->readScript();

	  return $to;
	}

  function readDefinition($r) {
	$t = $r->read(1);
	$t = $t[0];

	switch ($t) {
	case 0:
		$c = new VertexDef;
		$c->read($r);
		return $c;
	case 1:
		$c = new BorderDef;
		$c->read($r);
		return $c;
	case 2:
		$c = new PolygonDef;
		$c->read($r);
		return $c;
	case 4:
		$c = new RightDef;
		$c->read($r);
		return $c;
	}
  }
}

?>
