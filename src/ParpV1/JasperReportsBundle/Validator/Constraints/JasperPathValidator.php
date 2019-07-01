<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use ParpV1\JasperReportsBundle\Validator\Constraints\JasperPath;
use ParpV1\JasperReportsBundle\Fetch\JasperReportFetch;

/**
 * JasperPathValidator
 *
 * Walidator poprawności adresu raportu Jasper.
 */
class JasperPathValidator extends ConstraintValidator
{
    /**
     * @var JasperReportFetch
     */
    private $jasperReportFetch;

    /**
     * Konsturktor
     *
     * @param JasperReportFetch $jasperReportFetch
     */
    public function __construct(JasperReportFetch $jasperReportFetch)
    {
        $this->jasperReportFetch = $jasperReportFetch;
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

        if (!$this->jasperReportFetch->isResourceExist($value)) {
            $this
                ->context
                ->buildViolation($constraint->message)
                ->addViolation()
            ;
        }
    }
}
