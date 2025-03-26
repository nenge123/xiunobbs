X.methods.set('setsmtp',function(elm){
	console.log(elm);
	var jsmtp_body = $(elm);
	let maxrowid = jsmtp_body.find('tr').length;
	var jsmtp_tpl = $('#smtp_tpl');
	var tpl = jsmtp_tpl.text();
	function row_add(v) {
		var s = xn.template(tpl, v);
		jsmtp_body.append(s);
	}
	// 删除 delete
	var jdelete = $('a.row_delete');
	jsmtp_body.on('click', 'a.row_delete', function () {
		var jthis = $(this);
		var jtr = jthis.parents('tr');
		var rowid = jtr.attr('rowid');
		jtr.remove();
		return false;
	});
	// 增加 add
	var jadd = $('a.row_add');
	jadd.on('click', function () {
		var v = {
			email: '',
			host: '',
			port: '',
			user: '',
			pass: '',
			rowid: ++maxrowid
		};
		row_add(v);
		return false;
	});

});
