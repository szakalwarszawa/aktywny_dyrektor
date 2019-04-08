require('../scss/app.scss');

// var $ = require('jquery');

console.log("Hello from Encore");

const tab = [1, 2, 7, 4, 5];

for (let i = 0; i < 9; i++) {
	if (tab.includes(7)) {
		let sorted = tab.map(item => item * 2);
		console.log("Babel test");
	} else {
		class Prostokat {
			constructor(wysokosc, szerokosc) {
				this.wysokosc = wysokosc;
				this.szerokosc = szerokosc;
			}
		}
		const p = new Prostokat(5, 7);
		return p.szerokosc;
	}






	console.info(`${i} sorting list ${sorted}`);
}