<?php
/**
 * @version $Id: uninstall.php,v 1.7 2004/03/11 21:12:11 darren Exp $
 */
if (!$_SESSION["OBJ_user"]->isDeity()){
  header("location:index.php");
  exit();
}

  $status = 1;

?>