<?php

declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * JasperPath
 *
 * @Annotation
 */
class JasperPath extends Constraint
{
    /**
     * @var string
     */
    public $message = 'Podany adres raportu nie jest dostępny w Jasper.';
}
