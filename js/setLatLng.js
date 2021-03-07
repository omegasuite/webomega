define(function() {
	function setLatLng(loc, lat, lng) {
		var root = document.URL.split("?")[0];
		if (loc != 0) $.ajax({
			url:root + '?module=accessories&MOD_op=setLatLng&loc=' + loc + '&lat=' + lat + '&lng=' + lng,
			type:'get',
			dataType:'json',
			success: function(resp) {
			}
		  });
		else {
			document.getElementById('lat').value = lat;
			document.getElementById('lng').value = lng;
		}
	}

	return setLatLng;
});