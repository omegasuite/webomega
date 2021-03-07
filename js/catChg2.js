define(function() {
	var catcache = {};

	function catChg2(obj, cats, prodc) {
		var root = document.URL.split("?")[0];

		var handler = function (resp) {
			document.getElementById(cats).innerHTML += '/' + resp.catname;
			if (resp.cat.length == 0) {
				obj.style.display = 'none';
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
			if (!prodc) return;

			var prod = document.getElementById(prodc);
			prod.options.length=0;
			if (resp.products.length > 0) {
				prod.style.display = 'block';
				var option=document.createElement('option');
				option.text = '';
				option.value = 0;
				prod.add(option,null);
				for (var i = 0; i < resp.products.length; i++) {
					option=document.createElement('option');
					option.text = resp.products[i].text;
					option.value = resp.products[i].value;
					prod.add(option,null);
				}
			}
			else prod.style.display = 'none';
		};

		if (catcache[obj.options[obj.selectedIndex].value] != undefined) handler(catcache[obj.options[obj.selectedIndex].value]);
		else $.ajax({
			url:root + '?module=accessories&MOD_op=getcatspec&subcat=2&cat=' + obj.options[obj.selectedIndex].value,
			type:'get',
			dataType:'json',
			success: function(resp) {
				catcache[obj.options[obj.selectedIndex].value] = resp;
				handler(resp);
			}
		 });
	}
	return catChg2;
  }
);