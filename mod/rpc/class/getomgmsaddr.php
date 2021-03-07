<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");
require_once(PHPWS_SOURCE_DIR . "mod/rpc/class/Base58.php");

  define('OP', 'getomgmsaddr');

  // this is OMG address. not BTC address
  define('MultiSigAddrID', "78");		// 0x05 for mainnet, for test net:        0x67

  function params($xredeem = "", $xnsigs = 0) {
	  extract($_POST);

	  $s .= "新地址：" . PHPWS_Form::formTextField("address") . "<br>";
	  $s .= PHPWS_Form::formHidden("nsigs", $xnsigs) . PHPWS_Form::formSubmit("添加");
	  $s .= PHPWS_Form::formHidden("redeem", $xredeem);
	  $s .= PHPWS_Form::formHidden("directexec", 1);
	  $s .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($s), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_REQUEST);
	  extract($_POST);

	  if (!$address) return params();

	  $b58 = new Base58;

	  $nsigs++;
	  if (!$netid) {
		  switch ($_SESSION['SES_RPC']->chain) {
			  case 'testnet':
				  $netid = "67";
			  break;

			  case 'mainnet':
			  default:
				  $netid = MultiSigAddrID;
		  }
	  }
	  
	  $t = sprintf("5604%02x00%02x004a00", $nsigs, $nsigs);	// PUSH 4 M N SIGNTEXT textcode
	  
	  $addr = "5615" . bin2hex(substr($b58->decode($address), 0, 21));	// PUSH 21 address
	  $addr .= "4a00";	// SIGNTEXT textcode is left to 0 TBD when signing

	  $redeem = $t . substr($redeem, 18) . $addr;

	  $h = hash("sha256", hex2bin($redeem), true);
	  $h = hash("ripemd160", $h, false);
	  
	  $a = $netid . $h;

	  $ck = hash("sha256", hex2bin($a), false);
	  $ck = hash("sha256", hex2bin($ck), false);
	  $addr = $a . substr($ck, 0, 8);
	  
	  $addr = $b58->encode(hex2bin($addr));

	  $redeem = $netid . $redeem;

	  switch ($_SESSION['translate']->current_language) {
  		case 'cn':
		case 'zh':
			return params($redeem, $nsigs) . '<hr>&Omega;多重签名地址是：<b>' . $addr . '</b><br>赎回脚本是：<b>' . $redeem . '</b><br>请保存好这些信息，您转币时会需要。';

		default:
			return params($redeem, $nsigs) . '<hr>The &Omega; multisig address is: <b>' . $addr . '</b><br>The redeem script is: <b>' . $redeem . '</b><br>Please keep the info for your record. You will need them for signing transaction.';
	  }
  }

?>
