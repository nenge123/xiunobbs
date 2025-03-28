self.X.methods.set('admin_forum_list',
	/**
	 * 
	 * @param {HTMLElement} elm 
	 */
	function (elm) {
		const X = this;
		const jarrlist = $(elm);
		// 增加
		const jadd = $('a.row_add');
		let maxfid = parseInt(jadd.attr('max-id'));
		jarrlist.on('click','.uploadimage',function(event){
			const next = $(this).next();
			const fid = next.attr('name').match(/\d+/)[0];
			X.callAjax('uploadimage',jarrlist.attr('action'),json=>{
				if (json.message) return $.alert(json.message);
				this.src = json.url+'?'+Date.now();
				next.val(json.icon);
			},{fid});
		});
		jadd.on('click', function () {
			var jclone = jarrlist.find('tr').last().clone(true);
			jclone.insertAfter(jarrlist.find('tr').last());
			//var rowid = xn.intval(jfid.val()) + 1;
			var rowid = ++maxfid;
			jclone.find('input[name^="rank"]').val('0');
			jclone.find('input[name^="icon"]').val('0');
			jclone.find('input[name^="fid"]').val(rowid);
			jclone.find('input[name^="name"]').val(rowid);
			jclone.attr('rowid', rowid);
			// 清空值
			// 修改 [] 中的值为 rowid
			jclone.find('input').attr_name_index(rowid);

			// 图片缩略
			jclone.find('img.uploadimage').attr('src',jclone.find('img.uploadimage').attr('base-url'));
			return false;
		});
	});