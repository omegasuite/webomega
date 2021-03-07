<script type="text/javascript">
 //<![CDATA[

 function quickSelectMonth() {
   element = document.getElementById('FLD_quick_select_month');
   url = "{BACK_LINK}" + "&tsSmall=" + element.value;
   location.href = url;
 }

 function quickSelectYear() {
   element = document.getElementById('FLD_quick_select_years');
   url = "{BACK_LINK}" + "&tsSmall=" + element.value;
   location.href = url;
 }

 //]]>
</script>

<style type="text/css">
.shade {
	background: #EEEEEE;
}

.shadeWeekDays {
	background: #d4d4d4;
}


.no-shade {
       background: white;
}

.click {
	background: #00aa00;
}

.today-border {
	border:solid 1px #ff0000;
}

.selected-border {
	border:solid 1px #0000ff;
}

.calendar {
	font-size: .9em;
}

.overItem {
	background: #dddddd;
	border:solid 1px #00ff00;
}

.item {
	background: #dddddd;
	border:solid 1px #0000ff;
}

.calendar td.item hover {
	background:red;
}

.calendar a:link {
	text-decoration: none;
	color: #000000;
}

.calendar a:visited {
	text-decoration: none;
	color: #000000;
}

.calendar a:hover {
	text-decoration: underline;
	color: #000000;
}

.calendar a:active {
	text-decoration: none;
	color: #000000;
}
</style>

<table class="calendar bg_light" cellpadding="5" width="100%" border="0">
<tr>
<td align="center">

<table width="100%" cellspacing="0" cellpadding="0">
<tr><td valign="top">&nbsp;&nbsp;
<a href="{PREV_MONTH_LINK}" 
onmouseover="window.status='Previous month'; return true;" onmouseout="window.status='';">&lt;&lt;</a>
</td>
<td>
<!-- &#160;<i><b>{DATE}</b></i>&#160; -->
<div style="text-align:center;">
{START_FORM}
{FLD_QUICK_SELECT_MONTH}&nbsp;{FLD_QUICK_SELECT_YEARS}
{END_FORM}
</div>
</td>
<td valign="top">
<div style="text-align:right;">
<a href="{NEXT_MONTH_LINK}" 
onmouseover="window.status='Next month'; return true;" onmouseout="window.status='';">&gt;&gt;</a>&nbsp;&nbsp;
</div>
</td></tr></table>

</td>
</tr>
<tr>
<td>
<table width="100%" border="0" cellpadding="0" cellspacing="1">
<tr class="shadeWeekDays">
<td align="center"><b>{DAY_1}</b></td>
<td align="center"><b>{DAY_2}</b></td>
<td align="center"><b>{DAY_3}</b></td>
<td align="center"><b>{DAY_4}</b></td>
<td align="center"><b>{DAY_5}</b></td>
<td align="center"><b>{DAY_6}</b></td>
<td align="center"><b>{DAY_7}</b></td>
</tr>
{ROWS}
</table>
</td></tr>
<tr><td>

<!-- BEGIN LBL_LEGEND -->
<table cellspacing="0" cellpadding="0" align="right">
<tr><td class="today-border">&nbsp;&nbsp;&nbsp;</td>
<td>&nbsp;&nbsp;{LBL_TODAY}</td>
<!-- BEGIN LBL_EVENT -->
<td>&nbsp;&nbsp;</td><td class="selected-border">&nbsp;&nbsp;&nbsp;</td>
<td>&nbsp;&nbsp;{LBL_EVENT}</td>
<!-- END LBL_EVENT -->
</tr>
</table>
<!-- END LBL_LEGEND -->

</td></tr>
</table>

