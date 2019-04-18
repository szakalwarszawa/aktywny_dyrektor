$(document).ready(function () {
	var kontoSelect = $('#parp_mainbundle_edycjauzytkownika_isDisabled');
	$(kontoSelect).children().first().remove();
	var hiddenReason = $('#powod_wylaczenia_w_ad');

	var sekcja = $('#parp_mainbundle_edycjauzytkownika_info');
	var menager = $('#parp_mainbundle_edycjauzytkownika_manager');
	var departament = $('#parp_mainbundle_edycjauzytkownika_department');

	console.log('toggle class', departament.selected);
	console.info(departament);

	//--- nasluch na zmiane departamentu ---
	$(departament).on('change', function () {
		console.log('toggle class', departament.selectedOption);
		console.info(departament);
	});

	// --- uruchomienie biblioteki select2 na wybranych selectach ---
	$(sekcja).select2();
	$(menager).select2();

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