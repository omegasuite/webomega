<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');

	extract($_REQUEST);

	if ($place) {
		  $loc = $GLOBALS['core']->sqlSelect('mod_locations', 'id', $place);
		  $lat = $loc[0]['lat'];
		  $lng = $loc[0]['lng'];
	}

echo '
    <style type="text/css">
        body, html,#allmap {width: 100%;height: 600px;overflow: hidden;margin:0;font-family:"微软雅黑";}
    </style>
    <!-- script type="text/javascript" src="http://api.map.baidu.com/api?v=1.0&ak=EzfQTH533pbevnhohMX4QZRK"></script -->
	<script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=sSelQoVi2L3KofLo1HOobonW"></script>

    <div id="allmap"></div>
    <script type="text/javascript">
				var myGeo = new BMap.Geocoder();
				var lat = ' . $lat . ', lng = ' . $lng . ';

				showMap();

				function showMap() {
					// 百度地图API功能
					var map = new BMap.Map("allmap");            // 创建Map实例
					map.centerAndZoom(new BMap.Point(lng ,lat),12);  //初始化时，即可设置中心点和地图缩放级别。
					map.disableDragging();     //禁止拖拽

					var point = new BMap.Point(lng ,lat); 
					var defmarker = new BMap.Marker(point); // 创建点
					map.addOverlay(defmarker);
				}
					
</script>';
exit();
?>