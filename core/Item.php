<?php

/* required for proper IP authorization */
//require_once("Net/CheckIP.php");    

/**
 * Base class for items used in phpWebSite modules.
 *
 * This is the base class for any item used in phpWebSite modules.
 * It contains database identification, owner, created, updated,
 * and other information.  These variables can be manipulated via
 * get and set functions provided in this class.
 *
 * @version $Id: Item.php,v 1.36 2004/09/28 18:28:02 steven Exp $
 * @author  Adam Morton <adam@tux.appstate.edu>
 * @author  Steven Levin <steven@tux.appstate.edu>
 * @package Core
 */
class PHPWS_Item {

  /**
   * Database id of this item.
   *
   * @var     integer
   * @example $this->_id = 2; 
   * @access  private
   */
  var $_id = NULL;

  /**
   * The username of the user who currently owns this item.
   *
   * @var     integer
   * @example $this->_owner = "steven";
   * @access  private
   */
  var $_owner = NULL;

  /**
   * The username of the user who last updated this item.
   *
   * @var integer
   * @example $this->_editor = "admin";
   * @access private
   */
  var $_editor = NULL;

  /**
   * The IP address of the last person to create or update this item.
   *
   * Must be a valid IPv4 or IPv6 address.
   *
   * @var     string
   * @example $this->_ip = "127.0.0.1";
   * @access  private
   */
  var $_ip = NULL;

  /**
   * The textual label of this item.
   *
   * Must be unique in the owner's among similar items.
   *
   * @var     string
   * @example $this->_label = "Item Label 01";
   * @access  private
   */
  var $_label = NULL;

  /**
   * The ids for the groups allowed access to this item
   *
   * Must be an existing group in phpwebsite database.
   *
   * @var     array
   * @example $this->_groups = array("default", "admin", "site_user", "site_maint");
   * @access  private
   */
  //var $_groups = array();

  /**
   * The date and time this item was created.
   *
   * Must be in unix date/time format.
   *
   * @var     string
   * @example $this->_created = time();
   * @access  private
   */
  var $_created = NULL;

  /**
   * The date and time this item was last updated.
   *
   * Must be in unix date/time format.
   *
   * @var     string
   * @example $this->_updated = time();
   * @access  private
   */
  var $_updated = NULL;

  /**
   * A boolean designating whether this item is hidden from searching or not.
   *
   * @var     integer
   * @example $this->_hidden = 0;
   * @access  private
   */
  var $_hidden = 0;

  /**
   * A boolean designating whether this item has been approved or not.
   *
   * @var     integer
   * @example $this->_approved = 1;
   * @access  private
   */
  var $_approved = 1;

  /**
   * The table name this item should store and access data from.
   *
   * Must be a valid table in the database and be sql "friendly".
   *
   * @var     string
   * @example $this->_table = "mod_myitem_table";
   * @access  private
   */
  var $_table = NULL;

  /**
   * List of variables which to exclude from commit
   *
   * An object which extends this class must add excludes via
   * <code>$this->add_exclude();</code>
   *
   * @var     array
   * @example $this->_exclude = array("_exclude", "_table", "_id");
   * @access  private
   */
  var $_exclude = array("_exclude", "_table", "_id");

  /**
   * Loads all data for this item and the object which called this function.
   *
   * This function will use the id number and table name to retrieve data on an item
   * from the database.  If no id or table name is supplied, then this new item and the new
   * object which called this function have all variables initialized to NULL. If an id and
   * table name ARE supplied, the data is retrieved, processed, and used to initialize this
   * item as well as the object that called this function.  Column names in the table in the
   * database must coorespond to the variable names in the object which called this function.
   * Any values from the database with no cooresponding variable are ignored and passed to the
   * child object. Underscores preceding an objects member variables are ignored.
   *
   * NOTE: set_id() and  set_table() must be called before init or else it
   * will result in failure.
   *
   * @param  array  assoc array to use to init the object
   * @return mixed  array of vars left in database result on success and FALSE on failure.
   * @access public
   */
  function init($row=NULL) {
    if((isset($this->_id) && isset($this->_table)) || is_array($row)) {
      if(is_array($row)) {
	$itemResult[0] = $row;
      } else {
	$itemResult = $GLOBALS['core']->sqlSelect($this->_table, "id", $this->_id);
      }

      if($itemResult) {
	/* get the class name for the child object and then get ite variables */
	$className = get_class($this);
	$classVars = get_class_vars($className);

	/* for each of the class variables see if the have a corresponding column in the result */
	if(is_array($classVars)) {
	  foreach($classVars as $key => $value) {
	    $column = $key;
	    if($column[0] == "_") {
	      $column = substr($column, 1, strlen($column));
	    }
	    
	    /* if the column exists and is set, then set the class variable */
	    /* if the column exists and is not set, then unset it form the result */
	    /* if the column does not exists, leave the value in the result for the child */
	    if(array_key_exists($column, $itemResult[0])) {
	      if(isset($itemResult[0][$column])) {
		if(preg_match("/^[aO]:\d+:/", $itemResult[0][$column]))
		  $this->{$key} = unserialize($itemResult[0][$column]);
		else
		  $this->{$key} = $itemResult[0][$column];
		unset($itemResult[0][$column]);
	      } else {
		unset($itemResult[0][$column]);
	      }
	    }
	  }
	  return $itemResult[0];
	}
      } else {
	$error = "No database result returned for ID : " . $this->_id . " Table : " . $this->_table;
	return new PHPWS_Error("core", "PHPWS_Item::init()", $error, "exit", 1);
      }
    } else {
      $error = "The parent object was not properly initialized to call init."; 
      return new PHPWS_Error("core", "PHPWS_Item::init()", $error, "exit", 1);
    }
  } // END FUNC init

  /**
   * Saves this item and the object which calls this function to the database.
   *
   * This function will save the contents of this item and the contents of the
   * object which called this function to the table specified via the $this->_table
   * variable.  The column names in the table must coorespond to the variable
   * names in this object and the object which called this function.  Any variable
   * that appears which does not have a cooresponding column in the database will
   * be ignored. Underscores preceding private variables in the object will be
   * removed before interpreting the column name.
   *
   * @param  array   $extras       Extra variables to be saved to the database
   *                               Must be an associative array keyed by the table column
   * @param  boolean $suppressSets Keeps set functions from being called leaving it up to
                                   the developer
   * @return boolean TRUE on success and FALSE on failure.
   * @access public
   */
  function commit($extras=NULL, $supressSets=FALSE) {
    if(!$supressSets) {
      if(isset($this->_id)) {
	$this->setUpdated();
	$this->setEditor();
      } else {
	$this->setCreated();
	$this->setUpdated();
	$this->setOwner();
	$this->setEditor();
      }
      $this->setIp();
    }

    $commitValues = get_object_vars($this);
    if(is_array($commitValues)) {
      /* removing values from the commit array that need to be excluded */
      if(is_array($this->_exclude)) {
	foreach($this->_exclude as $value) {
	  unset($commitValues[$value]);
	}
      }

      foreach($commitValues as $key => $value) {
	$oldKey = $key;
	/* removeving the underscore before private variables to match columns in the database */
	if($key[0] == "_") {
	  $key = substr($key, 1, strlen($key));
	  $commitValues[$key] = $commitValues[$oldKey];
	  unset($commitValues[$oldKey]);
	}

	/* changing boolean variables to a value that can be saved in the db */
	if(is_bool($commitValues[$key])) {
	  if($commitValues[$key]) {
	    $commitValues[$key] = 1;
	  } else {
	    $commitValues[$key] = 0;
	  }
	}
	
	/* serializing all objects and arrays */
	if(is_object($commitValues[$key]) || is_array($commitValues[$key])) {
	  $commitValues[$key] = addslashes(serialize($commitValues[$key]));
	} else if(get_magic_quotes_gpc() || get_magic_quotes_runtime()) {
	    if(is_null($commitValues[$key])) {
		continue;
	    } else {
		$commitValues[$key] = addslashes($commitValues[$key]);
	    }
	}
      }
    } else {
      $error = "Bail, was not able to get_object_vars.";
      return new PHPWS_Error("core", "PHPWS_Item::commit()", $error, "exit", 1);
    }

    if(is_array($extras)) {
      $commitValues = array_merge($commitValues, $extras);
    }

    /* if this item's id exists then the database call is an update, otherwise it is an insert */
    if(isset($this->_id)) {
      if($GLOBALS['core']->sqlUpdate($commitValues, $this->_table, "id", $this->_id)) {
	return TRUE;
      } else {
	$error = "The database update was unsuccessful.";
	return new PHPWS_Error("core", "PHPWS_Item::commit()", $error, "exit", 1);
      }
    } else {
      if($this->_id = $GLOBALS['core']->sqlInsert($commitValues, $this->_table, FALSE, TRUE, FALSE)) {
	return TRUE;
      } else {
	$error = "The database insert was unsuccessful.";
	return new PHPWS_Error("core", "PHPWS_Item::commit()", $error, "exit", 1);
      }      
    }
  } // END FUNC commit

  /**
   * Remove this item from the database
   * 
   * Removes the current Item from the database if $this->_id is set properly.
   * Items which extend must provide the approval if necessary.
   *
   * @return mixed  TRUE on success and PHPWS_Error on false
   * @access public
   */
  function kill() {
    if(isset($this->_id) && isset($this->_table)) {
      if($GLOBALS['core']->sqlDelete($this->_table, "id", $this->_id)) {
	return TRUE;
      } else {
	$error = "Call to sqlDelete was unsuccessful.";
	return new PHPWS_Error("core", "PHPWS_Item::kill()", $error, "exit", 1);
      }
    } else {
      $error = "The item was unable to be removed from the database.";
      return new PHPWS_Error("core", "PHPWS_Item::kill()", $error, "exit", 1);
    }
  } // END FUNC kill

  /**
   * Sets all member variables.
   *
   * This function takes an associative array and uses it to set the member
   * variables for this item with the values found in the array.  This is done via 
   * the set functions for this item.  The set functions will do any error checking 
   * on the variables and set them accordingly.  If any variable is invalid, a FALSE 
   * is returned and this item remains unchanged.
   *
   * @param  array   $vars The associative array of variables.
   * @return boolean TRUE on success and PHPWS_Error on failure.
   * @access public
   */
  function setVars($vars = NULL) {
    $classVars = get_class_vars(get_class($this));

    if(is_array($vars) && is_array($classVars)) {
      foreach($vars as $key => $value) {
	/* checking to see if the key passed exists as a variable (with or without _ ) for this object */
	if(array_key_exists($key, $classVars)) {
	  $this->{$key} = $value;
	  unset($vars[$key]);
	} else {
	  $key = "_{$key}";
	  if(array_key_exists($key, $classVars)) {
	    $this->{$key} = $value;
	    unset($vars[$key]);
	  }
	}
      }
      return $vars;
    } else {
      $error = "Argument passed was not an array";
      return new PHPWS_Error("core", "PHPWS_Item::setVars()", $error, "exit", 1);
    }
  } // END FUNC setVars

  /**
   * Sets the database id for this item.
   *
   * The $id passed in will be checked to make sure it is an integer.
   *
   * @param  integer $id The integer to set this item's database id to.
   * @return boolean TRUE on success and PHPWS_Error on failure.
   * @access public
   */
  function setId($id = NULL) {
    if(isset($id) && is_numeric($id)) {
      $this->_id = $id;
    } else {
      $error = "No ID was passed.";
      return new PHPWS_Error("core", "PHPWS_Item::setId()", $error, "exit", 1);
    }
  } // END FUNC set_id

  /**
   * Sets the owner of this item.
   *
   * Check to see if the user session exists and sets the owner to the username
   * in that session
   *
   * @return boolean TRUE on success and PHPWS_Error on failure.
   * @access public
   */
  function setOwner() {
    if(isset($_SESSION['OBJ_user'])) {
      if(isset($_SESSION['OBJ_user']->username)) {
	$this->_owner = $_SESSION['OBJ_user']->username;
      } else {
	$this->_owner = NULL;
	$error = "The user session did not contain a username.";
	return new PHPWS_Error("core", "PHPWS_Item::setOwner()", $error, "exit", 1);
      }
    } else {
      $this->_owner = NULL;
      $error = "The user session was not available.";
      return new PHPWS_Error("core", "PHPWS_Item::setOwner()", $error, "exit", 1);
    }
  } // END FUNC set_owner

  /**
   * Sets editor.
   *
   * Check to see if the user session exists and sets the owner to the username
   * in that session
   *
   * @return boolean TRUE on success and PHPWS_Error on failure.
   * @access public
   */
  function setEditor() {
    if(isset($_SESSION['OBJ_user'])) {
      if(isset($_SESSION['OBJ_user']->username)) {
	$this->_editor = $_SESSION['OBJ_user']->username;
      } else {
	$this->_editor = NULL;
	$error = "The user session did not contain a username.";
	return new PHPWS_Error("core", "PHPWS_Item::setEditor()", $error, "exit", 1);
      }
    } else {
      $this->_editor = NULL;
      $error = "The user session was not available.";
      return new PHPWS_Error("core", "PHPWS_Item::setEditor()", $error, "exit", 1);
    }
  } // END FUNC set_editor

  /**
   * Sets the IP address for this item.
   *
   * Makes sure the $ip passed in is a valid IPv4 or IPv6 address. Uses the
   * PEAR Net_CheckIP package to validate IPv4 addresses and the Net_IPv6 package
   * to validate IPv6 addresses.
   *
   * @return boolean TRUE on success and PHPWS_Error on failure.
   * @access public
   */
  function setIp() {
    if(isset($_SERVER['REMOTE_ADDR'])) {
      if(class_exists("Net_CheckIP")) {
	if(Net_CheckIP::check_ip($_SERVER['REMOTE_ADDR'])) {
	  $this->_ip = $_SERVER['REMOTE_ADDR'];
	} else {
	  $this->_ip = NULL;
	  $error = "The remote address provided was not valid.";
	  return new PHPWS_Error("core", "PHPWS_Item::setIp()", $error, "exit", 1);
	}
      } else {
	$this->_ip = $_SERVER['REMOTE_ADDR'];
      }
    } else {
      $this->_ip = NULL;
      $error = "No remote address was available to set the ip.";
      return new PHPWS_Error("core", "PHPWS_Item::setIp", $error, "exit", 1);
    }
  } // END FUNC set_ip

  /**
   * Sets the textual label for this item.
   *
   * Makes sure the label is a valid string and does not contain php or
   * unallowed html tags.
   *
   * @param  string  $label The string to set this item's label to.
   * @return boolean TRUE on success and PHPWS_Error on failure.
   * @access public
   */
  function setLabel($label = NULL) {
    if($label) {
      $this->_label = PHPWS_Text::parseInput($label);
    } else {
      $error = "No label was requested.";
      return new PHPWS_Error("core", "PHPWS_Item::setLabel()", $error);
    }
  } // END FUNC set_label

  /**
   * Sets the created date for this item.
   *
   * Uses the unix timestamp for date records.
   *
   * @access public
   */
  function setCreated() {
    $this->_created = time();
  } // END FUNC set_created

  /**
   * Sets the updated date for this item.
   *
   * Uses the unix timestamp for date records.
   *
   * @access public
   */
  function setUpdated() {
    $this->_updated = time();
  } // END FUNC set_updated

  /**
   * Sets the hidden flag for this item.
   *
   * Makes sure the $flag passed in is a valid boolean variable.
   *
   * @param  boolean $flag A boolean TRUE or FALSE designating whether or not this item is hidden.
   * @return boolean TRUE
   * @access public
   */
  function setHidden($flag = TRUE) {
    if($flag) {
      $this->_hidden = 1;
    } else {
      $this->_hidden = 0;
    }

    return TRUE;
  } // END FUNC set_hidden

  /**
   * Sets the approved flag for this item.
   *
   * Makes sure the $flag passed in is a valid boolean variable.
   *
   * @param  boolean $flag A boolean TRUE or FALSE designating whether or not this item is approved.
   * @return boolean TRUE
   * @access public
   */
  function setApproved($flag = TRUE) {
    if($flag) {
      $this->_approved = 1;
    } else {
      $this->_approved = 0;
    }

    return TRUE;
  } // END FUNC set_approved

  /**
   * Sets the table name for this item.
   *
   * Makes sure the name passed in is a valid string and an sql "friendly" name.
   *
   * @param  string $table The name of the table
   * @return TRUE on success and PHPWS_Error on failure
   * @access public
   */
  function setTable($table = NULL) {
    if(is_string($table)) {
      $this->_table = $table;
    } else {
      $error = "Table name passed was not a string.";
      return new PHPWS_Error("core", "PHPWS_Item::setTable()", $error, "exit", 1);
    }
  } // END FUNC set_table
  
  /**
   * Adds a group.
   *
   * Adds a group to this item's $_groups array.  Make sure $group is valid phpWebSite
   * group id and is not already in the array.
   *
   * @param  integer $group The id of the group to be added.
   * @return boolean TRUE on success and PHPWS_Error on failure.
   * @access public
   */
  function addGroup($group = NULL) {
    $validGroup = TRUE;    /* should eventually check database for group */

    if($validGroup || !in_array($group, $this->_groups)) {
      array_push($this->_groups, $group);
    } else {
      $error = "The group passed was not a valid group or already existed.";
      return new PHPWS_Error("core", "PHPWS_Item::addGroup()", $error, "exit", 1);
    }
  } // END FUNC add_group

  /**
   * Removes a group
   *
   * Removes a group form this item's $_groups array.  Return FALSE if $group does 
   * not exist in the array
   *
   * @param  integer $group The id of the group to be removed.
   * @return boolean TRUE on success and PHPWS_Error on failure.
   * @access public
   */
  function removeGroup($group = NULL) {
    if(in_array($group, $this->_groups)) {
      $key = array_keys($group, $this->_groups);
      unset($this->_groups[$key]);
    } else {
      $error = "The group passed did not exist.";
      return new PHPWS_Error("core", "PHPWS_Item::removeGroup()", $error, "exit", 1);
    }
  } // END FUNC remove_group

  /**
   * Adds variable names to the exlude list
   *
   * Make sure $list past in is an array
   *
   * @param  array   $list items which to exclude from child object on commit.
   * @return boolean TRUE on success PHPWS_Error on failure.
   * @access public
   */
  function addExclude($list = NULL) {
    if(is_array($list)) {
      foreach($list as $value) {
	array_push($this->_exclude, $value);
      }
    } else {
      $error = "Argument passed was not an array.";
      return new PHPWS_Error("core", "PHPWS_Item::addExclude()", $error, "exit", 1);
    }
  } // END FUNC add exlude

  /**
   * Returns the current database id of this item.
   *
   * @return integer The database id of this item.
   * @access public
   */
  function getId() {
    if(isset($this->_id)) return $this->_id;
    else return NULL;
  } // END FUNC get_id

  /**
   * Returns the user id of the user who owns (created) this item.
   *
   * @return integer The user identification number of the user who owns this item.
   * @access public
   */
  function getOwner() {
    if(isset($this->_owner)) return $this->_owner;
    else return NULL;
  } // END FUNC get_owner

  /**
   * Returns the id of the last user to edit this item.
   *
   * @return integer The user id of the last user to edit this item.
   * @access public
   */
  function getEditor() {
    if(isset($this->_editor)) return $this->_editor;
    else return NULL;
  } // END FUNC get_editor

  /**
   * Returns the current ip address of this item.
   *
   * @return string The ip address of the last owner who created or updated this item.
   * @access public
   */
  function getIp() {
    if(isset($this->_ip)) return $this->_ip;
    else return NULL;
  } // END FUNC get_ip

  /**
   * Returns the current textual label of this item.
   *
   * @return string The textual label of this item in string format.
   * @access public
   */
  function getLabel() {
    if(isset($this->_label)) return $this->_label;
    else return NULL;
  } // END FUNC get_label

  /**
   * Returns the current created date of this item.
   *
   * Uses PHPWS_DATE_FORMAT and PHPWS_TIME_FORMAT defined in the datesettings.en.php
   *
   * @return string The created date of this item in the sql 'datetime' format.
   * @access public
   */
  function getCreated() {
    if($this->_created) return date(PHPWS_DATE_FORMAT . " " . PHPWS_TIME_FORMAT, $this->_created);
    else return NULL;
  } // END FUNC get_created

  /**
   * Returns the current updated date of this item.
   *
   * Uses PHPWS_DATE_FORMAT and PHPWS_TIME_FORMAT defined in the datesettings.en.php
   *
   * @return string The updated date of this item in the sql 'datetime' format.
   * @access public
   */
  function getUpdated() {
    if($this->_updated) return date(PHPWS_DATE_FORMAT . " " . PHPWS_TIME_FORMAT, $this->_updated);
    else return NULL;
  } // END FUNC get_updated

  /**
   * Returns the current table name for this item.
   *
   * @return string The table name for this item.
   * @access public
   */
  function getTable() {
    if(isset($this->_table)) return $this->_table;
    else return NULL;
  } // END FUNC get_table

  /**
   * Returns TRUE if this item is hidden and FALSE if it is not.
   *
   * @return boolean TRUE if hidden FALSE if not hidden.
   * @access public
   */
  function isHidden() {
    if(isset($this->_hidden)) return $this->_hidden;
    else return NULL;
  } // END FUNC isHidden

  /**
   * Returns TRUE if this item is approved or FALSE if it is not.
   *
   * @return boolean TRUE if approved, FALSE if not approved.
   * @access public
   */
  function isApproved() {
    if(isset($this->_approved)) return $this->_approved;
    else return NULL;
  } // END FUNC isApproved

  function set($name, $value) {
    $vars = get_object_vars($this);
    $pri = "_{$name}";
    $pub = "{$name}";
    if(array_key_exists($pri, $vars)) {
      $this->$pri = $value;
    } else if(array_key_exists($pub, $vars)) {
      $this->$pub = $value;
    }
  }

  function get($name) {
    $vars = get_object_vars($this);
    $pri = "_{$name}";
    $pub = "{$name}";
    if(array_key_exists($pri, $vars)) {
      return $this->$pri;
    } else if(array_key_exists($pub, $vars)) {
      return $this->$pub;
    } 
  }
}// END CLASS PHPWS_Item

?>