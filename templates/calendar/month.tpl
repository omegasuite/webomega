<script type="text/javascript">
//<![CDATA[
function viewEvent(link, ts) {
   url = link + "&fullMonthCal=1&ts=" + ts;
   location.href = url;
}

 function quickSelectMonth() {
   element = document.getElementById('FLD_quick_select_month');
   url = "{BACK_LINK}" + "&ts=" + element.value;
   location.href = url;
 }

 function quickSelectYear() {
   element = document.getElementById('FLD_quick_select_years');
   url = "{BACK_LINK}" + "&ts=" + element.value;
   location.href = url;
 }
//]]>
</script>

<style type="text/css">
.shade {
	background: #c1c1c1;
}

.no-shade {
	background: #ffffff;
}

.event-shade {
	background: #d3ce83;
}

.event-no-shade {
	background: #ece9a1;
}

.click {
	background: #00aa00;
}

.calendar {
	font-size: .9em;
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

.calendar ul {
	margin-left: 0;
	padding-left: 1em;
}

.today {
	background-color: #EEEEEE;
}

</style>

<i>{LBL_TODAY}: {TODAY}</i><br /><br />

<div align="center">
<table width="100%" cellspacing="0" cellpadding="0">
<tr><td valign="top">&nbsp;&nbsp;

<a href="{LINK_BACK}&amp;op=month&amp;ts={PREV_MONTH}" 
onmouseover="window.status='View previous month'; return true;" onmouseout="window.status='';">&lt;&lt;</a>
</td>
<td>
<!-- &#160;<b>{DATE}</b>&#160; -->
<div style="text-align:center;">
{START_FORM}
{FLD_QUICK_SELECT_MONTH}&nbsp;{FLD_QUICK_SELECT_YEARS}
{END_FORM}
</div>
</td>
<td valign="top">
<div style="text-align:right;">
<a href="{LINK_BACK}&amp;op=month&amp;ts={NEXT_MONTH}" 
onmouseover="window.status='View next month'; return true;" onmouseout="window.status='';">&gt;&gt;</a>
</div>
</td></tr></table>
</div><br />
<table width="100%" border="0" class="calendar"><tr><td bgcolor="#c1c1c1">
<table width="100%" border="0" cellpadding="5" cellspacing="1">
<tr>
<td align="center">{DAY_1}</td>
<td align="center">{DAY_2}</td>
<td align="center">{DAY_3}</td>
<td align="center">{DAY_4}</td>
<td align="center">{DAY_5}</td>
<td align="center">{DAY_6}</td>
<td align="center">{DAY_7}</td>
</tr>
{ROWS}
</table>
</td></tr></table>
