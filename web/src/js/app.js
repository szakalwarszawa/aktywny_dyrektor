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

// --- biblioteki zewnetrzne (node_modules) ---
import 'core-js/stable';
import 'regenerator-runtime/runtime';
import 'bootstrap-sass';
// import * as select2 from 'select2';
// import Datepicker from 'bootstrap-datepicker';
import 'bootstrap-datepicker';
// import 'bootstrap-filestyle'; // osobne entry
import 'bootstrap-slider';
import 'bootstrap-toggle';
import '../../../node_modules/colresizable/colResizable-1.6.min.js';
import * as datetimepicker from 'eonasdan-bootstrap-datetimepicker';
import 'jquery';
import 'jquery-ui';
import 'jquery.maskedinput';
import 'jquery.are-you-sure';
import '../../../node_modules/jquery-treegrid/js/jquery.treegrid.js';
import 'moment';
import 'moment-range';
import '../../../node_modules/moment/locale/pl.js';

import 'tablesorter';
import 'tag-it';

// require jQuery normally
// const $ = require('jquery');
import $ from 'jquery';

// create global $ and jQuery variables
global.$ = global.jQuery = $;

global.select2 = select2;
global.datepicker = datepicker;
global.datetimepicker = datetimepicker;


// fetch("./node_modules/bootstrap-datepicker/dist/js/bootstrap-datepicker.js")
//     .then(resp => {
//         if (resp.ok) {
//             return resp.json()
//         } else {
//             return Promise.reject(resp)
//         }
//     })
//     .then(resp => {
//         console.log(resp)
//     })
//     .catch(error => {
//         if (error.status === 404) {
//             console.log("Błąd: żądany adres nie istnieje");
//         }
//     });

if (window.hasOwnProperty('datepicker')) {
    console.info(typeof(datepicker));
    console.log(`%c ${datepicker} `, 'font-size: 9px; color: blue;');
} else {
    console.warn('no datepicker');
}

console.log(`%c ${select2} `, 'font-size: 6px;');

function showInfo(info) {
    console.info(
        `%c ${info} `,
        'text-shadow: 2px 1px 0 #7A7A7A; color: #fff; font-size: 18px;',
    );
}

showInfo('Aktywny Dyrektor v2.0 beta');
