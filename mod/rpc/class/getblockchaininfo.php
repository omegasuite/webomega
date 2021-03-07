<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'getblockchaininfo');

  $_POST['submiting'] = 1;

  function params() {
	  extract($_POST);
	  
	  $port = explode(":", $_SESSION['SES_RPC']->Host);
	  $s .= "端口：" . PHPWS_Form::formTextField("port", $port[1]) . "<br>";
	  $s .= PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formSubmit("获取区块链信息");
	  $s .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($s), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_POST);

	  if ($port) $_SESSION['SES_RPC']->Host = "localhost:" . $port;

//	  $_SESSION['SES_RPC']->Pass = "cOZ9ti";

	  return array();
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }

	  if ($res->result->chain) $_SESSION['SES_RPC']->chain = $res->result->chain;

	  return $res->result;
  }
?>
