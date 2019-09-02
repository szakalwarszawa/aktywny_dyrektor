export default $(document).ready(function tagItInitializer() {
    $('.tagAjaxInputUsers:not([disabled])').tagit({
        autocomplete: {delay: 0, minLength: 2, source : '/app_dev.php/user/suggest/'},
        allowSpaces: true,
        'placeholderText' : 'naciśnij enter by zaakceptować dodaną wartość i utworzyć kolejną (lub oddziel średnikem ;)',
        'singleFieldDelimiter': ";",
        'allowDuplicates' : true
    });
    $('.tagAjaxInput:not([disabled])').tagit({
        allowSpaces: true,
        'placeholderText' : 'naciśnij enter by zaakceptować dodaną wartość i utworzyć kolejną (lub oddziel średnikem ;)',
        'singleFieldDelimiter': ";",
        'allowDuplicates' : true
    });
});
