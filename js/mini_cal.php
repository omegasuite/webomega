<?php

if($_SESSION['OBJ_user']->js_on) {
    if(!isset($GLOBALS['mini_cal'])) $GLOBALS['mini_cal'] = TRUE;
    
    if($GLOBALS['mini_cal'] == 1) {
	$GLOBALS['core']->js_func[] = "
var calendar = null;

function miniCalPopUp(sectionMonth, sectionDay, sectionYear) {
    leftPos = 0
    topPos = 0
    if (screen) {
        topPos = (screen.height / 2) - 162
    }
    leftPos = (screen.width / 1.3);

    month = document.getElementById(sectionMonth).value;
    year  = document.getElementById(sectionYear).value;

    loc = './js/mini_cal/cal.php?sectionMonth=' + sectionMonth + '&sectionDay=' + sectionDay + '&sectionYear=' + sectionYear + '&month=' + month + '&year=' + year;

    calendar = window.open(loc, 'calendar', 'width=320,height=270,toolbar=no,scrollbars=yes,top='+topPos+',left='+leftPos+',screenX=50, screenY=50');
    calendar.focus();   
}\n";

    }

    $js = "<a href=\"javascript:miniCalPopUp('{$month}','{$day}', '{$year}');\" onmouseover=\"window.status='Show PopUp Mini Calendar'; return true;\" onmouseout=\"window.status='';\">" . phpws_text::imageTag("./images/javascript/mini_cal/calendar_icon.jpg", "PopUp Mini-Calendar",16,15) . "</a>\n";

    $GLOBALS['mini_cal'] ++;

} else {
  $js = NULL;
}

?>