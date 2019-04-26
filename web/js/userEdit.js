$(document).ready(function () {
	var kontoSelect = $('#parp_mainbundle_edycjauzytkownika_isDisabled');
	$(kontoSelect)
		.children()
		.first()
		.remove();
	var hiddenReason = $('#powod_wylaczenia_w_ad');

	// var sekcja = $('#parp_mainbundle_edycjauzytkownika_info');
	var sekcja = document.getElementById('parp_mainbundle_edycjauzytkownika_info');
	var menager = $('#parp_mainbundle_edycjauzytkownika_manager');
	var departament = document.getElementById(
		'parp_mainbundle_edycjauzytkownika_department',
	);
	var selectedDepartament = departament.options[departament.selectedIndex];

	console.log('Selected index', departament.selectedIndex);
	console.log('toggle class', departament);
	console.log('sected options ', departament.selectedOptions);
	console.log('selectedDepartament', selectedDepartament);
	console.info('selectedDepartament.text', selectedDepartament.text);
	console.log(departament.options.length);

	//--- nasluch na zmiane departamentu ---
	$(departament).on('change', function (event) {
		event.preventDefault();
		selectedDepartament = departament.options[departament.selectedIndex];
		console.info('selectedDepartament', selectedDepartament, selectedDepartament.text);
		// ---selectedDepartamentValue = parseInt(departament.options[departament.selectedIndex].value);
		// departament.options.selectedIndex = 0;
		for (var i = 0; i < departament.options.length; i++) {
			console.log(i, departament.options[i].selected, 'Selected index', departament.selectedIndex);
			if (departament.options[i].value !== undefined && parseInt(departament.options[i].value) === parseInt(departament.options[departament.selectedIndex].value)) {
				// departament.options.selectedIndex = selectedDepartamentValue;
				// --- departament.selectedIndex = selectedDepartamentValue;
				console.info(i, 'departament.selectedIndex ', departament.selectedIndex);
				console.log(i, 'selected options ', departament.selectedOptions);
				break;
			}
		}

		console.log('toggle class', departament);
		console.log('T ', this);
		console.log(selectedDepartament);
		console.info(selectedDepartament.text);
		console.warn('V ', selectedDepartament.value);
		constrainVisibleOptGroups();
		sekcja.selectedIndex = 0;
		console.log('sekcja.selectedIndex', sekcja.selectedIndex);
	});

	console.log('Sekcja', sekcja);

	var optgroups = document.getElementsByTagName('optgroup');
	console.log('groups ', optgroups);

	function constrainVisibleOptGroups() {
		for (var i = 0; i < optgroups.length; i++) {
			// console.log(optgroups[i].label);
			optgroups[i].classList.remove('hidden');
			if (optgroups[i].label.indexOf(selectedDepartament.text) === -1) {
				optgroups[i].classList.add('hidden');
			}
		}
	}
	// function constrainVisibleOptGroups(params) {
	// 	$(sekcja).select2({
	// 		matcher: function (params, data) {
	// 			if ($.trim(params.term) === '') {
	// 				return data;
	// 			}

	// 			if (data.text.toUpperCase().indexOf(params.term.toUpperCase()) > -1) {
	// 				var modifiedData = $.extend({}, data, true);
	// 				return modifiedData;
	// 			}

	// 			return null;
	// 		} // koniec: funkcja definiująca dopasownia przy wyszukiwaniu za pomocą select2
	// 	});
	// }

	constrainVisibleOptGroups();

	// console.log('group name ', optgroupsTxt);

	// --- własny matcher do select2 ---
	// function matchCustom(params, data) {
	// 	// If there are no search terms, return all of the data
	// 	if ($.trim(params.term) === '') {
	// 		return data;
	// 	}

	// 	// Do not display the item if there is no 'text' property
	// 	if (typeof data.text === 'undefined') {
	// 		return null;
	// 	}

	// 	// `params.term` should be the term that is used for searching
	// 	// `data.text` is the text that is displayed for the data object
	// 	if (data.text.indexOf(params.term) > -1) {
	// 		var modifiedData = $.extend({}, data, true);
	// 		modifiedData.text += ' (matched)';

	// 		// You can return modified objects from here
	// 		// This includes matching the `children` how you want in nested data sets
	// 		return modifiedData;
	// 	}

	// 	// Return `null` if the term should not be displayed
	// 	return null;
	// }

	// --- uruchomienie biblioteki select2 na wybranych selectach ---
	// $(sekcja).select2({
	// 	matcher: function (params, data) {
	// 		if ($.trim(params.term) === '') {
	// 			return data;
	// 		}

	// 		if (data.text.toUpperCase().indexOf(params.term.toUpperCase()) > -1) {
	// 			var modifiedData = $.extend({}, data, true);
	// 			return modifiedData;
	// 		}
	// 		//---//
	// 		var modifiedData = $.extend(modifiedData, data, true);
	// 		var currChildrenArray = [];
	// 		$.each(data.children, function (index, elem) {
	// 			if (elem.text.toUpperCase().indexOf(params.term.toUpperCase()) > -1) {
	// 				currChildrenArray.push(elem);
	// 			}
	// 		});
	// 		if (currChildrenArray.length > 0) {
	// 			modifiedData.children = currChildrenArray;
	// 			return modifiedData;
	// 		}

	// 		return null;
	// 	} // koniec: funkcja definiująca dopasownia przy wyszukiwaniu za pomocą select2
	// });
	$(menager).select2();




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