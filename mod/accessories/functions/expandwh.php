<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');

   extract($_REQUEST);
	
   function whexpand($hierachy, $shelfspace, $alloc, $goods, $indent = '', $slot = '') {
	  extract($_REQUEST);

	  list ($i, $hd) = each ($hierachy);
	  $subhierachy = $hierachy;
	  unset($subhierachy[$i]);
	  if ($alloc) {
		  $alloc = explode($hd, $alloc);
		  $a = $alloc[0];
		  unset($alloc[0]);
		  $alloc = implode($hd, $alloc);
		  $alloc = array($a=>$alloc);
	  }
	  else $s .= $indent . $slot . '<br>';

	  $indent .= '  ';

	  foreach ($shelfspace as $h=>$t) {
		  $ms = explode("-", $h);
		  $va = array($ms[0]);
		  if (sizeof($ms) == 2) {
			  if (is_numeric($ms[0])) {
				  for ($i = $ms[0] + 1; $i <= $ms[1]; $i++) $va[] = $i;
			  }
			  else {
				  for ($i = ord($ms[0]) + 1; $i <= ord($ms[1]); $i++) $va[] = chr($i);
			  }
		  }
		  if ($alloc) {
			  list($v, $b) = each ($alloc);
			  if (in_array($v, $va))
				  $s .= whexpand($subhierachy, $shelfspace[$h], $b, $goods, $indent, $slot . $v . $hd);
		  }
		  else {
			  if (sizeof($t) == 0) {
				  $m = 0; $g = $indent;
				  $mm = min(10, round(85 / strlen("$slot$hd"), 0));
				  $used = $GLOBALS['core']->query("SELECT * FROM mod_shelf_goods WHERE shelf like '$slot%' AND wharehouse=" . $id, true);
				  $usd = array();
				  while ($u = $used->fetchRow()) $usd[$u['shelf']] = 1;
				  foreach ($va as $v) {
					  $s .= $g . "$slot$v$hd" . ($usd["$slot$v$hd"]?'  ' : preg_replace("/[[:space:]]*$/", " ", PHPWS_Form::formCheckBox("alloc[$slot$v$hd]", $goods)));
					  if ((++$m % $mm) == 0) $g = "<br>" . $indent;
					  else $g = " ";
				  }
				  if ($m) $s .= "<br>";
			  }
			  else foreach ($va as $v) {
				  $s .= "<span id='$goods-$slot$v$hd'>$indent<a href=# onclick='expand(\"$goods-$slot$v$hd\", \"$slot$v$hd\", $goods);'>$v$hd</a></span><br>";
			  }
		  }
	  }
	  return $s;
  }
  
  $wh = $GLOBALS['core']->sqlSelect('mod_wharehouse', 'id', $id);
  $wh = $wh[0];
  $wh['hierachy'] = $wh['hierachy']?unserialize($wh['hierachy']) : array();
  $wh['shelfspace'] = $wh['shelfspace']?unserialize($wh['shelfspace']) : array();

  exit(json_encode(array('storelocs'=>whexpand($wh['hierachy'], $wh['shelfspace'], $loc, $goods))));
?>