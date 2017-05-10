function wniosekPracownikSpozaParp(){
    var v = $('#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownikSpozaParp').prop('checked');
    if(v){
        $('#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicy').closest('.form-group').addClass('hidden');
        $('#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicySpozaParp').closest('.form-group').removeClass('hidden');
        $('#parp_mainbundle_wnioseknadanieodebraniezasobow_managerSpozaParp').closest('.form-group').removeClass('hidden');
    }else{
        
        $('#parp_mainbundle_wnioseknadanieodebraniezasobow_pracownicySpozaParp').closest('.form-group').addClass('hidden');
        $('#parp_mainbundle_wnioseknadanieodebraniezasobow_managerSpozaParp').closest('.form-group').addClass('hidden');
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

    $('.tagAjaxInputNoAjax:not([disabled])').tagit({
        'allowSpaces' : true,
        'placeholderText' : 'naciśnij enter by zaakceptować dodaną wartość i utworzyć kolejną (lub oddziel średnikem ;)',
        'singleFieldDelimiter': ";",
        'allowDuplicates' : true
        //autocomplete: {delay: 0, minLength: 2, source : '/app_dev.php/user/suggest/'}
    });
    
    $('.inputAktywneDo').change(function(){
        var v = $(this).val();
        var row = $(this).closest('tr');
        console.log('.inputAktywneDo');
        console.log(v);
        console.log(row);
        if(v != ""){
            $('.inputBezterminowo', $(row)).prop('checked', false);
        }
    });
    $('.inputBezterminowo').change(function(){
        var v = $(this).prop('checked');
        var row = $(this).closest('tr');
        console.log('.inputBezterminowo');
        console.log(v);
        console.log(row);
        
        if(v){
            $('.inputAktywneDo', $(row)).val("");
        }
    });
    //form[userzasoby][0][bezterminowo]
    
});

function ZaakceptujWniosek(event, wlasciciel){
    if(wlasciciel == '1'){
        event.preventDefault();
        $('#acceptConfirm').modal('show')
    }
}
function beforeSubmit(event){
    var nieWybrane = false;
    $('.select2.multiwybor').each(function(){
        console.log(this.value);
        nieWybrane = this.value == "" || nieWybrane;
    });
    
    if(nieWybrane){
        event.preventDefault();
        alert('Musisz wybrać wartość w polu "Moduł" oraz "Poziom dostępu"!');
    }
    //
}