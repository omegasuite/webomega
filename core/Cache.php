<?php

/**
 * Allows the storing of variables in a cache for retrieval
 *
 * Thanks to Adam Morton and Steven Levin for specifications
 *
 * @version $Id: Cache.php,v 1.15 2005/01/25 22:11:14 steven Exp $
 * @author Matt McNaney <matt@NOSPAM.tux.appstate.edu>
 * @package Core
 */
class PHPWS_Cache{
  /**
   * Retrieves a value from the cache
   *
   * The id is a string. It is a max size of 32 (should you want to md5
   * your index. You do not have to enter the module title, however if your
   * module uses the cache while being accessed by another module, you may need
   * to include it. The data should be returned as an array or object if it was
   * saved as such.
   *
   * If the value has timed out (ie the current time is greater than the TTL of
   * the value), FALSE will be returned instead. The value will be flushed next time
   * set is called.
   *
   * Start example
   *
   * function viewTheTruth ($myMod_id) {
   *
   * if ($cache = PHPWS_Cache::get($myMod_id))
   *    return $cache;
   *
   *   // Rest of your function here
   *   .
   *   .
   *   .
   *
   *   PHPWS_Cache::set($myMod_data, $myMod_id);
   *   return $myMod_data;
   * }
   *
   * End example
   *
   * See set for more information on its use.
   *
   * @author                     Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param   string  id         Name of the value wanted
   * @param   string  mod_title  Name of your module
   * @return  mixed   data       Value of the id if exists, FALSE otherwise
   */
  function get($id, $mod_title=NULL){
    if (CACHE == FALSE)
      return FALSE;

    if (empty($mod_title))
      if (!($mod_title = $GLOBALS["core"]->current_mod))
	exit("Error in get: module title required");

    if (GLOBAL_CACHE == TRUE && isset($GLOBALS["PHPWS_Cache"][$mod_title][$id]))
      return $GLOBALS["PHPWS_Cache"][$mod_title][$id];

    $where["mod_title"] = $mod_title;
    $where["id"]        = $id;

    if (!($row = $GLOBALS["core"]->sqlSelect("cache", $where)))
      return FALSE;

    extract($row[0]);

    if ($ttl < mktime())
      return FALSE;

    if (!isset($data))
      return FALSE;

    if (isset($data)) {
	if (preg_match("/^[ao]:\d+:/", $data)) {
	    $unSerData = unserialize($data);
	    if (is_object($unSerData) || is_array($unSerData))
		$data =  $unSerData;
	}

	return $data;
    } else {
      return FALSE;
    }
  }

  /**
   * Sets a value to the cache
   *
   * The $data variable can be anything. If it is an object or array, it will be
   * serialized before insertion into the database. The id is an unique identifier
   * for the value that you will call in get. mod_title does not need specification
   * unless your module calls the cache when being accessed from another module.
   * The default TTL (Time to Live) is set from the define argument at the beginning 
   * of the file. It is incremented in minutes. If you feel you need a cache to last
   * longer, you may specify a longer TTL.
   *
   * set also cleans up the cache table by removing duplicates and  timed out values.
   *
   * See get for an example on its use.
   *
   * @author                     Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param   mixed   data       Value to save to the cache
   * @param   string  id         Name of the value to save
   * @param   string  mod_title  Name of your module
   * @param   integer timeout    Length the value should remain in cache
   * @return  boolean            True if successfully inserted, FALSE otherwise
   */
  function set($data, $id, $mod_title=NULL, $timeout=TTL){
    if (!CACHE)
      return FALSE;

    if (!is_numeric($timeout))
      $timeout = TTL;

    if (empty($mod_title))
      if (!($mod_title = $GLOBALS["core"]->current_mod))
	exit("Error in set: module title index.phprequired");

    if (GLOBAL_CACHE == TRUE)
      $GLOBALS["PHPWS_Cache"][$mod_title][$id] = $data;

    $sql["mod_title"] = $mod_title;
    $sql["id"]        = $id;

    $where["ttl"] = mktime();
    $compare["ttl"] = "<";

    $GLOBALS["core"]->sqlDelete("cache", $sql);
    $GLOBALS["core"]->sqlDelete("cache", $where, NULL, $compare);

    $sql["data"]      = $data;
    $sql["ttl"]       = mktime() + $timeout;

    return $GLOBALS["core"]->sqlInsert($sql, "cache", FALSE, FALSE, FALSE, FALSE);
  }

  function flush($module=NULL){
    if ($module){
      $GLOBALS["core"]->sqlDelete("cache", "mod_title", $module);
      $GLOBALS["PHPWS_Cache"][$module] = NULL;
    }
    else {
      $GLOBALS["core"]->sqlDelete("cache");
      $GLOBALS["PHPWS_Cache"] = NULL;
    }

  }
}
?>