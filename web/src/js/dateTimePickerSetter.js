export default $(function dateTimePickerSetter() {
    $('.datetimepicker:not([disabled])').on('click', function() {
        $('.datetimepicker:not([disabled])').datetimepicker({
            inline: true,
            locale: 'pl',
            format: 'YYYY-MM-DD HH:mm',
        })
    });
    $('.datetimepicker:not([disabled])').on('focus', function() {
        $('.datetimepicker:not([disabled])').datetimepicker({
            inline: true,
            locale: 'pl',
            format: 'YYYY-MM-DD HH:mm',
        })
    });
});
