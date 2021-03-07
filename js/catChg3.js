define(function() {
	var catcache = {};

	function catChg3(obj, cats, prodc) {
		var root = document.URL.split("?")[0];

		var handler = function (resp) {
			document.getElementById(cats).innerHTML += '/' + resp.catname;
			if (resp.cat.length == 0) {
				obj.style.display = 'none';
				document.getElementById(cats).style.display = 'none';
			}
			else {
				obj.options.length=0;
				obj.length=0;
				for (var i = 0; i < resp.cat.length; i++) {
					var option=document.createElement('option');
					option.text = resp.cat[i].text;
					option.value = resp.cat[i].value;
					obj.add(option,null);
				}
			}
			var prod = document.getElementById(prodc);
			prod.options.length=0;
			prod.length=0;
			if (resp.goods.length > 0) {
				prod.style.display = 'block';
				var option=document.createElement('option');
				option.text = '';
				option.value = 0;
				prod.add(option,null);
				for (var i = 0; i < resp.goods.length; i++) {
					option=document.createElement('option');
					option.text = resp.goods[i].text;
					option.value = resp.goods[i].value;
					prod.add(option,null);
				}
			}
			else prod.style.display = 'none';
		};

		var url = root + '?module=accessories&MOD_op=getcatspec&subcat=3&cat=' + obj.options[obj.selectedIndex].value;
		if (catcache[obj.options[obj.selectedIndex].value] != undefined) handler(catcache[obj.options[obj.selectedIndex].value]);
		else $.ajax({
			url:url,
			type:'get',
			dataType:'json',
			success: function(resp) {
				catcache[obj.options[obj.selectedIndex].value] = resp;
				handler(resp);
			}
		 });
	}
	return catChg3;
  }
);