<?php
require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');

	  extract($_REQUEST);
	  if (!$sel) {
		  $GLOBALS['core']->query('DELETE FROM mod_watchcats WHERE user_id=' . $_SESSION["OBJ_user"]->user_id . " AND category=$cat", true);
	  }
	  else {
		  $GLOBALS['core']->sqlInsert(array('user_id'=>$_SESSION["OBJ_user"]->user_id, 'category'=>$cat), 'mod_watchcats', true);

		  $ps = array();
		  $p = $cat;
		  while ($p && ($r = $GLOBALS['core']->sqlSelect('mod_category', 'id', $p))) {
			  $ps[] = $r[0]['parentid'];
			  $p = $r[0]['parentid'];
		  }
		  $c = array($cat);
		  while ($c) {
			  $t = array();
			  $r = $GLOBALS['core']->query('SELECT * FROM mod_category WHERE parentid IN (' . implode(",", $c) . ")", true);
			  while ($p = $r->fetchRow()) {
				  $t[] = $p['id'];
				  $ps[] = $p['id'];
			  }
			  $c = $t;
		  }
		  $GLOBALS['core']->query("DELETE FROM mod_watchcats WHERE  user_id=" . $_SESSION["OBJ_user"]->user_id . " AND category IN (" . implode(",", $ps) . ")", true);
	  }
	  
	  include_once(PHPWS_SOURCE_DIR . 'mod/accessories/functions/getwatchcats.php');
?>