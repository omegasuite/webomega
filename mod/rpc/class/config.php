<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'config');

  function params() {
	  extract($_POST);
	  
	  $port = explode(":", $_SESSION['SES_RPC']->Host);

	  $s .= "端口：" . PHPWS_Form::formTextField("port", $port[1]) . "<br>";
	  $s .= "原始输出：" . PHPWS_Form::formCheckBox("raw", 1) . "<br>";
	  $s .= PHPWS_Form::formHidden("directexec", 1) . PHPWS_Form::formSubmit("提交");
	  $s .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($s), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_POST);

	  $_SESSION['SES_RPC']->Host = "localhost:" . $port;
	  $_SESSION['SES_RPC']->useraw = ($raw == 1);

	  return "端口设置为：" . $port . "<br>" . ($raw == 1?"" : "不") . "使用原始输出。";
  }

?>
