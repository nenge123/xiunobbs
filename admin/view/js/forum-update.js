self.X.methods.set('admin_forum_update',
	/**
	 * 
	 * @param {HTMLElement} elm 
	 */
	function (elm) {
		const X = this;
		const jform = $(elm);
		// toggle
		jform.find('input[name="accesson"]').on('change', function () {
			console.log($(this).prop('checked'));
			const bool = $(this).prop('checked');
			$('#accesslist').prop('hidden',!bool);
			bool&&$('#accesslist')[0].scrollIntoView();
		});
		// 全选
		jform.find('table tr').each(function(k, v) {
			var jtr = $(v);
			jtr.find('td').eq(0).find('input[type="checkbox"]').on('click', function() {
				jtr.find('input[type="checkbox"]').prop('checked', $(this).prop('checked'));
			});
		});
		$(jform.find('table tr')[0]).find('td').each((a,b)=>{
			if(a<2)return;
			$(b).on('click',function(event){
				event.preventDefault();
				jform.find('table tr').each((k, v)=>{
					$($(v).find('td')[a]).find('input[type="checkbox"]').prop('checked',!this.checked);
				});
				this.checked = !this.checked; 
			});
		});
	});