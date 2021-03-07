<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'visual');

  $_POST['directexec'] = 1;

  function expandBorder($h, $rev, $rdata) {
	  $sub = array();
	  $vs = array();
	  foreach ($rdata as $k=>$r) {
		  if ($r->kind != 1 || $r->father != $h) continue;
		  $sub[$k] = $r;
	  }
	  $v = $rdata[$h]->begin;
	  if ($rev) $v = $rdata[$h]->end;
	  if (!$sub) {
		  $v = $rdata[$v];
		  $s .= "({$v->x}, {$v->y})<br>";
		  $vs[] = array($v->x, $v->y);
	  }
	  else {
		  for ($i = 0; $i < sizeof($sub); $i++) {
			  foreach ($sub as $k=>$p) {
				  if ($rev && $p->end == $v) {
					  $gs = expandBorder($k, $rev, $rdata);
					  $vs = array_merge($vs, $gs[1]);
					  $s .= $gs[0];
					  $v = $p->begin;
				  }
				  else if (!$rev && $p->begin == $v) {
					  $gs = expandBorder($k, $rev, $rdata);
					  $s .= $gs[0];
					  $vs = array_merge($vs, $gs[1]);
					  $v = $p->end;
				  }
			  }
		  }
	  }
	  return array($s, $vs);
  }

  function getCoords($hash) {
	  $param = array("jsonrpc"=>"1.0", 'method'=>'getdefine', 'params'=>array(2, $hash, true), 'id'=>rand());
	  $ret = $_SESSION['SES_RPC']->post(json_encode($param));

	  $rdata = json_decode($ret);
	  
	  if ($rdata->error) {
		  return "getCoords returned width error: " . print_r($rdata->error, true);
	  }

	  $rdata = (array) $rdata->result->definition;

	  $s = $hash;
	  
	  $h2n = array('0'=>0, '1'=>1, '2'=>2, '3'=>3, '4'=>4, '5'=>5, '6'=>6, '7'=>7, '8'=>8, '9'=>9, 'a'=>10, 'b'=>11, 'c'=>12, 'd'=>13, 'e'=>14, 'f'=>15);
	  $n2h = array_flip($h2n);

	  $poly = $rdata[$hash]->polygon;
	  $ply = ""; $pglue = '';
	  foreach ($poly as $loop) {
		  $s .= "<hr>";
		  $ply .=  $pglue . "[";
		  $glue = '';
		  foreach ($loop as $l) {
			  $l = strtolower($l);
			  $lstnib = $l{strlen($l) - 1};
			  $rev = $h2n["$lstnib"] & 0x1;
			  $h = substr($l, 0, strlen($l) - 1) . $n2h[($h2n[$lstnib] + 0) & 0xE];
			  $gc = expandBorder($h, $rev, $rdata);
			  $s .= $gc[0];
			  foreach ($gc[1] as $vx) {
				  $ply .= $glue . "[{$vx[0]},{$vx[1]}]";
				  $glue = ',';
			  }
		  }
		  $ply .= "]";
		  $pglue = ",";
	  }
	  return array($s, "[$ply]");
  }

  function execute() {
	  extract($_REQUEST);
	  
	  $colormenu = array("", "#FF0000"=>"红色", "#00FF00"=>"绿色", "#0000FF"=>"蓝色", "#FFFFFF"=>"白色", "#000"=>"黑色");

	  if ($in && $out) {
		  $s = "交易输入：<hr>";
		  $in = json_decode($in);

		  $s .= "<table><tr><th>序号</th><th>UTXO交易</th><th>UTXO序号</th></tr>";
		  foreach ($in as $utxo) {
			  if ($utxo->PreviousOutPoint->Index < 0) continue;
			  $s .= "<tr><td>{$utxo->Sequence}</td><td>{$utxo->PreviousOutPoint->Hash}</td><td>{$utxo->PreviousOutPoint->Index}</td></tr>";
		  }
		  $s .= "</table>";

		  $s .= "交易输出：<hr>";

		  $polys = "";

		  $out = json_decode($out);
		  $s .= "<table><tr><th>序号</th><th>转给</th><th>类型</th><th>内容</th></tr>";
		  $i = 0;
		  foreach ($out as $utxo) {
			  $s .= "<tr><td align=center>$i</td><td align=center>{$utxo->PkScript}</td><td align=center>{$utxo->TokenType}</td><td align=center>";
			  if ($utxo->TokenType == 0) $s .= $utxo->Value . "微";
			  else {
				  $s .= "着色：" . PHPWS_Form::formSelect("coloring[]", $colormenu, NULL, false, true, "coloring($i, this.options[this.selectedIndex].value)") . "<br>";
				  $gc = getCoords($utxo->Value);

				  $s .= $gc[0];
				  $polys .= "polygons.push({$gc[1]});\n drawnpolygons.push(null);\n";
			  }
			  $s .= "</td></tr>";
			  $i++;
		  }
		  $s .= "</table>\n";
	  }
	  else if ($polygon) {
		  $i = 0;
		  $s .= "着色：" . PHPWS_Form::formSelect("coloring[]", $colormenu, NULL, false, true, "coloring(0, this.options[this.selectedIndex].value)") . "<br>";
		  $gc = getCoords($polygon);
		  $s .= $gc[0];
		  $polys .= "polygons.push({$gc[1]});\n drawnpolygons.push(null);\n";
	  }

	  $s .= "<script>" . $polys . "</script>\n";
	  $s .= "<div id='map'></div>";

	  $_SESSION['OBJ_layout']->extraHead('<style type="text/css">
			  html, body { height: 100%; margin: 0; padding: 0; }
			  #map { height: 800px; width: 100%; }
			  #map2 { height: 50%; }
			  .reg { background:#bababa; text-decoration:none; }
			  .rega { background:#ff2a2a; text-decoration:none; }
			  .infoleft { float: left; width: 100px; text-wrap:none;}
			  .inforight { float: right; width: 60px; height:18px; text-align: right; color: #ff0000; text-wrap:none; }
		</style>');

	  $_SESSION['OBJ_layout']->extraHead("<script src=./js/map.js language=javascript></script>");

	  $south = $west = 200; $north = $east = -200;

	  $s .= "<script>
				zoom = 1;";
	  $s .= "north = 90; south = -90; east = -180; west = 180; lat = 0; lng = 0; ";
	  $s .= "</script>";
  
	  if (strstr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'zh')) {
		  $_SESSION['OBJ_layout']->extraHead("\n<script src=./js/baidu.js language=javascript></script>");
		  $s .= '<script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=sSelQoVi2L3KofLo1HOobonW"></script>
		  <script type="text/javascript">
				function showMap() {
					// 百度地图API功能
					var map = new BMap.Map("map");            // 创建Map实例
					map.centerAndZoom(new BMap.Point(lng ,lat),1);  //初始化时，即可设置中心点和地图缩放级别。
					map.enableScrollWheelZoom(true);
				}

				showMap();
			</script>';
	  } else {
		    $_SESSION['OBJ_layout']->extraHead("\n<script src=./js/google.js language=javascript></script>");
		  $s .= "<script type='text/javascript'>
			function initMap() {
			  map = new google.maps.Map(document.getElementById('map'), {
				center: {lat: lat, lng: lng},
				zoom: zoom
			  });

			  if (north - south > 1 && east - west > 1) {
				  var bound = new google.maps.LatLngBounds({lat: south, lng: west}, {lat: north, lng: east});
				  map.fitBounds(bound);
			  }
			}

			</script>
			<script async defer
			  src='http://maps.google.cn/maps/api/js?key=AIzaSyBA0rgfD8noKRavCmwcBS8h2_3PpZ2UgWk&callback=initMap'>
			</script>
			  <!-- use http://maps.googleapis.com/ outside china -->";
	  }

	  return $s;
  }

?>
