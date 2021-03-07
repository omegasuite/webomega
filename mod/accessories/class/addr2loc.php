<?php
if (!function_exists('addr2loc')) {		// find the proper form options for the current user
	function addr2loc($customer, $address, $province = NULL, $city = NULL, $lat  = NULL, $lng = NULL) {
		  $place = 0;
		  $ok = 'WARN'; $msg = '无法找到地址的地理位置。请稍后核对修改。';

	  return array('place'=>$place, 'ok'=>$ok, 'msg'=>$msg);
	}
}

?>