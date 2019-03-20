$(document).ready(function () {
	var kontoSelect = $('#parp_mainbundle_edycjauzytkownika_isDisabled');
	console.log('USER EDIT', kontoSelect);
	$(kontoSelect).on('change', function () {
		console.log($(kontoSelect).val());
	});
});