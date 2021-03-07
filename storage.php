<?php
class Storage {
	function Storage($a, $b) {
	}

	function write($bkt, $fname, $c) {
		$path = explode("/", $fname);
		$p = "data";
		foreach ($path as $d) {
			if (!is_dir($p)) mkdir($p);
			$p .= "/" . $d;
		}
		$fp = fopen($p, "wb");
		fputs($fp, $c);
		fclose($fp);
	}

	function delete($bkt, $fname) {
		unlink("data/" . $fname);
	}

	function getUrl($bkt, $fname) {
		return "http://" . $_SERVER['HTTP_HOST'] . preg_replace("@/[^/]*$@", '', $_SERVER['SCRIPT_NAME']) . "/data/" . $fname;
	}
}
?>
