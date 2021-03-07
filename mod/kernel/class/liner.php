<?php

require_once(PHPWS_SOURCE_DIR . 'core/Error.php');
require_once(PHPWS_SOURCE_DIR . 'core/Text.php');
require_once(PHPWS_SOURCE_DIR . 'core/Array.php');
require_once(PHPWS_SOURCE_DIR . 'core/Form.php');
require_once(PHPWS_SOURCE_DIR . 'mod/product/class/product.php');

class PHPWS_Liner {
  function PHPWS_Liner() {
  }

  function runtime($id, $group, $station, $dest) {
	  $ls = $GLOBALS['core']->query("SELECT * FROM mod_liner WHERE " . ($id?"lineid=$id" : "linegroup=$group") . " ORDER BY linegroup,lineid,seq ASC", true);
	  $line = array();
	  while ($l = $ls->fetchRow()) {
		  if (!$line[$l['linegroup']]) $line[$l['linegroup']] = array();
		  $line[$l['linegroup']][] = $l;
	  }

	  $d = 0x7FFFFFFF;

	  foreach ($line as $g=>$ln) {
		  unset($line[$g][0]['arrival']);
		  unset($line[$g][0]['staytime']);
		  unset($line[$g][sizeof($line[$g])-1]['staytime']);
		  unset($line[$g][sizeof($line[$g])-1]['departure']);

		  $start = NULL; $end = NULL;
		  foreach ($ln as $t) {
			  if ($t['station'] == $station) $start = $t['departure'];
			  if ($t['station'] == $dest) $end = $t['arrival'];
		  }
		  if ($start && $end) {
			  $t = strtotime($end) - strtotime($start);
			  $d = min($d, $t);
		  }
	  }
	  return $d == 0x7FFFFFFF?0 : $d;
  }
  function earliestLine($id, $group, $start, $station) {
	  $ls = $GLOBALS['core']->query("SELECT * FROM mod_liner WHERE " . ($id?"lineid=$id" : "linegroup=$group") . " ORDER BY linegroup,lineid,seq ASC", true);
	  $line = array();
	  while ($l = $ls->fetchRow()) {
		  if (!$line[$l['linegroup']]) $line[$l['linegroup']] = array();
		  $line[$l['linegroup']][] = $l;
	  }

	  $e = '24:00:00';
	  $m = $e;
	  $start = explode(" ", $start);

	  foreach ($line as $g=>$ln) {
		  unset($line[$g][0]['arrival']);
		  unset($line[$g][0]['staytime']);
		  unset($line[$g][sizeof($line[$g])-1]['staytime']);
		  unset($line[$g][sizeof($line[$g])-1]['departure']);

		  foreach ($ln as $t) {
			  if ($t['station'] != $station) continue;
			  if ($t['arrival'] >= '24:00:00') {
				  $t['arrival'] = explode(":", $t['arrival']);
				  $t['arrival'] = ($t['arrival'][0] % 24) . ":" . $t['arrival'][1] . ":" . $t['arrival'][2];
			  }
			  if ($t['arrival'] < $m) $m = $t['arrival'];
			  if ($t['arrival'] > $start[1] && $t['arrival'] < $e) $e =  $t['arrival'];
		  }
	  }
	  if ($e == '24:00:00') {
		  // next day earliest
		  $e = $m;
		  $start[0] = date("Y-m-d", strtotime($start[0] . " + 1 day"));
	  }
	  return $start[0] . ' ' . $e;
  }
  function latestLine($id, $group, $start, $station) {
	  $ls = $GLOBALS['core']->query("SELECT * FROM mod_liner WHERE " . ($id?"lineid=$id" : "linegroup=$group") . " ORDER BY linegroup,lineid,seq ASC", true);
	  $line = array();
	  while ($l = $ls->fetchRow()) {
		  if (!$line[$l['linegroup']]) $line[$l['linegroup']] = array();
		  $line[$l['linegroup']][] = $l;
	  }

	  $e = '00:00:00';
	  $m = $e;
	  $start = explode(" ", $start);

	  foreach ($line as $g=>$ln) {
		  unset($line[$g][0]['arrival']);
		  unset($line[$g][0]['staytime']);
		  unset($line[$g][sizeof($line[$g])-1]['staytime']);
		  unset($line[$g][sizeof($line[$g])-1]['departure']);

		  foreach ($ln as $t) {
			  if ($t['station'] != $station) continue;
			  if ($t['arrival'] >= '24:00:00') {
				  $t['arrival'] = explode(":", $t['arrival']);
				  $t['arrival'] = ($t['arrival'][0] % 24) . ":" . $t['arrival'][1] . ":" . $t['arrival'][2];
			  }
			  if ($t['arrival'] > $m) $m = $t['arrival'];
			  if ($t['arrival'] < $start[1] && $t['arrival'] > $e) $e =  $t['arrival'];
		  }
	  }
	  if ($e == '00:00:00') {
		  $e = $m;
		  $start[0] = date("Y-m-d", strtotime($start[0] . " - 1 day"));
	  }
	  return $start[0] . ' ' . $e;
  }
  function addliner() {
	  extract($_REQUEST);
	  extract($_POST);

	  if ($op == 'newliner' && sizeof($stop)) {
		  $m = $staymins[0]; $h = $stayhours[0] + round($m / 60); $m %= 60;
		  $lineid = $GLOBALS['core']->sqlInsert(array('seq'=>1, 'station'=>$stop[0], 'arrival'=>'00:00:00', 'staytime'=>'00:00:00', 'departure'=>"$h:$m:00", 'ownerOrg'=>$_SESSION['OBJ_user']->org, 'type'=>$means), 'mod_liner', false, true);
		  $GLOBALS['core']->sqlUpdate(array('lineid'=>$lineid), 'mod_liner', 'id', $lineid);

		  $stop[0] = locCache($stop[0]);
		  $stop[0] = $stop[0]['name'];

		  $m += $runmins[0]; $h += $runhours[0] + round($m / 60); $m %= 60;
		  for ($i = 1; $i < sizeof($stop); $i++) {
			$stayhours[$i] += 0;
			$dm = $m + $staymins[$i]; $dh = $h + $stayhours[$i] + round($dm / 60); $dm %= 60;
			$GLOBALS['core']->sqlInsert(array('seq'=>$i + 1, 'station'=>$stop[$i], 'arrival'=>"$h:$m:00", 'staytime'=>"{$stayhours[$i]}:{$staymins[$i]}:00", 'departure'=>"$dh:$dm:00", 'ownerOrg'=>$_SESSION['OBJ_user']->org, 'type'=>$means, 'lineid'=>$lineid), 'mod_liner');
			$stop[$i] = locCache($stop[$i]);
			$stop[$i] = $stop[$i]['name'];
			$m = $dm + $runmins[$i]; $h = $dh + $runhours[$i] + round($m / 60); $m %= 60;
		  }

		  $name = $stop[0] . " - " . $stop[sizeof($stop) - 1];
		  $GLOBALS['core']->sqlUpdate(array('name'=>$name), 'mod_liner', 'lineid', $lineid);
	  }

	  $ps = $GLOBALS['core']->query("SELECT DISTINCT province FROM supply_mod_locations", false);
	  $pv = array('');
	  while ($p = $ps->fetchRow()) $pv[] = $p['province'];

	  $s = "<table id=liner name=liner><tr><th colspan=3>班线</th></tr>";
	  $s .= "<tr><th colspan=3>交通工具：" . PHPWS_Form::formSelect("means", array("车辆", "船舶", "飞机")) . "</th></tr>";
	  $s .= "<tr><th>站点</th><th>停留时间</th><th>运行时间</th></tr></table><hr>";
	  $s .= "省份：" .  PHPWS_Form::formSelect("province", $pv, NULL, true, false, 'chgProvince(this);') . "城市：" .  PHPWS_Form::formSelect("city", array(''), NULL, true, false, 'chgCity(this);') . "站点：" .  PHPWS_Form::formSelect("place", array(), NULL, true, false) . "<a href=#liner onclick='insRow();'>添加站点</a><br>";

	  $s .= PHPWS_Form::formSubmit("提交班线");
	  $s .= PHPWS_Form::formHidden("module", $module) . PHPWS_Form::formHidden("MOD_op", $MOD_op) . PHPWS_Form::formHidden("op", 'newliner');
	  $s = PHPWS_Form::makeForm('addliner', $GLOBALS['SCRIPT'], array($s), "post", FALSE, TRUE);

	  $mins = array();
	  for ($i = 0; $i < 60; $i++) $mins[] = $i;

	  $script = "<script type='text/javascript'>
			var last = 3;

			function insRow() {
				var x=document.getElementById('liner').insertRow(last++);
				var y=x.insertCell(0);
				var z=x.insertCell(1);
				var s=x.insertCell(2);
				var e = document.getElementById('place');
				var d = e.options[e.selectedIndex].value;
				var n = e.options[e.selectedIndex].text;
				y.innerHTML='<input type=hidden name=\"stop[]\" value=\"' + d + '\">' + n;
				z.innerHTML='" . str_replace("\n", ' ', PHPWS_Form::formTextField("stayhours[]", '', 4) . "小时" . PHPWS_Form::formSelect("staymins[]", $mins, 0) . "分钟") . "';
				s.innerHTML='" . str_replace("\n", ' ', PHPWS_Form::formTextField("runhours[]", '', 4) . "小时" . PHPWS_Form::formSelect("runmins[]", $mins, 0) . "分钟") . "';
			}
			</script>";

	  $_SESSION['OBJ_layout']->extraHead("<script>
			  function chgProvince(obj) {
				$.ajax({
					url:'./{$GLOBALS['SCRIPT']}?module=accessories&MOD_op=getlocation&province=' + obj.options[obj.selectedIndex].text,
					type:'get',
					dataType:'json',
					success: function(resp) {
						var cts = document.getElementById('city');
						cts.options.length=1;
						cts.length=1;
						if (resp.cities.length > 0) {
							for (var i = 0; i < resp.cities.length; i++) {
								var option=document.createElement('option');
								option.text = resp.cities[i].text;
								option.value = resp.cities[i].value;
								cts.add(option,null);
							}
						}
						else {
							setPlaces(resp.places);
						}
					}
				 });
			  }
			  
			  function setPlaces(places) {
				var cts = document.getElementById('place');
				cts.options.length=1;
				cts.length=1;
				for (var i = 0; i < places.length; i++) {
					var option=document.createElement('option');
					option.text = places[i].text;
					option.value = places[i].value;
					cts.add(option,null);
				}
			  }

			  function chgCity(obj) {
				var province = document.getElementById('province');
				$.ajax({
					url:'./{$GLOBALS['SCRIPT']}?module=accessories&MOD_op=getlocation&province=' + province.options[province.selectedIndex].text + '&city=' + obj.options[obj.selectedIndex].text,
					type:'get',
					dataType:'json',
					success: function(resp) {
						setPlaces(resp.places);
					}
				 });
			  }
		  </script>");

	  return $s . $script;
  }

  function deleteline() {
	  extract($_REQUEST);
	  $GLOBALS['core']->sqlDelete('mod_liner', array('lineid'=>$id, 'ownerOrg'=>$_SESSION['OBJ_user']->org));
	  return $this->liners();
  }

  function viewline() {
	  extract($_REQUEST);
	  $ls = $GLOBALS['core']->query("SELECT * FROM mod_liner WHERE lineid=$id ORDER BY seq ASC", true);
	  $line = array();
	  while ($l = $ls->fetchRow()) $line[] = $l;
	  unset($line[0]['arrival']);
	  unset($line[0]['staytime']);
	  unset($line[sizeof($line)-1]['staytime']);
	  unset($line[sizeof($line)-1]['departure']);

	  $s = "<table border=1><tr><th colspan=4>{$line[0]['name']}</th></tr><tr><th>站点</th><th>到达</th><th>停留</th><th>出发</th></tr>";
	  foreach ($line as $t) {
		  $st = locCache($t['station']);
		  $s .= "<tr><td>{$st[0]['province']}{$st[0]['city']}{$st[0]['name']}</td><td>{$t['arrival']}</td><td>{$t['staytime']}</td><td>{$t['departure']}</td></tr>";
	  }

	  return $s . "</table>";
  }

  function liners() {
	  extract($_REQUEST);

	  $s = "<a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=addliner>新班线</a><hr>";

	  $fls = $GLOBALS['core']->query("SELECT distinct name,lineid FROM supply_mod_liner WHERE ownerOrg='{$_SESSION['OBJ_user']->org}'", false);

	  $s .= "<table><tr><th>班线名称</th><th>操作</th></tr>";
	  while ($c = $fls->fetchRow()) {
		  $s .= "<tr><td>{$c['name']}</td><td><a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=viewline&id={$c['lineid']}>查看</a> | <a href=./{$GLOBALS['SCRIPT']}?module=$module&MOD_op=deleteline&id={$c['lineid']}>删除</a></td></tr>";
	  }
	  return $s . "</table>";
  }
}

?>