<?php
/**
 * Supplies useful functions for manipulation of arrays and other array-like
 * functions.
 * 
 * @version $Id: Array.php,v 1.34 2005/05/23 12:49:47 darren Exp $
 * @author  Matt McNaney <matt@NOSPAM.tux.appstate.edu>
 * @author  Adam Morton <adam@NOSPAM.tux.appstate.edu>
 * @package Core
 */
class PHPWS_Array {
  /**
   * Uses the array given and swaps the value at index 1 with the value at index 2.
   *
   * @param  array $array  The array to use for the swap
   * @param  mixed $index1 The index of the first value to be swapped.
   * @param  mixed $index2 The index of the second value to be swapped.
   * @access public
   */
  function swap(&$array, $index1, $index2) {
    $temp = $array[$index1];
    $array[$index1] = $array[$index2];
    $array[$index2] = $temp;
  }

  /**
   * Reindexes the supplied array from 0 to number of values - 1.
   *
   * @param  array $array The array to reindex.
   * @access public
   */
  function reindex(&$array) {
    $temp = $array;
    $array = array();
    foreach($temp as $value) {
      $array[] = $value;
    }
  }

  /**
   * Returns the key to the last element in $checkArray
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  array $check_array Array to use in check
   * @return mixed $maxKey      The maximum key in the supplied array
   * @access public
   */
  function maxKey($checkArray){
    if(is_array($checkArray)){
      end($checkArray);
      list($maxKey) = each($checkArray);
      return $maxKey;
    } else {
      echo "PHPWS_Array: ERROR! Function maxKey() did not recieve an array!";
      return FALSE;
    }
  }// END FUNC maxKey()

  /**
   * Repositions an array element one slot up or down its current position.
   *
   * Original Array:
   * Array ( [name] => Tom, [sex] => male, [color] => blue, [age] => 31 ) 
   * 
   * moveElement(Array, "color") -- Move up by key.
   * Array ( [name] => Tom, [color] => blue, [sex] => male, [age] => 31 ) 
   * 
   * moveElement(Array, NULL, "blue") -- Move up by value.
   * Array ( [name] => Tom, [color] => blue, [sex] => male, [age] => 31 ) 
   * 
   * moveElement(Array, "color", NULL, false) -- Move down by key.
   * Array ( [name] => Tom, [sex] => male, [age] => 31, [color] => blue ) 
   * 
   * moveElement(Array, "color", NULL, false, false) -- Move down by key and reindex.
   * Array ( [0] => Tom, [1] => male, [2] => 31, [3] => blue )  
   *
   * @author Eloi George <eloi@NOSPAM.bygeorgeware.com>
   * @param  array  $array       Associative array to shove new cell into
   * @param  string $index       Index of the element to be moved
   * @param  mixed  $element     Value of the element to be moved (not required - use if you don't know what $index is ($index=NULL))
   * @param  bool   $moveup      Direction to move. Defaults to moving up (true).
   * @param  bool   $assoc_array Type of array.  Numeric arrays get re-indexed.
   * @return array  $new array   Finished array
   * @access public
   */
  function moveElement ($array, $index=NULL, $element=NULL, $moveup=true, $assoc_array=true) {
    /* parameter housekeeping */
    if (!is_array($array))
      exit("moveElement received a/an ".gettype($array)." variable, not an array<br />");
    /* If only the value was given, search for the key & delete it */
    if ($index==NULL) {
      $index = array_search($element, $array);
      if ($index === false)
	exit("moveElement received ".gettype($array) . ", a value that doesn't exist in the array.<br />");
    }
    $element = $array[$index];
    
    /* Process the array */
    if ($moveup) 
      $array = array_reverse($array, TRUE);
    foreach ($array as $key=>$value) {
      if ($key == $index)
	$buffer[$key] = $element;
      else {
	$new_array[$key] = $value;
	if (isset($buffer)) {
	  $new_array[$index] = $element; 
	  unset($buffer);
	}
      }
    }
    if ($moveup) 
      $new_array = array_reverse($new_array, TRUE);

    /* Reindex the result */
    if (!$assoc_array && count($array)) {
      foreach ($new_array as $new_val)
	$numerical_array[] = $new_val;
      $new_array = $numerical_array;
    }
    return $new_array;
  }// END FUNC moveElement()

  /**
   * Removes an element from an array then reindexes the array
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param array
   * @param mixed
   * @param mixed
   * @return array
   * @access public
   */
  function yank($array, $key=NULL, $value=NULL){
    if (is_array($array)){
      if (isset($key) && isset($value)){
        if ($array[$key] == $value)
          $new_key = $key;
        else 
          return NULL;
      } elseif (isset($key)){
        if (is_string($key) && !is_numeric($key)){
          unset($array[$key]);
          return $array;
        }
        else
          $new_key = $key;
      } elseif (isset($value))
	  $new_key = array_search($value, $array);
      else
        return NULL;

      unset($array[$new_key]);

      if (count($array)){
        foreach ($array as $new_val)
          $ret_array[] = $new_val;

        return $ret_array;
      } else
        return $array;

    } else 
      exit("Variable sent to yank() was not an array");
  }// END FUNC yank()


  /**
   * Inserts variables into an object based on an associative array. Useful if your
   * object variables mimic your table setup
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  array  $array  The associative array you want to transfer into the object
   * @param  object $object The object to receive the array values
   * @access public
   */
  function arrayToObject($array, &$object){
    if (is_array($array) && is_object($object)){
      foreach ($array as $key=>$value)
        $object->{$key} = $value;
    } else
      exit("arrayToObject failed because of a missing array and/or object");
  }// END FUNC arrayToObject()


  /**
   * Inserts variables into an array based on an object
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  object $object The object to copy variables from
   * @param  array  $array  The array to copy variables to
   * @access public
   */
  function objectToArray($object, &$array){
    if (is_array($array) && is_object($object)){
      $object_info = (get_object_vars($object));
      foreach ($object_info as $key=>$value)
        $array[$key] = $value;
    } else
      exit("arrayToObject failed because of a missing array and/or object");
  }// END FUNC objectToArray()


  /**
   * Unsets array rows where the value is NULL
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  array $array Array to drop nulls from
   * @access public
   */
  function dropNulls(&$array){
    if (is_array($array)){
      reset ($array);
      foreach ($array as $key=>$value){
        if (is_null($value)){
          unset ($array[$key]);
        }
      }
    } else
      exit("Error in dropNulls - array not received");
  }// END FUNC dropNulls()


  /**
   * Returns the currently received posted variables
   *
   * @return string An HTML table containing the current POST variables
   * @access public
   */
  function testPost(){
    return PHPWS_Debug::testArray($_POST);
  }// END FUNC testPost()
  
  /**
   * Returns the variables contained in your browser cookie
   *
   * @return string An HTML table containing the current COOKIE variables
   * @access public
   */
  function testCookie(){
    return PHPWS_Debug::testArray($_COOKIE);
  }// END FUNC testCookie()


  /**
   * Displays all current GET and POST variables
   *
   * @return string A table containing the current GET and POST variables
   * @access public
   */
  function testRequest(){
    return PHPWS_Debug::testArray($_REQUEST);
  }// END FUNC testRequest()


  /**
   * Displays the current GLOBALS without descending into the global variable itself
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  boolean $skip_predefined If TRUE, won't show predefined variables
   * @return string  An HTML table containing the current GLOBAL variables
   * @access public
   */
  function testGlobals($skip_predefined = FALSE){
    if (!$skip_predefined)
      $skip = "|^HTTP|^_";
    foreach ($GLOBALS as $key=>$value){
      if (!preg_match("/GLOBALS".$skip."/" , $key))
	$array[$key] = $value;

    }
    return PHPWS_Debug::testArray($array);
  }// END FUNC testGlobals()

  /**
   * Outputs variables set in an object
   *
   * @param  object $obj_var Object to test and display
   * @return string An HTML table of object variables
   * @access public
   */
  function testObject($obj_var){
    return PHPWS_Debug::testObject($obj_var);
  }// END FUNC testObject()

  /**
   * Returns a table displaying the contents of an array.
   *
   * @param  array  $array_var The array to be tested.
   * @return string An HTML table containing the contents of array.
   * @access public
   */
  function testArray($array_var){
    return PHPWS_Debug::testArray($array_var);
  }// END FUNC testArray()

  /**
   * paginateDataArray
   * 
   * This function will paginate an array of data. While using this function remember to always pass it the same content array
   * and DO NOT alter array during usage unless you are starting back at zero.
   *
   * @author Steven Levin <steven@NOSPAM.tux.appstate.edu>
   * @param  array    $content        Rows of data to be displayed
   * @param  string   $link_back      Link to where data is being displayed (ie. ./index.php?module=search&SEA_search_op=view_results)
   * @param  integer  $default_limit  Number of items to show per page
   * @param  boolean  $make_sections  Flag controls weather section links show up
   * @param  resource $curr_sec_decor HTML to decorate the current section
   * @param  string   $link_class     Style sheet class to use for navigation links
   * @param  integer  $break_point    Number of sections at which the section display will insert ... to show range
   * @return array 0=>string of rows to be displayed, 1=>navigation links for the paginated data, 2=>current section information
   * @access public
   */
  function paginateDataArray($content, $link_back, $default_limit=10, $make_sections=FALSE, $curr_sec_decor=NULL, $link_class=NULL, $break_point=20, $return_array=FALSE){
    
    if (is_null($curr_sec_decor))
      $curr_sec_decor = array("<b>[ ", " ]</b>");

    if(isset($_REQUEST['PDA_limit'])){
      $limit = $_REQUEST['PDA_limit'];
    } else {
      $limit = $default_limit;
    }
    
    if(isset($_REQUEST['PDA_start'])){
      $start = $_REQUEST['PDA_start'];
    } else {
      $start = 0;
    }
    
    if(isset($_REQUEST['PDA_section'])){
      $current_section = $_REQUEST['PDA_section'];
    } else {
      $current_section = 1;
    }
  
    if(is_array($content)){
      $numrows = count($content);
      $sections = ceil($numrows / $limit);
      $content_keys = array_keys($content);
      $string_of_items = "";
      $array_of_items = array();
      $nav_links = "";
      $item_count = 0;
      $pad = 3;
      
      if (isset($link_class)) {
	  $link_class = " class=\"$link_class\"";
      }

      reset($content_keys);
      for($x = 0; $x < $start; $x++){
	next($content_keys);
      }
      while((list($content_key, $content_value) = each($content_keys)) && (($item_count < $limit) && (($start + $item_count) < $numrows ))){
	if($return_array) {
	  $array_of_items[] = $content[$content_keys[$content_key]];
	} else {
	  $string_of_items .= $content[$content_keys[$content_key]] . "\n";
	}

	$item_count++;
      }

      if($start == 0){
	$nav_links = "&#60;&#60;\n";
      } else {
	$nav_links = "<a href=\"" . $link_back . "&amp;PDA_limit=" . $limit . "&#38;PDA_start=" . ($start - $limit) . "&#38;PDA_section=" . ($current_section - 1). "\"" . $link_class . "\" title=\"&#60;&#60;\">&#60;&#60;</a>\n";
      }
      
      if($make_sections && ($sections <= $break_point)){
	for($x = 1; $x <= $sections; $x++){
	  if($x == $current_section){
	    $nav_links .= "&#160;" . $curr_sec_decor[0] . $x . $curr_sec_decor[1] . "&#160;\n";
	  } else {
	    $nav_links .= "&#160;<a href=\"" . $link_back . "&amp;PDA_limit=" . $limit . "&#38;PDA_start=" . ($limit * ($x - 1)) . "&#38;PDA_section=" . $x . "\"" . $link_class . "\" title=\"" . $x . "\">" . $x . "</a>&#160;\n";
	  }
	}
      } else if($make_sections && ($sections > $break_point)){
	for($x = 1; $x <= $sections; $x++){
	  if($x == $current_section){
	    $nav_links .= "&#160;" . $curr_sec_decor[0] . $x . $curr_sec_decor[1] . "&#160;\n";
	  } else if($x == 1 || $x == 2){
	    $nav_links .= "&#160;<a href=\"" . $link_back . "&amp;PDA_limit=" . $limit . "&#38;PDA_start=" . ($limit * ($x - 1)) . "&#38;PDA_section=" . $x . "\"" . $link_class . "\" title=\"" . $x . "\">" . $x . "</a>&#160;\n";
	  } else if(($x == $sections) || ($x == ($sections - 1))){
	    $nav_links .= "&#160;<a href=\"" . $link_back . "&amp;PDA_limit=" . $limit . "&#38;PDA_start=" . ($limit * ($x - 1)) . "&#38;PDA_section=" . $x . "\"" . $link_class . "\" title=\"" . $x . "\">" . $x . "</a>&#160;\n";
	  } else if(($current_section == ($x - $pad)) || ($current_section == ($x + $pad))){
	    $nav_links .= "&#160;<b>. . .</b>&#160;";
	  } else if(($current_section > ($x - $pad)) && ($current_section < ($x + $pad))){
	    $nav_links .= "&#160;<a href=\"" . $link_back . "&amp;PDA_limit=" . $limit . "&#38;PDA_start=" . ($limit * ($x - 1)) . "&#38;PDA_section=" . $x . "\"" . $link_class . "\" title=\"" . $x . "\">" . $x . "</a>&#160;\n";
	  }
	}
      } else {
	$nav_links .= "&#160;&#160;\n";
      }

      if(($start + $limit) >= $numrows){
	$nav_links .= "&#62;&#62;\n";
	$section_info = ($start + 1) . " - " . ($start + $item_count) . ' ' . $_SESSION['translate']->it('of') . ' ' . $numrows . "\n";
      } else {
	$nav_links .= "<a href=\"" . $link_back . "&amp;PDA_limit=" . $limit . "&#38;PDA_start=" . ($start + $limit) . "&#38;PDA_section=" . ($current_section + 1) . "\"" . $link_class . "\" title=\"&#62;&#62;\">&#62;&#62;</a>\n";
	$section_info = ($start + 1) . " - " . ($start + $limit) . ' ' . $_SESSION['translate']->it('of') . ' ' .$numrows . "\n";
      }
      
    } else {
      exit("Argument 1 to function paginateDataArray not an array.");
    }
    

    if($return_array) {
      return array(0=>$array_of_items, 1=>$nav_links, 2=>$section_info);
    } else {
      return array("0"=>"$string_of_items", "1"=>"$nav_links", "2"=>"$section_info");
    }
  }// END FUNC paginateDataArray()

  /**
   * Copy of php's ksort with a descending mode
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  array  &$array The array to sort
   * @param  string $mode   The direction to sort
   * @access public
   */
  function ksort(&$array, $mode="SORT_ASC"){
    if ($mode == "SORT_DESC")
      uksort($array, "cmp");
    else
      ksort($array);
  }// END FUNC ksort()

  /**
   * Creates an array of numbers from start to final in
   * increments of the interval variable
   *
   * Note that the function will start on the start variable. In other words
   * If you start on 1 and interval by 4, your array will say 1, 5, 9, etc.
   * If the final number surpasses 'final' it will not be included.
   *
   * @author Matthew McNaney <matt@NOSPAM.tux.appstate.edu>
   * @param  integer $final    Final number of the count
   * @param  integer $start    Number to begin counting on (not from)
   * @param  integer $interval Number to increment the count
   * @return array   Array of intervaled numbers
   * @access public
   */
  function interval($final, $start=0, $interval=1){
    $count = 0;
    if ($interval && $final > $start){
      for ($i=$start; $i <= $final; $i = $i+$interval){
	$count++;
	$ret_array[] = $i;
	if ($count > 10000)
	  exit("Interval is too large");
      }
      return $ret_array;
    } else
      return NULL;
  }// END FUNC interval()

}// END CLASS PHPWS_Array
?>