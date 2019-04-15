$(document).ready(function () {
	var kontoSelect = $('#parp_mainbundle_edycjauzytkownika_isDisabled');
	$(kontoSelect).firstChild.remove();
	var hiddenReason = $('#powod_wylaczenia_w_ad');
	$(kontoSelect).on('change', function () {
		console.log($(kontoSelect).val());
		if ($(kontoSelect).val() === '1') {
			$(hiddenReason).removeClass('hidden');
		} else if ($(kontoSelect).val() === '0' && $(hiddenReason).hasClass('has-error') === false && $(hiddenReason).hasClass('hidden') === false) {
			$(hiddenReason).addClass('hidden');
		} else {
			return;
		}
	});
});