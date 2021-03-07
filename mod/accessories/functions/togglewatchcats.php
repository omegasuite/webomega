<?php
require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');

	  extract($_REQUEST);
	  if ($sel == 'false') {
		  $c = array($cat);
		  $del = array($cat);
		  while ($c) {
			  $t = array();
			  $r = $GLOBALS['core']->query('SELECT * FROM mod_category WHERE parentid IN (' . implode(",", $c) . ")", true);
			  while ($p = $r->fetchRow()) {
				  $t[] = $p['id'];
				  $del[] = $p['id'];
			  }
			  $c = $t;
		  }

		  $p = $cat;
		  while($p) {
			  $r = $GLOBALS['core']->sqlSelect('mod_category', 'id', $p);
			  if ($r[0]['parentid'] == 0) {
				  $p = $r[0]['parentid'];
				  continue;
			  }
			  $s = $GLOBALS['core']->sqlSelect('mod_category', 'parentid', $r[0]['parentid']);
			  $b = array();
			  foreach ($s as $q) if ($q['id'] != $p) $b[] = $q['id'];
			  if ($b) {
				  $p = $GLOBALS['core']->query('SELECT * FROM mod_watchcats WHERE user_id=' . $_SESSION["OBJ_user"]->user_id . " AND category IN (" . implode(",", $b) . ") LIMIT 0,1", true);
				  if ($p->fetchRow()) $p = 0;
				  else {
					  $p = $r[0]['parentid'];
					  $del[] = $p;
				  }
			  }
			  else $p = 0;
		  }
		  $GLOBALS['core']->query('DELETE FROM mod_watchcats WHERE user_id=' . $_SESSION["OBJ_user"]->user_id . " AND category IN (" . implode(",", $del) . ")", true);
	  }
	  else {
		  $p = $cat;
		  while ($p) {
			  $GLOBALS['core']->sqlInsert(array('user_id'=>$_SESSION["OBJ_user"]->user_id, 'category'=>$p), 'mod_watchcats', true);
			  $r = $GLOBALS['core']->sqlSelect('mod_category', 'id', $p);
			  $p = $r[0]['parentid'];
		  }
		  $c = array($cat);
		  while ($c) {
			  $t = array();
			  $r = $GLOBALS['core']->query('SELECT * FROM mod_category WHERE parentid IN (' . implode(",", $c) . ")", true);
			  while ($p = $r->fetchRow()) {
				  $t[] = $p['id'];
				  $GLOBALS['core']->sqlInsert(array('user_id'=>$_SESSION["OBJ_user"]->user_id, 'category'=>$p['id']), 'mod_watchcats', true);
			  }
			  $c = $t;
		  }
	  }
	  if ($refresh) header("location: ./index.php?module=product&MOD_op=categories");
?>