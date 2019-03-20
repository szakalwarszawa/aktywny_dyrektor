$(document).ready(function () {
	var kontoSelect = $('#parp_mainbundle_edycjauzytkownika_isDisabled');
	var hiddenReason = $('#powod_wylaczenia_w_ad');
	console.log('USER EDIT', kontoSelect);
	$(kontoSelect).on('change', function () {
		if ($(kontoSelect)[0].val() === "1") {
			console.log('TAK');
			$(hiddenReason).removeClass('hidden');
		} else if ($(kontoSelect)[0].val() === "0" && $(hiddenReason).hasClass('hidden') === false) {
			console.log('NIE');
			$(hiddenReason).addClass('hidden');
		} else {
			return;
		}
	});
});