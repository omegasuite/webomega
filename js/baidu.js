function map2gps(mapPoint) {
	var p2 = gps2map(mapPoint);
	var cvdpoint;

	cvdpoint.lat = 2 * mapPoint.lat - p2.lat;
	cvdpoint.lng = 2 * mapPoint.lng - p2.lng;

	return cvdpoint;
}


function gps2map(gpsPoint) {
	var cvdpoint;

	translateCallback = function (point){
		cvdpoint = point;
	}

	BMap.Convertor.translate(gpsPoint,0,translateCallback)

	return cvdpoint;
}

function getBounds() {
	n = map.getBounds();
	sw = n.getSouthWest();
	ne = n.getNorthEast();
	bound = {east: ne.lng, west : sw.lng, south: sw.lat, north: ne.lat};
	return bound;
}

function setPolygon(ps) {
	map.removeOverlay(link);
	link = new BMap.Polygon(ps, {
		strokeColor: '#FF0000',
		strokeOpacity: 0.8,
		strokeWeight: 2,
		fillColor: '#00FF00',
		fillColorOpacity: 0.35
	});
	map.addOverlay(link);
}

function drawEdge(e, color) {
	var ps = [];
	for (var i = 0; i < e.length; i++) {
		ps.push(new BMap.Point(e[i].lng, e[i].lat));
	}

	plg = new BMap.Polyline(ps,	{
		strokeColor:color,
		strokeWeight:2,
	});

	map.addOverlay(plg);
}

var arrow = null;

function drawBorder(e) {
	var ps = [];
	for (var i = 0; i < e.edge.length; i++) {
		ps.push(new BMap.Point(e.edge[i].lng, e.edge[i].lat));
	}
debugger;
	if (arrow == null) {
		var sy = new BMap.Symbol(BMap_Symbol_SHAPE_BACKWARD_OPEN_ARROW, {
                scale: 0.6,//图标缩放大小
                strokeColor:'#fff',//设置矢量图标的线填充颜色
                strokeWeight: 2,//设置线宽
            });
		arrow = new BMap.IconSequence(sy, '100%', '10%',false);//设置为true，可以对轨迹进行编辑
	}

	var colors = ["red", "blue", "green"];

	plg = new BMap.Polyline(ps,	{
		strokeColor:colors[e.leaf],
		strokeWeight:2,
		enableClicking:true,
		icons:[arrow],
		strokeStyle:(e.leaf<2?'dashed' : 'solid'),
	});

	plg.addEventListener("click", function() {   onselectedge(e);   });

	map.addOverlay(plg);
}

function coloring(inp, color) {
	var p = polygons[inp];
	if (drawnpolygons[inp] != null) map.removeOverlay(drawnpolygons[inp]);

	var plg = drawPolygon(p, color);

	drawnpolygons[inp] = plg;

	map.addOverlay(plg);
}

function drawPolygonDirect(p, color) {
	var plg = drawPolygon(p, color);
	map.addOverlay(plg);
	return plg.getPath();
}

function drawPolygon(p, color) {
	var ps = [];
	for (var i = 0; i < p.length; i++) {
		var l = Array();
		for (var j = 0; j < p[i].length; j++) {
			l.push(new BMap.Point(p[i][j][1], p[i][j][0]));
		}
		ps.push(l);
	}
	
	return new BMap.Polygon(ps[0],	{
		strokeColor:"#FF0000",
		fillColor:color,
		fillColorOpacity:"0.5",
		strokeWeight:2,
		strokeOpacity:0.8
	}); 
}

var cur = null;

function pointDrop(point)  {
    if (cur == null) {
		cur = setMarker(map, point.lat, point.lng, '');
		map.removeEventListener('click', getPoint);

		map.addEventListener("dragend", getPoint);
//		map.addEventListener("addvertex", function (e) {
//				getPoint(e); addvertex();
//		});
	 }
	 else cur.setPosition(point);
}

function pointDropEnd()  {
	if (cur) {
		map.removeEventListener('dragend', getPoint);
		map.addEventListener('click', getPoint);
		cur = null;
	}
}

function setMarker(map, latm, lngm, id) {
	var shape = {
			coords: [1, 1, 1, 64, 40, 64, 40, 1],
			type: 'poly'
	};

//	var icon2 = new BMapGL.Icon('images/maps.png', new BMapGL.Size(32, 32), {   
//	    anchor: new BMapGL.Size(16, 32),   
//	});     

	var point = new BMap.Point(lngm ,latm); 
//	var defmarker = new BMap.Marker(point, {icon: icon2}); // 创建点
	var defmarker = new BMap.Marker(point); // 创建点

	if (id) {
		defmarker.disableDragging();
	} else {
		defmarker.enableDragging();
	}

	map.addOverlay(defmarker);

	var label = new BMap.Label(id.toString(),{offset:new BMap.Size(20,-10)});
	defmarker.setLabel(label);

	return defmarker;
}

 //鼠标点击获取指定点的经纬度
 function getPoint(event) {
	 if (!vertexmode) return;

	 var point = event.point;
	 document.getElementById('lng').value = point.lng;
	 document.getElementById('lat').value = point.lat;
	 
	 pointDrop(point);
}
