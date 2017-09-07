<?php
namespace ParpV1\MainBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Doctrine\Common\Persistence\ObjectManager;

class ParpDateTransformer implements DataTransformerInterface
{

    protected $input_format = 'Y-m-d H:i:s';
    protected $output_format = 'Y-m-d H:i:s';
    /**
     * @param ObjectManager $om
     */
    public function __construct()
    {
        //$this->om = $om;
    }

    /**
     * Transforms an object (date) to a string (formatted).
     *
     * @param  Datetime $date
     * @return string
     */
    public function transform($date)
    {
        //print_r($date);
        //die('a');
        if (null === $date) {
            return "";
        }
        return $date->format($this->output_format);
    }

    /**
     * Transforms a string (number) to an object (Datetime).
     *
     * @param  string $datestr
     *
     * @return Datetime|null
     *
     * @throws TransformationFailedException if object (issue) is not found.
     */
    public function reverseTransform($datestr)
    {
        //return $datestr;
        //die('b '.$datestr);
        if (!$datestr) {
            return null;
        }
        //echo ".".$datestr;
        if (strstr($datestr, " ") === false) {
            $d = explode(" ", $datestr);
            $datestr = $d[0]." 00:00:00";
        }
        $date = \DateTime::createFromFormat($this->input_format, $datestr);
        //die(print_r($date, true));
        return $date;
    }
}
