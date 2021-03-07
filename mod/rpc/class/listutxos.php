<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'listutxos');
  set_time_limit(0);

  function params() {
	  extract($_REQUEST);
	  extract($_POST);
	  
	  $s = "开始：" . PHPWS_Form::formTextField("begin", $begin + $run) . "<br>";
	  $s .= "个数：" . PHPWS_Form::formTextField("run", $txseq) . "<br>";
	  $s .= "最低价值：" . PHPWS_Form::formTextField("minval", $minval) . "<br>";

	  $s .= PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formSubmit("获取UTXO信息");
	  $s .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($s), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_REQUEST);
	  extract($_POST);

	  if (!$minval) return array($begin + 0, $run?$run + 0 : NULL);
	  return array($begin + 0, $run?$run + 0 : NULL, $minval * 1e8);
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }

	  return $res->result;
  }

?>
