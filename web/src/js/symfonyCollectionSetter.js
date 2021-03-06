import 'symfony-collection';

export default function symfonyCollectioSetter() {
    $(document).ready(function () {
        if ($('.collection').length) {
            $('.collection').collection({
                up: '<i class="collection-element fa-2x fas fa-angle-up"></i>',
                down: '<i class="collection-element fa-2x fas fa-angle-down"></i>',
                add: '<a href="#" class="btn btn-info">Dodaj <i class="collection-element text-success fa-1x fas fa-plus"></i></a>',
                remove: '<a href="#" class="btn btn-info">Usuń <i class="collection-element text-danger fa-1x fas fa-trash"></i></a>',
                duplicate: '<i class="collection-element fa-2x fas fa-clone"></i>',
                allow_up: false,
                allow_down: false,
                add_at_the_end: true,
                preserve_names: true,
                after_add: function (collection, element) {
                    $(element).find('select').each(function () {
                        if ($(this).hasClass('select2') && !$(this).hasClass('select2-hidden-accessible')) {
                            $(this).select2({dropdownAutoWidth : true, width: '100%'});
                        }
                    });
                },
            });
        }
    });
}
