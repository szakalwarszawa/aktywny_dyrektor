<?php    
//    die();
// Sprawdzenie czy adres uzytkownika (lub jego czesc) jest na liscie dozwolonych IP.
$ipDozwolone = array(
       '10.10.120.',               // Wewnetrzne adresy. 
       '192.168.',               // Wewnetrzne adresy. 
       ''
);
function czyIpJestDozwolony($ip = null)
{
       $ip = (string) $ip;
       $remoteAddr = (string) @$_SERVER['REMOTE_ADDR'];
       $wycinek = substr($remoteAddr, 0, strlen($ip));
       $wynik = ($wycinek === $ip) ? true : false;
       return $wynik;
}
$dostepZabroniony = true;
foreach ($ipDozwolone as $ip) {
       if (true === czyIpJestDozwolony($ip)) {
               $dostepZabroniony = false;
               break;
       }
}
if (true === $dostepZabroniony) {
       header('HTTP/1.0 403 Forbidden');
       exit('Brak uprawnień do korzystania z zasobu.');
}
// Koniec sprawdzania IP.

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;
// If you don't want to setup permissions the proper way, just uncomment the following PHP line
// read http://symfony.com/doc/current/book/installation.html#configuration-and-setup for more information
umask(0002);
error_reporting(-1);
ini_set('display_errors', 1); 
// This check prevents access to debug front controllers that are deployed by accident to production servers.
// Feel free to remove this, extend it, or make something more sophisticated.
if (1 == 0 && (isset($_SERVER['HTTP_CLIENT_IP'])
    || isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    || !in_array(@$_SERVER['REMOTE_ADDR'], array('127.0.0.1', 'fe80::1', '::1'))
)) {
    header('HTTP/1.0 403 Forbidden');
    exit('You are not allowed to access this file. Check '.basename(__FILE__).' for more information.');
}

$loader = require_once __DIR__.'/../app/bootstrap.php.cache';
Debug::enable();

require_once __DIR__.'/../app/AppKernel.php';

$kernel = new AppKernel('dev', true);
$kernel->loadClassCache();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
