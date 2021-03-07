<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'shutdown');

  $_POST['directexec'] = 1;

  function execute() {
	  $param = array("jsonrpc"=>"1.0", 'method'=>'shutdownserver', 'params'=>array(), 'id'=>$_SESSION['SES_RPC']->id);
	  $ret = $_SESSION['SES_RPC']->post(json_encode($param));
	  $rdata = json_decode($ret);
	  $_SESSION['SES_RPC']->id++;

	  return "shutdown";
  }

?>
