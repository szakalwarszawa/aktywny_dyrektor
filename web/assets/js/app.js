require('../scss/app.scss');

// var $ = require('jquery');

console.log("Hello from Encore");

const tab = [1, 2, 6, 4, 5];

for (let i = 0, sorted; i < 9; i++) {
	if (tab.includes(7)) {
		(() => {
			sorted = tab.map(item => item * 2 + i);
			console.log(i, "Babel test: ", sorted);
			return sorted;
		})();
	} else {
		(() => {
			class Prostokat {
				constructor(wysokosc, szerokosc) {
					this.wysokosc = wysokosc;
					this.szerokosc = szerokosc;
				}
			}
			const p = new Prostokat(5, 7);
			let width = p.szerokosc;
			return width;
		})();
	}

	console.info(`${i} sorting list ${sorted} o szerokości ${p.szerokosc}`);
}