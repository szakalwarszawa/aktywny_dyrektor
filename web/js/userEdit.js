$(document).ready(function () {
	var kontoSelect = $('#parp_mainbundle_edycjauzytkownika_isDisabled');
	$(kontoSelect)
		.children()
		.first()
		.remove();
	var hiddenReason = $('#powod_wylaczenia_w_ad');

	var sekcja = $('#parp_mainbundle_edycjauzytkownika_info');
	var menager = $('#parp_mainbundle_edycjauzytkownika_manager');
	var departament = document.getElementById(
		'parp_mainbundle_edycjauzytkownika_department',
	);
	var selectedDepartament = departament.options[departament.selectedIndex];

	console.log('toggle class', departament);
	console.log('sected options ', departament.selectedOptions);
	console.log(selectedDepartament);
	console.info(selectedDepartament.text);
	console.log(departament.options.length);

	//--- nasluch na zmiane departamentu ---
	$(departament).on('change', function (event) {
		event.preventDefault();
		selectedDepartament = departament.options[departament.selectedIndex];
		selectedDepartamentValue = parseInt(departament.options[departament.selectedIndex].value);
		// departament.options.selectedIndex = 0;
		for (var i = 0; i < departament.options.length; i++) {
			console.log(i, 'Selected index', departament.selectedIndex);
			if (parseInt(departament.options[i].value) !== udefined && parseInt(departament.options[i].value) === parseInt(departament.options[departament.options.selectedIndex].value)) {
				departament.options.selectedIndex = selectedDepartamentValue;
				console.info(i, 'departament.options.selectedIndex ', departament.options.selectedIndex);
				console.log(i, 'selected options ', departament.selectedOptions);
				//break;
			}
		}

		console.log('toggle class', departament);
		console.log('T ', this);
		console.log(selectedDepartament);
		console.info(selectedDepartament.text);
		console.warn('V ', selectedDepartament.value);
		constrainVisibleOptGroups();
	});

	console.log(sekcja);

	var optgroups = document.getElementsByTagName('optgroup');
	console.log('group ', optgroups);

	function constrainVisibleOptGroups() {
		for (var i = 0; i < optgroups.length; i++) {
			// console.log(optgroups[i].label);
			if (optgroups[i].label.indexOf(selectedDepartament.text) === -1) {
				optgroups[i].classList.add('hidden');
			}
		}
	}

	constrainVisibleOptGroups();

	// console.log('group name ', optgroupsTxt);

	// --- uruchomienie biblioteki select2 na wybranych selectach ---
	// $(sekcja).select2(); // deaktywacja select2 sekcji na czas obróbki danych
	$(menager).select2();
	// --- zmienne zależne od wykonania select2 ---
	// var sekcjaResults = $('#select2-parp_mainbundle_edycjauzytkownika_info-results');

	// --- zaciąganie elementów po podanym czasie ---
	// var timeout = function (time) {
	// 	window.setTimeout(modifySelect2Results(), time)
	// };

	// function modifySelect2Results() {
	// 	var sekcjaResults = document.getElementById('select2-parp_mainbundle_edycjauzytkownika_info-results');
	// 	console.log(sekcjaResults);
	// 	var sekcjaResultsItem = $(sekcjaResults).children('li');
	// 	console.log('item', sekcjaResultsItem);
	// 	console.log('item2', $(sekcjaResults).find('li'));
	// 	clearTimeout(timeout);
	// }

	// timeout(20000);

	if ($(kontoSelect).val() === '1') {
		$(hiddenReason).removeClass('hidden');
	}

	$(kontoSelect).on('change', function () {
		if ($(kontoSelect).val() === '1') {
			$(hiddenReason).removeClass('hidden');
		} else if (
			$(kontoSelect).val() === '0' &&
			$(hiddenReason).hasClass('has-error') === false &&
			$(hiddenReason).hasClass('hidden') === false
		) {
			$(hiddenReason).addClass('hidden');
		} else {
			return;
		}
	});
});