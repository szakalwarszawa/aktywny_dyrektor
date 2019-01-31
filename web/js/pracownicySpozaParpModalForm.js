$(document).ready(function () {
    if (
        document.getElementById(
            'parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicySpozaParp'
        ) !== null
    ) {
        //console.log('1 2 3 document ready');
        //---modyfikacja domyslego widoku podstrony---

        //---pokaz cialo formularza---
        function displayOutsideEmployeeForm() {
            //console.info('displayOutsideEmployeeForm()');
            var outsideEmployeeModalForm = $('#add-outside-employee-layer');
            $('#add-outside-employee-layer').removeClass('hidden');
            //console.log(outsideEmployeeModalForm);
            inputsValidation();
        }

        function buttonDisplayController() {
            //console.info('buttonDisplayController()');
            var addEmployeeBtn = $('#add-outside-employee-btn');
            addEmployeeBtn.removeClass('--full-width');
            addEmployeeBtn.addClass('--narrow');
        }

        //---ukrywanie modala---
        function hideOutsideEmployeeForm() {
            //console.info('hideOutsideEmployeeForm()');
            var outsideEmployeeModalForm = $('#add-outside-employee-layer');
            //$('#add-outside-employee-layer').addClass('hidden');
            outsideEmployeeModalForm.addClass('hidden');
            //console.log(outsideEmployeeModalForm);
        }

        function isEmail(event) {
            console.log('Email testing');
            //console.log(event.target);
            //var outsideEmployeeEmail = $('.outside-employee-email');
            console.log('Pole do przetestowania ', event.target);
            // var regexp = /^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/;
            var regexp = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;
            if (regexp.test(event.target.value)) {
                $('.status-board').removeClass('alert alert-danger');
                return true;
            } else {
                //event.target.after('<div>Podaj poprawny adres e-mail</div>');
                $('.status-board').text('Podaj poprawny adres e-mail').addClass('alert alert-danger');
                console.log('Podaj poprawny adres e-mail');
                event.target.focus();
                return false;
            }
        }

        function isText(event, msg) {
            var regx = /^([A-Za-z]{3,25})+$/;
            console.log('text testing');
            console.log('Pole tekstowe do przetestowania ', event.target.value);
            if (regx.test(event.target.value)) {
                $('.status-board').removeClass('alert alert-danger');
                return true;
            } else {
                console.log("podaj imie i nazwisko");
                $('.status-board').text(msg).addClass('alert alert-danger');
                event.target.focus();
                return false;
            }
        }

        function inputsValidation() {
            console.log("dodano nasluch na pola");
            var modalForm = $('.modal-form');

            var employees = modalForm.find('.form-group');
            employees.on('blur', '.outside-employee-email', function () {
                isEmail(event);
            });
            employees.on('blur', '.outside-employee-name', function (event) {
                isText(event, "Podaj imię");
            });
            employees.on('blur', '.outside-employee-surname', function (event) {
                isText(event, "Podaj nazwisko");
            });
        }

        //---obsluga przycisku zatwierdz w modalu---
        function confirmation() {
            var confirmBtn = $('#submit-outside-employee');
            confirmBtn.on('click', function (event) {
                event.preventDefault();
                if ($('.modal-form input').val(length > 0)) {
                    passEmployeesToInputUp();
                    buttonDisplayController();
                    hideOutsideEmployeeForm();
                } else {
                    $('.status-board').text('Pola nie mogą być puste').addClass('alert alert-danger');
                }

            });
        }

        //---obsluga przycisku dodaj w modalu---
        function addingInputs() {
            var addBtn = $('#add-another-outside-employee');
            addBtn.on('click', function (event) {
                addInputsForNextEmployee();
            });
        }

        //---generowanie przycisku modalnego formularza w widoku---
        (function showModalBtn() {
            //console.info('showModalBtn()');
            //---1) zaczep $('#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicySpozaParp')
            var startingPoint = $(
                '#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicySpozaParp'
            );
            //---2) stwórz przycisk $('#showOutsideEmployeeFormBtn')
            var addEmployeeBtn = $(
                '<button type="button">Dodaj zewnętrznego pracownika</button>'
            ).attr({
                class: 'btn btn-primary --full-width',
                id: 'add-outside-employee-btn'
            });
            //---3) wstrzyknij i wypozycjonuj przycisk
            addEmployeeBtn.appendTo(startingPoint.parent());
            //---4) dodaj nasłuch na przycisk
            addEmployeeBtn.on('click', function (event) {
                displayOutsideEmployeeForm();
            });

            //---5) dodaj nasluch przyciskow w modalu
            confirmation();
            addingInputs();
        })();

        //---zarzadzanie modalem---
        function addInputsForNextEmployee() {
            //console.info('addInputsForNextEmployee()');
            var modalForm = $('.modal-form');

            var template = $('<div class="single-employee"></div>');
            template.html(
                '<div class="form-group"><label for="outside-employee-name">Imię pracownika<input type="text" class="form-control outside-employee-name" placeholder="Wpisz imię pracownika" minlength="3" maxlength="16" /></label></div><div class="form-group"><label for="outside-employee-surname">Nazwisko pracownika<input type="text" class="form-control outside-employee-surname" placeholder="Wpisz nazwisko pracownika" minlength="2" maxlength="32" /></label></div><div class="form-group"><label for="outside-employee-email">Email pracownika<input type="email" class="form-control outside-employee-email" placeholder="Podaj adres email pracownika" minlength="6" maxlength="64" /></label></div>'
            );

            $('.single-employee')
                .last()
                .after(template);

            inputsValidation()
        }

        function passEmployeesToInputUp() {
            //console.info('passEmployeesToInputUp()');

            var employeesFields = $('.single-employee');
            //console.log('Pracownicy', employeesFields, employeesFields.length);

            var dataToPass = JSON.stringify(
                $('.single-employee')
                .map(function (index) {
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
                        email: emails
                    };
                    var singleEmployeeArr = [singleEmployee];
                    return singleEmployeeArr;
                })
                .get()
            );

            //console.log(dataToPass);
            var resultInput = $(
                '#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicySpozaParp'
            );
            resultInput.removeClass('tagit-hidden-field');
            resultInput.next('ul.tagit').remove();
            //console.log(resultInput);
            resultInput.val(dataToPass);

            //---pole wyświetlania przykrywajace input---
            var fakeInput = $('<div class="form-control" id="fake-input"></div>');
            resultInput.before(fakeInput);
            var dataToDisplay = JSON.parse(dataToPass);
            //console.log(dataToDisplay);
            var space = ' ';
            var glueEmail = ', email: ';
            var employeesToDisplay = '';
            for (var i = 0; i < dataToDisplay.length; i++) {
                var singlePerson = '';
                singlePerson =
                    ' ' +
                    dataToDisplay[i].name +
                    space +
                    dataToDisplay[i].surname +
                    glueEmail +
                    dataToDisplay[i].email;
                //console.log('Pracownik ', singlePerson);
                employeesToDisplay += singlePerson;
            }

            //console.log('Pracownicy ', employeesToDisplay);
            fakeInput.text(employeesToDisplay);
        }
    }
});