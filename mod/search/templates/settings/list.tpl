<form method="post" action="index.php">
<input type="hidden" name="module" value="search" />
<input type="hidden" name="search_op" value="settings" />
<table border="0" width="100%" cellspacing="1" cellpadding="6" align="center">
<tr class="bg_dark">
<td width="30%" nowrap="nowrap"><b>{MODULE_LABEL}</b></td>
<td width="60%" nowrap="nowrap"><b>{BLOCK_TITLE_LABEL}</b></td>
<td width="10%" nowrap="nowrap"><b>{SHOW_BLOCK_LABEL}</b></td>
</tr>
{LIST_ITEMS}
</table><br />
<input type="submit" name="save" value="{SAVE}" />
</form>
