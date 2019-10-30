<?php

namespace ParpV1\MainBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class RoleTransformer implements DataTransformerInterface
{
    /**
     * @var ObjectManager
     */
    private $em;
    private $data;

    /**
     * @param ObjectManager $om
     */
    public function __construct($em, $data)
    {
        //echo "<pre>"; print_r($data);
        $this->data = $data;
        $this->em = $em;
    }
    /**
     * Transforms an array to a string.
     *
     * @return string
     */
    public function transform($array)
    {
        $ret = [];
        foreach ($array as $a) {
            $ret[$a->getSamaccountname()] = $a->getSamaccountname();
        }
        //echo "<pre>"; print_r($array); die();
        return $ret; //$array ? explode(",", $array->getUsers()) : array();
    }

    /**
     * Transforms a string to an array.
     *
     * @param  string $string
     *
     * @return array
     */
    public function reverseTransform($val)
    {
        $ret = [];
        //var_dump($val); die();
        foreach ($val as $k => $v) {
            if (is_int($k)) {
                //nowe
                $r = null;
            } else {
                //stare
                $r = $this->em->getRepository('ParpMainBundle:AclUserRole')->findOneBy(['samaccountname' => $v, 'role' => $this->data]);
            }
            if (!$r) {
                $r = new \ParpV1\MainBundle\Entity\AclUserRole();
                $r->setRole($this->data);
                $r->setSamaccountname($v);
                $this->data->addUser($r);
                $this->em->persist($r);
            }
            $ret[] = $r;
        }
        
        //var_dump($ret); die();
        return $ret; //implode(",", $val);
    }
}
