export default class forum_delete {
	constructor(elm, X) {
		elm.once('submit', function (event) {
			event.preventDefault();
			const E = this;
			E.innerHTML = '';
			$(E).disabled();
			const scroll = document.querySelector('#end-result');
			E.style.cssText = 'white-space: pre;';
			X.callMethod('createEventSource', E.getAttribute('action'), {
				open(event) {
					console.log(event);
					if (event.data) {
						let p = JSON.parse(event.data);
						p && E.append(p.message + "\n");
					}
				},
				progress(event) {
					console.log(event);
					if (event.data) {
						let p = JSON.parse(event.data);
						p && E.append(p.message + "\n");
						X.callMethod('scrollView', scroll)
					}
				},
				close(event) {
					if (event.data) {
						let p = JSON.parse(event.data);
						if (p) {
							E.append(p.message + "\n");
							if (p.url) {
								$.reload(p.url,1000);
							}
						}

					}
					this.close();
				}
			});
		})
	}
}