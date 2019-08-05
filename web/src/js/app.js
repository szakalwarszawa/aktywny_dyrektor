import '../scss/app.scss';

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
import '../../../node_modules/bootstrap-sass/assets/javascripts/bootstrap/tooltip.js';
import '../../../node_modules/bootstrap-sass/assets/javascripts/bootstrap/popover.js';
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

global.select2 = select2;
global.moment = moment;
global.datepicker = datepicker;
global.datetimepicker = datetimepicker;
// global.colResizable = colResizable;


if (window.hasOwnProperty('popover')) {
    console.log(`%c bootstrap popover is type ${typeof(popover)} and: ${popover} `, 'font-size: 4px; font-style: italic; color: orange;');
} else {
    console.warn('no bootstrap popover');
}
global.popover = popover;

// --- test & log ---
if (window.hasOwnProperty('$')) {
    console.log(`%c jQuery is type ${typeof($)} and: ${$} `, 'font-size: 5px; color: pink;');
} else {
    console.warn('no jQuery');
}

if (window.hasOwnProperty('moment')) {
    console.log(`%c moment is type ${typeof(moment)} and: ${moment} `, 'font-size: 5px; font-style: italic; color: brown;');
} else {
    console.warn('no moment');
}

if (window.hasOwnProperty('datepicker')) {
    console.log(`%c datepicker is type ${typeof(datepicker)} and: ${datepicker} `, 'font-size: 9px; color: blue;');
} else {
    console.warn('no datepicker');
}

if (window.hasOwnProperty('select2')) {
    console.log(`%c select to is type ${typeof(select2)} and: ${select2} `, 'font-size: 4px; color: green;');
} else {
    console.warn('no select2');
}
// --- end of test & log ---

function showInfo(info) {
    console.info(
        `%c ${info} `,
        'text-shadow: 2px 1px 0 #7A7A7A; color: #fff; font-size: 18px;',
    );
}

showInfo('Aktywny Dyrektor v2.0 beta');
