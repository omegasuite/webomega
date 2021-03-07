<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");

  define('OP', 'createrawtransaction');
  define('CoordPrecision', 0x200000);

  function params() {
	  extract($_POST);


	  $script = "<style>
		.pop {
			position:absolute;
			top:0;
			left:0;
			display:none;
			background-color:#fff;
			opacity:1.0;
			padding:20px;
			border:3px;
			border-color:#0505FF;
			border-style:solid;
		}
	  </style>";
			
	  $_SESSION['OBJ_layout']->extraHead($script);

	  $s = "<a href=# onclick='pop(\"vertex\");'>添加顶点</a> | ";
	  $s .= "<a href=# onclick='pop(\"border\");'>添加边界线</a><br>";
	  $s .= "<a href=# onclick='pop(\"polygon\");'>添加多边形</a> |";
	  $s .= "<a href=# onclick='pop(\"right\");'>添加权益</a><br>";
	  $s .= "<a href=# onclick='pop(\"input\");'>添加输入项</a> | ";
	  $s .= "<a href=# onclick='pop(\"output\");'>添加输出项</a><br>";
	  $s .= "<a href=# onclick='pop(\"contract\");'>添加合约调用</a><br>";
	  $s .= "<a href=# onclick='pop(\"output2\");'>产生拆分输入多边形的输出</a><br>";
	  $s .= "<a href=# onclick='pop(\"output3\");'>产生合并输入多边形的输出</a><br>";
	  $s .= "<a href=# onclick='withdraw(\"output\");'>撤回上一步</a><hr>";
	  $s .= '<a href=# onclick="submitrawtx();">' . PHPWS_Form::formButton("生成原始交易") . "</a>";
	  $s .= "<div id=params></div><div style='position:absolute;width:30%;height:200px;left:0;top:200px;'>";

	  $s .= "<div class=pop id=vertex>";
	  $s .= "经度：" . PHPWS_Form::formTextField("lng") . "<br>";
	  $s .= "纬度：" . PHPWS_Form::formTextField("lat") . "<br>";
	  $s .=  PHPWS_Form::formButton("添加", 'add', "addvertex(false)") . " " . PHPWS_Form::formButton("连续添加", 'add', "addvertex(true)");
	  $s .= "</div>";

	  $s .= "<div class=pop id=border>";
	  $s .= "父边界线：" . PHPWS_Form::formTextField("father") . "<br>";
	  $s .= "顶点：" . PHPWS_Form::formTextField("points", array('placeholder'=>'以“,”隔开的一组顶点')) . "<br>";
	  $s .= PHPWS_Form::formButton("添加", 'add', "addborder()");
	  $s .= "</div>";
	  
	  $s .= "<div class=pop id=polygon>";
	  $s .= "边界线：" . PHPWS_Form::formTextField("loop", array('placeholder'=>'以“,”隔开的一组边界线')) . "<br>";
	  $s .= "逆向：" . PHPWS_Form::formCheckBox("reversed", 1) . "<br>";

	  $s .= PHPWS_Form::formButton("添加到当前环", 'add', "addLoop()") . "<br>";
	  $s .= "添加并结束当前：" . PHPWS_Form::formButton("环", 'add', "addpolygon(0)") . " | ";
	  $s .= PHPWS_Form::formButton("多边形", 'add', "addpolygon(1)");
	  $s .= "</div>";
	  
	  $s .= "<div class=pop id=right>";
	  $s .= "父权益：" . PHPWS_Form::formTextField("fatherRight") . "<br>";
	  $s .= "定义：" . PHPWS_Form::formTextField("content") . "<br>";
	  $s .= "肯定性：" . PHPWS_Form::formCheckBox("affirm", 1, 1) . " | ";
	  $s .= "可拆分：" . PHPWS_Form::formCheckBox("divisible", 1, 1) . "<br>";
	  $s .= PHPWS_Form::formButton("添加", 'add', "addright()");
	  $s .= "</div>";
	  
	  $s .= "<div class=pop id=input>";
	  $s .= "UTXO哈希：" . PHPWS_Form::formTextField("txin") . "<br>";
	  $s .= "UTXO序号：" . PHPWS_Form::formTextField("txseq") . "<br>";
	  $s .= PHPWS_Form::formButton("添加", 'add', "addinput()");
	  $s .= "</div>";
	  
	  $s .= "<div class=pop id=output>";
	  $s .= "通证类型：" . PHPWS_Form::formTextField("tokentype", 0) . "<br>";
	  $s .= "权益：" . PHPWS_Form::formTextField("rights") . "<br>";
	  $s .= PHPWS_Form::formButton("添加权益项", 'add', "addrights()") . "<br>";
	  $s .= "接收方：" . PHPWS_Form::formTextField("receiver") . "<br>";
	  $s .= "接收物：" . PHPWS_Form::formTextField("amount") . "<br>";
	  $s .= PHPWS_Form::formButton("添加", 'add', "addoutput()");
	  $s .= "</div>";
	  
	  $s .= "<div class=pop id=output2>";
	  $s .= "输入项：" . PHPWS_Form::formTextField("initem") . "<br>";
	  $s .= "接收方：" . PHPWS_Form::formTextField("receiver2") . "<br>";
	  $s .= "拆分多边形：" . PHPWS_Form::formTextField("addition") . "<br>";
	  $s .= PHPWS_Form::formButton("添加", 'add', "addoutput2()");
	  $s .= "</div>";
	  
	  $s .= "<div class=pop id=output3>";
	  $s .= "输入项：" . PHPWS_Form::formTextField("initem1") . "<br>";
	  $s .= "输入项：" . PHPWS_Form::formTextField("initem2") . "<br>";
	  $s .= "接收方：" . PHPWS_Form::formTextField("receiver3") . "<br>";
	  $s .= PHPWS_Form::formButton("添加", 'add', "addoutput3()");
	  $s .= "</div>";
	  
	  $s .= "<div class=pop id=contract>";
	  $s .= "费用：" . PHPWS_Form::formTextField("callamount") . "<br>";
	  $s .= "合约调用脚本（HEX编码）：" . PHPWS_Form::formTextArea("contractcall", '', 30, 30) . "<br>";
	  $s .= PHPWS_Form::formButton("添加", 'add', "addcontractcall()");
	  $s .= "</div>";
	  
	  $s .= "</div>";
	  $s = "<center>" . $s . "</center>";

	  $s .= "<script type='text/javascript'>
			var ins = ['vertex', 'border', 'polygon', 'right', 'input', 'output', 'output2', 'output3'];
	  </script>";

	  $_SESSION['OBJ_layout']->extraHead('<style type="text/css">
			  html, body { height: 100%; margin: 0; padding: 0; }
			  #map { height: 800px; width: 70%; }
			  #result { height: 800px; width: 70%; position:absolute; top:800px; left:30%; }
			  .reg { background:#bababa; text-decoration:none; }
			  .rega { background:#ff2a2a; text-decoration:none; }
			  .infoleft { float: left; width: 100px; text-wrap:none;}
			  .inforight { float: right; width: 60px; height:18px; text-align: right; color: #ff0000; text-wrap:none; }
		</style>');

	  $_SESSION['OBJ_layout']->extraHead("\n<script src=./js/map.js language=javascript></script>");
	  $s = "<div style='position:relative;'><div style='position:absolute;width:29.5%;top:0;left:0;'>$s</div><div style='position:absolute;top:0;left:30%;' id=map></div><div id=result></div></div>";

	  if (strstr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 'zh')) {
		  $appkey = "";
		  // user baidu map
		  $_SESSION['OBJ_layout']->extraHead("\n<script src=./js/baidu.js language=javascript></script>");
		  $s .= '<script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=' . $appkey . '"></script>
		  <script type="text/javascript">
				zoom = 1;
				north = 90; south = -90; east = -180; west = 180; lat = 0; lng = 0;

				function showMap() {
					// 百度地图API功能
					map = new BMap.Map("map");            // 创建Map实例
					map.centerAndZoom(new BMap.Point(lng ,lat),1);  //初始化时，即可设置中心点和地图缩放级别。
					map.enableScrollWheelZoom(true);

					map.addEventListener("click", getPoint);
				}
				showMap();
			</script>';
	  } else {
		  $appkey = "";
		  $_SESSION['OBJ_layout']->extraHead("\n<script src=./js/google.js language=javascript></script>");
		  $s .= "<script type='text/javascript'>
					zoom = 1;
					north = 90; south = -90; east = -180; west = 180; lat = 0; lng = 0;

					function initMap() {
						map = new google.maps.Map(document.getElementById('map'), {
							center: {lat: lat, lng: lng},
							zoom: zoom
						});

						google.maps.event.addListener(map, 'click', getPoint);

						if (north - south > 1 && east - west > 1) {
							  var bound = new google.maps.LatLngBounds({lat: south, lng: west}, {lat: north, lng: east});
							  map.fitBounds(bound);
						}
					}

				</script>
				<!-- script async defer
				  src='http://maps.googleapis.com/maps/api/js?key=$appkey&callback=initMap'>
				</script -->

				<script async defer
				  src='https://www.google.cn/maps/api/js?key=$appkey&callback=initMap'>
				</script>
				  <!-- use http://maps.googleapis.com/ outside china -->";
	  }

	  return $s;
  }

  function execute() {
	  extract($_REQUEST);
	  extract($_POST);

//	  $GLOBALS["HTTP_RAW_POST_DATA"] =  file_get_contents("php://input");
//	  $data = html_entity_decode($GLOBALS["HTTP_RAW_POST_DATA"], ENT_NOQUOTES, 'UTF-8');

	  $tx = json_decode(stripslashes($data));
	  $txin = array();
	  $txdef = array();
	  $txout = array();

	  $hashmap = array();

	  foreach ($tx as $i=>&$t) {
		  switch ($t->type) {
			  case 'input':
				  if (!$receiver) {
					  $param = array("jsonrpc"=>"1.0", 'method'=>'gettxout', 'params'=>array($t->txin, $t->txseq + 0, false), 'id'=>$_SESSION['SES_RPC']->id);
					  $param = json_encode($param);
					  $ret = $_SESSION['SES_RPC']->post($param);
					  $in = json_decode($ret);
					  $_SESSION['SES_RPC']->id++;
					  $in = $in->result;
					  $t->result = $in;
					  if ((($in->tokentype & 0x3) == 0) && $in->scriptPubKey->type == "pubkeyhash") {
						  $receiver = substr($in->scriptPubKey->hex, 0, 42);
					  }
				  }
		  }
	  }

	  $vertices = array();

	  foreach ($tx as $i=>&$t) {
		  switch ($t->type) {
			  case 'vertex':
				  $hashmap[$i + 1] = sizeof($vertices);
				  $vertices[] = array('lat'=>$t->lat + 0, 'lng'=>$t->lng + 0);
				  break;
			  case 'border':
				  $hashmap[$i + 1] = sizeof($txdef);
				  // it is an index into current data, use its hash
				  if ($t->father && strlen($t->father) < 20) $t->father = '[' . $hashmap[$t->father] .']';
				  $t->begin = $vertices[$hashmap[$t->begin]];
				  $t->end = $vertices[$hashmap[$t->end]];
//				  if (strlen($t->begin) < 20) $t->begin = '[' . $hashmap[$t->begin] .']';
//				  if (strlen($t->end) < 20) $t->end = '[' . $hashmap[$t->end] .']';
				  $txdef[] = array('deftype'=>1, 'data'=>array('father'=>$t->father, 'begin'=>$t->begin, 'end'=>$t->end));
				  break;
			  case 'polygon':
				  $hashmap[$i + 1] = sizeof($txdef);
				  $p = array();
				  foreach ($t->polygon as &$loop) {
					  $lp = array();
					  foreach ($loop as &$l) {
						  $l->border = strtolower($l->border);
						  if ($l->reversed) {
							  if (strlen($l->border) < 20) $l->border = '[' . $hashmap[$l->border] .']R';
							  else {
								  $b0 = (ord($l->border{1}) - ord($l->border{1} <= '9'?'0' : 'W')) | 1;
								  $b0 = chr($b0 + ord($b0 <= 9?'0' : 'W'));
								  $l->border = $l->border{0} . $b0 . substr($l->border, 2);
							  }
						  }
						  else if (strlen($l->border) < 20) $l->border = '[' . $hashmap[$l->border] .']';
						  $lp[] = $l->border;
					  }
					  $p[] = $lp;
				  }
				  $txdef[] = array('deftype'=>2, 'data'=>array('loops'=>$p));
				  break;
			  case 'right':
				  $hashmap[$i + 1] = sizeof($txdef);
				  if ($t->father && strlen($t->father) < 20) $t->father = '[' . $hashmap[$t->father] .']';
				  $txdef[] = array('deftype'=>4, 'data'=>array('father'=>$t->father, 'desc'=>$t->desc, 'attrib'=>($t->affirm?1:0) + ($t->divisible?2:0)));
				  break;
			  case 'rightset':
				  foreach ($t->rights as &$r) {
					  if (strlen($r) < 20) $r = '[' . $hashmap[$r] .']';
				  }
				  $txdef[] = array('deftype'=>5, 'data'=>array("rights"=>$t->rights));
				  break;
			  case 'input':
				  $txin[] = array("txid"=>$t->txin, "vout"=>$t->txseq + 0);
				  if ($t->payment) {
					  $in = $t->result;
					  if (($in->tokentype & 0x3) == 0 && $in->value->Val >= $t->payment + $t->payback && $in->scriptPubKey->type == "pubkeyhash") {
						  $to = array("tokentype"=>$in->tokentype, "value"=>array("val"=>$t->payment + 0.0));
						  $txout[] = array("mjt7WtJzcrG8rcNdn8WC1UveLRpqP69cmp"=>$to);
						  if ($t->payback != 0) {
							  $back = array("tokentype"=>$in->tokentype, "value"=>array("val"=>$t->payback + 0.0));
							  $txout[] = array(substr($in->scriptPubKey->hex, 0, 42)=>$back);
						  }
					  }
				  }
				  break;
			  case 'output':
				  if (strlen($t->amount) > 20 || !strstr($t->amount, '.')) {
					  if (strlen($t->amount) <= 20) {
						  $t->amount = '[' . $hashmap[$t->amount] .']';
					  }
					  $to = array("tokentype"=>3, "value"=>array("Hash"=>$t->amount), "rights"=>$t->rightset);
				  } else {
					  $to = array("tokentype"=>$t->tokentype?$t->tokentype : 0, "value"=>array("val"=>$t->amount + 0.0), "rights"=>$t->rightset);
				  }
				  if ($t->script) $to['script'] = $t->script;
				  if (!$t->receiver) $t->receiver = $receiver;
				  $txout[] = array($t->receiver=>$to);
				  break;

			  case 'divide':
				  if (strlen($t->addition) <= 20) {
					  $maddition = '[' . $hashmap[$t->addition] .']';
				  }
				  else $maddition = $t->addition;

				  if (!$t->receiver) {
					  $t->receiver2 = "mjt7WtJzcrG8rcNdn8WC1UveLRpqP69cmp";
					  $t->receiver = $receiver;
				  } else $t->receiver2 = $t->receiver;

				  if ($t->app) {
					  $app = $GLOBALS['core']->sqlSelect("mod_apps", 'id', $t->app);

					  // $app[0]['land'] is the polygon hash of land available in the app
					  // $app[0]['landtx'] is the UTXO of land available in the app.
					  // $app[0]['utxo'] is the initial land utxo for the app. its vout=1
					  // is the land available for further right division.

					  $utxo['polygon'] = getDefine($app[0]['land']);
					  $utxo['rightset'] = $app[0]['righthash'];

					  $utxo = (object) $utxo;

					  if (!$app[0]['landtx']) $txin[] = unserialize($app[0]['utxo']);
					  else $txin[] = unserialize($app[0]['landtx']);
				  } else $utxo = getUtxo($txin[$t->initem]);

				  // split out polygon
				  $txout[] = array($t->receiver2=>array("tokentype"=>3, "value"=>array("Hash"=>$maddition), "rights"=>$utxo->rightset));		
				  
				  $pdef = sizeof($txdef);

				  if (strlen($t->addition) <= 20) $rpoly = $txdef[$hashmap[$t->addition]]['data']['loops'];
				  else $rpoly = getDefine($t->addition);

				  foreach ($rpoly as $lp) {
					  $nlp = array();
					  for ($i = sizeof($lp) - 1; $i >= 0; $i--) {
						  $l = $lp[$i];
						  if (preg_match("/(\\[[0-9]+\\])(R?)/", $l, $mtch)) {
							  $nlp[] = $mtch[1] . ($mtch[2] == "R"?"" : "R");
						  }
						  else {
							  $b0 = (ord($l{1}) - ord($l{1} <= '9'?'0' : 'W'));
							  if ($b0 & 1) $b0 &= 0xFE;
							  else $b0 |= 1;
							  $b0 = chr($b0 + ord($b0 <= 9?'0' : 'W'));
							  $nlp[] = $l->border{0} . $b0 . substr($l->border, 2);
						  }
					  }
					  $utxo->polygon[] = $nlp;
				  }

				  $txdef[] = array('deftype'=>2, 'data'=>array('loops'=>$utxo->polygon));

				  // remaining polygon
				  $txout[] = array($t->receiver=>array("tokentype"=>3, "value"=>array("Hash"=>"[$pdef]"), "rights"=>$utxo->rightset));
				  break;
			  case 'merge':
				  $utxo1 = getUtxo($txin[$t->initem[0]]);
				  $utxo2 = getUtxo($txin[$t->initem[1]]);

				  $pdef = sizeof($txdef);
				  $txdef[] = array('deftype'=>2, 'data'=>array('loops'=>merge($utxo1->polygon, $utxo2->polygon)));

				  $txout[] = array($t->receiver=>array("tokentype"=>3, "value"=>array("Hash"=>"[$pdef]"), "rights"=>$utxo1->rightset));
				  break;
		  }
	  }
	  return array($txin, $txdef, $txout, 0);
  }

  function getUtxo($outpoint) {
	  $param = array("jsonrpc"=>"1.0", 'method'=>'gettxout', 'params'=>array($outpoint['txid'], $outpoint['vout'], false), 'id'=>$_SESSION['SES_RPC']->id);
	  $ret = json_decode($_SESSION['SES_RPC']->post(json_encode($param)));
	  $_SESSION['SES_RPC']->id++;

	  // need format conversion
	  $ret->polygon = getDefine($ret->result->value->Hash);
	  $ret->rights = $ret->result->rights;

	  return $ret;
  }

  function getDefine($hash) {
	  $param = array("jsonrpc"=>"1.0", 'method'=>'getdefine', 'params'=>array(2, $hash, false), 'id'=>$_SESSION['SES_RPC']->id);
	  $ret = json_decode($_SESSION['SES_RPC']->post(json_encode($param)));
	  $_SESSION['SES_RPC']->id++;

	  // need format conversion
	  return ((array)$ret->result->definition)[$hash]->polygon;
  }

  function showResult($res) {
	  if ($res->error) {
		  return "Op " . $res->id . " returned width error: " . print_r($res->error, true);
	  }
	  
	  require_once(PHPWS_SOURCE_DIR . "mod/rpc/class/MsgTx.php");

	  $raw = strtolower($res->result);
	  $r = new Reader($raw);
	  $r->StrToByte();

	  $tx = new MsgTx;
	  $tx->BtcDecode($r);

	  $_SESSION['SES_RPC']->tx = $tx;
	  $_SESSION['SES_RPC']->tx->rawtx = $res->result;

//	  $tx->visual = "<a href=./index.php?module=rpc&MOD_op=visual&in=" . json_encode($tx->TxIn) . "&out=" . json_encode($tx->TxOut) . " target=_blank>交易图解</a>";

	  exit("<pre>" . print_r($tx, true) . "</pre>");
  }
?>
