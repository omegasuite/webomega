<?php
require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'core/phpqrcode.php');

	  extract($_REQUEST);

	  $ps = $GLOBALS['core']->query("SELECT * FROM srcodes WHERE tablename='$for' AND tablerow=" . ($id?$id : $codeid), true);
	  $p = $ps->fetchRow();

	  QRcode::png($p['srcode'] . ($append?"&" . str_replace(":", "=", $append) : ''), false, QR_ECLEVEL_M, 4);
	  exit();
?>