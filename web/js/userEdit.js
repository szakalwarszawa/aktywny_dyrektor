$(document).ready(function () {
	var kontoSelect = $('#parp_mainbundle_edycjauzytkownika_isDisabled'); // Czy konto wyłączone w AD
	var hiddenReason = $('#powod_wylaczenia_w_ad'); // Powód wyłączenia w Active Directory
	var sekcja = document.getElementById('parp_mainbundle_edycjauzytkownika_info');
	var menager = $('#parp_mainbundle_edycjauzytkownika_manager'); // jako jQuery, bo odpalamy na tym select2
	var departament = document.getElementById('parp_mainbundle_edycjauzytkownika_department');
	var selectedDepartament = departament.options[departament.selectedIndex]; // wykorzystywane w f. constrainVisibleOptGroups(), aktualizowane w on.change-u
	var optgroups = document.getElementsByTagName('optgroup');

	//--- nasluch na zmiane departamentu ---
	$(departament).on('change', function (event) {
		event.preventDefault();
		selectedDepartament = departament.options[departament.selectedIndex];
		// for (var i = 0; i < departament.options.length; i++) {
		// 	if (departament.options[i].value !== undefined && parseInt(departament.options[i].value) === parseInt(departament.options[departament.selectedIndex].value)) {
		// 		break;
		// 	}
		// }
		constrainVisibleOptGroups(); // zaweżenie pola Sekcja do konkretnej optgroup
		sekcja.selectedIndex = 0;
	});

	function constrainVisibleOptGroups() {
		for (var i = 1; i < optgroups.length; i++) {
			optgroups[i].classList.remove('hidden');
			if (optgroups[i].label.indexOf(selectedDepartament.text) === -1) {
				optgroups[i].classList.add('hidden');
			}
		}
	}

	constrainVisibleOptGroups(); // odpalenie f. jeden raz - po załadowaniu strony

	// --- uruchomienie biblioteki select2 na wybranych selectach ---
	$(menager).select2();

	// --- pola Czy konto wyłączone i Powód wyłączenia w Active Directory ---
	$(kontoSelect).children().first().remove();

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