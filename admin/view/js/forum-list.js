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
			console.log(this);
			const next = $(this).next();
			const fid = next.attr('name').match(/\d+/)[0];
			X.callAjax('uploadimage',jarrlist.attr('action'),json=>{
				if (json.message) return $.alert(json.message);
				this.src = json.url;
				next.val(json.icon);
			},{fid});
			console.log(this,event);
		});
		jadd.on('click', function () {
			var jclone = jarrlist.find('tr').last().clone(true);
			jclone.insertAfter(jarrlist.find('tr').last());
			var jfid = jclone.find('input[name^="fid"]');
			//var rowid = xn.intval(jfid.val()) + 1;
			var rowid = ++maxfid;
			Array.from(jclone.find('[name]'),e=>{
				const el = $(e);
				if(el.attr('type')=='number')el.val(0);
				else el.val('');
				el.removeAttr('id');
				el.attr('name',el.attr('name').replace(/\d+/,rowid));
				if(el.next()[0]){
					el.next().attr('for',el.next().attr('for').replace(/\d+/,rowid));
				}
			});
			jfid.val(rowid);
			jclone.attr('rowid', rowid);
			// 清空值
			// 修改 [] 中的值为 rowid
			jclone.find('input').attr_name_index(rowid);

			// 图片缩略
			jclone.find('img.uploadimage').attr('src',jclone.find('img.uploadimage').attr('base-url'));
			return false;
		});
	});