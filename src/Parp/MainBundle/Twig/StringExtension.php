<?php
// src/Parp/MainBundle/Twig/AppExtension.php
namespace Parp\MainBundle\Twig;

class StringExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('toCamelcase', array($this, 'toCamelcase')),
        );
    }

    public function toCamelcase($string)
    {   
        $out = "";
        $ps = explode("_", $string);
        $i = 0;
        foreach($ps as $p){
            if($i++ != 0)
                $out .= ucfirst(strtolower($p));
            else
                $out .= (strtolower($p));
        }
        //$string = strtoupper($string);

        return $out;
    }

    public function getName()
    {
        return 'app_extension';
    }
}