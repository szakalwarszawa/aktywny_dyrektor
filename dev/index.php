<?php
namespace zoo;
use zoo\pets;
error_reporting(E_ALL);
ini_set('display_errors', 'on');

include("Pet.php");

//use zoo\Pets\Pet;
//use function zoo\pets\funkcja;
echo("dev zaczynam <br>\n");

//$pet = new Pet();
//echo $pet->nazwa;

pets\funkcja();
echo("<br>koncze<br>\n");
?>