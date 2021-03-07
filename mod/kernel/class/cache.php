<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/consts.php');

function Cache($place, $cachetbl, $maintbl, $cols) {
	  $loc = $GLOBALS['core']->sqlSelect($cachetbl, 'id', $place);
	  if (!$loc) {
		  $loc = $GLOBALS['core']->sqlSelect($maintbl, 'id', $place);
		  if ($loc) {
			  $sql = "INSERT INTO $cachetbl (id, " . implode(",", $cols) . ") VALUES ($place, ";
			  foreach ($cols as &$c) {
				  $c = $loc[0][$c];
				  if ($c === NULL) $c = 'NULL';
				  elseif (is_string($c)) $c = "'$c'";
				  else $c = "$c";
			  }
			  $sql .= implode(", ", $cols) . ")";
			  $GLOBALS['core']->query($sql, true);
		  }
	  }
	  return array('province'=>$loc[0]['province'], 'city'=>$loc[0]['city'], 'name'=>$loc[0]['name']);
}

function locCache($place) {
	  return Cache($place, 'cache_location', 'mod_locations', array('province', 'city', 'name'));
}

function prodCache($p) {
	  return $c['name'];
}

function goodsCache($p) {
	  return $c['name'];
}

function orgCache($p) {
	  return $c['name'];
}

function catCache($p) {
	  return $c['name'];
}

function wharehouseCache($p) {
	  return $c['name'];
}

?>