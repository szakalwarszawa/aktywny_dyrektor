function wniosekPracownikSpozaParp(){
    var v = $('#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownikSpozaParp').prop('checked');
    if(v){
        $('#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicy').closest('.form-group').addClass('hidden');
        $('#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicySpozaParp').closest('.form-group').removeClass('hidden');
    }else{
        
        $('#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicySpozaParp').closest('.form-group').addClass('hidden');
        $('#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicy').closest('.form-group').removeClass('hidden');
    }
}
function usunUzytkownikaZwniosku(id, that){
    console.log('kasuje '+id);
    var sams = JSON.parse($('#form_samaccountnames').val());
    console.log(sams);   
    
    for(k in sams){
        if(k == id){
            delete sams[k];
        }
    }
    $('#form_samaccountnames').val(JSON.stringify(sams));
    var table = $(that).closest('table');
    $(that).closest('tr').remove();
    var i = 0;
    $('tr', $(table)).each(function(){
        $('.rowNumber', $(this)).text(i++);
    })
    
}
$(document).ready(function(){
    wniosekPracownikSpozaParp();
    $('#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownikSpozaParp').change(wniosekPracownikSpozaParp);
    $('form').submit(function(){
        $('*[disabled]').attr('disabled', false);
        return true;    
    });
    $('[data-toggle="tooltip"]').tooltip(); 

    $('.tagAjaxInputNoAjax').tagit({
        'allowSpaces' : true,
        'placeholderText' : 'naciśnij enter by zaakceptować dodaną wartość i utworzyć kolejną'
        //autocomplete: {delay: 0, minLength: 2, source : '/app_dev.php/user/suggest/'}
    });
});