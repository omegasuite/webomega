<?php

// if you are having problems with the calendar popup, you will
// need to hard code the following below. Make sure to copy the
// file to your branch sites.
//ini_set("include_path", ".:/path/to/your/phpwebsite/lib/pear/");

require_once('HTML/Template/IT.php'); 
require_once('Calendar/Month/Weekdays.php');
require_once('Calendar/Day.php');

define('POPCAL_START_YEAR', date('Y'));
define('POPCAL_END_YEAR', date('Y') + 10);

$tags    = array();
$content = array();

if(isset($_REQUEST['time'])) {
    $time = $_REQUEST['time'];
} else {
    $time = time();
}

if(isset($_REQUEST['month'])) {
    $m = $_REQUEST['month'];
} else {
    $m = date('m', $time);
}

if(isset($_REQUEST['year'])) {
    $y = $_REQUEST['year'];
} else {
    $y = date('Y', $time);
}

$Month =& new Calendar_Month_Weekdays($y, $m);
$Month->build();

$prev_year  = $Month->prevYear(true);
$prev_month = $Month->prevMonth(true);
$next_month = $Month->nextMonth(true);
$next_year  = $Month->nextYear(true);

if($y > POPCAL_START_YEAR) {
    $tags['PREV_YEAR']  = "<a href=\"./popcalendar.php?time={$prev_year}\" onmouseover=\"window.status='Previous year'; return true;\" onmouseout=\"window.status='';\">&lt;&lt;</a>";
}

if(!(($y == POPCAL_START_YEAR) && ($m == '01'))) {
    $tags['PREV_MONTH'] = "<a href=\"./popcalendar.php?time={$prev_month}\" onmouseover=\"window.status='Previous month'; return true;\" onmouseout=\"window.status='';\">&lt;</a>";
}

if(!(($y == POPCAL_END_YEAR) && ($m == '12'))) {
    $tags['NEXT_MONTH'] = "<a href=\"./popcalendar.php?time={$next_month}\" onmouseover=\"window.status='Next month'; return true;\" onmouseout=\"window.status='';\">&gt;</a>";
}

if($y < POPCAL_END_YEAR) {
    $tags['NEXT_YEAR']  = "<a href=\"./popcalendar.php?time={$next_year}\" onmouseover=\"window.status='Next year'; return true;\" onmouseout=\"window.status='';\">&gt;&gt;</a>";
}

$years = array();
for($i = POPCAL_START_YEAR; $i <= POPCAL_END_YEAR; $i++) {
    $years[$i] = $i;
}

$months = array('01'=>'January',
		'02'=>'February',
		'03'=>'March',
		'04'=>'April',
		'05'=>'May',
		'06'=>'June',
		'07'=>'July',
		'08'=>'August',
		'09'=>'September',
		'10'=>'October',
		'11'=>'November',
		'12'=>'December');

$options   = array();
$options[] = '<select id="month" name="month" onchange="this.form.submit();">';
foreach($months as $key => $value) {
    if($key == $m) {
	$options[] = "<option value=\"{$key}\" selected=\"selected\">{$value}</option>";
    } else {
	$options[] = "<option value=\"{$key}\">{$value}</option>";
    }
}
$options[] = '</select>';

$tags['MONTH'] = implode("\n", $options);

$options   = array();
$options[] = '<select id="year" name="year" onchange="this.form.submit();">';
foreach($years as $key => $value) {
    if($key == $y) {
	$options[] = "<option value=\"{$key}\" selected=\"selected\">{$value}</option>";
    } else {
	$options[] = "<option value=\"{$key}\">{$value}</option>";
    }
}
$options[] = '</select>';

$tags['YEAR'] = implode("\n", $options);

while($Day =& $Month->fetch()) {
    if($Day->isFirst()) {
	$content[] = '<tr class="shade">';
    }
    
    if($Day->isEmpty()) {
	$content[] =  '<td class="no-shade">&#160;</td>';
    } else {
	$timestamp = $Day->getTimeStamp();
	
	$month = date('m', $timestamp);
	$day   = date('d', $timestamp);
	$year  = date('Y', $timestamp);
	$date  = date('m/d/Y', $timestamp);
	
	$class = null;
	if(date('Y n j', time()) == date('Y n j', $Day->getTimeStamp())) {
	    $class = 'class="today-border-shade" ';
	    $js = implode(' ', array("onclick=\"window.opener.setnewdate('{$month}', '{$day}', '{$year}');\"",
				     "onmouseover=\"this.className='today-border-dark-shade'; window.status='{$date}'; return true;\"",
				     "onmouseout=\"this.className='today-border-shade'; window.status='';\""));
	} else {
	    $js = implode(' ', array("onclick=\"window.opener.setnewdate('{$month}', '{$day}', '{$year}');\"",
				     "onmouseover=\"this.className='dark-shade'; window.status='{$date}'; return true;\"",
				     "onmouseout=\"this.className='shade'; window.status='';\""));
	}
	
	$day = $Day->thisDay();
	
	$content[] = "<td {$class}{$js}>{$day}</td>";
    }
    
    if($Day->isLast()) {
	$content[] = '</tr>';
    }
}

$tags['ROWS'] = implode("\n", $content);

$tpl = new HTML_Template_IT('./'); 
$tpl->loadTemplatefile('popcalendar.tpl', true, true);

$tpl->setVariable($tags);
$tpl->parse();
$tpl->show();

?>