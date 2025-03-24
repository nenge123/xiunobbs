const A = new class {
	pluginFetch(url, post) {

		fetch(url, {
			method: 'POST',
			body: post,
		}).then(r => r.json).then(function (result) {
			const data = result.data;
			if (data) {
				for (let key in data) {
					let elm = document.querySelector('#plugin-' + key);
					if (elm) {
						elm.hidden = false;
						elm.textContent = entry[1];
					}
				}
			};
			if (result.message) {
				$.alert(result.message);
			}
		});
	}
}
Object.defineProperty(self,'A',{
	get:()=>A
});
export default A;