<?php

require_once("./settings.php");

define("PHPWS_SOURCE_DIR", $hub_dir);
require_once($hub_dir . 'core/Core.php');
require_once($hub_dir . 'core/Array.php');
require_once($hub_dir . 'conf/cache.php');
require_once($hub_dir . 'mod/language/class/Language.php');

session_start();
$GLOBALS["core"] = new PHPWS_Core(NULL, $hub_dir);
$_SESSION["translate"] = new PHPWS_Language;

include($hub_dir . "conf/config.php");

require_once($source_dir . "core/Calendar.php");
$cal = new PHPWS_Cal();
$cal->setLinkBack("./cal.php?sectionMonth={$_REQUEST['sectionMonth']}&amp;sectionDay={$_REQUEST['sectionDay']}&amp;sectionYear={$_REQUEST['sectionYear']}");
$cal->jsOnClickFunc("setDate");

if(isset($_REQUEST["month"]) && $_REQUEST["year"]) {
  if(is_numeric($_REQUEST["year"])) {
    $timets = mktime(0,0,0, $_REQUEST["month"], 1, $_REQUEST["year"]);
    $_REQUEST["tsSmall"] = $timets;
  }
}

$html   = array();
$html[] = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\"\n\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">";
$html[] = "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en-US\" lang=\"en-US\">";
$html[] = "<head>";
$html[] = "<title>PopUp Mini-Calendar</title>";

$result = $GLOBALS["core"]->sqlSelect("mod_layout_config");
$theme = $result[0]["default_theme"];

if(USE_THEME_COLORS) {
  require_once($hub_dir . "mod/layout/class/Layout.php");
  $layout = new PHPWS_Layout();
  $html[] = "<link rel='stylesheet' href='http://".
    $GLOBALS["core"]->home_http . substr($layout->theme_dir, 6) . "style.css' type='text/css' />";  
}
$html[] = "<script type=\"text/javascript\">";
$html[] = "//<![CDATA[";
$html[] = "\n";
$html[] = "function setDate(month, day, year) {";
$html[] = "   ";
$html[] = "   var monthField = opener.document.getElementById('{$_REQUEST['sectionMonth']}');";
$html[] = "   monthField.value = month;";
$html[] = " ";
$html[] = "   var dayField = opener.document.getElementById('{$_REQUEST['sectionDay']}');";
$html[] = "   dayField.value = day;";
$html[] = " ";
$html[] = "   var yearField = opener.document.getElementById('{$_REQUEST['sectionYear']}');";
$html[] = "   yearField.value = year;";
$html[] = "   self.close();";
$html[] = "}";
$html[] = "\n";
$html[] = "//]]>";
$html[] = "</script>\n";
$html[] = "<style type=\"text/css\">";
$html[] = "body {";
$html[] = "   margin:5px;";
$html[] = "}\n";
$html[] = "</style>";
$html[] = "</head>";

$html[] = "<body class='bg_dark'>";

$html[] = $cal->getMiniMonthView();
$html[] = "<div style='text-align:right;margin:5px;'><input class='bg_light' type='button' value='Close' onClick='javascript:window.close();'></div>";
$html[] = "</body>";
$html[] = "</html>";

echo implode("\n", $html);

unset($cal);
unset($layout);
unset($GLOBALS["core"]);

?>