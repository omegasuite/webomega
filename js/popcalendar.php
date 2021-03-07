<?php

if($_SESSION['OBJ_user']->js_on) {

    if (!isset($GLOBALS['popcalendar'])) $GLOBALS['popcalendar'] = 1;
    
    if ($GLOBALS['popcalendar'] == 1) {
	$source = "
var month    = null;
var day      = null;
var year     = null;
var calendar = null;
function popcalendar(monthfield, dayfield, yearfield) {
    leftPos = topPos = 100;
    if (screen) {
      topPos = (screen.height / 2) - 162;    
      leftPos = (screen.width / 1.3);
    }

    month = document.getElementById(monthfield);
    day = document.getElementById(dayfield);
    year = document.getElementById(yearfield);

    url = './js/popcalendar/popcalendar.php?';

    rmonth = month.value;
    ryear  = year.value;
    if((rmonth.length != 0) && (ryear != 0)) {
        url = url + '&year=' + ryear + '&month=' + rmonth;
    }

    calendar = window.open(url, 'calendar','width=320,height=230,toolbar=no,scrollbars=no,location=no,status=yes,resizable=0,dependent=no,left='+leftPos+',top='+topPos+',screenX='+topPos+',screenY='+leftPos);

    if(calendar && !calendar.closed) {
        try {
            calendar.focus();
        }
        catch(e) {}
    }
}

function setnewdate(newmonth, newday, newyear) { 
    month.options.selectedIndex = newmonth - 1;
    day.options.selectedIndex   = newday - 1;

    for(i = 0; i < year.options.length; i++) {
        if(year.options[i].value == newyear) {
            year.options[i].selected = true;
            break;
        }
    }

    calendar.close();
}
";

	$GLOBALS['core']->js_func[] = $source;
	$GLOBALS['popcalendar'] ++;
    }

    $text = $_SESSION['translate']->it("Pop Calendar");

    $js = "<a href=\"javascript:popcalendar('{$month}', '{$day}', '{$year}');\" onmouseover=\"window.status='{$text}'; return true;\" onmouseout=\"window.status='';\"><img src=\"./images/javascript/calendar.png\" alt=\"{$text}\" title=\"{$text}\" border=\"0\" /></a>";
} else {
    $js = null;
}