<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'setgenerate');

  $_POST['submiting'] = 1;

  function params() {
	  extract($_POST);
	  
	  $s .= PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formCheckBox("miner", 1) . "矿权挖矿<br>" . PHPWS_Form::formSubmit("开通挖矿");
	  $s .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($s), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  $_SESSION['SES_RPC']->generate = !$_SESSION['SES_RPC']->generate;
	  return array($_SESSION['SES_RPC']->generate, $miner?true : false, 1);
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }

	  return $res->result;
  }

?>
