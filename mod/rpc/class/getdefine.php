<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'getdefine');

  function params() {
	  extract($_POST);

	  $s = "类别：" . PHPWS_Form::formSelect("kind", array(0=>"顶点", 1=>"边界线", 2=>"多边形", 4=>"权益")) . "<br>";
	  $s .= "哈希：" . PHPWS_Form::formTextField("hash") . "<br>";
	  $s .= "递归：" . PHPWS_Form::formCheckBox("recur", 1) . "<br>";
	  
	  $s .= PHPWS_Form::formHidden("submiting", 1) . PHPWS_Form::formSubmit("哈希所定义的内容");
	  $s .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($s), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_POST);
	  $hash = strtolower($hash);
	  return array($kind + 0, $hash, $recur?true : false);
  }

  function showResult($res) {
	  extract($_POST);

	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }

	  if ($recur) {
		  $res->result->definition = (array) $res->result->definition;

		  $def = array();

		  foreach ($res->result->definition as $i=>$v) {
			  if ($v->father) $v->father = "<a href=#" . substr($v->father, 0, 31) . ">" . $v->father . "</a>";
			  if ($v->begin) $v->begin = "<a href=#" . substr($v->begin, 0, 31) . ">" . $v->begin . "</a>";
			  if ($v->end) $v->end = "<a href=#" . substr($v->end, 0, 31) . ">" . $v->end . "</a>";
			  if ($v->polygon) {
				  foreach ($v->polygon as $i=>$lp) {
					  foreach ($lp as $j=>$b) {
						  $v->polygon[$i][$j] = "<a href=#" . substr($b, 0, 31) . ">" . $b . "</a>";
					  }
				  }
			  }

			  $def["<a name=" . substr($i, 0, 31) . "></a>" . $i] = $v;
		  }
		  $res->result->definition = $def;
	  }
	  
	  return $res->result;
  }
?>
