<?xml version="1.0" encoding="ISO-8859-1"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
<title>Pop Calendar</title>
<style type="text/css">
.shade {
    background: #cccccc;
}
.dark-shade {
    background: #b0b0b0;
}
.no-shade {
    background: #ffffff;
}
td.today-border-shade {
    background: #cccccc;
    border:solid 1px #ff0000;
}
td.today-border-dark-shade {
    background: #b0b0b0;
    border:solid 1px #ff0000;
}
</style>
</head>
<body>
<form name="Pop_calendar" method="post" action="popcalendar.php">
<table width="100%" border="0">
<tr>
<td align="center">
{PREV_YEAR}
&#160;&#160;
{PREV_MONTH}
&#160;&#160;{MONTH}&#160;{YEAR}&#160;&#160;
{NEXT_MONTH}
&#160;&#160;
{NEXT_YEAR}
</td>
</tr>
<tr>
<td>
<table width="100%" border="0" cellpadding="3" cellspacing="1">
<tr class="shade">
<td align="center">M</td>
<td align="center">T</td>
<td align="center">W</td>
<td align="center">T</td>
<td align="center">F</td>
<td align="center">S</td>
<td align="center">S</td>
</tr>
{ROWS}
</table>
</td></tr></table>
</form>
</body>
</html>
