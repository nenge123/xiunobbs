export default class forum_update{
	constructor(elm,X){
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
			$(b).css('cursor','pointer');
			$(b).on('click',function(event){
				event.preventDefault();
				jform.find('table tr').each((k, v)=>{
					$($(v).find('td')[a]).find('input[type="checkbox"]').prop('checked',!this.checked);
				});
				this.checked = !this.checked; 
			});
		});
		//编辑器
		$('[data-edit=tinymce] input').one('click',async function(){
			const {editor} = await import(X.admin_jsroot+'module/tinymce.js');
			const p = $(this).parent().next();
			const E = new editor(p[0]);
			$(this).prop('disabled',true);
			E.form = elm;
		});
	}
}