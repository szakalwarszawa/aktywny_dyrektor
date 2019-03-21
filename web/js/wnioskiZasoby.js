function wniosekPracownikSpozaParp() {
	var v = $('#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownikSpozaParp').prop('checked');
	if (v) {
		$('#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicy').closest('.form-group').addClass('hidden');
		$('#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicySpozaParp').closest('.form-group').removeClass('hidden');
		$('#parp_mainbundle_wnioseknadanieodebraniezasobow_managerSpozaParp').closest('.form-group').removeClass('hidden');
	} else {

		$('#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicySpozaParp').closest('.form-group').addClass('hidden');
		$('#parp_mainbundle_wnioseknadanieodebraniezasobow_managerSpozaParp').closest('.form-group').addClass('hidden');
		$('#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicy').closest('.form-group').removeClass('hidden');
	}
}

function usunUzytkownikaZwniosku(id, that) {
	var sams = JSON.parse($('#form_samaccountnames').val());

	for (k in sams) {
		if (k == id) {
			delete sams[k];
		}
	}
	$('#form_samaccountnames').val(JSON.stringify(sams));
	var table = $(that).closest('table');
	$(that).closest('tr').remove();
	var i = 0;
	$('tr', $(table)).each(function () {
		$('.rowNumber', $(this)).text(i++);
	})

}

$(document).ready(function () {
	wniosekPracownikSpozaParp();
	$('#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownikSpozaParp').change(wniosekPracownikSpozaParp);
	$('form').submit(function () {
		$(this).find('input[type="submit"], button[type="submit"]').attr('disabled', 'disabled');
	});
	$('[data-toggle="tooltip"]').tooltip();

	$('.tagAjaxInputNoAjax:not([disabled])').tagit({
		'allowSpaces': true,
		'placeholderText': 'Kolejność: nazwisko i imię, następnie naciśnij enter by zaakceptować dodaną wartość i utworzyć kolejną (lub oddziel średnikiem)',
		'singleFieldDelimiter': ";",
		'allowDuplicates': true
		//autocomplete: {delay: 0, minLength: 2, source : '/app_dev.php/user/suggest/'}
	});

	$('.inputAktywneDo').change(function () {
		var v = $(this).val();
		var row = $(this).closest('tr');
		console.info(v);

		if (v != "") {
			$('.inputBezterminowo', $(row)).prop('checked', false);
		}
	});
	$('.inputBezterminowo').change(function () {
		var v = $(this).prop('checked');
		var row = $(this).closest('tr');

		if (v) {
			$('.inputAktywneDo', $(row)).val("");
		}
	});

	$('*[data-ajax]').click(function (event) {
		event.preventDefault();
		var form = extractParentForm(this);
		ajaxFormCall(form);
	});
});

function ZaakceptujWniosek(event, wlasciciel) {
	if (wlasciciel == '1') {
		event.preventDefault();
		$('#acceptConfirm').modal('show')
	}
}

function beforeSubmit(event) {
	var nieWybrane = false;
	$('.select2.multiwybor').each(function () {
		nieWybrane = this.value == "" || nieWybrane;
	});


	if ($('form[data-form="resources"]').length) {
		if ($('#form_wybraneZasoby').val().length == 0) {
			event.preventDefault();
			alert('Należy wybrać przynajmniej jedno uprawnienie do zasobu dla wskazanego pracownika.');
		}
	}

	var message = '';
	var atLeastOne = false;
	if ($('input[data-required]').length > 0) {
		$('input[data-required]').each(function () {
			var element = $(this);
			var inputType = element.attr('type');

			if ('text' === inputType) {
				if (element.val().length > 0) {
					atLeastOne = true;
				}
			}

			if ('checkbox' === inputType) {
				if (element.is(':checked')) {
					atLeastOne = true;
				}
			}
		});
	} else {
		atLeastOne = true;
	}


	if (!atLeastOne) {
		message += 'Brak wybranego terminu końcowego.\n';
	}

	if (nieWybrane) {
		message += 'Musisz wybrać wartość w polu "Moduł" oraz "Poziom dostępu"!';
	}

	if (nieWybrane || !atLeastOne) {
		event.preventDefault();
		alert(message);
	}
}

function extractParentForm(childObject) {
	if (!(childObject instanceof jQuery)) {
		childObject = $(childObject);
	}

	return childObject.parent('form');
}

function ajaxFormCall(formObject) {
	var url = formObject.prop('action');
	var serializedForm = formObject.serialize();

	$.post(url, serializedForm, function (responseData) {
		responseData = JSON.parse(responseData);
		prompt(responseData.message, responseData.token);
	});
}