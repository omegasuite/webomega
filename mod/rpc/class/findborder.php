<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'searchborder');
  define('CoordPrecision', 0x200000);

  $_POST['directexec'] = 1;

  function execute() {
	  extract($_REQUEST);
	  extract($_POST);

	  $west = round($west * CoordPrecision);
	  $east = round($east * CoordPrecision);
	  $south = round($south * CoordPrecision);
	  $north = round($north * CoordPrecision);

	  $param = array("jsonrpc"=>"1.0", 'method'=>OP, 'params'=>array($west, $east, $south, $north, $lod + 0), 'id'=>$_SESSION['SES_RPC']->id);

	  $ret = $_SESSION['SES_RPC']->post(json_encode($param));

	  $rdata = json_decode($ret);
	  $_SESSION['SES_RPC']->id++;

	  $res = array();

	  foreach ($rdata->result as $hash) {
		  $param = array("jsonrpc"=>"1.0", 'method'=>'getdefine', 'params'=>array(1, $hash, false), 'id'=>$_SESSION['SES_RPC']->id);

		  $ret = $_SESSION['SES_RPC']->post(json_encode($param));
		  
		  $r = json_decode($ret);
		  $t = array($r->result->definition);
		  list(,$c) = each($t);
		  $d = each($c);
		  $res[] = array(hash2coord($d[1]->begin), hash2coord($d[1]->end));

		  $_SESSION['SES_RPC']->id++;
	  }
	  exit(json_encode($res));
  }

  function hash2coord($h) {
	  $rawnib = array('0'=>0, '1'=>1, '2'=>2, '3'=>3, '4'=>4, '5'=>5, '6'=>6, '7'=>7, '8'=>8, '9'=>9, 'a'=>10, 'b'=>11, 'c'=>12, 'd'=>13, 'e'=>14, 'f'=>15);
	  for ($i = 0; $i < 2; $i++) {
		  $s = 0;
		  for ($j = 0; $j < 8; $j++) {
			  $s += $rawnib[$h{63 - ($i * 8 + $j)}] << (4 * $j);
		  }
		  if ($s & 0x80000000) {
			  $s |= (-1) << 32;
		  }
		  $s /= CoordPrecision;
		  $res[] = $s;
	  }

	  return array('lat'=>$res[0], 'lng'=>$res[1]);
  }

?>
