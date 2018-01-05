<?php

namespace ParpV1\MainBundle\Twig;

/**
 * Class StringExtension
 * @package ParpV1\MainBundle\Twig
 */
class StringExtension extends \Twig_Extension
{
    protected $renameService;
    protected $ldapService;
    protected $sam2name = null;

    /**
     * StringExtension constructor.
     * @param \ParpV1\MainBundle\Services\RenameService $renameService
     * @param \ParpV1\SoapBundle\Services\LdapService $ldapService
     */
    public function __construct(\ParpV1\MainBundle\Services\RenameService $renameService, \ParpV1\SoapBundle\Services\LdapService $ldapService)
    {
        $this->renameService = $renameService;
        $this->ldapService = $ldapService;
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('addSpaces', array($this, 'addSpaces')),
            new \Twig_SimpleFilter('toCamelcase', array($this, 'toCamelcase')),
            new \Twig_SimpleFilter('gridTitles', array($this, 'gridTitles')),
            new \Twig_SimpleFilter('datetime', array($this, 'datetimeFormat')),
            new \Twig_SimpleFilter('objectTitles', array($this, 'objectTitles')),
            new \Twig_SimpleFilter('actionTitles', array($this, 'actionTitles')),
            new \Twig_SimpleFilter('zasobNazwa', array($this, 'zasobNazwa')),
            new \Twig_SimpleFilter('zasobyNazwa', array($this, 'zasobyNazwa')),
            new \Twig_SimpleFilter('getObjectValue', array($this, 'getObjectValue')),
            new \Twig_SimpleFilter('getMultipleCheckboxLabel', array($this, 'getMultipleCheckboxLabel')),
            new \Twig_SimpleFilter('getMultipleCheckboxLabelClasses', array($this, 'getMultipleCheckboxLabelClasses')),
            new \Twig_SimpleFilter('showMultiFieldAsNewLines', array($this, 'showMultiFieldAsNewLines')),
            new \Twig_SimpleFilter('showFullname', array($this, 'showFullname')),
            new \Twig_SimpleFilter('base64Decode', array($this, 'base64DecodeFilter')),
            new \Twig_SimpleFilter('base64Encode', array($this, 'base64EncodeFilter')),
        );
    }

    /**
     * @param $samaccountname
     * @return mixed
     */
    public function showFullname($samaccountname)
    {
        if ($this->sam2name === null) {
            $this->sam2name = [];
            //wypelniamy cache ze slownikiem
            $users = $this->ldapService->getAllFromAD();
            foreach ($users as $u) {
                $this->sam2name[$u['samaccountname']] = $u['name'];
            }
        }
        return (isset($this->sam2name[$samaccountname]) ? $this->sam2name[$samaccountname] : $samaccountname);
    }

    /**
     * @param $str
     * @return string
     */
    public function addSpaces($str)
    {
        $sams = explode(",", $str);
        $ret = [];
        foreach ($sams as $s) {
            $ret[] = $this->showFullname($s);
        }
        ///$str = str_replace(",", ", ", $str);//old function content
        return implode(", ", $ret);
    }

    /**
     * @param $zids
     * @return string
     */
    public function zasobyNazwa($zids)
    {
        $arr = explode(",", $zids);
        $ret = "";
        foreach ($arr as $zid) {
            $ret[$zid] = $this->renameService->zasobNazwa($zid);
        }
        return implode(", ", $ret);
    }

    /**
     * @param $zid
     * @return mixed
     */
    public function zasobNazwa($zid)
    {
        //echo ".$zid.";
        return $this->renameService->zasobNazwa($zid);
    }

    /**
     * @param $string
     * @return string
     */
    public function toCamelcase($string)
    {
        $out = "";
        $ps = explode("_", $string);
        $i = 0;
        foreach ($ps as $p) {
            if ($i++ != 0) {
                $out .= ucfirst(strtolower($p));
            } else {
                $out .= (strtolower($p));
            }
        }
        //$string = strtoupper($string);

        return $out;
    }

    /**
     * @param $value
     * @return string
     */
    public function datetimeFormat($value)
    {
        if ($value instanceof \DateTime) {
            $value = $value->format("Y-m-d H:i:s");
        } else {
            $value = "";
        }
        return $value;
    }

    /**
     * @param $var
     * @return mixed
     */
    public function gridTitles($var)
    {
        $ret = $var;
/*
        $ret = "";
        $last = "";
        for($i = 0; $i < mb_strlen($var); $i++){
            $c = $var[$i];
            $duza = $c == mb_strtoupper($c);
            if($last == "mala" && $duza){
                $ret .= " ";
            }
            $ret .= $last == "" ? mb_strtoupper($c) : $c;            
            $last = $duza ? "duza" : "mala";
        }
*/
        
        return $ret;
    }

    /**
     * @param $var
     * @return string
     */
    public function getObjectValue($var)
    {
        if ($var instanceof \DateTime) {
            $var = $var->format("Y-m-d H:i:s");
        }
        return $var;
    }

    /**
     * @param $string
     * @param $part
     * @return string
     */
    public function getMultipleCheckboxLabel($string, $part)
    {
        $e = explode("@@@", $string);
        $ret = $part <= count($e) -1 ? $e[$part] : "";
        return $ret;
    }

    /**
     * @param $string
     * @return string
     */
    public function getMultipleCheckboxLabelClasses($string)
    {
        $cs = $this->getMultipleCheckboxLabel($string, 2);
        $ss = explode(",", $cs);
        $ret = array();
        foreach ($ss as $id) {
            $ret[] = "grupaUprawnien".$id;
        }
        return implode(" ", $ret);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'app_extension';
    }


    /**
     * @param $var
     * @return mixed
     */
    public function objectTitles($var)
    {
        return $this->renameService->objectTitles($var);
    }


    /**
     * @param $var
     * @return mixed
     */
    public function actionTitles($var)
    {
        return $this->renameService->actionTitles($var);
    }

    /**
     * @param $str
     * @return string
     */
    public function showMultiFieldAsNewLines($str)
    {
        return "<div class='border'>".str_replace(";", "</div><div class='border'>", $str)."</div>";
    }

    /**
     * @param $txt
     * @return string
     */
    public function base64EncodeFilter($txt)
    {
        return base64_encode($txt);
    }

    /**
     * @param $txt
     * @return bool|string
     */
    public function base64DecodeFilter($txt)
    {
        return base64_decode($txt);
    }
}