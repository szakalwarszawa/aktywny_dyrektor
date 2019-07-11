<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use ParpV1\JasperReportsBundle\Validator\Constraints\JasperPath;
use ParpV1\JasperReportsBundle\Fetch\JasperFetch;

/**
 * JasperPathValidator
 *
 * Walidator poprawności adresu raportu Jasper.
 */
class JasperPathValidator extends ConstraintValidator
{
    /**
     * @var JasperFetch
     */
    private $jasperFetch;

    /**
     * Konsturktor
     *
     * @param JasperFetch $jasperFetch
     */
    public function __construct(JasperFetch $jasperFetch)
    {
        $this->jasperFetch = $jasperFetch;
    }

    /**
     * @see ConstraintValidator
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof JasperPath) {
            throw new UnexpectedTypeException($constraint, JasperPath::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        if (!$this->jasperFetch->isResourceExist($value)) {
            $this
                ->context
                ->buildViolation($constraint->message)
                ->addViolation()
            ;
        }
    }
}
