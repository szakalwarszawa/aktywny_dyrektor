<?php

declare(strict_types=1);

namespace ParpV1\MainBundle\Helper;

/**
 * Klasa IloczynKartezjanskiHelper
 */
class IloczynKartezjanskiHelper
{
    /**
     * Zwraca iloczyn kartezjanski tablic.
     *
     * @see https://gist.github.com/jwage/11193216
     *
     * @param array $set
     *
     * @return array
     */
    public static function build(array $set): array
    {
        if (!$set) {
            return array(array());
        }

        $subset = array_shift($set);
        $cartesianSubset = self::build($set);
        $result = array();
        foreach ($subset as $value) {
            foreach ($cartesianSubset as $p) {
                array_unshift($p, $value);
                $result[] = $p;
            }
        }

        return $result;
    }
}
