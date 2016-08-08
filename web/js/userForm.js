$(document).ready(function(){
   $('#form_cn').change(function(){
      var val = $(this).val();
      val = val.replace(" ", "_").toLowerCase();
      $('#form_samaccountname').val(val); 
   }); 
});
//$('#form_fromWhen').mask("9999-99-99");
$('#suggestinitials').click(function () {
    var samaccountname = $('#form_samaccountname').val();
    var department = $('#form_department').val();
    var cn = $('#form_name').val();
    if (!samaccountname.trim()) {
        alert('Prosze wprrowadzic nazwę konta.');
        return;
    }
    if (!cn.trim()) {
        alert('Prosze wprowadzic imię i nazwisko pracownika.');
        return;
    }
    if (!department.trim()) {
        alert('Nie wybrano Biura/Departamentu');
        return;
    }
    var url = Routing.generate('suggest_initials');
    $.post(url, {samaccountname: samaccountname, department: department, cn: cn}, function (data) {
        $("#form_initials").val(data);
    }).fail(function () {
        alert("Wystapił błąd pobierania danych!");
    })
});
$('#nadaj').click(function () {
    //$("#myModal").modal('show');
    $('#myModal').modal('toggle');
});
$("#imienazwisko").keyup(function () {
    var imienazwisko = null;
    imienazwisko = $("#imienazwisko").val();
    if (imienazwisko.trim()) {
        var url = Routing.generate('find_manager');
        $.post(url, {imienazwisko: imienazwisko}, function (data) {
            $("#lista").html(data);
        }).fail(function () {
            alert("Wystapił błąd pobierania danych przy wyborze przełożonego!");
        })
    }
});
$('#zapisz').click(function () {
    $("#myModal").modal('hide');
    $("#form_manager").val($("#manager").val());
    $("#imienazwisko").val();
});