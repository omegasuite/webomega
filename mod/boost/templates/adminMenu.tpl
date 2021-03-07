{PERMISSION_WARNING}
<!-- BEGIN boostUpdate -->
<table border="0" width="100%" cellpadding="6">
<tr class="bg_medium">
<td align="center"><b>{UPDATE_WARNING}</b></td>
</tr>
<tr>
<td align="center">{UPDATE_BOOST}</td>
</tr>
</table>
<br />
<!-- END boostUpdate -->

<!-- BEGIN CoreUpdate -->
<table border="0" width="100%" cellpadding="6">
<tr class="bg_medium">
<td align="center"><b>{CORE}</b></td>
</tr>
<tr>
<td align="center">{UPDATE_CORE}</td>
</tr>
</table>
<br />
<!-- END CoreUpdate -->
<h2>{CORE_VERSION_TEXT}:&#160;{CORE_VERSION}</h2>
<hr />
<h2>{CORE_MODS}</h2>
<table cellpadding="6" cellspacing="1" width="100%">
  <tr class="bg_medium">
    <td width="5%"><b>{VERSION}</b></td>
    <td><b>{MOD_NAME}</b></td>
    <td><b>{COMMAND}</b></td>
  </tr>
  {CORE_ROWS}
</table>

<!-- BEGIN NONCORE_MODS -->
<h2>{NONCORE_MODS}</h2>
<!-- BEGIN Modules -->
<table cellpadding="6" cellspacing="1" width="100%">
  <tr class="bg_medium">
    <td width="5%"><b>{NC_VERSION}</b></td>
    <td><b>{NC_MOD_NAME}</b></td>
    <td><b>{NC_COMMAND}</b></td>
    <td><b>{NC_UNINSTALL}</b></td>
  </tr>
  {NONCORE_ROWS}
</table>
<!-- END NONCORE_MODS -->

<!-- BEGIN updateall --><div align="center">{UPDATE_ALL}</div><!-- END updateall -->
<!-- END Modules -->
