//---Polyfill dla metody includes---
if (!Array.prototype.includes) {
	Array.prototype.includes = function(searchElement /*, fromIndex*/) {
		'use strict';
		var O = Object(this);
		var len = parseInt(O.length) || 0;
		if (len === 0) {
			return false;
		}
		var n = parseInt(arguments[1]) || 0;
		var k;
		if (n >= 0) {
			k = n;
		} else {
			k = len + n;
			if (k < 0) {
				k = 0;
			}
		}
		var currentElement;
		while (k < len) {
			currentElement = O[k];
			if (
				searchElement === currentElement ||
				(searchElement !== searchElement &&
					currentElement !== currentElement)
			) {
				// NaN !== NaN
				return true;
			}
			k++;
		}
		return false;
	};
}

//---po zaladowaniu dokumentu---
$(document).ready(function() {
	//---sprawdzenie, czy na stronie wystepuje interesujacy nas input---
	if (
		document.getElementById(
			'parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicySpozaParp',
		) !== null &&
		$('#general>h1:contains("nadanie")').length > 0
	) {
		//---przestawienie inputa w tryb: tylko do odczytu---
		//---przestawienie typu inputa na ukryty
		//---usuniecie zbednych elementow tagit-a---
		(function setTypeToReadonly() {
			var input = $(
				'#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicySpozaParp',
			);
			input.attr('readonly', 'readonly');
			input.attr('type', 'hidden');
			input.next('ul.tagit').remove();
		})();

		//---pokaz cialo formularza---
		function displayOutsideEmployeeForm() {
			$('#add-outside-employee-layer').removeClass('hidden');
			resizeLayer();
		}

		//---kontrolowanie wyglądu przycisku dodawania pracowników spoza PARP---
		function buttonDisplayController() {
			var addEmployeeBtn = $('#add-outside-employee-btn');
			addEmployeeBtn.addClass('--narrow');
		}

		//---ukrywanie modala---
		function hideOutsideEmployeeForm() {
			var outsideEmployeeModalForm = $('#add-outside-employee-layer');
			outsideEmployeeModalForm.addClass('hidden');
		}

		//---parsowanie wartości pól do JSONa---
		//---przekazywanie danych pracowników do inputa---
		function passEmployeesToInputUp() {
			var dataToPass = JSON.stringify(
				$('.single-employee')
					.map(function(index) {
						var names = $(this)
							.find('.outside-employee-name')
							.val();
						var surnames = $(this)
							.find('.outside-employee-surname')
							.val();
						var emails = $(this)
							.find('.outside-employee-email')
							.val();
						var singleEmployee = {
							name: names,
							surname: surnames,
							email: emails,
						};
						var singleEmployeeArr = [singleEmployee];
						return singleEmployeeArr;
					})
					.get(),
			);

			var resultInput = $(
				'#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicySpozaParp',
			);
			resultInput.removeClass('tagit-hidden-field');
			resultInput.next('ul.tagit').remove();
			resultInput.val(dataToPass);

			//---pole wyświetlania przykrywajace input---
			if ($('.fake-input').length > 0) {
				$('.fake-input')
					.first()
					.remove();
			}
			var fakeInput = $('<div class="fake-input"></div>');
			resultInput.before(fakeInput);
			var dataToDisplay = JSON.parse(dataToPass);
			var space = ' ';
			var glueEmail = '<i class="fa fa-envelope"/>';
			var employeesToDisplay = '';

			//---wagoniki---
			var inputTrainStart = '<div class="in-train">';
			var inputTrainEnd = '</div>';

			//---------------------------
			for (var i = 0; i < dataToDisplay.length; i++) {
				var singlePerson = '';
				singlePerson =
					inputTrainStart +
					dataToDisplay[i].name +
					space +
					dataToDisplay[i].surname +
					glueEmail +
					dataToDisplay[i].email +
					inputTrainEnd;

				employeesToDisplay += singlePerson;
			}

			fakeInput.html(employeesToDisplay);
		}

		function hideAllert(element) {
			$(element)
				.parent()
				.parent()
				.children('.status-board')
				.text('')
				.removeClass('alert alert-danger')
				.hide();
		}

		function showAllert(element, allertTxt) {
			$(element)
				.parent()
				.parent()
				.children('.status-board')
				.text(allertTxt)
				.addClass('alert alert-danger')
				.show();
		}

		function trimWhitespace() {
			var inputsInModal = $('.modal-form input');
			$.each(inputsInModal, function(idx, item) {
				var text = $(item).val();
				if (text.length > 0) {
					text = text.trim();
					item = $(item).val(text);
					return item;
				}
			});
		}

		//---walidacja pól---
		function inputsValidation() {
			var names = $('.outside-employee-name');
			var surnames = $('.outside-employee-surname');
			var emails = $('.outside-employee-email');
			var regSurnames = /^([A-ZŚŻĆŹŹŃŁ]{1}[A-ZŚŻĆŹŹŃŁa-zęóąśłżźńć/-]{1,24})+$/;
			var regNames = /^([A-ZŚŻŹŃŁ]{1}[a-zęóąśłżźńć]{2,15})+$/; ///^([A-ZŚŻŹŃŁa-zęóąśłżźńć]{3,16})+$/
			var regMail = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
			var status = [];

			//---testowanie pól wg wzorca + wyswietlanie komunikatu---
			function stringTesting(inputsToTest, reg, msg) {
				if (reg.test($(inputsToTest).val())) {
					hideAllert(inputsToTest);
					status.push('ok');
				} else {
					console.log(msg);
					showAllert(inputsToTest, msg);
					status.push('bad');
				}
			}

			$.each(names, function(index, value) {
				stringTesting(value, regNames, 'Podaj imię');
			});
			$.each(surnames, function(index, value) {
				stringTesting(value, regSurnames, 'Podaj nazwisko');
			});
			$.each(emails, function(index, value) {
				stringTesting(value, regMail, 'Podaj adres e-mail');
			});

			var isPassedValidation = status.includes('bad') ? false : true;
			return isPassedValidation;
		}

		//---obsluga przycisku zatwierdz w modalu---
		function confirmation() {
			var confirmBtn = $('#submit-outside-employee');
			confirmBtn.on('click', function(event) {
				event.preventDefault();
				trimWhitespace();
				if (inputsValidation() === true) {
					console.info('Dane przeszły walidację');
					passEmployeesToInputUp();
					buttonDisplayController();
					hideOutsideEmployeeForm();
				}
			});
		}

		//---dynamiczne dostosowanie wymiarów warstwy---
		function resizeLayer() {
			var modalFormLayer = $('#add-outside-employee-layer');
			modalFormLayer.height($(document).height());
		}

		//---nasłuch na zmianę wymiarów okna---
		$(window).resize(function() {
			resizeLayer();
		});

		//---obsluga przycisku dodaj w modalu---
		function addingInputs() {
			var addBtn = $('#add-another-outside-employee');
			addBtn.on('click', function(event) {
				addInputsForNextEmployee();
				resizeLayer();
			});
		}

		//---usuwanie pracownika z formularza---
		function removeEmployee(evFromClick) {
			$(evFromClick.target)
				.parent()
				.parent()
				.remove();
		}

		//---obsługa przycisku usun w modalu---
		function removeEmployeeListener() {
			var modalForm = $('.modal-form');
			modalForm.on('click', '.remove-outside-employee', function(event) {
				event.preventDefault;
				removeEmployee(event);
			});
		}

		//---obsluga przycisku anuluj w modalu---
		function cancel() {
			var cancelBtn = $('.cancel-btn');
			cancelBtn.on('click', function(event) {
				hideOutsideEmployeeForm();
			});
		}

		//---generowanie przycisku modalnego formularza w widoku---
		(function showModalBtn() {
			//---1) zaczep $("#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicySpozaParp")
			var startingPoint = $(
				'#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicySpozaParp',
			);
			//---2) stwórz przycisk $("#showOutsideEmployeeFormBtn")
			var addEmployeeBtn = $(
				'<button type="button">Dodaj zewnętrznego pracownika</button>',
			).attr({
				class: 'btn btn-primary',
				id: 'add-outside-employee-btn',
			}); // --full-width
			//---3) wstrzyknij i wypozycjonuj przycisk
			addEmployeeBtn.appendTo(startingPoint.parent());
			//---4) dodaj nasłuch na przycisk
			addEmployeeBtn.on('click', function(event) {
				displayOutsideEmployeeForm();
			});
			//---5) dodaj nasluch przyciskow w modalu
			addingInputs();
			confirmation();
			cancel();
			removeEmployeeListener();
		})();

		//---zarzadzanie modalem---
		function addInputsForNextEmployee() {
			var template = $('<div class="single-employee"></div>');
			template.html(
				'<div class="form-group"><button class="remove-outside-employee btn btn-xs btn-success">X</button><label for="outside-employee-name">Imię pracownika<input type="text" class="form-control outside-employee-name" placeholder="Wpisz imię pracownika" minlength="3" maxlength="16" /></label><div class="status-board for-name">Podaj pierwsze imię</div></div><div class="form-group"><label for="outside-employee-surname">Nazwisko pracownika<input type="text" class="form-control outside-employee-surname" placeholder="Wpisz nazwisko pracownika" minlength="2" maxlength="32" /></label><div class="status-board for-surname">Podaj nazwisko</div></div><div class="form-group"><label for="outside-employee-email">Email pracownika<input type="email" class="form-control outside-employee-email" placeholder="Podaj adres email pracownika" minlength="6" maxlength="64" /></label><div class="status-board for-email">Podaj adres e-mail</div></div>',
			);

			$('.single-employee')
				.last()
				.after(template);
		}
	}
});
