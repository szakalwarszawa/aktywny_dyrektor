<?php
namespace Parp\MainBundle\Grid;

use APY\DataGridBundle\Grid\Export\Export;

class ParpExcelExport extends Export
{
    protected $fileExtension = 'xlsx';
    protected $emptyRows = -1;

    protected $mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    
    public function __construct($tilte, $fileName = 'export', $params = array(), $charset = 'UTF-8')
    {
        $this->parameters['service'] = (isset($params['service'])) ? $params['service'] : null;
        $this->parameters['title'] = (isset($params['title'])) ? $params['title'] : "Default title";
        $this->emptyRows = (isset($params['emptyRows'])) ? $params['emptyRows'] : -1;
        $this->parameters['filename'] = (isset($params['filename'])) ? $params['filename'] : "filename.xlsx";

        parent::__construct($tilte, $fileName, $params, $charset);
    }
    
    
    public function computeData($grid)
    {
        
        //\PHPExcel_Settings::setZipClass(\PHPExcel_Settings::PCLZIP);
        //\PHPExcel_Settings::setZipClass(\PHPExcel_Settings::ZIPARCHIVE);
        //die('a');
        //$this->content = 'Hello world!';
        $titles = $this->getRawGridData($grid);
        $data = $this->getFlatGridData($grid);
        
        //die("<pre>".print_r($grid->getFilters(), true));
        $edata = array();
        $edata[] = $titles['titles'];
        $edata = $data;//array_merge($edata, $data);
        
        //proccess dates
        foreach($edata as &$d){
            foreach($d as $k => $v){
                if(strstr($v, ")") !== false && strstr($v, "(") !== false){
                    //mamy cos z nawiasami, sprawdzamy czy to data
                    $date = \DateTime::createFromFormat("Y-m-d (D)", $v);
                    if($date){
                        $nv = $date->format("Y-m-d");
                        $d[$k] = $nv;
                    }
                }
            }
        }
        
        die("<pre>".print_r($edata, true));
        
        
    }
}