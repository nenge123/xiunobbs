export default class pluginread{
	constructor(elm,X){
		let url = $(elm).attr('data-url');
		if(url){
			$.ajax({
				type: 'POST',
				url,
				data:{id:$(elm).attr('data-siteid'),name:$(elm).attr('data-name')},
				dataType: 'json',
				timeout: 6000000,
				/**
				 * @param {XMLHttpRequest} xhr 
				 */
				beforeSend(xhr){
					xhr.setRequestHeader('ajax-fetch',1);
				},
				success(r) {
					if(X.isOBJ(r.data)){
						for(let i in r.data){
							$('#plugin-'+i).text(r.data[i]);
						}
					}
					if(r.message){
						$.alert(r.message);
					}
				},
			});
		}
	}
}