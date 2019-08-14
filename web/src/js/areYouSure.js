import 'jquery.are-you-sure';

export default $(function() {
    $('form').areYouSure();

    window.onbeforeunload = confirmExit;
    function confirmExit() {
        var f = $('form').length > 0 && $('form').hasClass('dirty');
        if(f)
            return ("Masz niezapisane zmiany w formularzu, na pewno chcesz wyjść?");
    }

    $('form').on('dirty.areYouSure', function() {
        // Enable save button only as the form is dirty.
        $(this).find('input[type="submit"]').removeAttr('disabled');
        $(this).find('input.btncancel').removeAttr('disabled');
    });

    $('form').on('clean.areYouSure', function() {
        // Form is clean so nothing to save - disable the save button.
        $(this).find('input[type="submit"]').attr('disabled', 'disabled');
        $(this).find('input.btncancel').attr('disabled', 'disabled');
    });

    function cancelForm(){
        $('input:not(input[type=submit]):not(input[type=button])').each(function(){
            var v1 = $(this).val();
            var v2 = $(this).data('ays-orig');
            console.log('v1 '+v1+' v2 '+v2)
            if(v2 && v2.length > 0 && v1 && v1.length > 0 && v1 != v2)
                $(this).val(v2);
            
            // if(v1 != v2){
            //     $(this).addClass('dirty');
            // }else{
            //     $(this).removeClass('dirty');
            // }
        });
        $('form').trigger('reinitialize.areYouSure');
    }
});
