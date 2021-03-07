<?php
if (!defined("PHPWS_DATE_FORMAT"))
     define("PHPWS_DATE_FORMAT", "m/d/Y");

if (!defined("PHPWS_TIME_FORMAT"))
     define("PHPWS_TIME_FORMAT", "h:i A");

$date_month  = "m";
$date_day    = "d";
$date_year   = "Y";
$day_mode    = "l";
$day_start   = 0; // 0 = Sunday, 1 = Monday
$time_dif    = 0;

// Deprecated.  Use above defines
$date_order  = PHPWS_DATE_FORMAT;
$time_format = PHPWS_TIME_FORMAT;

?>