<?php declare(strict_types=1);

namespace ParpV1\MainBundle\Validator;

use DateTime;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ZastepstwaValidator extends ConstraintValidator
{
    /**
     * @var string
     */
    private const RESULT_VALID = 'valid';

    /**
     * @var string
     */
    private const RESULT_INVALID = 'invalid';

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Wartości, których nie można zmieniać przed rozpoczeciem.
     *
     * @var array
     */
    private $zablokowanePrzedRozpoczeciem = [
        'kogoZastepuje',
    ];

    /**
     * Wartości, których nie można zmieniać w trakcie trwania.
     *
     * @var array
     */
    private $zablokowanePodczasTrwania = [
        'ktoZastepuje',
        'kogoZastepuje',
        'opis',
        'dataOd',
    ];

    /**
     * Konstruktor
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Walidacja zastępstwa
     *
     * @param mixed $value
     * @param Constraint $constraint
     *
     * @return void
     */
    public function validate($value, Constraint $constraint): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        $result = self::RESULT_VALID;
        $zablokowanePola = [];
        $now = new DateTime();

        if ($value->getKtoZastepuje() === $value->getKogoZastepuje()) {
            $this->context->buildViolation($constraint->zastepujeSamSiebie)
                ->atPath('ktoZastepuje')
                ->addViolation();
            $result = self::RESULT_INVALID;
        }

        $stareZastepstwo = $this
            ->entityManager
            ->getUnitOfWork()
        ;
        $stareZastepstwoDane = $stareZastepstwo->getOriginalEntityData($value);

        if (!empty($stareZastepstwoDane)) {
            if ($stareZastepstwoDane['dataDo'] < $value->getDataDo()
                && $now > $value->getDataOd()
            ) {
                $this->context->buildViolation($constraint->wydluzenieZastepstwa)
                    ->atPath('dataDo')
                    ->setParameter('{{ value }}', $stareZastepstwoDane['dataDo']->format('Y-m-d H:i:s'))
                    ->addViolation();
                $result = self::RESULT_INVALID;
            }

            if ($now < $stareZastepstwoDane['dataOd']) {
                $zablokowanePola = array_flip($this->zablokowanePrzedRozpoczeciem);
            } else {
                $zablokowanePola = array_flip($this->zablokowanePodczasTrwania);
            }

            $stareZastepstwo->computeChangeSets();
            $zmienioneWartosci = $stareZastepstwo->getEntityChangeSet($value);
            $zmienioneZablokowaneWartosci = array_intersect_key($zmienioneWartosci, $zablokowanePola);

            if (!empty($zmienioneZablokowaneWartosci)) {
                foreach ($zmienioneZablokowaneWartosci as $zablokowanePole => $zablokowaneWartosci) {
                    $this->context->buildViolation($constraint->niedozwolonaZmiana)
                        ->atPath($zablokowanePole)
                        ->addViolation();
                    $result = self::RESULT_INVALID;
                }
            }
        }

        if (self::RESULT_INVALID === $result) {
            $this->context->addViolation($constraint->formularzZawieraBledy);
        }
    }
}
