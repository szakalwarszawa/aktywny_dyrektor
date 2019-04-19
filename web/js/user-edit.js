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
	console.log(selectedDepartament);
	console.info(selectedDepartament.text);

	//--- nasluch na zmiane departamentu ---
	$(departament).on('change', function () {
		console.log('toggle class', departament);
		console.log(selectedDepartament);
		console.info(selectedDepartament.text);
	});

	console.log(sekcja);

	var optgroups = $('optgroup');
	var optgroupsTxt = $(optgroups).each(function (item, index) {
		console.log(item.label);
		return (item.label);
	});

	console.log('group ', optgroups);
	console.log('group name ', optgroupsTxt);

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