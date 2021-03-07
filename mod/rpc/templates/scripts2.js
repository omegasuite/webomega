	var link;
	var map;
	var north, south, east, west, type, zoom = 1;
	var extent = {east: -1, west:1, south:1, north:-1 };
	var markerextent = {east: -1, west:1, south:1, north:-1 };
	var infowindow = null;
	var lat = 0, lng = 0;
	var tries = 1;
	var bound = null;
	var minw = -1.0;

	var total = [];
	var loop = [];
	var polygon = [];
	var outrights = [];
	var ins = ['vertex', 'border', 'polygon', 'right', 'input', 'output', 'output2', 'output3'];
	var seq = 1;
	var blockstack = [];

	var drawnpolygons = [];
	var polygons = [];
	var vertexmode = false;

	function pop(s) {
		for (var i = 0; i < 8; i++) {
			document.getElementById(ins[i]).style.display = 'none';
		}
		document.getElementById(s).style.display = 'block';
		vertexmode = (s == 'vertex');
	}

	function withdraw() {
		if (blockstack.length > 0) {
			if (blockstack[blockstack.length-1].block != null)
				document.getElementById(blockstack[blockstack.length-1].block).style.display = 'none';
			switch (blockstack[blockstack.length-1].type) {
				case 0: var m = blockstack[blockstack.length-1].marker;
					if (m) m.setMap(null);
					total.length--;
					break;
				case 1: loop.length--;
					break;
				case 2: outrights.length--;
					break;
				case 3: loop = polygon[polygon.length - 1];
					polygon.length--;
					break;
				case 4: polygon = total[total.length - 1].polygon;
					total.length--;
					break;
			}
			blockstack.length--;
		}
	}

	function addvertex() {
		var flat = document.getElementById('lat').value;
		var flng = document.getElementById('lng').value;
		var lat = parseInt(flat * 2097152);
		var lng = parseInt(flng * 2097152);

		pointDropEnd();

		if (flat !='' && flng != '') {
			total.push({'type':'vertex', 'lng':lng, 'lat':lat, 'seq':seq});
			blockstack.push({'block':'block' + seq, 'type':0, 'marker':setMarker(map, parseFloat(flat), parseFloat(flng), total.length)});

			document.getElementById('params').innerHTML += '<div id=block' + seq + '>顶点[' + total.length + ']：<input type=text id=lng[' + seq + '] value=' + flng + '>, <input type=text id=lat[' + seq + '] value=' + flat + '><br></div>';
			seq++;
		}

		if (!document.getElementById('cont').checked) {
			document.getElementById('vertex').style.display = 'none';
			vertexmode = false;
		}
	}
	
	function addborder() {
		var father = document.getElementById('father').value;
		var points = document.getElementById('points').value.split(',');

		if (points.length < 2) {
			alert('至少需要2个顶点');
			return;
		}

		end = points[0];
		for (var i = 1; i < points.length; i++) {
			begin = end; end = points[i];
			
			total.push({'type':'border', 'father':father, 'begin':begin, 'end':end, 'seq':seq});
			blockstack.push({'block':'block' + seq, 'type':0});

			document.getElementById('params').innerHTML += '<div id=block' + seq + '>边界线[' + total.length + ']：<input type=text id=father[' + seq + ']  placeholder=父边界线 value=' + father + '><br>(<input type=text id=begin[' + seq + '] value=' + begin + '> , <input type=text id=end[' + seq + '] value=' + end + '>)<br></div>';
			seq++;
		}

		document.getElementById('border').style.display = 'none';
	}
	
	function addLoop() {
		var lps = document.getElementById('loop').value.split(',');
		var reversed = document.getElementById('reversed').checked;

		for (var i = 0; i < lps.length; i++) {
			lp = lps[i];
			loop.push({'border':lp, 'reversed':reversed, 'seq':seq});
			blockstack.push({'block':'block' + seq, 'type':1});

			document.getElementById('params').innerHTML += '<div id=block' + seq + '>环[' + (polygon.length + 1) + ']+：' + lp + ', ' + reversed + '<br></div>';
			seq++;
		}
		document.getElementById('polygon').style.display = 'none';
	}
	function addpolygon(close) {
debugger;
		addLoop();

		polygon.push(loop);
		loop = [];
		blockstack.push({'block':null, 'type':3});

		if (close) {
			total.push({'type':'polygon', 'polygon':polygon, 'seq':seq});
			document.getElementById('params').innerHTML += '<div id=block' + seq + '>多边形[' + total.length + ']：' + JSON.stringify(polygon) + '<br>';
			blockstack.push({'block':'block' + seq, 'type':4});
			seq++;

			polygon = [];
		}

		document.getElementById('polygon').style.display = 'none';
	}
	function addright(s) {
		var father = document.getElementById('fatherRight').value;
		var desc = document.getElementById('content').value;
		var affirm = document.getElementById('affirm').checked;
		var divisible = document.getElementById('divisible').checked;
		total.push({'type':'right', 'father':father, 'desc':desc, 'affirm':affirm?1:0, 'divisible':divisible?1:0, 'seq':seq});

		document.getElementById('params').innerHTML += '<div id=block' + seq + '>权利[' + total.length + ']：<input type=text id=father[' + seq + '] placeholder=父权益 value=' + father + '> 否定：<input type=text id=desc[' + seq + '] value=' + desc + '> (<input type=checkbox id=affirm[' + seq + '] ' + (affirm?'checked' : '') + '>' +  + ', 可分：<input type=checkbox id=divisible[' + seq + '] ' + (divisible?'checked' : '') + '<br></div>';
		blockstack.push({'block':'block' + seq, 'type':0});
		seq++;
		document.getElementById('right').style.display = 'none';
	}

	function addinput(s) {
		var txin = document.getElementById('txin').value;
		var txseq = document.getElementById('txseq').value;
		total.push({'type':'input', 'txin':txin, 'txseq':txseq, 'seq':seq});

		document.getElementById('params').innerHTML += '<div id=block' + seq + '>输入[' + total.length + ']：<input type=text id=txin[' + seq + '] value=' + txin + '>, <input type=text id=txseq[' + seq + '] value=' + txseq + '><br></div>';
		blockstack.push({'block':'block' + seq, 'type':0});
		seq++;
		document.getElementById('input').style.display = 'none';
	}
	function addrights(s) {
		var rights = document.getElementById('rights').value;
		outrights.push(rights);

		document.getElementById('params').innerHTML += '<div id=block' + seq + '>权益项[' + total.length + ']：' + rights + '<br></div>';
		blockstack.push({'block':'block' + seq, 'type':2});
		seq++;
		document.getElementById('output').style.display = 'none';
	}

	function addoutput(s) {
		var receiver = document.getElementById('receiver').value;
		var amount = document.getElementById('amount').value;
		var rights = document.getElementById('rights').value;

		if (rights != '') {
			outrights.push(rights);
		}

		total.push({'type':'output', 'receiver':receiver, 'amount':amount, 'rights':outrights, 'seq':seq});
		outrights = [];

		document.getElementById('params').innerHTML += '<div id=block' + seq + '>输出[' + total.length + ']：<input type=text id=receiver[' + seq + '] value=' + receiver + '> , <input type=text id=amount[' + seq + '] value=' + amount + '><br></div>';
		blockstack.push({'block':'block' + seq, 'type':0});
		seq++;
		document.getElementById('output').style.display = 'none';
	}
	function addoutput2(s) {
		var receiver = document.getElementById('receiver2').value;
		var addition = document.getElementById('addition').value;
		var initem = document.getElementById('initem').value;

		if (rights != '') {
			outrights.push(rights);
		}

		total.push({'type':'divide', 'receiver':receiver, 'addition':addition, 'initem':initem, 'seq':seq});
		outrights = [];

		document.getElementById('params').innerHTML += '<div id=block' + seq + '>拆分[' + total.length + ']：<input type=text id=receiver2[' + seq + '] value=' + receiver + '> , <input type=text id=initem[' + seq + '] value=' + initem + '>， <input type=text id=addition[' + seq + '] value=' + addition + '><br></div>';
		blockstack.push({'block':'block' + seq, 'type':0});
		seq++;
		document.getElementById('output2').style.display = 'none';
	}
	function addoutput3(s) {
		var receiver = document.getElementById('receiver3').value;
		var initem1 = document.getElementById('initem1').value;
		var initem2 = document.getElementById('initem2').value;

		if (rights != '') {
			outrights.push(rights);
		}

		total.push({'type':'merge', 'receiver':receiver, 'initem':[initem1, initem2], 'seq':seq});
		outrights = [];

		document.getElementById('params').innerHTML += '<div id=block' + seq + '>拆分[' + total.length + ']：<input type=text id=receiver3['+ seq + '] value=' + receiver + '> , <input type=text id=initem1[' + seq + '] value=' + initem1 + '>， <input type=text id=initem2[' + seq + '] value=' + initem2 + '><br></div>';
		blockstack.push({'block':'block' + seq, 'type':0});
		seq++;
		document.getElementById('output3').style.display = 'none';
	}

	function submitrawtx() {
		for (var i = 0; i < total.length; i++) {
			var seq = total[i].seq;
			switch (total[i].type) {
				case 'vertex':
					var flat = document.getElementById('lat[' + seq + ']').value;
					var flng = document.getElementById('lng[' + seq + ']').value;
					total[i].lng = parseInt(flng * 2097152);
					total[i].lat = parseInt(flat * 2097152);
					break;
				case 'border':
					total[i].father = document.getElementById('father[' + seq + ']').value;
					total[i].begin = document.getElementById('begin[' + seq + ']').value;
					total[i].end = document.getElementById('end[' + seq + ']').value;
					break;
				case 'right':
					total[i].father = document.getElementById('father[' + seq + ']').value;
					total[i].desc = document.getElementById('desc[' + seq + ']').value;
					total[i].affirm = document.getElementById('affirm[' + seq + ']').checked;
					total[i].divisible = document.getElementById('divisible[' + seq + ']').checked;
					break;
				case 'input':
					total[i].txin = document.getElementById('txin[' + seq + ']').value;
					total[i].txseq = document.getElementById('txseq[' + seq + ']').value;
					break;
				case 'output':
					total[i].receiver = document.getElementById('receiver[' + seq + ']').value;
					total[i].amount = document.getElementById('amount[' + seq + ']').value;
					break;
				case 'divide':
					total[i].receiver = document.getElementById('receiver2[' + seq + ']').value;
					total[i].initem = document.getElementById('initem[' + seq + ']').value;
					total[i].addition = document.getElementById('addition[' + seq + ']').value;
					break;
				case 'merge':
					total[i].receiver = document.getElementById('receiver3[' + seq + ']').value;
					total[i].initem[0] = document.getElementById('initem1[' + seq + ']').value;
					total[i].initem[1] = document.getElementById('initem2[' + seq + ']').value;
					break;
			}
		}
		var d = JSON.stringify(total);
//		document.getElementById('data').value = d;

		$.ajax({
			url:'./index.php?module=rpc&MOD_op=createrawtransaction&submiting=1',
			type:'post',
			data:{ 'module': "rpc", 'MOD_op' : "createrawtransaction", 'submitting' : 1, 'data': d },
			success: function(resp) {
				document.body.innerHTML += resp;
			},
			error: function(a, v, c) {
				alert("系统错误。");
			}
		});

//		total = [];

//		var fm = document.getElementById('addProduct');
//		fm.submit();
	}


	function updatevertex() {
		for (var i = 0; i < 21; i++) {
			ps[i].lat = parseFloat(document.getElementById('lat[' + i + ']').value);
			ps[i].lng = parseFloat(document.getElementById('lng[' + i + ']').value);
		}

		setPolygon(ps);
	}


  function loadScript(url) {
	var script = document.createElement("script");
	var head = document.head || document.getElementsByTagName("head")[0] || document.documentElement;
	script.async = true;
	script.type = "text/javascript";
	script.src = url;
	head.insertBefore(script, head.firstChild);
  }

  function getFormJson(frm) {
	var o = {};
	var a = $(frm).serializeArray();
	$.each(a, function () {
		if (o[this.name] !== undefined) {
			if (!o[this.name].push) {
				o[this.name] = [o[this.name]];
			}
			o[this.name].push(this.value || '');
		} else {
			o[this.name] = this.value || '';
		}
	});
			
	return o;
  }

