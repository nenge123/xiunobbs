export default class groupadd {
	constructor(elm, X) {
		const T = this;
		//Math.max(...$('[id^=group-gid]').map((i,v)=>v.value).toArray())
		const jform = $(elm);
		T.jform = jform;
		jform.islock = false;
		jform.on('click', '.group_add', function (event) {
			var jhtml = $('#tpl_group').html();
			let credits = T.maxcredits();
			if(credits>=Math.pow(10,10)){
				credits = 0;
			}
			jhtml = jhtml.replace(/{gid}/ig,T.maxgid()+1).replace(/{time}/ig,Date.now).replace(/{creditsfrom}/,credits).replace(/{creditsto}/,credits*10);
			const newadd = $('#new-group-list').append(jhtml);
			T.bindcredits(newadd.find('[id^=new-from]'),'form');
			T.bindcredits(newadd.find('[id^=new-to]'),'to');
		});
		jform.on('click','.group-remove',function(event){
			event.preventDefault();
			let gid = $(this).attr('data-gid') ;
			if(gid){
				$('#group-delete-list').val($('#group-delete-list').val()+','+gid);
			}
			$(this).parent().parent().remove();
		});
		jform.on('click','.group-unlock',function(event){
			event.preventDefault();
			T.jform.islock = !T.jform.islock;
		});
		T.bindcredits(jform.find('[id^=member-from]'),'form');
		T.bindcredits(jform.find('[id^=member-to]'),'to');
		$('#system-credits').on('change',function(){
			$('[id^=member-from]').trigger('change');
			$('[id^=new-from]').trigger('change');
		});
	}
	bindcredits(list,type){
		const T = this;
		if(type=='to'){
			list.on('change',e=>T.credits_change(e.target,'from'));
			list.each(function(k,v){
				T.credits_beforeinput(v,'from');
			});

		}else{
			list.on('change',e=>T.credits_change(e.target,'to'));
			list.each(function(k,v){
				T.credits_beforeinput(v,'to');
			});
		}
	}
	credits_beforeinput(target,type){
		if(target.elm) return;
		if(target.value==0) return;
		if(target.value=='') return;
		let elm = $('input[type=number][id^=member-'+type+'][value='+$(target).val()+']');
		if(!elm[0]){
			elm = $('input[type=number][id^=new-'+type+'][value='+$(target).val()+']');
		}
		if(elm[0]){
			target.elm = elm[0];
			target.elm.elm = target;
		}

	}
	credits_change(targetelm){
		if(this.jform.islock) return;
		const T = this;
		const target = $(targetelm);
		const div = target.parent().parent();
		const name = target.attr('name');
		const idpre = target.attr('id').replace(/^(\w+\-).*$/ig,'$1');
		console.log(idpre);
		let nowvalue = parseInt(new Number(target.val()));
		console.log(nowvalue,target.val(),targetelm);
		//member[102][creditsfrom]
		if(name.match(/creditsfrom/)){
			const mincredits = T.mincredits();
			const fromvalue= parseInt(div.find('[id^='+idpre+'to]').val());
			if(fromvalue<nowvalue){
				nowvalue = fromvalue-1;
				target.val(nowvalue);
			}else{
				if(targetelm.elm){
					const div2 = $(targetelm.elm).parent().parent();
					const idpre2 = $(targetelm.elm).attr('id').replace(/^(\w+\-).*$/ig,'$1');
					const tovalue= parseInt(div2.find('[id^='+idpre2+'from]').val());
					if(nowvalue<tovalue){
						nowvalue =tovalue+1;
						target.val(nowvalue);
					}
				}
			}
			if(nowvalue<=mincredits){
				nowvalue = mincredits;
				target.val(mincredits);

			}
		}else{
			const tovalue= parseInt(div.find('[id^='+idpre+'from]').val());
			console.log(tovalue);
			if(tovalue>nowvalue){
				nowvalue = tovalue+1;
				target.val(nowvalue);
			}
		}
		if(nowvalue<=0){
			nowvalue = 0;
			target.val(0);
		}
		if(targetelm.elm){
			$(targetelm.elm).val(nowvalue);
		}
	}
	maxgid(){
		const maxid  = Math.max(0,...$('[id^=member-gid]').map((i,v)=>parseInt(v.value)).toArray());
		const maxid2  = Math.max(0,...$('[id^=new-gid]').map((i,v)=>parseInt(v.value)).toArray());
		if(maxid2==0 || maxid2<maxid){
			return maxid;
		}else{
			return maxid2
		}
	}
	maxcredits(){
		const maxid  = Math.max(0,...$('[id^=member-to]').map((i,v)=>parseInt(v.value)).toArray());
		const maxid2  = Math.max(0,...$('[id^=new-to]').map((i,v)=>parseInt(v.value)).toArray());
		if(maxid2==0 || maxid2<maxid){
			return maxid;
		}else{
			return maxid2
		}
	}
	mincredits(){
		return parseInt(new Number($('#system-credits').val()));
	}
}