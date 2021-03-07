<?php

require_once(PHPWS_SOURCE_DIR . 'mod/kernel/class/attrib.php');

	extract($_REQUEST);

	if ($useprodcat) {
		if ($goods > 0)
			$c = $GLOBALS['core']->query('SELECT id as gid, name as gname FROM supply_mod_product WHERE id=' . $goods, false);
		else $c = $GLOBALS['core']->query('SELECT -id as gid, name as gname FROM supply_mod_category WHERE id=' . (-$goods), false);
	}
	else $c = $GLOBALS['core']->query('SELECT *,g.attribs as gattribs,g.id as gid, g.name as gname, p.name as pname FROM supply_goodsonsale as g JOIN supply_mod_product as p ON g.product=p.id AND ' . ($goods?'g.id=' . $goods : ($code?'custid=' . $code : '')), false);

	$c = $c->fetchRow();

	if (!$c) exit(json_encode(array('status'=>'ERROR', 'msg'=>"商品不存在。")));

	if (!isset($c['gattribs'])) $c['gattribs'] = array();

	exit(json_encode(array('status'=>'OK', 'product'=>$c['gid'], 'price'=>$c['price'] / 100.0, 'name'=>$c['gname']?$c['gname'] : $c['pname'], 'unit'=>$c['unit'], 'code'=>$c['custid'], 'type'=>spec($c['spec']), 'pubid'=>$c[$useprodcat?'gid' : 'product'], 'attrib'=>convformattrib(unserialize($c['gattribs'])))));
?>