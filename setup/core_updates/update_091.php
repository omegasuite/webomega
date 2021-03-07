<?php

if (!$_SESSION["OBJ_user"]->isDeity()){
  header("location:index.php");
  exit();
}

  include (PHPWS_SOURCE_DIR . "conf/config.php");
  
  if ($dbversion == "mysql")
    $columnType = "mediumtext";
  else
    $columnType = "text";

$GLOBALS['core']->query("DROP TABLE cache", TRUE);

  $update = "CREATE TABLE cache (
  mod_title varchar(30) NOT NULL default '',
  id varchar(32) NOT NULL default '',
  data $columnType NOT NULL,
  ttl int unsigned NOT NULL default '0',
  INDEX mod_title (mod_title,id)
)";
  $GLOBALS['core']->query($update, TRUE);

?>