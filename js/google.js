function gps2map(gpsPoint) {
	return gpsPoint;
}

function map2gps(mapPoint) {
	return mapPoint;
}

function setPolygon(ps) {
	link.setMap(null);
	link = new google.maps.Polygon({
		paths: [ps],
			strokeColor: '#FF0000',
			strokeOpacity: 0.8,
			strokeWeight: 2,
			fillColor: '#00FF00',
			fillOpacity: 0.35
		});

		link.setMap(map);
}

function getBounds() {
	m = map.getBounds();
	bound = {east: m.getNorthEast().lng(), west : m.getSouthWest().lng(), south: m.getSouthWest().lat(), north: m.getNorthEast().lat()};
	return bound;
}

function coloring(inp, color) {
	var p = polygons[inp];
	if (drawnpolygons[inp] != null) drawnpolygons[inp].setMap(null);

	var plg = drawPolygon(p, color);

	drawnpolygons[inp] = plg;
	drawnpolygons[inp].setMap(map);
}

function drawPolygonDirect(p, color) {
	var plg = drawPolygon(p, color);
	plg.setMap(map);
	return plg. getPoints();
}

function drawEdge(e, color) {
	plg = new google.maps.Polyline({
		paths: e,
		strokeColor:color,
		strokeWeight:2,
	});

	plg.setMap(map);
}

function drawPolygon(p, color) {
	var ps = [];
	for (var i = 0; i < p.length; i++) {
		var l = Array();
		for (var j = 0; j < p[i].length; j++) {
			l.push({lat: p[i][j][1],  lng: p[i][j][0]})
		}
		ps.push(l);
	}

	return new google.maps.Polygon({
		paths: ps,
		strokeColor: '#FF0000',
		strokeOpacity: 0.8,
		strokeWeight: 2,
		fillColor: color,
		fillOpacity: 0.35
	});		 
}


var cur = null;

function pointDrop(point)  {
    if (cur == null) {
		cur = setMarker(map, point.lat(), point.lng(), '');
		google.maps.event.clearListeners(map, 'click');

		google.maps.event.addListener(cur,'dragend',getPoint);
//		google.maps.event.addListener(cur,'addvertex',function (e) {
//				getPoint(e); addvertex();
//		});
	 }
	 else cur.setPosition(point);
}

function pointDropEnd()  {
	if (cur) {
		google.maps.event.clearListeners(cur, 'dragend');
		google.maps.event.addListener(map, 'click', getPoint);
		cur.setMap(null);
		cur = null;
	}
}

function setMarker(map, latm, lngm, id) {
	var shape = {
			coords: [1, 1, 1, 64, 40, 64, 40, 1],
			type: 'poly'
	};

	var icon2 = {
		url: 'images/maps.png',
		scaledSize: new google.maps.Size(32, 32),
		labelOrigin: new google.maps.Point(16, 10),
		size: new google.maps.Size(32, 32),
		origin: new google.maps.Point(0, 0),
		anchor: new google.maps.Point(16, 32)
	};

	var m2 = new google.maps.Marker({
		position: {lat: latm, lng: lngm},
		map: map,
		shape: shape,
		icon: icon2,
		draggable:id?false : true
	});
	m2.setLabel(id.toString());

	return m2;
}

 //鼠标点击获取指定点的经纬度
 function getPoint(event) {
	 if (!vertexmode) return;
	 
	 var point = event.latLng;
	 document.getElementById('lng').value = point.lng();
	 document.getElementById('lat').value = point.lat();
	 
	 pointDrop(point);
}
