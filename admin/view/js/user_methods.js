Object.entries({
	user_type_change(elm){
		elm.on('change',function(){
			const value = this.value;
			$('[name=keyword]').val('');
			switch(value){
				case 'uid':
				case 'gid':
					$('[name=keyword]').attr('type','number');
				break;
				case 'email':
					$('[name=keyword]').attr('type','email');
				break;
				case 'username':
				case 'create_ip':
					$('[name=keyword]').attr('type','text');
				break;
			}
			console.log(value);
		});
		elm.toEvent('change');
	}
}).forEach(v=>X.methods.set(v[0],v[1]));