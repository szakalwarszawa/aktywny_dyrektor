<?php
namespace Parp\MainBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class StringToArrayTransformer implements DataTransformerInterface
{
    /**
     * Transforms an array to a string. 
     *
     * @return string
     */
    public function transform($array)
    {
        
        return $array ? explode(",", $array) : array();
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
        return implode(",", $string);
    }
}