<?php

require_once(PHPWS_SOURCE_DIR . "core/Text.php");
require_once(PHPWS_SOURCE_DIR . "core/Error.php");
require_once(PHPWS_SOURCE_DIR . "mod/rpc/class/MsgTx.php");
require_once(PHPWS_SOURCE_DIR . "mod/rpc/class/Base58.php");

define('OP', 'vmdebugger');

  function params() {
	  $s .= ".源文件：" . PHPWS_Form::formFile("srcfile") . "<br>";
	  $s .= ".dbg文件：" . PHPWS_Form::formFile("src") . "<br>";

	  $s .= PHPWS_Form::formSubmit("加载");
	  $s .= PHPWS_Form::formHidden('module', 'rpc') . PHPWS_Form::formHidden('MOD_op', OP) . PHPWS_Form::formHidden('directexec', 1);
	  return "<center>" . PHPWS_Form::makeForm('addProduct', "index.php", array($s), "post", FALSE, TRUE) . "</center>";
  }

  function execute() {
	  extract($_REQUEST);
	  extract($_POST);

	  set_time_limit(0);

	  $_SESSION['SES_RPC']->Host = "localhost:18840";
	  $_SESSION['SES_RPC']->chain = "testnet";

	  if (!$_FILES['src'] && !$_SESSION['VMDEBUG']) {
		  return params();
	  }

	  if ($_FILES['srcfile']) {
		  $fname = uploadfile('srcfile');
		  $srccode = file($fname);
		  $_SESSION['VMSRCCODE'] = $srccode;
	  }

	  if ($_FILES['src']) {
		  $fname = uploadfile('src');
		  $src = file_get_contents($fname);
		  $_SESSION['VMDEBUG'] = (array) json_decode($src);
	  }

	  $s = "<div style='position:relative;width:1200px;height:700px;'><div style='position:absolute;width:60%;'><span id=contractstatus style='width:50%;'></span> <span style='width:30%;float:right;'><button id=stopact disabled=disabled onclick='stop();'><img src=./images/blank.png style='background-color:red;' width=16 height=16></button> ";
	  $s .= "<button id=goact disabled=disabled onclick='go();'><img src=./images/greenarrow.jpg width=16 height=16></button>";
	  $s .= "<button id=stepact disabled=disabled onclick='step();'><img src=./images/downarrow.PNG width=16 height=16></button> </span><pre id=srccode style='position:absolute;width:100%;left:20px;height:600px;overflow:auto;top:30px;'>";

	  $s .= "<table class=mytable id=codetable>";
	  $incline = array();

	  $bpl = "var bplist = [];\n";
	  foreach ($_SESSION['VMDEBUG'] as $code) {
		  foreach ($code->lines as $m) {
			  $incline[$m[1]] = true;
			  $bpl .= "bplist.push([{$m[0]}, {$m[1]}]);\n";
		  }
	  }

	  $bpl .= "var codes = [];\n";
	  foreach ($_SESSION['VMDEBUG'] as $code) {
		  $vars = ""; $glue = "";
		  foreach ($code->vars as $u) {
			  list($name, $v) = each($u);
			  $vars .= $glue . "{name:'$name', loc:\"{$v->loc}\", size:{$v->size}}";
			  $glue = ",";
		  }
		  $bpl .= "codes.push({code:'{$code->code}', begin:{$code->begin}, end:{$code->end}, vars:[$vars]});\n";
	  }

	  $s .= "<tr><td with=20px height=18px></td><td width=38px align=center height=18px></td><td width=600px height=18px>\t\t\t<b><i>" . $_FILES['srcfile']['name'] . "</i></b></td></tr>";
	  foreach ($_SESSION['VMSRCCODE'] as $i=>$ln) {
		  $s .= "<tr><td height=18px>";
		  if ($incline[$i + 1]) 
			  $s .= "<input type='checkbox' value=" . ($i + 1) . " onchange='checkField(this.checked, this.value);'>";
		  $s .= "</td><td height=18px><i>" . ($i + 1) . "</i></td><td height=18px>$ln</td></tr>";
	  }
	  $s .= "</table>";

	  $s .= "</pre></div>";
	  $s .= "<div style='position:absolute;width:35%;right:20px;height:100%;'>Call Stack<div id=stack style='width:100%;height:80px;border:2px solid #000;overflow-x:auto;overflow-y:auto;'></div>
			Data<textarea rows=10 id=data style='width:100%;border:2px solid #000;'></textarea>
			Log<div id=log style='width:100%;height:250px;border:2px solid #000;overflow-x:auto;overflow-y:auto;'></div>
		  </div></div>";
	  $auth = "Basic " . base64_encode($_SESSION['SES_RPC']->User . ":" . $_SESSION['SES_RPC']->Pass);

	  $_SESSION['OBJ_layout']->extraHead("
	  <style>
	    .mytable table{
            border-collapse: collapse;
            box-sizing: border-box;
        }
        .mytable td{
            height: 16px;
            line-height: 18px !important;
        }
		</style>
		<script>
	    var allbtns = ['stopact', 'goact', 'stepact'];
	    var gobtns = ['goact', 'stepact'];
		var lastbreak = -1;
		var constructor = 0;	// is current code a constructor?
	  	var pendingbrs = [];
		var contractloaded = false;
		var attachpending = null;

		$bpl

		function linemapping(line) {
			for (var i = 0; i < bplist.length; i++) {
				if (bplist[i][0] == line) return bplist[i][1];
			}
			return 0;
		}

		function highlight(line) {
			var e = document.getElementById('srccode');

			line = linemapping(line);
			if (line == 0) return;

			var p = line - 10;
			if (p > 0) e.scrollTop = p * 20;

			var e = document.getElementById('codetable');
			var row = e.rows[line];
			var c = row.cells[2];
			c.style.background = '#404080';
		}

		function unhighlight(line) {
			if (line == 0) return;

			line = linemapping(line);
			var e = document.getElementById('codetable');
			var row = e.rows[line];
			var c = row.cells[2];
			c.style.background = '';
		}

		function checkField(ischecked, value) {
			if (!contractloaded) {
				if (ischecked) pendingbrs.push(value);
				else {
					for (var i = 0; i < pendingbrs.length; i++) {
						if (pendingbrs[i] == value) {
							pendingbrs[i] = 0;
							return;
						}
					}
				}
				return;
			}
			if (ischecked) {
				setbreakpoint(value);
			} else {
				unsetbreakpoint(value);
			}
		}

		function gclosure(a, b) {
			var d = document.getElementById('data');

			debugcall(a,
				function(resp) {
					if (!resp) {
						e.innerHTML += 'Unexpected response<br>';
						if (e.scrollHeight > e.clientHeight) e.scrollTop = e.scrollHeight - 250;
						allbtns.forEach(function (v, index, arr) {
							document.getElementById(v).disabled = 'disabled';
						});
						return;
					}

					d.innerHTML += b.name + ': ' + resp.result + '\\n';
				});
		}

	  	function flow(op) {
			gobtns.forEach(function (v, index, arr) {
				document.getElementById(v).disabled = 'disabled';
			});

			var e = document.getElementById('log');
			e.innerHTML += '$ ' + op + '<br>';

			if (lastbreak >= 0) unhighlight(lastbreak);

			if (e.scrollHeight > e.clientHeight) e.scrollTop = e.scrollHeight - 250;

			debugcall('\"' + op + '\"', function(resp) {
				if (!resp || !resp.result) {
					e.innerHTML += 'Unexpected response<br>';
					allbtns.forEach(function (v, index, arr) {
						document.getElementById(v).disabled = 'disabled';
					});
					attaching();
					return;
				}
				resp = resp.result;
				
				if (!resp.result.match('Break at inst '))
					e.innerHTML += resp.result + '<br>';

//					e.innerHTML += 'Break at line ' + linemapping(resp.line).toString() + '<br>';

				if (e.scrollHeight > e.clientHeight) e.scrollTop = e.scrollHeight - 250;
				if (op == 'stop' || resp.result == 'Terminated') {
					gobtns.forEach(function (v, index, arr) {
						document.getElementById(v).disabled = false;
					});
					if (op != 'stop') attaching();
					return;
				}

				highlight(resp.line);
				lastbreak = resp.line;
				
				var d = document.getElementById('data');
				d.innerHTML = '';

				for (var i = 0; i < codes.length; i++) {
					var code = codes[i];
					if (lastbreak < code.begin || lastbreak > code.end)
						  continue;
					for (var j = 0; j < code.vars.length; j++) {
						var u = code.vars[j];
						var s = '\"getdata\",\"' + u.loc + '\",' + u.size.toString();

						gclosure(s, u);
					}
				}

				var st = document.getElementById('stack');
				st.innerHTML = '';

				debugcall('\"getstack\"',
					function(resp) {
						if (!resp) {
							e.innerHTML += 'Unexpected response<br>';
							if (e.scrollHeight > e.clientHeight) e.scrollTop = e.scrollHeight - 250;
							allbtns.forEach(function (v, index, arr) {
								document.getElementById(v).disabled = 'disabled';
							});
							return;
						}
						for (var i = 0; i < resp.result.length; i++) {
							var t = resp.result[i];
							for (var j = 1; j < codes.length; j++) {
							  	  if (t < codes[j].begin || t > codes[j].end)
									  continue;
								  st.innerHTML += codes[j].code + '<br>';
							}
						}
			  });
			  
			  gobtns.forEach(function (v, index, arr) {
				  document.getElementById(v).disabled = false;
			  });
			});
		}

	  	function stop() {
			flow('stop');
		}
	  	function go() {
			flow('go');
		}
	  	function step() {
			flow('step');
		}

	  	function breakpoint() {
			var line = document.getElementById('breakpoint').value;
			setbreakpoint(line);
		}

	  	function setbreakpoint(line) {
			var e = document.getElementById('log');
			e.innerHTML += '$ breakpoint ' + line + '<br>';
			if (e.scrollHeight > e.clientHeight) e.scrollTop = e.scrollHeight - 250;
			bphandler('breakpoint', line);
		}

		var bodycode = {$_SESSION['VMDEBUG'][0]->body};

	  	function bphandler(fn, line) {
			var e = document.getElementById('log');
			var offset = 0;
			if (constructor) offset = bodycode;
				  
			for (var j = 0; j < bplist.length; j++) {
				  if (bplist[j][1] == line) {
					  var s = bplist[j][0] + offset;
					  s = s.toString();
					  debugcall('\"' + fn + '\",\"\",' + s,
						  function(resp) {
							e.innerHTML += resp.result + '<br>';
					  });
					  return;
				  }
			}
			if (constructor)
				e.innerHTML += 'Code line ' + line + ' does not exist. It is in constructor?<br>';
			else e.innerHTML += 'Code line ' + line + ' does not exist.<br>';
		}

	  	function clearbreakpoint() {
			var line = document.getElementById('clearbreakpoint').value;
			unsetbreakpoint(line);
		}

	  	function unsetbreakpoint(line) {
			var e = document.getElementById('log');
			e.innerHTML += '$ clearbreakpoint ' + line + '<br>';
			if (e.scrollHeight > e.clientHeight) e.scrollTop = e.scrollHeight - 250;

			bphandler('clearbreakpoint', line, function(resp) {
					e.innerHTML += resp.result + '<br>';
			});
		}

		var sesid = 100;

		function debugcall(fn, succ) {
			var d = '{\"jsonrpc\":\"1.0\",\"method\":\"vmdebug\",\"params\":[' + fn + '],\"id\":' + sesid + '}';
			var r = $.ajax({
				url:'http://127.0.0.1:18840',
				global:true,
				type:'post',
				dataType:'json',
				data: d,
				beforeSend: function(request) {
					request.setRequestHeader('Authorization', '$auth');
				},
				success: succ,
			});
			sesid++;
			return r;
		}

		window.onbeforeunload = function () {
			if (attachpending != null) {
				attachpending = null;
				$.ajax({
					url:'http://127.0.0.1:18840',
					global:true,
					type:'post',
					dataType:'json',
					data: '{\"jsonrpc\":\"1.0\",\"method\":\"vmdebug\",\"params\":[\"detach\"],\"id\":' + sesid + '}',
					beforeSend: function(request) {
						request.setRequestHeader('Authorization', '$auth');
					},
				});
				// wait for sometime to let detach finish
				for (var i = 0; i < 10000000; i++) {
					attachpending = null;
				}
			} else stop();
		}

	  	function attached(resp) {
			var e = document.getElementById('contractstatus');
			attachpending = null;

			if (resp && resp.result[0] == 'C') {
				if (resp.result[1] == 'C') constructor = 1;

				e.innerHTML = 'contract ready to go: ' + resp.result.substr(2);
				if (e.scrollHeight > e.clientHeight) e.scrollTop = e.scrollHeight - 250;
				allbtns.forEach(function (v, index, arr) {
					document.getElementById(v).disabled = false;
				});

				contractloaded = true;
				for (var i = 0; i < pendingbrs.length; i++) {
					if (pendingbrs[i] != 0) {
						setbreakpoint(pendingbrs[i]);
					}
				}
				pendingbrs = [];

				return;
			}
						
			if (resp) {
				e.innerHTML = 'Unexpected: ' + resp.result;
			} else {
				e.innerHTML = 'Unexpected empty attach result';
			}
			if (e.scrollHeight > e.clientHeight) e.scrollTop = e.scrollHeight - 250;
		}

	  	function attaching() {
			var e = document.getElementById('contractstatus');
			e.innerHTML = 'Wait for contract';
			if (e.scrollHeight > e.clientHeight) e.scrollTop = e.scrollHeight - 250;

			allbtns.forEach(function (v, index, arr) {
				document.getElementById(v).disabled = 'disabled';
			});
			attachpending = debugcall('\"attach\"', attached);
		}

		$(document).ready(function () {
			var e = document.getElementById('contractstatus');
			e.innerHTML = 'Wait for contract';
			attachpending = debugcall('\"attach\"', attached);
		});
	  </script>");

	  return $s;
  }

?>
