export default class smtp {
	constructor(elm, X) {
		this.X = X;
		var jsmtp_body = $(elm);
		let maxrowid = jsmtp_body.find('tr').length;
		var tpl = $('#smtp_tpl').html();
		function row_add(v) {
			var s = xn.template(tpl, v);
			jsmtp_body.append(s);
		}
		// 删除 delete
		jsmtp_body.on('click', 'a.row_delete', function () {
			var jthis = $(this);
			var jtr = jthis.parents('tr');
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
	}
}