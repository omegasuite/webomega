define(function() {
	function chgCustomer(obj) {
		var root = document.URL.split("?")[0];
		$.ajax({
			url:root + '?module=accessories&MOD_op=getcustcontact&cust=' + obj.options[obj.selectedIndex].value,
			type:'get',
			dataType:'json',
			success: function(resp) {
				var prod = document.getElementById('sel_receipient');
				var adr = document.getElementById('sel_address');
				var prod2 = document.getElementById('sel_phone');
				prod.options.length=0;
				prod.length=0;
				prod2.options.length=0;
				prod2.length=0;
				adr.options.length=0;
				adr.length=0;
				if (resp.contacts.length > 0) {
					for (var i = 0; i < resp.contacts.length; i++) {
						var option = document.createElement('option');
						option.text = resp.contacts[i].receipient;
						option.value = resp.contacts[i].receipient;
						prod.add(option,null);
									
						option = document.createElement('option');
						option.text = resp.contacts[i].phone;
						option.value = resp.contacts[i].phone;
						prod2.add(option,null);
					}
					document.getElementById('receipient').value = resp.contacts[0].receipient;
					document.getElementById('phone').value = resp.contacts[0].phone;
				}
				if (resp.addresses.length > 0) {
					for (var i = 0; i < resp.addresses.length; i++) {
						option = document.createElement('option');
						option.text = resp.addresses[i].address;
						option.value = resp.addresses[i].address;
						adr.add(option,null);
					}
					document.getElementById('address').value = resp.addresses[0].address;
				}
			}
		 });
	}
	return chgCustomer;
  }
);