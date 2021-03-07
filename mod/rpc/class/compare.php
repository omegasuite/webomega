<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'compare');

  $_POST["directexec"] = 1;

  function execute() {
	  extract($_POST);

	  $ports = array("18334", "28334", "38334", "48334");
	  $s = date("Y-m-d H:i:s") . "<table cellspacing=5 width=100%><tr><th></th>";
	  foreach ($ports as $p) {
		  $s .= "<th width=20%>$p</th>";
	  }
	  $s .= "</tr>";

	  $fg = $_SESSION['SES_RPC']->Host;

	  foreach ($ports as $p) {
		  $_SESSION['SES_RPC']->Host = "localhost:" . $p;

		  $param = array("jsonrpc"=>"1.0", 'method'=>'getblockchaininfo', 'params'=>array(), 'id'=>$_SESSION['SES_RPC']->id++);
		  $res = json_decode($_SESSION['SES_RPC']->post(json_encode($param)));
		  $ret[] = $res;
		  $_SESSION['SES_RPC']->id++;
	  }

	  $_SESSION['SES_RPC']->Host = $fg;

	  $res = (array)$res->result;
	  foreach ($res as $col=>$v) {
		  $s .= "<tr><th>$col</th>";
		  foreach ($ret as $t) {
			  $t = (array)$t->result;
			  if (in_array($col, array("bestblockhash", "minerbestblockhash"))) {
				  $t[$col] = substr($t[$col], 0, 5) . "..." . substr($t[$col], -5);
			  }
			  $s .= "<td>" . $t[$col] . "</td>";
		  }
		  $s .= "</tr>";
	  }
	  return $s . "</table>";
  }

?>
