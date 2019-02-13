$(document).ready(function() {
	//---sprawdzenie, czy na stronie wystepuje interesujacy nas input---
	if (
		document.getElementById(
			'parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicySpozaParp',
		) !== null &&
		$('#general>h1:contains("nadanie")').length > 0
	) {
		//---przestawienie inputa w tryb: tylko do odczytu---
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
			var outsideEmployeeModalForm = $('#add-outside-employee-layer');
			$('#add-outside-employee-layer').removeClass('hidden');
			inputsListeners();
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
			//---ukrycie modala musi wywolac nasluchiwanie na widoku glownym---
			//---(aby moc usunac dodanych pracownikach)---
			//removeListener();
		}

		//---walidacja adresu email---
		function isEmail(event) {
			console.log('Pole do przetestowania ', event.target);
			var regexp = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
			if (regexp.test(event.target.value)) {
				$('.status-board')
					.text('')
					.removeClass('alert alert-danger');
				return true;
			} else {
				$('.status-board')
					.text('Podaj poprawny adres e-mail')
					.addClass('alert alert-danger');
				console.log('Podaj poprawny adres e-mail');
				event.target.focus();
				return false;
			}
		}

		//---walidacja pol tekstowych---
		function isText(event, msg) {
			var regx = /^([A-ZŚŻŹŃŁa-zęóąśłżźńć/-]{3,25})+$/;
			if (regx.test(event.target.value)) {
				$('.status-board')
					.text('')
					.removeClass('alert alert-danger');
				return true;
			} else {
				console.log('podaj imie i nazwisko');
				$('.status-board')
					.text(msg)
					.addClass('alert alert-danger');
				event.target.focus();
				return false;
			}
		}

		//---parsowanie wartości pól do JSONa---
		//---przekazywanie danych pracowników do inputa---
		//---
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
			// var removeIcon = '<i class="fa fa-trash-o"/>';
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
					// removeIcon +
					inputTrainEnd;
				employeesToDisplay += singlePerson;
			}

			//console.log('Pracownicy ', employeesToDisplay);
			fakeInput.html(employeesToDisplay);
		}

		//---dodawanie na pola nasluchu odpalajacego walidacje---
		function inputsListeners() {
			var modalForm = $('.modal-form');
			var employees = modalForm.find('.form-group');
			employees.on('blur', '.outside-employee-email', function() {
				isEmail(event);
			});
			employees.on('blur', '.outside-employee-name', function(event) {
				isText(event, 'Podaj imię');
			});
			employees.on('blur', '.outside-employee-surname', function(event) {
				isText(event, 'Podaj nazwisko');
			});
		}

		function isEmpty() {
			console.log('isEmpty()');
			var inputsInModal = $('.modal-form input');
			var inputsLengthsTab = [];
			$.each(inputsInModal, function(index, value) {
				inputsLengthsTab.push(
					$(value)
						.val()
						.trim().length,
				);
				console.log(index, inputsLengthsTab);
			});
			//console.log(inputsLengthsTab.includes(0));
			return inputsLengthsTab.includes(0);
		}

		//---obsluga przycisku zatwierdz w modalu---
		function confirmation() {
			//console.log('confirm');
			var confirmBtn = $('#submit-outside-employee');
			confirmBtn.on('click', function(event) {
				event.preventDefault();
				var inputsInModal = $('.modal-form input');
				//var inputsInModal = document.querySelectorAll('.modal-form input');
				console.log('inputs in modal form: ', inputsInModal);
				if (!isEmpty()) {
					console.log('nie zawiera pustych', !isEmpty());
					passEmployeesToInputUp();
					buttonDisplayController();
					hideOutsideEmployeeForm();
				} else {
					console.log('zawiera puste', !isEmpty());
					$('.status-board')
						.text('Pola nie mogą być puste')
						.addClass('alert alert-danger');
				}
			});
		}

		function resizeLayer() {
			var modalFormLayer = $('#add-outside-employee-layer');
			modalFormLayer.height($(document).height());
		}

		//---obsluga przycisku dodaj w modalu---
		function addingInputs() {
			var addBtn = $('#add-another-outside-employee');
			addBtn.on('click', function(event) {
				addInputsForNextEmployee();
				resizeLayer();
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
			//---1) zaczep $('#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicySpozaParp')
			var startingPoint = $(
				'#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicySpozaParp',
			);
			//---2) stwórz przycisk $('#showOutsideEmployeeFormBtn')
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
			confirmation();
			addingInputs();
			cancel();
		})();

		//---zarzadzanie modalem---
		function addInputsForNextEmployee() {
			var template = $('<div class="single-employee"></div>');
			template.html(
				'<div class="form-group"><label for="outside-employee-name">Imię pracownika<input type="text" class="form-control outside-employee-name" placeholder="Wpisz imię pracownika" minlength="3" maxlength="16" /></label></div><div class="form-group"><label for="outside-employee-surname">Nazwisko pracownika<input type="text" class="form-control outside-employee-surname" placeholder="Wpisz nazwisko pracownika" minlength="2" maxlength="32" /></label></div><div class="form-group"><label for="outside-employee-email">Email pracownika<input type="email" class="form-control outside-employee-email" placeholder="Podaj adres email pracownika" minlength="6" maxlength="64" /></label></div>',
			);

			$('.single-employee')
				.last()
				.after(template);

			inputsListeners();
		}
	}
});
