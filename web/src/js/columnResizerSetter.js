import * as ColumnResizer from '../../../node_modules/column-resizer/dist/column-resizer.js';
import { showSmallInfo } from './showInfo.js';

function guid() {
    function s4() {
      return Math.floor((1 + Math.random()) * 0x10000)
        .toString(16)
        .substring(1);
    }
    return s4() + s4() + '_' + s4() + '_' + s4() + '_' +
      s4() + '_' + s4() + s4() + s4();
}

// --- column-resizer ---
export default function ColumnResizerSetter() {
    window.onload = function() {
        let colResizable = ColumnResizer.default;

        $("table").each(function(){
            if($(this).closest('.tab-pane').length == 0){
                let id = $(this).attr('id');
                if (typeof myVar == 'undefined'){
                    id = "tableId"+guid();
                    $(this).attr('id', id);
                }
                showSmallInfo(`From ColumnResizerSetter.js module: Tabelka z id ${id}`);
                const tableToResize = document.getElementById(id);
                new colResizable(tableToResize, {
                    liveDrag:true,
                    gripInnerHtml:"<div class='grip'></div>",
                    draggingClass:"dragging",
                    resizeMode:'fit'
                });
            }
        });
    };
}
