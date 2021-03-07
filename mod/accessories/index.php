<?php
if (!isset($GLOBALS['core'])){
  header("location:../../");
  exit();
}

if($GLOBALS["module"] == "accessories")
  $GLOBALS["CNT_accessories"] = array("title"=>NULL, "content"=>NULL);

if (is_array($_REQUEST["MOD_op"])) {
	$foo = $_REQUEST["MOD_op"];		// strange behavior in GreenGeeks PHP, must have this to get the next stmt work
	list($operation,) = each ($_REQUEST["MOD_op"]);
}
elseif ($_REQUEST["MOD_op"]) $operation = $_REQUEST["MOD_op"];

$operation = preg_replace("/_wx$/", '', $operation);

include(PHPWS_SOURCE_DIR . "mod/accessories/functions/$operation.php");
?>