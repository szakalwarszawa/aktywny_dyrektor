<?php

declare(strict_types=1);

namespace ParpV1\MainBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Zastepstwa extends Constraint
{
    /**
     * @var string
     */
    public $formularzZawieraBledy = 'Formularz zawiera błędy!';

    /**
     * @var string
     */
    public $zastepujeSamSiebie = 'Pracownik nie może zastępować samego siebie.';

    /**
     * @var string
     */
    public $wydluzenieZastepstwa = 'Po rozpoczęciu zastępstwa nie można go wydłużyć (poprzednia data do: {{ value }}).';

    /**
     * @var string
     */
    public $niedozwolonaZmiana = 'Zmiana tej wartości jest niedozwolona.';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
