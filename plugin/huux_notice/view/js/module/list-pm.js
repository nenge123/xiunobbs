export default class listpm{
	constructor(elm,X){
		X.methods.set('remove_pm',function(elm){
			$(elm).parent().parent().parent().remove();
		});
	}
}