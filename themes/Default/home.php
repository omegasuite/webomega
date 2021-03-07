<?php
require_once(PHPWS_SOURCE_DIR . 'mod/accessories/class/formoptions.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/consts.php');
require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/cache.php');
require_once(PHPWS_SOURCE_DIR . 'mod/basic.php');

$flows = formoptions('homepage', NULL, true);
$homepage = array();
foreach ($flows as $f) {
	if ($f['defaultval']) $f['defaultval'] = unserialize($f['defaultval']);
	$homepage[$f['colname']] = $f;
}

$s = '';

// alerts
$orgs = array($_SESSION["OBJ_user"]->org);
$tasks = array();
$homepage['alert']['defaultval'][0] = 1;
sort($homepage['alert']['defaultval'], SORT_NUMERIC);

// frequent used tasks

// announcements
$orgs = anciestorgs();
$annouce = '<table class=table-medium width=100%>';

$ann = $GLOBALS['core']->query("SELECT * FROM mod_announce WHERE expiration>'" . date("Y-m-d H:i:s") . "'", true);

while ($p = $ann->fetchRow()) {
	$annouce .= "<tr><th><a href=./index.php?module=announce&ANN_id={$p['id']}&ANN_op=view>{$p['subject']}</a></th><td>" . orgCache($p['org']) . "</td><td>" . dayofdate($p['poston']) . "</td></tr>";
}
$annouce .= "</table>";

$THEME['HOMEBODY'] = '<style>td{text-align:center;overflow:hidden;}</style><table width=100%><tr><td>' . $s . "</td><td width=1px style='background:#888;'></td><td width=33% valign=top><h4>公告通知</h4><hr>$annouce</td></tr></table>";

?>
