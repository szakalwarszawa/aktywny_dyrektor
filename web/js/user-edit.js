$(document).ready(function () {
	var kontoSelect = $('#parp_mainbundle_edycjauzytkownika_isDisabled');
	$(kontoSelect).children().first().remove();
	var hiddenReason = $('#powod_wylaczenia_w_ad');

	var sekcja = $('#parp_mainbundle_edycjauzytkownika_info');
	var menager = $('#parp_mainbundle_edycjauzytkownika_manager');
	var departament = document.getElementById('parp_mainbundle_edycjauzytkownika_department');
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



	// --- uruchomienie biblioteki select2 na wybranych selectach ---
	$(sekcja).select2();
	$(menager).select2();
	// --- zmienne zależne od wykonania select2 ---
	var sekcjaResults = $('#select2-parp_mainbundle_edycjauzytkownika_info-results');
	console.log(sekcjaResults);
	var sekcjaResultsItem = $(sekcjaResults).children();
	console.log('item', sekcjaResultsItem);

	if ($(kontoSelect).val() === '1') {
		$(hiddenReason).removeClass('hidden');
	}

	$(kontoSelect).on('change', function () {
		if ($(kontoSelect).val() === '1') {
			$(hiddenReason).removeClass('hidden');
		} else if ($(kontoSelect).val() === '0' && $(hiddenReason).hasClass('has-error') === false && $(hiddenReason).hasClass('hidden') === false) {
			$(hiddenReason).addClass('hidden');
		} else {
			return;
		}
	});
});