import '../scss/app.scss';

require('@fortawesome/fontawesome-pro/css/all.min.css');
require('symfony-collection');

// require('@fortawesome/fontawesome-pro/css/all.min.css');
/* <script type="text/javascript"src="{{ asset('js/moment.min.js') }}"></script>
<script type="text/javascript"src="{{ asset('js/moment-with-langs.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/bootstrap.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/bootstrap-datetimepicker.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/bootstrap-slider.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/jquery.maskedinput.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/bootstrap-filestyle.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/jsapi.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/jquery-ui.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/tag-it.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/jquery.are-you-sure.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/jquery.treegrid.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/jquery.treegrid.bootstrap3.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/colResizable-1.6.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/jquery.tablesorter.js') }}"></script>
<script type="text/javascript" src="{{ asset('js/bootstrap-toggle.min.js') }}"></script> */


// require jQuery normally
// const $ = require('jquery');
import $ from 'jquery';

// create global $ and jQuery variables
global.$ = global.jQuery = $;

// --- inne biblioteki zewnetrzne (node_modules) ---
import 'core-js/stable';
import 'regenerator-runtime/runtime';
// import 'bootstrap';
import '../../../node_modules/bootstrap-sass/assets/javascripts/bootstrap.js';
// import * as modal from '../../../node_modules/bootstrap-sass/assets/javascripts/bootstrap/modal.js';
// import * as popover from '../../../node_modules/bootstrap-sass/assets/javascripts/bootstrap/popover.js';
// import * as tooltip from '../../../node_modules/bootstrap-sass/assets/javascripts/bootstrap/tooltip.js';
// import * as select2 from 'select2';
// import Datepicker from 'bootstrap-datepicker';
import * as datepicker from 'bootstrap-datepicker';
// import 'bootstrap-filestyle'; // osobne entry
import 'bootstrap-slider';
import 'bootstrap-toggle';
// import 'jquery';
import 'jquery-ui';
import 'jquery.maskedinput';
import 'jquery.are-you-sure';
import '../../../node_modules/jquery-treegrid/js/jquery.treegrid.js';
import 'moment';
import 'moment-range';
import '../../../node_modules/moment/locale/pl.js';
import '../../../node_modules/colresizable/colResizable-1.6.min.js';
import * as datetimepicker from 'eonasdan-bootstrap-datetimepicker';
import 'tablesorter';
import 'tag-it';
// import colResizable from '../../../node_modules/colresizable/colResizable-1.6.min.js';

// --- nasze moduły ---
import {smallText, mixText} from './testModule.js';


global.select2 = select2;
global.moment = moment;
global.datepicker = datepicker;
global.datetimepicker = datetimepicker;
// global.colResizable = colResizable;
// global.modal = modal;
// (global.popover = {popover} = bootstrap);
// global.tooltip = tooltip;


// --- test & log ---
if (window.hasOwnProperty('$')) {
    console.log(`%c jQuery is type ${typeof($)} and: ${$} `, 'font-size: 5px; color: pink;');
} else {
    console.warn('no jQuery');
}

if (window.hasOwnProperty('moment')) {
    console.log(`%c moment is type ${typeof(moment)} and: ${moment} `, 'font-size: 7px; font-style: italic; color: brown;');
} else {
    console.warn('no moment');
}

if (window.hasOwnProperty('datepicker')) {
    console.log(`%c datepicker is type ${typeof(datepicker)} and: ${datepicker} `, 'font-size: 9px; color: blue;');
} else {
    console.warn('no datepicker');
}

if (window.hasOwnProperty('popover')) {
    console.log(`%c bootstrap popover is type ${typeof(popover)} and: ${popover} `, 'font-size: 6px; font-style: italic; color: orange;');
} else {
    console.warn('no bootstrap popover');
}

if (window.hasOwnProperty('select2')) {
    console.log(`%c select to is type ${typeof(select2)} `, 'font-size: 10px; color: green;');
} else {
    console.warn('no select2');
}
// --- end of test & log ---
console.log(mixText("Test importu modułów wewnętrznych"));

function showInfo(info) {
    console.info(
        `%c ${info} `,
        'text-shadow: 2px 1px 0 #7A7A7A; color: #fff; font-size: 18px;',
    );
}

showInfo('Aktywny Dyrektor v2.0 beta');

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
})
