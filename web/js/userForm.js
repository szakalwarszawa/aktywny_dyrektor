$(document).ready(function(){
   $('#form_cn').change(function(){
       var data = {'name' : $(this).val()};
       
       var url = Routing.generate('userSuggestLoginAction');
            $.post(url, data, function (ret) {
                $('#form_samaccountname').val(ret);
            }).fail(function () {
                alert("Wystapił błąd pobierania danych do wygenerowania loginu!");
            })
       
      var val = $(this).val();
      val = val.replace(" ", "_").toLowerCase();
      $('#form_samaccountname').val(val); 
   }); 
});
//$('#form_fromWhen').mask("9999-99-99");
$('#suggestinitials').click(function () {
    suggestInitials();
});
function suggestInitials(){
    var samaccountname = $('#form_samaccountname').val();
    var department = $('#form_department').val();
    var cn = $('#form_cn').val();
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
}
$('#nadaj').click(function () {
    //$("#myModal").modal('show');
    $('#myModal').modal('toggle');
});
$("#imienazwisko").keyup(function (e) {
    if (e.keyCode == 13) {
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
    }
});
$('#form_cn').change(function(){suggestInitials();});
$('#zapisz').click(function () {
    $("#myModal").modal('hide');
    $("#form_manager").val($("#manager").val());
    $("#imienazwisko").val();
});