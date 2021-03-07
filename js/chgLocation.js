function chgProvince(obj, city, place) {
	var root = document.URL.split("?")[0];
	$.ajax({
		url:root + '?module=accessories&MOD_op=getlocation&province=' + obj.options[obj.selectedIndex].text,
		type:'get',
		dataType:'json',
		success: function(resp) {
			if (resp.cities.length > 0) {
				if (city != null) {
					var cts = document.getElementById(city);
					cts.options.length=1;
					cts.length=1;
					for (var i = 0; i < resp.cities.length; i++) {
						var option=document.createElement('option');
						option.text = resp.cities[i].text;
						option.value = resp.cities[i].value;
						cts.add(option,null);
					}
				}
			}
			else {
				if (place != null) {
					var cts = document.getElementById(place);
					cts.options.length=1;
					cts.length=1;
					for (var i = 0; i < resp.places.length; i++) {
						var option=document.createElement('option');
						option.text = resp.places[i].text;
						option.value = resp.places[i].value;
						cts.add(option,null);
					}
				}
			}
		}
	 });
}
				  
function chgCity(obj, province, place) {
	var root = document.URL.split("?")[0];
	var province = document.getElementById(province);
	$.ajax({
		url:root + '?module=accessories&MOD_op=getlocation&province=' + province.options[province.selectedIndex].text + '&city=' + obj.options[obj.selectedIndex].text,
		type:'get',
		dataType:'json',
		success: function(resp) {
			if (place != null) {
				var cts = document.getElementById(place);
				cts.options.length=1;
				cts.length=1;
				for (var i = 0; i < resp.places.length; i++) {
					var option=document.createElement('option');
					option.text = resp.places[i].text;
					option.value = resp.places[i].value;
					cts.add(option,null);
				}
			}
		}
	 });
}