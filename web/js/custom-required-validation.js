$(document).ready(function () {
	//-----------------------------
	var submitBottom = $('#parp_mainbundle_wniosekutworzeniezasobu_submit');
	var submitTop = $('#parp_mainbundle_wniosekutworzeniezasobu_submit2');
	console.log("---where is my script---");

	var inputWlascicielZasobu = $('#parp_mainbundle_wniosekutworzeniezasobu_zasob_wlascicielZasobu');
	console.log(inputWlascicielZasobu);
	console.log(inputWlascicielZasobu.val());


	$(submitBottom).on('click', function (e) {

		console.log("EVENT: ", e, e.target);
	});
	//-----------------------------
});