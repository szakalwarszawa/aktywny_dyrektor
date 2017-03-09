<?php
error_reporting(E_ALL);
ini_set('display_errors', 'on');
    define ('CONSTANT');
    define ('_CONSTANT');
    define('EMPTY', '');
    
    if(!empty(EMPTY)){
        if(!(boolean) _CONSTANT){
            print "1";
        }
    }else if(constant('CONSTANT') == 1){
        print "2";
    }