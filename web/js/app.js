$(function () {
    $('a, button').click(function () {
        var href = $(this).attr('href');
        if (href !== undefined) {
            preventDoubleClick($(this));
        }
    })
})

function preventDoubleClick(object)
{
    if (!(object instanceof jQuery)) {
        object = $(object);
    }

    object.attr('disabled', 'disabled');
    setTimeout(
        function()
        {
            object.removeAttr('disabled');
        }, 8000);
}
