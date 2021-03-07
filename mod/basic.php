<?php
class __tablenames {
	public static $tables = array('auth'=>'mod_authorization', 'cities'=>'mod_cities');
}

  function querySel($target, $table, $cond, $full = false) {
	  $tables = __tablenames::$tables;
	  if ($full) foreach ($tables as &$c) $c = "supply_" . $c;
	  return $GLOBALS['core']->query("SELECT $target FROM " . strtr($table, $tables) . ($cond?(strstr($table, " JOIN ")?" ON " : " WHERE ") . $cond : ''), !$full);
  }

  function queryDel($table, $cond, $full = false) {
	  $tables = __tablenames::$tables;
	  if ($full) foreach ($tables as &$c) $c = "supply_" . $c;
	  return $GLOBALS['core']->query("DELETE FROM " . $tables[$table] . ($cond?" WHERE " . $cond : ''), !$full);
  }

  function queryUpdate($target, $table, $cond, $full = false) {
	  $tables = __tablenames::$tables;
	  if ($full) foreach ($tables as &$c) $c = "supply_" . $c;
	  return $GLOBALS['core']->query("UPDATE " .  $tables[$table] . " SET $target " . ($cond?" WHERE " . $cond : ''), !$full);
  }

  function sqlSelect($table, $a = NULL, $b = NULL, $c = NULL, $d = NULL) {
	  return $GLOBALS['core']->sqlSelect($tables[$table], $a, $b, $c, $d);
  }

  function sqlInsert($target, $table, $compare = false, $returnid = false) {
	  if ($returnid)
		  return $GLOBALS['core']->sqlInsert($target, __tablenames::$tables[$table], $compare, $returnid);
	  return $GLOBALS['core']->sqlInsert($target, __tablenames::$tables[$table], $compare);
  }

  function sqlUpdate($target, $table, $a = NULL, $b = NULL) {
	  return $GLOBALS['core']->sqlUpdate($target, __tablenames::$tables[$table], $a, $b);
  }

  function sqlDelete($table, $a = NULL, $b = NULL) {
	  return $GLOBALS['core']->sqlDelete(__tablenames::$tables[$table], $a, $b);
  }
?>