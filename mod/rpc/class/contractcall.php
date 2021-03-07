<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'contractcall');

  function params() {
	  extract($_POST);
	  
	  $s .= "合约地址（HEX编码）：" . PHPWS_Form::formTextField("contract") . "<br>";
	  $s .= "合约参数（HEX编码）：" . PHPWS_Form::formTextField("data") . "<br>";
	  $s .= PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formSubmit("执行合约");
	  $s .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($s), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_REQUEST);
	  extract($_POST);
	  return array($contract, $data);
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }

	  return $res->result;
  }

?>
