 define(function() {
	 function chgProvince(obj) {
		var root = document.URL.split("?")[0];
		$.ajax({
			url:root + '?module=accessories&MOD_op=getcities&province=' + obj.options[obj.selectedIndex].text,
			type:'get',
			dataType:'json',
			success: function(resp) {
				if (resp.cities.length > 0) {
					var cts = document.getElementById('city');
					cts.options.length=1;
					cts.length=1;
					for (var i = 0; i < resp.cities.length; i++) {
						var option=document.createElement('option');
						option.text = resp.cities[i].text;
						cts.add(option,null);
					}
				}
			}
		 });
	 }
	 return chgProvince;
  }
);