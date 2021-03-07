<?php


/**
 * Controls the database interactions with phpWebSite
 * 
 * This class relies heavily on the PEAR DB class and will incorporate
 * more of the PEAR implementation soon.
 *
 * @version $Id: Database.php,v 1.95 2005/07/05 13:14:20 matt Exp $
 * @author  Matt McNaney <matt@NOSPAM.tux.appstate.edu>
 * @author  Adam Morton  <adam@NOSPAM.tux.appstate.edu>
 * @author  Steven Levin <steven@NOSPAM.tux.appstate.edu>
 * @package Core
 */

define('SHOW_DB_ERRORS', TRUE);

class PHPWS_Database {
  /**
   * Prefix appended to table names.
   * @var string
   * @access public
   */
  var $tbl_prefix;

  /**
   * Contains the database object for the current connection.
   * @var object DB
   * @access private
   */
  var $db;

  /**
   * Loads the database indicated by the config_file
   *
   * Specialized, but useful for phpwebsite. When the
   * core is loaded, it passes the configuration file name
   * to this function to initialize it. Besides preparing the hub
   * it can be used to open a branch database as well.
   *
   * @author   Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @modified Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @param    string  $configFile     Name of the database configuration file
   * @param    boolean $suppress_error Whether or not to print error messages
   * @return   boolean TRUE on success, FALSE on failure.
   * @access   public
   */
  function loadDatabase($configFile=NULL, $suppress_error=FALSE, $skipDefine=FALSE) {
    if (isset($this->db))
      $this->db->disconnect();

    if($configFile && file_exists($configFile)) require($configFile);
    else require($this->source_dir . $this->configFile);

    if (isset($table_prefix)){
      if(!$skipDefine)
	(!defined("PHPWS_TBL_PREFIX")) ? define("PHPWS_TBL_PREFIX", $table_prefix) : NULL;
      $this->tbl_prefix = $table_prefix;
    }
    else {
      if(!$skipDefine)
	(!defined("PHPWS_TBL_PREFIX")) ? define("PHPWS_TBL_PREFIX", NULL) : NULL;
      $this->tbl_prefix = NULL;
    }

    $this->db = DB::connect("$dbversion://$dbuser:$dbpass@$dbhost/$dbname");

    if (isset($this->db->message)){
      if ($suppress_error) return FALSE;
      else exit("Error connecting to the database: ".$this->db->message);
    } else {
      return TRUE;
    }
  }// END FUNC loadDatabase()

  function getTablePrefix() {
    return $this->tbl_prefix;
  }

  /**
   * Queries the SQL db with supplied command
   *
   * The most basic method for dealing with the database.
   * Read the PEAR documentation for information on what
   * methods to apply to the resultant object to retrieve
   * its data.
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string  $sql            The SQL command query to execute
   * @param  boolean $addTablePrefix If TRUE, add table prefix to SQL statement
   * @param  boolean $error_pass     If TRUE, don't echo errors
   * @return object  $sql_result     DB Result object of the query
   * @access public
   */
  function query($sql, $addTablePrefix=FALSE, $error_pass=FALSE){
    if (method_exists($this->db, "query")) {
      if ($addTablePrefix)
	$sql = $this->addTablePrefix($sql);

      if (DB::iserror($sql_result = $this->db->query($sql))) {
	if ($error_pass) {
            return FALSE;
        } else {
            if (SHOW_DB_ERRORS) {
                exit($sql_result->getMessage() . "<br />" . $sql_result->userinfo);
            } else {
                exit('An error occurred.');
            }
        }
      } else return $sql_result;
    }
    else return FALSE;
  }// END FUNC query()

  /**
   * Retrieves one row from the database
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string  $sql            SQL query to execute
   * @param  boolean $addTablePrefix If TRUE, adds table prefix to SQL command
   * @return mixed  $sql_result      Returns selected row
   * @access public
   */
  function getOne($sql, $addTablePrefix=FALSE) {
    if (method_exists($this->db, "getOne")) {
      if ($addTablePrefix)
	$sql = $this->addTablePrefix($sql);

      if (DB::iserror($sql_result = $this->db->getOne($sql)))
	exit ("SQL: $sql<br />".DB::errorMessage($sql_result));
      else return $sql_result;
    }
    else return NULL;
  }// END FUNC getOne()


  /**
   * Performs the PEAR getRow command
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string  $sql            SQL command
   * @param  boolean $addTablePrefix If TRUE, adds table prefix to SQL command
   * @return mixed   $sql_result     Returns selected rows
   * @access public
   */
  function getRow($sql, $addTablePrefix=FALSE) {
    if (method_exists($this->db, "getRow")) {
      if ($addTablePrefix)
	$sql = $this->addTablePrefix($sql);
      
      if (DB::iserror($sql_result = $this->db->getRow($sql)))
	exit ("SQL: $sql<br />".DB::errorMessage($sql_result));
      else return $sql_result;
    } else return NULL;
  }// END FUNC getRow()


  /**
   * Retrieves a column from a table
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string  $sql            SQL command
   * @param  boolean $addTablePrefix If TRUE, adds table prefix to SQL command
   * @return mixed   $sql_result     Returns selected columns
   * @access public
   */
  function getCol($sql, $addTablePrefix=FALSE) {
    if (method_exists($this->db, "getCol")) {
      if ($addTablePrefix)
	$sql = $this->addTablePrefix($sql);
	 
      if (DB::iserror($sql_result = $this->db->getCol($sql)))
	exit ("SQL: $sql<br />".DB::errorMessage($sql_result));
      else return $sql_result;
    } else return NULL;
  }// END FUNC getCol()


  /**
   * Performs PEAR getAssoc command
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string  $sql            SQL command
   * @param  boolean $addTablePrefix If TRUE, adds table prefix to SQL command
   * @return mixed   $sql_result     Returns selected rows
   * @access public
   */
  function getAssoc($sql, $addTablePrefix=FALSE) {
    if (method_exists($this->db, "getAssoc")) {
      if ($addTablePrefix) $sql = $this->addTablePrefix($sql);

      if (DB::iserror($sql_result = $this->db->getAssoc($sql)))
	exit ("SQL: $sql<br />".DB::errorMessage($sql_result));
      else return $sql_result;
    } else return NULL;
  }// END FUNC getAssoc()


  /**
   * getAllAssoc() fetches the entire result set
   * of a query and return it as an associative array within
   * another array. This was based on getAssoc (which never
   * did much for me)
   *
   * @author Matt McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string  $sql            SQL command
   * @param  boolean $addTablePrefix If TRUE, adds table prefix to SQL command
   * @return mixed   $sql_result     Returns array of selected rows
   * @access public
   */
  function getAllAssoc($sql, $addTablePrefix=FALSE) {
    $this->setFetchMode("assoc");
    $sql = $this->query($sql, $addTablePrefix);
    $result = array();
    while($row = $sql->fetchrow())
      $result[] = $row;

    return $result;
  }// END FUNC getAllAssoc()


  /**
   * getAll() fetches all queried rows.
   *
   * This is an internal PEAR command
   * @author Matt McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string  $sql            SQL select statement
   * @param  boolean $addTablePrefix If TRUE, adds table prefix to query
   * @return array   $sql_result     Rows find, NULL if empty
   * @access public
   */
  function getAll($sql, $addTablePrefix=FALSE) {
    if (method_exists($this->db, "getAll")) {
      if ($addTablePrefix)
	$sql = $this->addTablePrefix($sql);
      
      if (DB::iserror($sql_result = $this->db->getAll($sql)))
	exit ("SQL: $sql<br />".DB::errorMessage($sql_result));
      else return $sql_result;
    } else return NULL;
  }// END FUNC getAll()


  /**
   * Returns a single row from a db select query
   *
   * Not used often. sqlSelect is easier but this function allows
   * a specific sql request.
   * For sql select statements that return one row ONLY.
   * Not be run in while/for loops.
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string  $sql            SQL select statement ran against db
   * @param  boolean $addTablePrefix If TRUE, add table prefix to sql statement
   * @param  string  $mode           The mode to use during the fetch.  This affects the return value
   * @return array   $row            Info received from query
   * @access public
   */
  function quickFetch($sql, $addTablePrefix=NULL, $mode="assoc") {
    $this->setFetchMode($mode);
    $sql_result = $this->query($sql, $addTablePrefix);
    $row = $sql_result->fetchrow();
  
    if ($row) return $row;
    else return NULL;
  }// END FUNC quickFetch()


  /**
   * Sets a default switchmode for PEAR selects
   *
   * If this function is called before your query calls
   * you can call $foo->fetchrow() without entering the setFetchMode
   * in the parens. 'ordered' is usually the default for PEAR but
   * 'assoc' is more useful.
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string $mode Type of setFetchMode
   * @access public
   */
  function setFetchMode($mode) {
    switch ($mode) {
    case "assoc":
    $this->db->setFetchMode(DB_FETCHMODE_ASSOC);
    break;
    
    case "ordered":
    $this->db->setFetchMode(DB_FETCHMODE_ORDERED);
    break;

    case "object":
    $this->db->setFetchMode(DB_FETCHMODE_OBJECT);
    break;
    }
  }// END FUNC setFetchMode()


  /**
   * Prepares a value for database writing or reading
   *
   * @author Matt McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  mixed $value The value to prepare for the database.
   * @return mixed $value The prepared value
   * @access public
   */
  function dbReady($value=NULL) {
    if (is_array($value) || is_object($value))
      return $this->dbReady(serialize($value));
    elseif (is_string($value)){
      $value = preg_replace("/(?<!\\\)'/", "\'", $value);
      return "'$value'"; 
    }
    elseif (is_null($value))
      return "NULL";
    else
      return $value;
  }// END FUNC dbReady()


  /**
   * Prepares the WHERE statement for DB queries
   *
   * This function is called by the various SQL_* functions to create
   * the "where" string. It is called when the function notices an 
   * array was sent instead of a string for the comparison.
   *
   * @author Matt McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  array  $match   Array of column:match coupling
   * @param  mixed  $compare Comparison operator(s)
   * @param  mixed  $and_or  Comparison logic
   * @return string $sql     The prepared where statement
   * @access public
   */
  function makeWhere($match, $compare, $and_or) {
    $i = 0;
    $sql = " where ";
    foreach($match as $column_name => $column_value) {
      if($i) {
	if (is_array($and_or)) {
	  if ($and_or[$column_name]) $sql .= " $and_or[$column_name] ";
	  else $sql .= " and ";
	} else $sql .= " $and_or ";
      }
      $column_value = PHPWS_Core::dbReady($column_value);
    
      if (is_array($compare)) {
	if (isset($compare[$column_name])) $sql .= $column_name." ".$compare[$column_name]." ".$column_value;
	else $sql .= $column_name." = ".$column_value;
      } else $sql .= $column_name." ".$compare." ".$column_value;
      $i=1;
    }
    return $sql;
  }// END FUNC makeWhere()


  /**
   * Prepares user input for being a column or table name
   *
   * This function will take a string and prepare it for use as a 
   * database column name or table name. It will strip all characters
   * but alpha numberic and will only return a valid string if the first
   * character of the string is alpha.
   *
   * @author Mike Wilson <mike@NOSPAM.tux.appstate.edu>
   * @modified Steven Levin <steven@NOSAM.tux.appstate.edu>
   * @param  string $name The string from user
   * @param  array  $extraReserved A list of extra reserved words
   * @return string The string or FALSE if invalid
   * @access public
   */
  function sqlFriendlyName($name, $extraReserved = array()) {
    $reserved = array("ADD", "ALL", "ALTER", "ANALYZE", "AND", "AS", "ASC", "AUTO_INCREMENT", "BDB",
		      "BERKELEYDB", "BETWEEN", "BIGINT", "BINARY", "BLOB", "BOTH", "BTREE", "BY", "CASCADE",
		      "CASE", "CHANGE", "CHAR", "CHARACTER", "CHECK", "COLLATE", "COLUMN", "COLUMNS", "CONSTRAINT",
		      "CREATE", "CROSS", "CURRENT_DATE", "CURRENT_TIME", "CURRENT_TIMESTAMP", "DATABASE", "DATABASES",
		      "DAY_HOUR", "DAY_MINUTE", "DAY_SECOND", "DEC", "DECIMAL", "DEFAULT",
		      "DELAYED", "DELETE", "DESC", "DESCRIBE", "DISTINCT", "DISTINCTROW",
		      "DOUBLE", "DROP", "ELSE", "ENCLOSED", "ERRORS", "ESCAPED", "EXISTS", "EXPLAIN", "FIELDS",
		      "FLOAT", "FOR", "FOREIGN", "FROM", "FULLTEXT", "FUNCTION", "GEOMETRY", "GRANT", "GROUP",
		      "HASH", "HAVING", "HELP", "HIGH_PRIORITY", "HOUR_MINUTE", "HOUR_SECOND",
		      "IF", "IGNORE", "IN", "INDEX", "INFILE", "INNER", "INNODB", "INSERT", "INT",
		      "INTEGER", "INTERVAL", "INTO", "IS", "JOIN", "KEY", "KEYS", "KILL", "LEADING",
		      "LEFT", "LIKE", "LIMIT", "LINES", "LOAD", "LOCK", "LONG", "LONGBLOB", "LONGTEXT",
		      "LOW_PRIORITY", "MASTER_SERVER_ID", "MATCH", "MEDIUMBLOB", "MEDIUMINT", "MEDIUMTEXT", 
		      "MIDDLEINT", "MINUTE_SECOND", "MRG_MYISAM", "NATURAL", "NOT", "NULL", "NUMERIC", "ON", "OPTIMIZE",
		      "OPTION", "OPTIONALLY", "OR", "ORDER", "OUTER", "OUTFILE", "PRECISION", "PRIMARY", "PRIVILEGES",
		      "PROCEDURE", "PURGE", "READ", "REAL", "REFERENCES", "REGEXP", "RENAME", "REPLACE", "REQUIRE",
		      "RESTRICT", "RETURNS", "REVOKE", "RIGHT", "RLIKE", "RTREE", "SELECT", "SET", "SHOW",
		      "SMALLINT", "SONAME", "SPATIAL", "SQL_BIG_RESULT", "SQL_CALC_FOUND_ROWS", "SQL_SMALL_RESULT",
		      "SSL", "STARTING", "STRAIGHT_JOIN", "STRIPED", "TABLE", "TABLES", "TERMINATED", "THEN", "TINYBLOB",
		      "TINYINT", "TINYTEXT", "TO", "TRAILING", "TYPES", "UNION", "UNIQUE", "UNLOCK", "UNSIGNED",
		      "UPDATE", "USAGE", "USE", "USER_RESOURCES", "USING", "VALUES", "VARBINARY", "VARCHAR", "VARYING",
		      "WARNINGS", "WHEN", "WHERE", "WITH", "WRITE", "XOR", "YEAR_MONTH", "ZEROFILL");

    $reserved = array_merge($reserved, $extraReserved);

    $temp = strtoupper($name);

    if(in_array($temp, $reserved))
      return FALSE;

    if(!preg_match("/^[a-zA-Z]/", $name)) 
      return FALSE;

    return preg_replace("/[^a-zA-Z0-9_]/", "\\1", $name);
  } // END FUNC sqlFriendlyName()

  /**
   * Performs the SQL insert command on the database.
   *
   * The db_array must contain an associative array. The key of each
   * cell is the name of the db table column. The value of the cell
   * is what should be inserted into that column. If check_dup is triggered
   * This function WILL NOT insert a row if an identical row is present.
   * For the duplicate check, the function can only compare against the column names
   * it has been given. Returns FALSE is there was an error accessing the database.
   *
   * If a table's column name is sent to maxColumn, then the highest
   * value of that column will be returned. Make sure that 1) you are locking
   * tables before running sqlInsert, 2) maxColumn is a real column
   * otherwise a NULL will be returned instead of a value or TRUE. This could
   * cause problems if you are performing a boolean check on the sqlInsert result.
   *
   * If autoIncrement is true, then the maxColumn will be added to the insert statement.
   * Useful for auto incrementing id columns.
   *
   * show_sql just echoes the sqlInsert statement for error checking.
   *
   * Table prefixing is taken care of automatically.
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  array   $db_array          Associative array of row to be inserted
   * @param  string  $table_name        Name of the table to insert to.
   * @param  boolean $check_dup         Whether to check for duplicate entry or not.
   * @param  string  $return_max_column The column to return the max value of after execution
   * @param  boolean $show_sql          Whether or not to echo the sql query created
   * @param  boolean $autoIncrement     Instructs the function to apply the maxColumn amount to the insert
   * @return boolean (TRUE or 1 = success), (FALSE or 0 = failure).
   * @access public
   */
  function sqlInsert ($db_array, $table_name, $check_dup=FALSE, $returnId=FALSE, $show_sql=FALSE, $autoIncrement=TRUE) {

    if ($check_dup)
      if ($test_sql = $this->sqlSelect($table_name, $db_array))
	return FALSE;

    $loop = 0;
    $left = NULL;
    $right = NULL;
    if (is_array($db_array)) {
      foreach ($db_array as $key=>$value) {
	if ($loop) {
	  $left .= ", ";
	  $right .= ", ";
	}
	$left .= "$key";
	$right .= $this->dbReady($value);
	$loop = 1;
      }

      if ($autoIncrement && $column_name = $this->sqlGetIndex($table_name)){
	$maxId = $this->db->nextId($this->tbl_prefix.$table_name);
	if (is_object($maxId))
	  exit("sqlInsert error: maxId returned error - <b>".$maxId->message)."</b>";
	  $left .= ", $column_name";
	  $right .= ", $maxId";
      }
      
      $sql = "insert ".$this->tbl_prefix.$table_name." ($left) values ($right)";     
      
      if ($show_sql) echo $sql."<br />";
      
      if($sql_result = $this->query($sql, FALSE, TRUE)){
	if ($returnId)
	  return $maxId;
	else
	  return TRUE;
      }
      elseif (isset($maxId)) {
	$this->sqlUpdate(array("id"=>$maxId-1), $table_name."_seq");
	exit ("SQL: $sql<br />".DB::errorMessage($sql_result));
      }
      else 
	  return FALSE;

    }
    else exit("Error: sqlInsert() did not receive an array");
  }// END FUNC sqlInsert()

  /**
   * Attempts to find the primary index of a table
   *
   * @author                    Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param   string tableName  Name of table to search
   * @return  string            Name of column if found, NULL otherwise
   * @access  public
   */
  function sqlGetIndex($tableName){
    if ($tableInfo = $this->getAllAssoc("show index from " . $this->tbl_prefix . $tableName))
      if (stristr($tableInfo[0]["Key_name"], "PRIMARY"))
	return $tableInfo[0]["Column_name"];

    return NULL;
  }

  /**
   * Performs the SQL select command on the database.
   *
   * sqlSelect will return a numerically indexed array, regardless
   * of the number of returns. You can compare '$match_column' to '$match_value'.
   * If you wish to compare more than one column then the $match_column is an array that 
   * contains the column names as the index of the value to compare. $match_value will
   * not be used in this case.
   * This will default to an 'equal to' comparison, which can be altered by
   * inputing a different '$compare'. If different operator is needed per element
   * of your matching array, you can send an array of operators to $compare indexed
   * by the column names in your previous $match_column comparison array.
   * Send "or" to $and_or for the where array if you don't want to separate
   * with "and". You may change and/or by sending an array in the same method of
   * $compare.
   * The output will be put in ascending alphabetic order according to the '$order_by'
   * received. If you wish to changed how the query is ordered, then $order by is a
   * numeric array. Each entry contains the column name and the list order
   * like so : array("customer_name asc", "date_of_order desc")
   * Finally, sqlSelect returns an array of associative arrays as default. You can
   * also have it return an array of ordered arrays or objects by sending "ordered" and
   * "object" respectively.
   *
   * @author   Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @modified Ryan Cowan <ryan@NOSPAM.tux.appstate.edu>
   * @param    string  $table_name   The DB table selected from.
   * @param    mixed   $match_column The table column name to match the query.
   * @param    string  $match_value  The value to which to compare match_col.
   * @param    mixed   $order_by     The table column name to order the output.
   * @param    mixed   $compare      Comparison operator for matching
   * @param    string  $and_or       Whether to use sql 'AND' or 'OR' with multiple comparisons
   * @param    string  $mode         Controls mode in which rows are returned
   * @param    boolean $test         If TRUE sqlSelect will echo the sql query created
   * @return   array   $array        An array with the queried data.
   * @access   public
   */
  function sqlSelect ($table_name, $match_column=NULL, $match_value=NULL, $order_by=NULL, $compare=NULL, $and_or=NULL, $limit=NULL, $mode=NULL, $test=FALSE) {
    if (is_null($compare)) $compare = "=";
    if (is_null($and_or)) $and_or = "and";
    if (is_null($mode)) $mode = "assoc";
    if (is_null($compare)) $compare = "=";
    $j = 0;
    $sql = "select * from " . $this->tbl_prefix . $table_name;

    if(!empty($match_column)) {
      if (is_array($match_column)) $sql .= $this->makeWhere($match_column, $compare, $and_or);
      else $sql .= " where ".$match_column." ".$compare." ".$this->dbReady($match_value);
    }

    if ($order_by) {
      $sql .= " order by ";
    
      if (is_array($order_by)) {
	foreach($order_by as $method) {
	  if($j == 1) $sql .= ", ";
	  if($method) $sql .= " $method";
	  $j = 1;
	}
      } elseif($order_by) $sql .= $order_by;
    }

    if (isset($limit))
      $sql .= " limit $limit "; 

    if ($test) echo $sql."<br />";

    $array = array();
    $this->setFetchMode($mode);
    if ($sql_result = $this->query($sql)) {
      while ($row = $sql_result->fetchrow()) $array[] = $row;
      $sql_result->free();
      if (count($array)) return $array;
      else return NULL;
    }
    else return FALSE;
  }// END FUNC sqlSelect()


  /**
   * Performs the SQL update command on the database.
   *
   * Updates a table
   *
   * match_column, match_value, compare, and and_or work identically
   * as sqlSelect. See above.
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  array   $db_array     Associative array of row to be updated.
   * @param  string  $table_name   Name of table to be updated.
   * @param  mixed   $match_column Which column(s) to update
   * @param  string  $match_value  What to value to match to the column
   * @param  mixed   $compare      Operator used for comparison
   * @param  mixed   $and_or       Logic of comparison
   * @return boolean TRUE on success, FALSE on failure
   * @access public
   */
  function sqlUpdate($db_array, $table_name, $match_column=NULL, $match_value=NULL, $compare="=", $and_or="and") {
    $loop = 0;
    if (is_array($db_array)) {
      $sql = "update ".$this->tbl_prefix.$table_name." set ";
      
      foreach ($db_array as $key=>$value) {
	if($loop) $sql .= ", ";
	$sql .= "$key=".$this->dbReady($value);
	$loop = 1;
      }
      
      if(!empty($match_column)) {
	if (is_array($match_column)) $sql .= $this->makeWhere($match_column, $compare, $and_or);
	else {
	  $sql .= " where ".$match_column." ".$compare." ".$this->dbReady($match_value);
	  if ($match_column && $match_value === NULL) exit("Error: sqlUpdate() statement was incomplete:<br />$sql");
	}
      }

      if ($this->query($sql)) return TRUE;
      else return FALSE;
    } else exit("Error: sqlUpdate() did not receive an array");
  }// END FUNC sqlUpdate()

  function sqlReplace($db_array, $table_name) {
    $loop = 0;
    if (is_array($db_array)) {
      $sql = "replace into ".$this->tbl_prefix.$table_name;
	  
	  $keys = ''; $vals = '';
      
      foreach ($db_array as $key=>$value) {
		  if($loop) {
			  $keys .= ", ";
			  $vals .= ", ";
		  }
		  $keys .= $key;
		  $vals .= $this->dbReady($value);
		  $loop = 1;
      }
	  $sql .= "($keys) VALUES ($vals)";

      if ($this->query($sql)) return TRUE;
      else return FALSE;
    } else exit("Error: sqlReplace() did not receive an array");
  }// END FUNC sqlUpdate()

  /**
   * Performs the SQL lock command on the database.
   *
   * Locks a table in the SQL database.  The default state of the locked table
   * is WRITE but the state can be set to READ using the $state variable.
   * If an array is used, the $state is ignored. Instead the key of the array
   * is used as the table name and its element is used as the state.
   * Example: $array = array("whatever"=>"WRITE", "whoever"=>"READ", "whenever"=>"WRITE");
   * Sends SQL command: LOCK TABLES whatever WRITE, whoever READ, whenever WRITE
   *
   * @author Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @modified Steven Levin <steven@NOSPAM.tux.appstate.edu>
   * @param  string $table_name Name of the table to be locked.
   * @param  string $state      State to lock table into (i.e: WRITE, READ).
   * @access public
   */
  function sqlLock($table_name, $state="WRITE") {
    $i= NULL;
    $locked = NULL;
    if (is_array($table_name)) {
      foreach ($table_name as $tables=>$sub_state) {
	if($i) $locked .= ", ";
	$locked .= $this->tbl_prefix . $tables . " " . $sub_state;
	$i = 1;
      }
      $state = NULL;
      $sql = "LOCK TABLES " . $locked;
    } elseif(is_string($table_name)) {
      $sql = "LOCK TABLES " . $this->tbl_prefix . $table_name . " " . $state;
    }

    $this->query($sql);
  }// END FUNC sqlLock()


  /**
   * Unlocks all tables in the SQL database.
   *
   * @author Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @access public
   */
  function sqlUnlock() {
    $sql = "UNLOCK TABLES";
    $this->query($sql);
  }// END FUNC sqlUnlock()


  /**
   * Performs the SQL delete command on the database.
   *
   * Deletes a row or multiple rows rows from the SQL database.
   * Matching functionality is identical to sqlSelect.
   * If you wish to delete everything from the table simply send the
   * table_name alone.
   * 
   * @author   Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @modified Ryan Cowan <ryan@NOSPAM.tux.appstate.edu>
   * @param    string  $table_name   Name of the table you want to delete from.
   * @param    string  $match_column Column to use in the WHERE comparison.
   * @param    mixed   $match_value  Value you are using in the comparison (e.g: '5', NULL, etc.).
   * @param    mixed   $compare      Operator used for comparison (<, >, =, !=, etc.).
   * @param    mixed   $and_or       Logic of comparison
   * @return   boolean TRUE if successful, FALSE if failed
   * @access   public
   */
  function sqlDelete($table_name, $match_column=NULL, $match_value=NULL, $compare="=", $and_or="and") {
    $sql = "delete from ".$this->tbl_prefix."$table_name";

    if(!empty($match_column)) {
      if (is_array($match_column)) $sql .= $this->makeWhere($match_column, $compare, $and_or);
      else $sql .= " where ".$match_column." ".$compare." ".$this->dbReady($match_value);
    }

    if ($this->query($sql)) return TRUE;
    else return FALSE;
  }// END FUNC sqlDelete()


  /**
   * Returns the maximum value of the specified column in the specified table.
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string $table        Name of the table to select the maximum value from.
   * @param  string $field        Name of the column to select the maximum value from.
   * @param  mixed  $match_column Which column(s) to update
   * @param  string $match_value  What to value to match to the column
   * @param  mixed  $compare      Operator used for comparison
   * @param  mixed  $and_or       Logic of comparison
   * @return string $value        The maximum value from the specified column in the specified table.
   * @access public
   */
  function sqlMaxValue ($table, $field, $match_column=NULL, $match_value=NULL, $compare="=", $and_or="and") {
    $sql = "select max($field) from " . $this->tbl_prefix . $table;

    if (empty($compare)) $compare = "=";

    if(!empty($match_column)) {
      if (is_array($match_column)) $sql .= $this->makeWhere($match_column, $compare, $and_or);
      else $sql .= " where ".$match_column." ".$compare." ".$this->dbReady($match_value);
    }

    if ($sql_result = $this->quickFetch($sql)){
      foreach ($sql_result as $value);
      return $value;
    } else return NULL;
  }// END FUNC sqlMaxValue()


  /**
   * Determines whether or not a table exists in the current database. Returns TRUE if table exists, FALSE otherwise
   *
   * @author Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @param  string  $tableName Name of the table to check for
   * @param  boolean $addPrefix Whether or not to add the table prefix
   * @return boolean TRUE if table exists, FALSE if it does not exist
   * @access public
   */
  function sqlTableExists($tableName, $addPrefix=FALSE) {
    if (!($result = $this->listTables()))
      return FALSE;

    if ($addPrefix && $this->tbl_prefix)
      return in_array($this->tbl_prefix . $tableName, $result);
    else
      return in_array($tableName, $result);
  }// END FUNC sqlTableExists()


  /**
   * Determines whether or not a table exists in the current database. Returns TRUE if table exists, FALSE otherwise
   *
   * @author Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @param  string  $table_name  Name of the table to check in
   * @param  string  $column_name Name of the column to check for
   * @return boolean TRUE if column exists, FALSE if it does not exist
   * @access public
   */
  function sqlColumnExists($table_name, $column_name) {
    $result = $this->query("DESCRIBE " . $this->tbl_prefix . $table_name);
    while ($row = $result->fetchrow()) $describe_table[] = $row["Field"];

    return in_array($column_name, $describe_table);
  }// END FUNC sqlColumnExists()

  function sqlCreateIndex($table_name, $columns){
    if (!is_array($columns)){
      echo "Unable to create index on table $table_name.";
      return FALSE;
    }

    if (!$this->sqlTableExists($this->tbl_prefix . $table_name)){
      echo "Table $table_name does not exist.";
      return FALSE;
    }

    $sql = "ALTER TABLE " . $this->tbl_prefix . $table_name . " ADD INDEX (" . implode(", ", $columns) . ")";
    if ($this->query($sql)) return TRUE;
    else return FALSE;

  }


  /**
   * Creates a table in the database.
   *
   * @author                      Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @modified                    Matthew McNaney<matt@NOSPAM.tux.appstate.edu>
   * @param  string  table_name   Name of the table to create
   * @param  array   columns      Array of columns to be added to this table (key=column name, value=column type/extra stuff
   *                              where extra stuff = "NOT NULL", "PRIMARY KEY", ...)
   * @param  boolean ignoreIndex  If TRUE, create table will use the value of the array ONLY and ignore the index.
   * @return boolean              TRUE on success and FALSE on failure.
   * @access public
   */
  function sqlCreateTable($table_name, $columns, $ignoreIndex=FALSE) {
    $flag = 0;
    if(is_array($columns)) {
      if($this->sqlTableExists($this->tbl_prefix . $table_name)) exit("ERROR! sqlCreateTable() : Table $table_name already exists! $result");

      $sql = "CREATE TABLE " . $this->tbl_prefix . $table_name . " (";
      foreach($columns as $column_name=>$column_type) {
	if($flag == 1) $sql .= ", ";
	if ($ignoreIndex)
	  $sql .= $column_type;
	else
	  $sql .= $column_name . " " . $column_type;
	$flag = 1;
      }
      $sql .= ")";
      if ($this->query($sql)) return TRUE;
      else return FALSE;
    } else exit("ERROR! sqlCreateTable() : Did not receive an array!");
  }// END FUNC sqlCreateTable()


  /**
   * Drops a table from the database. 
   *
   * Removes the sequencial table as well.
   *
   * @author   Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @modified Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param    string  $table_name Name of the table to drop.
   * @return   boolean TRUE on success FALSE on failure
   * @access   public
   */
  function sqlDropTable($table_name) {
    $sql = "DROP TABLE " . $this->tbl_prefix . $table_name;
    if ($result = $this->query($sql)){
      $this->db->dropSequence($this->tbl_prefix . $table_name);
    }

    return $result;
  }// END FUNC sqlDropTable()


  /**
   * Adds a column to the specified table.
   *
   * @author                        Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @modified                      Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param     string $table_name  Name of the table to add the column to
   * @param     array  $columns     Associative array of columns to be added (key=column name, value=column type/extra
   *                                stuff where extra stuff = "NOT NULL", "DEFAULT '0'", ...)
   * @return    boolean             Result of query
   * @access public
   */
  function sqlAddColumn($table_name, $columns) {
    if(is_array($columns)) {
      $flag = 0;
      $sql = "ALTER TABLE " . $this->tbl_prefix . $table_name . " ADD (";
      foreach($columns as $column_name=>$column_type) {
	if($this->sqlColumnExists($table_name, $column_name)) {
	  reset($columns);

	  foreach($columns as $column_name_2=>$column_type_2) {
	    if($column_name != $column_name_2 && $this->sqlColumnExists($table_name, $column_name_2))
	      $this->sqlDropColumn($table_name, $column_name_2);
	  }

	  exit("ERROR! sqlAddColumn() : Column $column_name already exists!");
	}

	if($flag) $sql .= ", ";
	$sql .= $column_name . " " . $column_type;
	$flag = 1;
      }
      $sql .= ")";
      return $this->query($sql);
    } else exit("ERROR! sqlAddColumn() : Did not receive an array!");
  }// END FUNC sqlAddColumn()

  function sqlModifyColumn($tableName, $column, $settings){
    if (!$this->sqlTableExists($tableName, TRUE))
      exit("Error in sqlModifyColumn : Table '$tableName' does not exist.");

    $sql = "ALTER TABLE " . $this->tbl_prefix . $tableName . " MODIFY $column $settings";
    return $this->query($sql);
  }

  /**
   * Drops a table from the database.
   *
   * @author Adam Morton <adam@NOSPAM.tux.appstate.edu>
   * @param  string $table_name Name of the table to drop columns from
   * @param  array  $columns    Array of column names to be dropped or a string of a single column to be dropped
   * @access public
   */
  function sqlDropColumn($table_name, $columns) {
    if(is_array($columns)) {
      foreach($columns as $column_name) {
	if(!$this->sqlColumnExists($table_name, $column_name))
	  exit("ERROR! sqlDropColumn : Column $column_name does not exist! Some columns may have already been dropped!");

	$sql = "ALTER TABLE " . $this->tbl_prefix . $table_name . " DROP " . $column_name;
	$this->query($sql);
      }
    } else if(is_string($columns)) {
      if(!$this->sqlColumnExists($table_name, $columns))
	exit("ERROR! sqlDropColumn() : Column $columns does not exist!");

      $sql = "ALTER TABLE " . $this->tbl_prefix . $table_name . " DROP " . $columns;
      return $this->query($sql);
    } else exit("ERROR! sqlDropColumn() : Did not receive an array nor a string for columns!");
  }// END FUNC sqlDropColumn()


  
  function sqlImport($filename, $write = TRUE, $suppress_error=FALSE){
    $count = $log = 0;
    if (file_exists($filename))	{

      $file = PHPWS_File::readFile($filename, 1);
      $sql_array = PHPWS_Text::sentence($file);

      foreach ($sql_array as $key=>$command){
	if (preg_match("/^(create\s|insert\s|update\s|delete\s|alter\s|drop\s)/i", trim($command))){
	  if ($log){
	    $error_start = $key - 5;

	    if ($error_start < 0)
	      $error_start = 0;

	    $error_end = $key + 5;

	    if ($error_end > count($sql_array))
	      $error_end = count($sql_array);
	    $error = "<table cellpadding=\"5\" cellspacing=\"0\">";
	    for ($i = $error_start; $i <= $error_end; $i++)
	      $error .= "<tr><td align=\"right\" style=\"border-right: black, 1pt, solid\">$i&nbsp;&nbsp; </td><td>&nbsp;&nbsp;". $sql_array[$i]."</td></tr>";
   	    $error .= "</table>";
	    exit("<b>SQL Import error</b>: incorrect formating<br /><b>Line:</b> $key<br /> ".$error);
	  }

	  $count++;
	  $log = 1;
	  $section[$count]["start"] = $key;
	}

	if ($log && preg_match("/drop table\s*\w*;+/i", trim($command))) {
	  $log = 0;
	  $section[$count]["end"] = $key;
	} elseif ($log && preg_match("/alter table/i", trim($command))) {
	  $log = 0;
	  $section[$count]["end"] = $key;
	} elseif ($log && preg_match("/\);$/", trim($command))){
	  $log = 0;
	  $section[$count]["end"] = $key;
	} elseif ($log && preg_match("/\)\s*TYPE=MyISAM;$/", trim($command))){
	  $log = 0;
	  $section[$count]["end"] = $key;
	}
      }

      if (!count($section)){
	echo "<b>WARNING:</b> $filename did not contain SQL queries.<br />";
	return;
      }
      $count = 0;

      foreach ($section as $query){
	$queryString = NULL;
	for ($i = $query["start"]; $i <= $query["end"]; $i++)
	  $queryString .= $sql_array[$i];

	if($sqlQuery = $this->extractSQLData($queryString, $write)){
	  if (!$write)
	    $queryList[] = $sqlQuery;
	  else {
	    if (isset($sqlQuery["command"]))
	      $command = $sqlQuery["command"];

	    if (isset($sqlQuery["table"]))
	      $table   = $sqlQuery["table"];

	    if (isset($sqlQuery["values"]))
	      $values  = $sqlQuery["values"];
	    
	    if (isset($sqlQuery["compare"]))
	      $compare = $sqlQuery["compare"];
	    
	    if (isset($sqlQuery["and_or"]))
	      $and_or  = $sqlQuery["and_or"];

	    switch ($command){
	    case "insert":
	      $this->sqlInsert($values, $table);
	    break;
	    
	    case "create table":
	      $this->sqlCreateTable($table, $values, TRUE);
	    break;

	    case "create index":
	      $this->sqlCreateIndex($table, $values);
	      break;

	    case "delete":
	      $this->sqlDelete($table, $values, NULL, $compare, $and_or);
	    break;

	    case "drop":
	      $this->sqlDropTable($table);
	    break;

        case "alter":
            $adds = array();
            $drops = array();
            foreach ($values as $val) {
                $v = explode(" ", trim($val));
                if (preg_match("/add/i", $v[0])) {
                    $adds[$v[1]] = "";
                    for($i = 2 ; $i <= count($v) ; $i++)
                        $adds[$v[1]] .= $v[$i] . " ";
                } elseif (preg_match("/drop/i", $v[0])) {
                    $drops[$v[1]] = "";
                    for($i = 2; $i <= count($v); $i++)
                        $drops[$v[1]] .= $v[$i] . " ";
                } else {
                    echo "That type of alter statement is not supported by sqlImport just yet";
                    exit();
                }
            }

            if(count($adds))
                $this->sqlAddColumn($table,$adds);
            if(count($drops))
                $this->sqlDropColumn($table,$drops);

	        break;

	    default:
	      echo "$command is not supported by sqlImport just yet";
	      exit();
	      break;
	    }
	  }
	}
      }

      if (!$write)
	return $queryList;
      else
	return TRUE;
    }
  }

  
  /**
   * Adds the table prefix to the front of the table name
   *
   * Used by several database functions if their prefix mode
   * is triggered. Can be used anywhere however.
   *
   * @author Matt McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  string $sql_value SQL query statement
   * @return string $sql_value Sql string with added prefix
   * @access public
   */
  function addTablePrefix($sql_value){
    if (isset($this->tbl_prefix)){
      $tableName = PHPWS_Database::extractTableName($sql_value);
    
      return str_replace($tableName, $this->tbl_prefix.$tableName, $sql_value);
    } else return $sql_value;
  }// END FUNC addTablePrefix()


  function extractTableName($sql_value){
    $temp = explode(" ", trim($sql_value));
    PHPWS_Array::dropNulls($temp);
    if (!is_array($temp))
      return NULL;
    foreach ($temp as $whatever)
      $format[] = $whatever;

      switch (trim(strtolower($format[0]))) {
      case "insert":
      if (stristr($format[1], "into"))
	return preg_replace("/\(+.*$/", "", str_replace("`", "", $format[2]));
      else
	return preg_replace("/\(+.*$/", "", str_replace("`", "", $format[1]));
      break;
	
      case "update":
	return preg_replace("/\(+.*$/", "", str_replace("`", "", $format[1]));
      break;
      
      case "select":
      case "show":
	return preg_replace("/\(+.*$/", "", str_replace("`", "", $format[3]));
      break;

      case "drop":
      case "alter":
	return preg_replace("/;/", "", str_replace("`", "", $format[2]));
      break;

      case "create":
	if (strtolower($format[1]) == "table")
	  return preg_replace("/\(+.*$/", "", str_replace("`", "", $format[2]));
	elseif (strtolower($format[1]) == "index")
	  return preg_replace("/\(+.*$/", "", str_replace("`", "", $format[4]));
	break;
	
      default:
	return preg_replace("/\(+.*$/", "", str_replace("`", "", $format[2]));
      break;
      }
  }// END FUNC extractTableName


  function extractSQLData($query, $write=TRUE){
    $query = trim($query);
    $queryList["query"]   = $query;
    $start = 0;
    $sentence = NULL;
    $startQuery = explode(" ", $query);
    
    $queryList["command"] = strtolower($startQuery[0]);
    if (strtolower($queryList["command"]) == "create")
      $queryList["command"] .= " " . strtolower($startQuery[1]);

    $queryList["table"]   = $this->extractTableName($query);
    switch ($queryList["command"]){
    case "insert":
      $keySearch = "[\w\s]*\((.*)\).*values.*";
    $valSearch = ".*values[\w\s]*\((.*)\).*;";

    if(preg_match("/$keySearch/i", $query))
      $keys = explode(",", str_replace("'", "", trim(preg_replace("/$keySearch/i", "\\1", $query)))); 
    elseif ($write)
      $keys = $this->getColumnNames($queryList["table"]);
    else
      exit("Error Database.php : extractSQLData cannot retrieve column names from the query.");

    if ($write && $tableInfo = $this->getAll("show index from " . $this->tbl_prefix . $queryList["table"]))
      if (stristr($tableInfo[0]["Key_name"], "PRIMARY"))
	if (!is_null($indexKey = array_search($tableInfo[0]["Column_name"], $keys)))
	  $keys = PHPWS_Array::yank($keys, $indexKey);

    $query = preg_replace("/null,/i", "'',", $query);

    if (preg_match("/$valSearch/i", $query)){
      $values =  trim(preg_replace("/$valSearch/i", "\\1", $query));
      $phrase = 0;

      $values = str_replace("\\r\\n", "\r\n", $values);
      $values = str_replace("\\n", "\n", $values);

      for ($i=0; $i< strlen($values); $i++){
	$prev = substr($values, $i-1, 1);
	$char = substr($values, $i, 1);
	$nextval = substr($values, $i+1, 1);
	
	if (!$start && ($char == " " || $char == ","))
	  continue;

	if ($start && is_numeric($prev) && $char==","){
	  $finalVal[$phrase] = $sentence;
	  $phrase++;
	  $start = 0;
	  $sentence = NULL;
	  continue;
	}

	if (!$start && is_numeric($char)){
	  $sentence .= $char;
	  $start = 1;
	  continue;
	}

	if (!$start && $char == "'"){
	  $start = 1;
	  continue;
	}
	
	if ($start && (($char == "'" && $prev != "\\"))){
	  $finalVal[$phrase] = $sentence;
	  $phrase++;
	  $start = 0;
	  $sentence = NULL;
	  continue;
	}

	$sentence .= $char;
      }
      if ($start)
	$finalVal[$phrase] = $sentence;

    }
    else
      exit("Error extractSQLData: Unable to retrieve finalVal for insert<br /><b>$query</b>");

    for ($i = 0; $i < count($keys); $i++){
      if (isset($finalVal[$i])){
	$toQuery = trim($finalVal[$i]);
	$queryList["values"][trim($keys[$i])] = $toQuery;
      }
    }
    break;
    
    case "create index":
    case "create table":
      $startline = strpos($query, "(") + 1;
    $endline = strpos($query, ");");
    $columns = explode("," , substr($query, $startline, $endline - $startline));
    $queryList["values"] = $columns;
    break;

    case "alter":
        $startline = strpos($query, $queryList["table"]) + strlen($queryList["table"]) + 1;
        $endline = strpos($query, ";");
        $columns = explode("," , substr($query, $startline, $endline - $startline));
        $queryList["values"] = $columns;
        break;
    }

    return $queryList;
} // END FUNC extractSQLData

  /**
   * Returns a list of tables in the current database
   *
   * Uses the PEAR getlistOf command.
   *
   * @author        Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  array  List of tables or error message
   */
  function listTables(){
    if (method_exists($this->db, "getlistOf")) {
      if (DB::iserror($sql_result = $this->db->getlistOf("tables")))
	exit ("SQL: $sql<br />".DB::errorMessage($sql_result));
      else return $sql_result;
    } else return NULL;
  }

  /**
   * Returns a list of databases
   *
   * Uses the PEAR getlistOf command.
   *
   * @author        Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  array  List of databases or error message
   */
  function listDatabases(){
    if (method_exists($this->db, "getlistOf")) {
      if (DB::iserror($sql_result = $this->db->getlistOf("databases")))
	exit ("SQL: $sql<br />".DB::errorMessage($sql_result));
      else return $sql_result;
    } else return NULL;
  }

  /**
   * Returns an array of column names from a table
   *
   * @author                     Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param    string  tableName Name of the table to search
   * @returns  array             Array of column names
   */
function getColumnNames($tableName){
  if (!$this->sqlTableExists($this->tbl_prefix.$tableName))
    exit("Error in getColumnNames - Unable to locate table name <b>$tableName</b>.");    

  $rows = $this->db->tableInfo($this->tbl_prefix.$tableName);

  foreach ($rows as $tableInfo)
    $columnNames[] = $tableInfo["name"];
  
  return $columnNames;
}// END FUNC: getColumnNames


}