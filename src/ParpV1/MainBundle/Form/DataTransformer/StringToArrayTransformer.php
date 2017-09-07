<?php
namespace ParpV1\MainBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class StringToArrayTransformer implements DataTransformerInterface
{
    protected $separator;
    public function __construct($separator = ",")
    {
        $this->separator = $separator;
    }
    /**
     * Transforms an array to a string.
     *
     * @return string
     */
    public function transform($array)
    {
        //echo "<pre>"; print_r($array);
        return $array ? explode($this->separator, $array) : array();
    }

    /**
     * Transforms a string to an array.
     *
     * @param  string $string
     *
     * @return array
     */
    public function reverseTransform($string)
    {
        //var_dump($string); die();
        return implode($this->separator, $string);
    }
}
