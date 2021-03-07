<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'getasset');

  $_POST['submiting'] = 1;

  function params() {
  }

  function execute() {
	  extract($_REQUEST);

	  if ($detail) $detail = true;
	  else $detail = false;

	  return array("*", $detail);
  }

  function showResult($res) {
	  extract($_REQUEST);

	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }

	  foreach ($res->result as $i=>$r) {
		  if ($r->Hash) {
			  $rd = new Reader("");
			  $rd->SetBytes($r->Hash);
			  $res->result[$i]->Hash = $rd->readHash();
		  }

		  if ($r->TokenType == 3) {
			  	  $s = PHPWS_Form::formHidden("kind", 2) . 
					  PHPWS_Form::formHidden("hash", $res->result[$i]->VHash) . PHPWS_Form::formHidden("recur", 1) . PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', "getdefine") . PHPWS_Form::formSubmit("多边形数据");
				  $s = str_replace("\n", "", $s);
				  $res->result[$i]->visual = "<div style='display:inline;'>" . PHPWS_Form::makeForm('visual', "index.php", array($s), "post", FALSE, TRUE) . "</div> | ";
				  $res->result[$i]->visual .= "<a href=./index.php?module=rpc&MOD_op=visual&polygon=" . $res->result[$i]->VHash . " target=_blank>地图</a>";
		  }
	  }

	  $mode = "<a href=./index.php?module=rpc&MOD_op=getasset&detail=" . ($detail?0 : 1) . ">" . ($detail?"综合" : "详情") . "</a>";

	  return array($res->result, $mode);
  }

?>
