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
import 'bootstrap';
import 'eonasdan-bootstrap-datetimepicker';
import 'select2';
import 'jquery.maskedinput';
import 'moment';
import '../../../node_modules/moment/locale/pl.js';

function showInfo(info) {
    console.info(
        `%c ${info} `,
        'text-shadow: 2px 1px 0 #7A7A7A; color: #fff; font-size: 18px;',
    );
}

showInfo('Aktywny Dyrektor v2.0 beta');
