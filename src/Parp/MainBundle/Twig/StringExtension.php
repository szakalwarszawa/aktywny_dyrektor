<?php
// src/Parp/MainBundle/Twig/AppExtension.php
namespace Parp\MainBundle\Twig;

class StringExtension extends \Twig_Extension
{
    protected $renameService;

    public function __construct(\Parp\MainBundle\Services\RenameService $renameService)
    {
        $this->renameService = $renameService;
    }
    
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('toCamelcase', array($this, 'toCamelcase')),
            new \Twig_SimpleFilter('gridTitles', array($this, 'gridTitles')),
            new \Twig_SimpleFilter('datetime', array($this, 'datetimeFormat')),
            new \Twig_SimpleFilter('objectTitles', array($this, 'objectTitles')),
            new \Twig_SimpleFilter('actionTitles', array($this, 'actionTitles')),
            new \Twig_SimpleFilter('getObjectValue', array($this, 'getObjectValue')),
            new \Twig_SimpleFilter('getMultipleCheckboxLabel', array($this, 'getMultipleCheckboxLabel')),
            new \Twig_SimpleFilter('getMultipleCheckboxLabelClasses', array($this, 'getMultipleCheckboxLabelClasses')),
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
    public function datetimeFormat($value){
        if ($value instanceof \DateTime){
            $value = $value->format("Y-m-d H:i:s");
        }else{
            $value = "";
        }
        return $value;
    }
    public function gridTitles($var)
    {
        $ret = "";
        $last = "";
        for($i = 0; $i < strlen($var); $i++){
            $c = $var[$i];
            $duza = $c == strtoupper($c);
            if($last == "mala" && $duza){
                $ret .= " ";
            }
            $ret .= $last == "" ? strtoupper($c) : $c;            
            $last = $duza ? "duza" : "mala";
        }
        
        return $ret;
    }
    
    public function getObjectValue($var)
    {   
        if ($var instanceof \DateTime ) {
            $var = $var->format("Y-m-d H:i:s");
        }
        return $var;
    }
    
    public function getMultipleCheckboxLabel($string, $part)
    {   
        $e = explode("@@@", $string);
        $ret = $part <= count($e) -1 ? $e[$part] : "";
        return $ret;
    }
    public function getMultipleCheckboxLabelClasses($string){
        $cs = $this->getMultipleCheckboxLabel($string, 2);
        $ss = explode(",", $cs);
        $ret = array();
        foreach($ss as $id){
            $ret[] = "grupaUprawnien".$id;
        }
        return implode(" ", $ret);
    }
    
    public function getName()
    {
        return 'app_extension';
    }
    
    
    public function objectTitles($var)
    {
        return $this->renameService->objectTitles($var);
    }
    
    
    public function actionTitles($var)
    {
        return $this->renameService->actionTitles($var);
    }
}