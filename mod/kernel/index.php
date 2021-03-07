<?php
if (!isset($GLOBALS['core'])){
  header("location:../../");
  exit();
}

if($GLOBALS["module"] == "kernel")
  $GLOBALS["CNT_kernel"] = array("title"=>NULL, "content"=>NULL);

if (is_array($_REQUEST["MOD_op"])) {
	$foo = $_REQUEST["MOD_op"];		// strange behavior in GreenGeeks PHP, must have this to get the next stmt work
	list($operation,) = each ($_REQUEST["MOD_op"]);
}
elseif ($_REQUEST["MOD_op"]) $operation = $_REQUEST["MOD_op"];
else $operation = '';

if(!in_array($operation, array('')) && !isset($_SESSION["OBJ_user"]->username)) {
  header("location:../../");
  exit();
}

if ($operation) {
	if (file_exists(PHPWS_SOURCE_DIR . 'mod/kernel/class/settings/' . $operation . '.php')) {
		include_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/settings/' . $operation . '.php');
		$GLOBALS['CNT_kernel']['content'] = $operation();
	}
}

?>