console.log('USER EDIT');

$(document).ready(function () {
	var kontoSelect = $('#parp_mainbundle_edycjauzytkownika_isDisabled');
	$(kontoSelect).on('change', function () {
		console.log("Zmiana");
	});
});