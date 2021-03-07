<?php
error_reporting (E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

session_start();
include("allow_setup.php");

$_SESSION['allowSetup'] = TRUE;

define("PHPWS_SOURCE_DIR", "../");
require_once ("../core/Core.php");
require_once("../mod/boost/class/Boost.php");

if     (!is_file("../conf/config.php")) $header = "set_config.php";
else {
  $core = new PHPWS_Core(NULL, "../", TRUE);
  $core->killSession("OBJ_user");
  if (!$core->sqlTableExists("modules", TRUE)) $header = "setup.php";
  else   $header = "update.php";
}


header ("location: $header");
exit();
?>