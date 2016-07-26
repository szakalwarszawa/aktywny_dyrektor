function submitFirstForm(e){
    $('#navTabs a[href="#zasob"]').tab('show');
}
$(document).ready(function(){
    $('#parp_mainbundle_wniosekutworzeniezasobu_typWnioskuDoUruchomienia').change(function(){
        if($(this).prop('checked')){
            $('#parp_mainbundle_wniosekutworzeniezasobu_typWnioskuDoRejestru').prop('checked', true); 
            $('#parp_mainbundle_wniosekutworzeniezasobu_typWnioskuDoRejestru').trigger('change');  
        }
    });
    $('#parp_mainbundle_wniosekutworzeniezasobu_typWnioskuZmianaWistniejacym').change(function(){
        if($(this).prop('checked')){
            $('#parp_mainbundle_wniosekutworzeniezasobu_typWnioskuZmianaInformacji').prop('checked', true);  
            $('#parp_mainbundle_wniosekutworzeniezasobu_typWnioskuZmianaInformacji').trigger('change');   
        }
    });
    $('#parp_mainbundle_wniosekutworzeniezasobu_typWnioskuWycofanieZinfrastruktury').change(function(){
        if($(this).prop('checked')){
            $('#parp_mainbundle_wniosekutworzeniezasobu_typWnioskuWycofanie').prop('checked', true); 
            $('#parp_mainbundle_wniosekutworzeniezasobu_typWnioskuWycofanie').trigger('change');    
        }
    });
    
    $('#parp_mainbundle_wniosekutworzeniezasobu_typWnioskuDoRejestru').change(function(){
        if($(this).prop('checked')){
            $('#parp_mainbundle_wniosekutworzeniezasobu_typWnioskuZmianaInformacji, #parp_mainbundle_wniosekutworzeniezasobu_typWnioskuWycofanie, #parp_mainbundle_wniosekutworzeniezasobu_typWnioskuZmianaWistniejacym, #parp_mainbundle_wniosekutworzeniezasobu_typWnioskuWycofanieZinfrastruktury').prop('checked', false);  
        }
    });
    $('#parp_mainbundle_wniosekutworzeniezasobu_typWnioskuZmianaInformacji').change(function(){
        if($(this).prop('checked')){
            $('#parp_mainbundle_wniosekutworzeniezasobu_typWnioskuDoRejestru, #parp_mainbundle_wniosekutworzeniezasobu_typWnioskuWycofanie, #parp_mainbundle_wniosekutworzeniezasobu_typWnioskuDoUruchomienia, #parp_mainbundle_wniosekutworzeniezasobu_typWnioskuWycofanieZinfrastruktury').prop('checked', false);  
        }
    });
    $('#parp_mainbundle_wniosekutworzeniezasobu_typWnioskuWycofanie').change(function(){
        if($(this).prop('checked')){
            $('#parp_mainbundle_wniosekutworzeniezasobu_typWnioskuZmianaInformacji, #parp_mainbundle_wniosekutworzeniezasobu_typWnioskuDoRejestru, #parp_mainbundle_wniosekutworzeniezasobu_typWnioskuZmianaWistniejacym, #parp_mainbundle_wniosekutworzeniezasobu_typWnioskuDoUruchomienia').prop('checked', false);  
        }
    });
    
    $('.tagAjaxInputNoAjax').tagit({
        'allowSpaces' : true,
        'placeholderText' : 'naciśnij enter by zaakceptować dodaną wartość i utworzyć kolejną'
        //autocomplete: {delay: 0, minLength: 2, source : '/app_dev.php/user/suggest/'}
    });
});
function submitWniosekType(e){
    console.log('submitWniosekType');
    var typy = [];
    $('input[type=checkbox]').each(function(){
        if($(this).prop('checked'))
            typy.push($(this).attr('id'));
    });
    console.log(typy);
    console.log('submitWniosekType');
    if(typy.length == 0){
        alert('Musisz wybrać typ wniosku');
    }else{
        $('form[name=parp_mainbundle_wniosekutworzeniezasobu]').removeClass('dirty');
        var href = Routing.generate('wniosekutworzeniezasobu_new_z_type', {'typ1' : typy[0], 'typ2' : (typy.length > 1 ? typy[1] : '')});
        window.location.href = href;
    }
}