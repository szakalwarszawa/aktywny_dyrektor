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

$(document).ready(function(){
    wniosekPracownikSpozaParp();
    $('#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownikSpozaParp').change(wniosekPracownikSpozaParp);
    $('form').submit(function(){
        $('*[disabled]').attr('disabled', false);
        return true;    
    });
});