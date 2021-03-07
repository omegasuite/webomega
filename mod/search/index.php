<?php

/**
 * index.php
 *
 * Main Control switch for the search module
 * @author Steven Levin <steven@NOSPAM.tux.appstate.edu, steven@NOSPAM.tux.appstate.edu>
 * @version $Id: index.php,v 1.8 2004/03/10 20:49:01 steven Exp $
 */

if(!isset($GLOBALS['core'])) {
  header("Location: ../..");
  exit();
}

define("SEARCH_DEFAULT_RESULT_LIMIT", 10);
$CNT_search_results['content'] = NULL;
if ($_SESSION['OBJ_search']) {
	$_SESSION['OBJ_search']->action();

	if(isset($GLOBALS['module']))
	  $_SESSION['OBJ_search']->block($GLOBALS['module']);
}

?>