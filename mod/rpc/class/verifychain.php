<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'verifychain');

  function params() {
	  extract($_POST);
	  
	  $s .= "范围：" . PHPWS_Form::formTextField("CheckLevel", 4) . "<br>";
	  $s .= "区块数：" . PHPWS_Form::formTextField("CheckDepth", 500) . "<br>";
	  $s .= PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formSubmit("verify chain");
	  $s .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($s), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_POST);

	  $a = ($CheckLevel * 2 + 0) / 2;
	  $b = ($CheckDepth * 3 + 0) / 3;

	  return array($a, $b);
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }

	  return $res;
  }

?>
