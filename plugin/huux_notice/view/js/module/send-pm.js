export default class sendpm {
	constructor(elm, X) {
		this.X = X;
		console.log(elm);
		X.callMethod('tinymce7').then(tinyMce => {
			const txtelm = $('[name="message"]', elm);
			const target = txtelm[0];
			tinyMce.init({
				target,
				setup(editor) {
					txtelm.parent().removeClass('form-floating');
					txtelm.next().prop('hidden', true);
					editor.on('change', function () {
						this.save();
					})
				}
			});
		});
	}
}