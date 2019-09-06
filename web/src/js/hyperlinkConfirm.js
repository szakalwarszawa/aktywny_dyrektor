
export default $(function() {
    $('a[data-confirm]').click(function (e){
        e.preventDefault();
        var url = $(this).attr('href');
        var confirmMessage = $(this).data('confirm');
        if (confirm(confirmMessage)) {
            window.location = url;
        }
    })
})
