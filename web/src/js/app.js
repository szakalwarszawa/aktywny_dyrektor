import '../scss/app.scss';

// require('@fortawesome/fontawesome-pro/css/all.min.css');

// --- require jQuery normally ---
import $ from 'jquery';
// --- create global $ and jQuery variables ---
global.$ = global.jQuery = $;

// --- inne biblioteki zewnetrzne (node_modules) ---
import 'core-js/stable';
import 'regenerator-runtime/runtime';
import '../../../node_modules/bootstrap-sass/assets/javascripts/bootstrap.js';
// import Datepicker from 'bootstrap-datepicker';
import * as datepicker from 'bootstrap-datepicker';
// import 'bootstrap-filestyle'; // osobne entry
import 'bootstrap-slider';
import 'bootstrap-toggle';
// import 'jquery-ui';
import 'jquery.maskedinput';
import 'jquery.are-you-sure';
import '../../../node_modules/jquery-treegrid/js/jquery.treegrid.js';
import 'moment';
import 'moment-range';
import '../../../node_modules/moment/locale/pl.js';
import * as datetimepicker from 'eonasdan-bootstrap-datetimepicker';
import 'tablesorter';
// import 'tag-it';
import '@fortawesome/fontawesome-pro';
import 'webpack-jquery-ui';
require('webpack-jquery-ui/css'); // ekhmm, nie moge

// --- nasze moduły ---
import { smallText, bigText } from './testModule.js';
import { showGreeting, showSmallInfo, showBigInfo } from './showInfo.js';
import columnResizerSetter from './columnResizerSetter';
import symfonyCollectionSetter from './symfonyCollectionSetter';
import areYouSure from './areYouSure'; // moduł IIFE


// --- set another globals ---
global.select2 = select2;
global.moment = moment;
global.datepicker = datepicker;
global.datetimepicker = datetimepicker;


// --- test & log ---
// if (window.hasOwnProperty('$')) {
//     console.log(`%c jQuery is type ${typeof($)} and: ${$} `, 'font-size: 8px; color: pink;');
// } else {
//     console.warn('no jQuery');
// }

// if (window.hasOwnProperty('moment')) {
//     console.log(`%c moment is type ${typeof(moment)} and: ${moment} `, 'font-size: 7px; font-style: italic; color: brown;');
// } else {
//     console.warn('no moment');
// }

// if (window.hasOwnProperty('datepicker')) {
//     console.log(`%c datepicker is type ${typeof(datepicker)} and: ${datepicker} `, 'font-size: 9px; color: blue;');
// } else {
//     console.warn('no datepicker');
// }

// if (window.hasOwnProperty('select2')) {
//     console.log(`%c select to is type ${typeof(select2)} `, 'font-size: 10px; color: green;');
// } else {
//     console.warn('no select2');
// }
// console.log(bigText("Test importu modułów wewnętrznych"));
// --- end of test & log ---
showGreeting('Aktywny Dyrektor v2.0 beta');
columnResizerSetter();
symfonyCollectionSetter();
