<?php

/**
 * Calendar Class
 *  See docs/developers/calendar.txt for full documentation
 *
 * Class was orgnially part of the Career Gear package but has been modified
 * for use with any phpwebsite module.
 *
 * @author Steven Levin
 * @modified Darren Greene
 *
 */
require_once(PHPWS_SOURCE_DIR . "core/EZform.php");
define('FIRST_DAY_OF_WEEK', 0); // 0 Sunday, 1 Monday, 2 Tuesday, etc

/**
 * Core calendar class.
 */
class PHPWS_Cal {
  var $_ts                = null;
  var $_tsSmall           = null;
  var $_fullMonthLink     = null;
  var $_module            = null;
  var $_templateDir       = null;
  var $_callerObj         = null;
  var $_linkBack          = null;
  var $_jsOnClickDaysFunc = null;
  var $_itemOp            = null;
  var $_blankOp           = null;
  var $_fullMonthLink     = null;
  var $_jsPopUp           = null;
  var $_items             = null;
  var $_month             = null;
  var $_useMouseOvers     = TRUE;

  function PHPWS_Cal($module=NULL, $caller=NULL) {
    if(isset($caller))
      $this->_callerObj = $caller;

    if(isset($module))
      $this->_module    = $module;

    $this->_ts        = time();
    $this->_tsSmall   = time();
  }

  function turnOffMouseOvers() {
    $this->_useMouseOvers = FALSE;
  }

  function _createDayHeaders($abbr=false) {
    if(!$abbr) {
      $days = array($_SESSION["translate"]->it("Sunday"),
		    $_SESSION["translate"]->it("Monday"),
		    $_SESSION["translate"]->it("Tuesday"),
		    $_SESSION["translate"]->it("Wednesday"),
		    $_SESSION["translate"]->it("Thursday"),
		    $_SESSION["translate"]->it("Friday"),
		    $_SESSION["translate"]->it("Saturday"));
    } else {
      $days = array($_SESSION["translate"]->it("S"),
		    $_SESSION["translate"]->it("M"),
		    $_SESSION["translate"]->it("T"),
		    $_SESSION["translate"]->it("W"),
		    $_SESSION["translate"]->it("T"),
		    $_SESSION["translate"]->it("F"),
		    $_SESSION["translate"]->it("S"));

    }

    $index = FIRST_DAY_OF_WEEK;
    $daysPrinted = 0;
    $tags = array();

    while($daysPrinted < 7) {
      $daysPrinted++;
      
      $tags["DAY_".$daysPrinted] = $days[$index % 7];
      $index++;
    }
    
    return $tags;
  }

  function getExpandedMonthView() {
    require_once('Calendar/Month/Weekdays.php');
    require_once('Calendar/Day.php');
    
    if(isset($_REQUEST["ts"])) {
      $this->_ts = $_REQUEST["ts"];
    } else
      $this->_ts = time();

    if(isset($this->_itemOp)) 
      $activeOp = $this->_itemOp;
    else
      $activeOp = $this->_linkBack;

    if(isset($this->_blankOp))
      $blankOp  = $this->_blankOp;
    else
      $blankOp  = $this->_linkBack;

    $Month =& new Calendar_Month_Weekdays(date("Y", $this->_ts), date("m", $this->_ts), FIRST_DAY_OF_WEEK);

    $start = $Month->thisMonth(true);
    $end = $Month->nextMonth(true);

    $result = $this->getFullMonthActiveDays(date("m", $this->_ts), date('Y', $this->_ts));

    $selection = array();
    if(is_array($result) && (sizeof($result) > 0)) {
      foreach($result as $row) {
	$start = $row['start'];
	do {
	  $Day =& new Calendar_Day(date('Y', $row['start']), date('m', $row['start']), date('d', $row['start']));

	  $ts = $Day->getTimeStamp();

	  $label  = $row['label'];
	  $id     = $row['id'];

	  if(isset($row['idPrefix']))
	    $prefix = $row['idPrefix'];
	  else
	    $prefix = NULL;

	  if(array_key_exists($ts, $selection)) {
	    // key exists so just add the next label
	    $selection[$ts]->add($label, $id, $prefix);
	  } else {
	    $Event =& new Event($Day);
	    $Event->add($label, $id, $prefix);
	    $selection[$ts] = $Event;
	  }

	  $row['start'] = $ts + (3600*24);
	} while($row['start'] <= $row['end']);
      }
    }

    $Month->build($selection);
    
    $tags    = array();
    $content = array();

    $tags = array();
    $tags = $this->getQuickSelect(FALSE);

    $tags['TODAY'] = date("l F d, Y", time());

    $tags['BACK_LINK'] = str_replace('&amp;', '&', $this->_linkBack);
    $tags['BACK_LINK'] .= '&ts=';
    $tags['LINK_BACK'] = $this->_linkBack;
    $tags['PREV_MONTH'] = $Month->prevMonth('timestamp');
    $tags['NEXT_MONTH'] = $Month->nextMonth('timestamp');

    $tags['DATE'] = date("F Y", $Month->getTimeStamp());    

    while($Day =& $Month->fetch()) {
      if($Day->isFirst()) {
	$content[] = "<tr>";
      }
    
      if($Day->isEmpty()) {
	$content[] =  "<td class=\"no-shade\">&#160;</td>";
      } else {
	$timestamp = $Day->getTimeStamp();
	$date = date("l F d, Y", $timestamp);
	
	$inactiveShade = "no-shade";
	if($this->today($Day, $timestamp))
	  $inactiveShade = "today";

	$jsArr = array("onclick=\"viewEvent('".$this->_linkBack . "&amp;" . $blankOp . "','{$timestamp}');\"");

	if($this->_useMouseOvers) {
	  $jsArr[] = "onmouseover=\"this.className='shade'; window.status='View {$date}';\"";
	  $jsArr[] = "onmouseout=\"this.className='$inactiveShade'; window.status='';\"";
	  $jsArr[] = "onmousedown=\"this.className='click';\"";
	}

	$js = implode(" ", $jsArr);

	$content[] = "<td width=\"14%\" height=\"120\" valign=\"top\" ";

	if($this->today($Day, $timestamp))
	  $content[] = " class=\"$inactiveShade\" ";
	else 
	  $content[] = " class=\"$inactiveShade\" ";

	$content[] = " {$js}>";

	if($this->_useMouseOvers) {
	  $js = implode(" ", array("onmouseover=\"window.status='View {$date}';\"",
				   "onmouseout=\"window.status='';\""));
	}

	$day = $Day->thisDay();


	$link      = implode("", array("<a href=\"".$this->_linkBack . "&amp;" . $blankOp . "&amp;fullMonthCal=1&amp;ts={$timestamp}\" {$js}>",
				       $day,
				       "</a>"));

	$content[] = "<div align=\"right\">{$link}</div><br />";

	if($Day->isSelected()) {
	  $events = $Day->getEvents();
	  $list = array();

	  foreach($events as $value) {
	    if(isset($value["idPrefix"]))
	      $prefix = $value["idPrefix"] . "_";

	    $list[] = implode("", array("<li>",
					"<a href=\"".$this->_linkBack . "&amp;" . $activeOp . "&amp;fullMonthCal=1&amp;ts={$timestamp}&amp;".$prefix."id=".$value["id"]."\" {$js}>",
					$value["label"],
					"</a>",
					"</li>"));;
	  }
	  
	  $content[] = implode("\n", array("<ul>",
					   implode("\n", $list),
					   "</ul>"));
	  
	}

	$content[] = "</td>";
      }
      
      if($Day->isLast()) {
	$content[] = "</tr>";
      }
    }

    $tags = array_merge($tags, $this->_createDayHeaders(FALSE));
    $tags['ROWS'] = implode("\n", $content);
    $tags['LBL_TODAY'] = $_SESSION["translate"]->it("Today is");

    return $this->_processTemplate($tags, "month.tpl");
  }

  function today($Day, $timestamp) {
    if($Day->thisDay() == date('d') && date('m',$timestamp) == date('m') &&
       date('Y', $timestamp) == date('Y'))
      return true;
    else
      return false;
  }
  
  /**
   * Used the items retrieved from a module and builds the month
   * and addes the events on the proper dates.
   *
   */
  function buildMonth($data=NULL) {
    $this->_month =& new Calendar_Month_Weekdays(date("Y", $this->_tsSmall), date("n", $this->_tsSmall), FIRST_DAY_OF_WEEK);

    $selection = array();

    if(!isset($this->_jsOnClickDaysFunc)) {
      if(is_array($data) && (sizeof($data) > 0)) {
	foreach($data as $row) {
	  $start = $row['start'];
	  do {
	    $Day =& new Calendar_Day(date('Y', $row['start']), date('m', $row['start']), date('d', $row['start']));
	    
	    $ts = $Day->getTimeStamp();
	    
	    $label  = $row['label'];
	    $id     = $row['id'];
	    if(isset($row['idPrefix']))
	      $prefix = $row['idPrefix'];
	    else
	      $prefix = NULL;

	    if(array_key_exists($ts, $selection)) {
	      // key exists so just add the next label
	      $selection[$ts]->add($label, $id, $prefix);
	    } else {
	      $Event =& new Event($Day);
	      $Event->add($label, $id, $prefix);
	      $selection[$ts] = $Event;
	    }
	    
	    $row['start'] = $ts + (3600*24);
	  } while($row['start'] <= $row['end']);
	}
      }
    }

    $this->_month->build($selection);

  }

  function getYears($offset=10, $miniView=TRUE) {
    $ts = null;
    if($miniView)
      $ts = $this->_tsSmall;
    else
      $ts = $this->_ts;

    $selMon  = date('m', $ts);
    $selYear = date('Y', $ts);
    $selDay  = 1;    

    $count = $offset;
    while($count > 0) {
      $newYear = $selYear - $count;
      $yearsArr[mktime(0,0,0,$selMon,  $selDay, $newYear)] = $newYear;
      $count--;
    }

    $yearsArr[mktime(0,0,0,$selMon, $selDay, $selYear)] = $selYear;

    $count = 0;
    while($count < $offset) {
      $count++;
      $newYear = $selYear + $count;
      $yearsArr[mktime(0,0,0,$selMon, $selDay, $newYear)] = $newYear;
    }    

    return $yearsArr;
  }

  function getMonths() {
    $month_list   = array();
    if(isset($_SESSION["translate"]) && is_object($_SESSION["translate"])) {
      $month_list = array($_SESSION["translate"]->it("January"),
			  $_SESSION["translate"]->it("Febuary"),
			  $_SESSION["translate"]->it("March"),
			  $_SESSION["translate"]->it("April"),
			  $_SESSION["translate"]->it("May"),
			  $_SESSION["translate"]->it("June"),
			  $_SESSION["translate"]->it("July"),
			  $_SESSION["translate"]->it("August"),
			  $_SESSION["translate"]->it("September"),
			  $_SESSION["translate"]->it("October"),
			  $_SESSION["translate"]->it("November"),
			  $_SESSION["translate"]->it("December"));
    } else {
      $month_list = array("January",
			  "Febuary",
			  "March",
			  "April",
			  "May",
			  "June",
			  "July",
			  "August",
			  "September",
			  "October",
			  "November",
			  "December");
    }
    return $month_list;
  }

  function getMonthSelect($miniView=FALSE) {
    $ts = null;
    if($miniView)
      $ts = $this->_tsSmall;
    else
      $ts = $this->_ts;

    $selMon  = date('m', $ts);
    $selYear = date('Y', $ts);
    $selDay  = 1;
    $months = $this->getMonths();

    $month_list = array(mktime(0,0,0,1,  $selDay, $selYear) => $months[0],
			mktime(0,0,0,2,  $selDay, $selYear) => $months[1],
			mktime(0,0,0,3,  $selDay, $selYear) => $months[2],
			mktime(0,0,0,4,  $selDay, $selYear) => $months[3],
			mktime(0,0,0,5,  $selDay, $selYear) => $months[4],
			mktime(0,0,0,6,  $selDay, $selYear) => $months[5],
			mktime(0,0,0,7,  $selDay, $selYear) => $months[6],
			mktime(0,0,0,8,  $selDay, $selYear) => $months[7],
			mktime(0,0,0,9,  $selDay, $selYear) => $months[8],
			mktime(0,0,0,10, $selDay, $selYear) => $months[9],
			mktime(0,0,0,11, $selDay, $selYear) => $months[10],
			mktime(0,0,0,12, $selDay, $selYear) => $months[11]);

    return $month_list;
  }

  function getQuickSelect($miniView=TRUE) {
    $ts = null;
    if($miniView) {
      $ts = $this->_tsSmall;
    } else {
      $ts = $this->_ts;
    }

    $form = new EZform("quick_select");
    $form->add("FLD_quick_select_month", "select", $this->getMonthSelect($miniView));
    $form->setExtra("FLD_quick_select_month", 'onchange=\'quickSelectMonth();\'');
    $form->setMatch("FLD_quick_select_month", mktime(0,0,0,date('m', $ts), 1, date('Y', $ts)));
    $form->setId("FLD_quick_select_month", "FLD_quick_select_month");

    $form->add("FLD_quick_select_years", "select", $this->getYears(10,$miniView));
    $form->setMatch("FLD_quick_select_years", mktime(0,0,0,date('m', $ts), 1, date('Y', $ts)));
    $form->setExtra("FLD_quick_select_years", 'onchange=\'quickSelectYear();\'');
    $form->setId("FLD_quick_select_years", "FLD_quick_select_years");

    return $form->getTemplate();
  }

  function getMiniMonthView() {
    require_once('Calendar/Month/Weekdays.php');
    require_once('Calendar/Day.php');

    $timestamp  = null;
    if(isset($_REQUEST["tsSmall"])) {
      $this->_tsSmall = $timestamp = $_REQUEST["tsSmall"];
    } else {
      $this->_tsSmall = $timestamp = time();
    }

    if(empty($this->_jsOnClickDaysFunc)) {
      $result = $this->getActiveDays(date("m", $timestamp), date('Y', $timestamp));
      $this->buildMonth($result);
    } else {
      $this->buildMonth();
    }

    $tags    = array();
    $content = array();

    $tags = $this->getQuickSelect(TRUE);

    $tags['PREV_MONTH_LINK'] = $this->_linkBack . "&amp;miniMonthCal=1&amp;tsSmall=".$this->_month->prevMonth(true);
    $tags['NEXT_MONTH_LINK'] = $this->_linkBack . "&amp;miniMonthCal=1&amp;tsSmall=".$this->_month->nextMonth(true);

    $tags['BACK_LINK'] = str_replace('&amp;', '&', $this->_linkBack);
    $tags['BACK_LINK'] .= '&tsSmall=';

    $timestamp = $this->_month->getTimeStamp();
    $month = date("F", $timestamp);
    $year = date("Y", $timestamp);

    $js = implode(" ", array("onMouseOver=\"window.status='View {$month} {$year}'; return true;\"",
			     "onMouseOut=\"window.status='';\""));

    $tags['DATE']   = array();
    if(!empty($this->_fullMonthLink))
      $tags['DATE'][] = "<a href=\"".$this->_fullMonthLink . "&amp;miniMonthCal=1&amp;ts={$timestamp}\" {$js}>{$month}</a>";
    else
      $tags['DATE'][] = $month;

    $tags['DATE'][] = $year;
    $tags['DATE']   = implode("&#160;", $tags['DATE']);
  
    while($Day =& $this->_month->fetch()) {
      if($Day->isFirst()) {
	$content[] = "<tr class=\"shade\">";
      }
      
      if($Day->isEmpty()) {
	$content[] =  "<td class=\"no-shade\">&#160;</td>";
      } else {
	$timestamp = $Day->getTimeStamp();

	$class = null;

	if(date('Y n j', $timestamp) == date('Y n j', $this->_ts)) {
	  $class = "today-border";
	}

	$date = date("l F d, Y", $timestamp);

	$js = implode(" ", array("onmouseover=\"window.status='View {$date}'; return true;\"",
				 "onmouseout=\"window.status='';\""));
            
	if(isset($this->_jsOnClickDaysFunc)) {
	  $js = "onclick=\"".$this->_jsOnClickDaysFunc."(".
                         date('n', $timestamp) . "," . 
	                 date('j', $timestamp) . "," .
	                 date('Y', $timestamp) . ");"."\"";	  

	  $link      = implode("", array("<a href=\"#\" {$js}>",
					 $Day->thisDay(),
					 "</a>"));
	} else {
	
	  if($Day->isSelected()) {
	    $events = $Day->getEvents();
	    
	    $todayEvntConflict = "";
	    if($this->today($Day, $timestamp)) {
	      $todayEvntConflict = " class='today-border' ";
	    }
	    
	    $link  = "<a $todayEvntConflict href=\"".$this->_linkBack;
	    $link .= "&amp;";
	    
	    if(isset($this->_itemOp))
	      $link .= $this->_itemOp . "&amp;";

	    if(count($events) > 1) {	      
	      $link .= "multi_id=";

	      foreach($events as $value) {
		if(isset($value["idPrefix"]))
		  $prefix = $value["idPrefix"] . "_";
		else
		  $prefix = NULL;

		$link .= $prefix . $value["id"] . "::";
	      }

	      $link = substr($link, 0, -2);
	      
	    } else {	    
	      if(isset($events[0]["idPrefix"])) {
		$link .= $events[0]["idPrefix"] . '_id=';
	      } else {	      
		$link .= "id=";
	      }	    
	      
	      $link .= $events[0]["id"];
	    }
	    
	    $link .= "&amp;miniMonthCal=1&amp;tsSmall={$timestamp}\" {$js}>";
	    $link  = implode("", array($link,
				       $Day->thisDay(),
				     "</a>"));	      
	   
	  } else {
	    $link = $Day->thisDay();
	  }

	} // end check for js
	
	$selClass = "";
	if($Day->isSelected()) 
	  $selClass = " class=\"item {$class}\" onmouseover=\"this.className='overItem';\" onmouseout=\"this.className='item'\" ";
	else
	  $selClass = " class=\"{$class}\" ";

	$content[] = "<td $selClass align=\"center\" valign=\"top\">{$link}</td>";
	     
      } // check if empty

      if($Day->isLast()) {
	$content[] = "</tr>";
      }
    }   // end while

    $tags = array_merge($tags, $this->_createDayHeaders(TRUE));
    $tags['ROWS'] = implode("\n", $content);   
    if(!isset($this->_jsOnClickDaysFunc)) {
      $tags['LBL_EVENT'] = $_SESSION["translate"]->it("Event");
      $tags['LBL_TODAY'] = $_SESSION["translate"]->it("Today");
    } 
 
    return $this->_processTemplate($tags, "smallmonth.tpl");
  }

  function getActiveDays($month, $year) {
    return $this->_callerObj->cal_getActiveDays($month, $year);
  }

  function getFullMonthActiveDays($month, $year) {
    return $this->_callerObj->cal_getFullMonthActiveDays($month, $year);
  }

  function _processTemplate(&$tags, $tplName) {
    if(isset($this->_templateDir)) {
      return PHPWS_Template::processTemplate($tags, $this->_module, $this->_templateDir . "/" . $tplName);
    } else {
      return PHPWS_Template::processTemplate($tags, "core", PHPWS_SOURCE_DIR . "templates/calendar/" . $tplName, FALSE);
    }
  }

  function getFirstEvent() {
    $events = NULL;
    while($Day =& $this->_month->fetch()) {
      if($Day->isSelected()) {
	$events = $Day->getEvents();
	$events[1] = $Day->getTimeStamp();
	break;
      }
    }

    if($events != null) {
      return $events;
    } else 
      return false;
  }

  function getPrevEvent($currTS) {
    $events = null;
    $prevDay = null;

    reset($this->_month->children);
    while($Day =& $this->_month->fetch()) {

      if($Day->isSelected() && $Day->getTimeStamp() == $currTS ) {
	if(isset($prevDay)) {
	  $events = $prevDay->getEvents();
	  $events["ts"] = $prevDay->getTimeStamp();
	}
      }

      if($Day->isSelected()) {
	$prevDay = $Day;
      }
    }

    if($events != null) {
      return $events;
    } else 
      return false;
  }

  function getNextEvent($currTS) {
    $events = null;
    reset($this->_month->children);
    while($Day =& $this->_month->fetch()) {
      if($Day->isSelected() && $Day->getTimeStamp() > $currTS ) {
	$events = $Day->getEvents();
	$events["ts"] = $Day->getTimeStamp();	
	break;
      }
    }

    if($events != null) {
      return $events;
    } else 
      return false;
  }

  /**
   * Op attended to any items.
   * Useful for editing.
   */
  function setItemOp($op) {
    $this->_itemOp = $op;
  }

  /**
   * Used with the Expand Month View for Empty Days
   *
   * See the getExpandedMonthView for information.
   * 
   */
  function setBlankOp($op) {
    $this->_blankOp = $op;
  }

  /**
   * Set javascript function to call.
   * The month, day, and year selected will be passed to the 
   * javascript function in that order.
   */
  function jsOnClickFunc($jsFuncName) {
    $this->_jsOnClickDaysFunc = $jsFuncName;
  }

  /**
   *
   * Used with the Mini-View Month to make the name
   * of the month a link (such as to a full month view).
   * $link param
   *  Ex. index.php?module=mname
   *  The ts timestamp will be appended.
   *
   */
  function setFullMonthLink($link) {
    $this->_fullMonthLink = $link;
  }

  /**
   * Used to set the link to get back to the calendar.
   * 
   */
  function setLinkBack($link) {
    $this->_linkBack = $link;
  }

  /**
   * Direct way to set the items for the calendar without the 
   * callback function.
   *
   */
  function setData($items) {
    $this->_items = $items;
  }

  /*
   *  Used to provide your custom template that are different from the 
   *  default core calendar templates.
   */
  function setTemplateDir($templateDir) {
    $this->_templateDir = $templateDir;
  }

  /**
   * Access Methods
   **/
  function getFullMonthYear() {
    return date('Y', $this->_ts);
  }

  function getFullMonthMonth() {
    return date('m', $this->_ts);
  }

  function getMiniViewMonth() {
    if(isset($_REQUEST["tsSmall"]))
      $this->_tsSmall = $_REQUEST["tsSmall"];

    return date('m', $this->_tsSmall);
  }
}

require_once('Calendar/Decorator.php');

class Event extends Calendar_Decorator {
  var $_data = array();
  var $_currIndex;

  function Event(&$Day) {
    parent::Calendar_Decorator($Day);
    $this->_currIndex = 0;
  }

  function fetch() {
    if($Hour = parent::fetch()) {      
      if($Hour->thisHour() < 8 || $Hour->thisHour() > 17) {
	return $this->fetch();
      } else {
	return $Hour;
      }
    } else {
      return false;
    }
  }

  function add($label, $id=NULL, $prefix=NULL) {
    $this->_data[$this->_currIndex]["label"] = $label;

    if(isset($id))
      $this->_data[$this->_currIndex]["id"]    = $id;

    if(isset($prefix))
      $this->_data[$this->_currIndex]["idPrefix"] = $prefix;

    $this->_currIndex++;
  }

  function getEvents() {
    return $this->_data;
  }
}

?>