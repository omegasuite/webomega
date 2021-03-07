<?php
/**
 * @version $Id: index.php,v 1.14 2004/11/16 19:57:22 rizzo Exp $
 */

if (!isset($GLOBALS['core'])){
  header('location:../../?module=rpc');
  exit();
}

define('CoordPrecision', 0x200000);

/* Check to see if the manager exists and create it if it doesn't */
if(!isset($_SESSION['SES_RPC'])) {
  $_SESSION['SES_RPC'] = new PHPWS_RPCClient;
}

if($GLOBALS['module'] == 'rpc') {
  $GLOBALS['CNT_rpc'] = array('title'=>$_SESSION['translate']->it('rpc'),
				   'content'=>NULL);
}

$operation = $_REQUEST['MOD_op'];

if ($operation && $operation != 'menu') {
	include_once(PHPWS_SOURCE_DIR . "mod/rpc/class/$operation.php");

	$GLOBALS['CNT_rpc']['content'] = "<center>" . $_SESSION['SES_RPC']->Host . "</center><hr>" . $_SESSION['SES_RPC']->execute($operation);
}
else {
	$GLOBALS['CNT_rpc']['content'] = menu();
}

function menu() {
	$ops = array(
		"config", "shutdown", "compare", "getinfo", "ping", "getnettotals", "getpeerinfo", "getconnectioncount", "verifychain",
		"getaddednodeinfo", "addnode", NULL,	// "getnetworkinfo", 

		"createrawtransaction", "signrawtransaction", "sendrawtransaction", 
		"getrawmempool", "gettxout", "getrawtransaction", "decoderawtransaction",
		"decodescript",

		"getblockchaininfo", "getblockcount", "getbestblockhash", "getblockhash", "getblock", "getblocks", "getblocktxhashes", "getminerblock", "getminerblocks",
		"sigverify", "getmininginfo", "getdifficulty", "getnetworkhashps", "gethashespersec", "getgenerate", "setgenerate",
		"getblocktemplate", "submitblock", "searchrawtransactions", "generate", "getrawmempool", NULL, /* "getwork",  */

		"listsinceblock", "listtransactions", "gettransaction", "walletpassphrase", "walletlock", "walletpassphrasechange", 
		"listaccounts", "getaddressesbyaccount", "validateaddress",
		"dumpprivkey", "getnewaddress", "importprivkey", "createmultisig",
		"addmultisigaddress", "getbalance", "getasset", "move", "listunspent", "listlockunspent", "lockunspent",
		"getrawchangeaddress", "listaddressgroupings", "signmessage", "verifymessage", NULL, /* "gettxoutsetinfo",  "getwalletinfo", "backupwallet", "importwallet", 
		"dumpwallet", "getaccountaddress", "getaccount", "setaccount", "keypoolrefill", */

		"getdefine" // , "maplocater"
		);
	$n = 0; $g = '';
	$s = "<center>";
	foreach($ops as $p) {
		if ($p)	{
			$s .= $g . "<a href=./index.php?module=rpc&MOD_op=$p target=_blank>$p</a>";
			$g = " |\n";
		}
		else {
			$s .= "<hr>\n";
			$n = 0;
			$g = '';
		}
		if ((++$n % 8) == 0) {
			$g = "<br>";
		}
	}
	$s .= "</center>";
	return $s;
}
?>
