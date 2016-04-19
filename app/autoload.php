<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;

/**
 * @var ClassLoader $loader
 */
$loader = require __DIR__.'/../vendor/autoload.php';
AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

$loader->add(
    // Swift, Twig etc.
    'PHPExcel' ,__DIR__ . '/../vendor/phpexcel/lib/PHPExcel'
);
return $loader;
