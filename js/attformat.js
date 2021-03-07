define(function () {
	var Model = function(){
		this.callParent();
	};

	function getdepth(s) {
		var m = 0, n;
		if (s == null) return 0;
		if (s instanceof Array) {
			for (var i = 0; i < s.length; i++) {
				n = getdepth(s[i]);
				if (n > m) m = n;
			}
		}
		else if (s.val instanceof Array) {
			n = 1 + getdepth(s[i]);
			if (n > m) m = n;
		}
		else if (m < 2) m = 2;
		return m;
	}

	function _formatattribs(s, depth, cols, top, sec, ht, tv, full) {
		var r = '';
		if (s == null) return '';
		if (s instanceof Array) {
			for (var i = 0; i < s.length; i++) {
				if (!full && s[i].name == '_')
					return (ht?'<tr><td colspan=' + cols + ' align=right>' : '<td>') + s[i].val + "</td></tr>";
				if (s[i].name[0] == '_') continue;

				r += _formatattribs(s[i], depth, cols, top, sec, ht, tv + i);
				ht = true;
				top = sec;
			}
		}
		else if (s.val instanceof Array) {
			r += (ht?'<tr><td colspan=' + cols + ' align=right>' : '<td>') + s.name + "：</td>" + _formatattribs(s.val, depth, cols + 1, top, false, false, tv);
		}
		else {
			if (!full && s.name == '_') return r;
			if (s.name[0] == '_') return r;

			r += (ht?'<tr><td colspan=' + cols + ' align=right>' : '<td>') + s.name + "：</td><td>" + s.val + "</td>";
			if (top) r += '<td colspan=' + (depth - cols) + ' align=right>' + (tv>=0?'<input type=checkbox name=topsel value=' + tv + ' checked>' : '') + '</td>';
			r += "</tr>";
		}
		return r;
	}

	Model.prototype.seq = 0;

	Model.prototype.toggleattrib = function (seq) {
		var srts = document.getElementsByName("__shortattrib[" + seq + "]");
		var opmode = null, sm;
		if (srts.length == 0) return;
		var opmode = srts[0].style.display;

		if (opmode == 'none') sm = 'block';
		else sm = 'none';

		for (var i = 0; i < srts.length; i++) srts[i].style.display = sm;

		srts = document.getElementsByName("__fullattrib[" + seq + "]");
		for (var i = 0; i < srts.length; i++) srts[i].style.display = opmode;
	}

	Model.prototype.formatattribs = function (s, tv) {
		var dep = getdepth(s);

		if (tv == undefined) tv = 0;

		var r = _formatattribs(s, dep, 1, true, true, true, tv, false);
		var s = _formatattribs(s, dep, 1, true, true, true, tv, true);
		Model.prototype.seq++;

		if (r == s) return '<table class=table-small>' + r + '</table>';

		return '<a href=# onclick="toggleattrib(' + Model.prototype.seq + ');"><div name="__shortattrib[' + Model.prototype.seq + ']" style="display:block;"><table class=table-small>' + r + '</table></div><div name="__fullattrib[' + Model.prototype.seq + ']" style="display:none;"><table class=table-small>' + s + '</table></div></a>';
	}

	return Model;
 });
