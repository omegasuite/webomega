<?php
// delete vssver.scc files
// $root = "/usr/local/www/data/discoverlaws";

set_time_limit(0);
error_reporting (E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

$n = 0;
$root = getcwd();

if ($_REQUEST['dir']) $n = delorig("$root/" . $_REQUEST['dir']);
else $n = delorig($root);
echo "$n files deleted.<br>";

function delorig($state) {
	$n = 0;
	echo $state . "<br>"; flush();
	$dir = opendir($state);
	while ($f = readdir($dir)) {
		if ($f == '.' || $f == '..') continue;
		if (is_dir("$state/$f"))
			$n += delorig("$state/$f");
		elseif ($f == "vssver.scc" || preg_match("/.bak$/", $f) || $f == '_desktop.ini' || $f == 'Thumb.db') {
			echo "$state/$f deleted<br>";  flush();
			unlink("$state/$f");
			$n++;
		}
	}
	closedir($dir);
	return $n;
}

?>