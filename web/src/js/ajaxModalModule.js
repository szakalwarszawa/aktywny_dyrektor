export default $(function() {
    $('[data-loadajaxmodal]').click(function (e) {
        e.preventDefault();
        var target = $(this).data('target');
        var href = $(this).prop('href');
        $(target).html('');
        $.get(href, function (data) {
            $(target).html(data);
            $(target).modal();
        })
    });
})
