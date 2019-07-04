<?php declare(strict_types=1);

namespace ParpV1\JasperReportsBundle\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Doctrine\ORM\EntityManager;
use ParpV1\JasperReportsBundle\Fetch\JasperFetch;
use ParpV1\JasperReportsBundle\Entity\RolePrivilege;

/**
 * Voter ReportVoter
 */
class ReportVoter extends Voter
{
    /**
     * @var string
     */
    const REPORT_READ = 'report_read';

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var JasperFetch
     */
    private $jasperFetch;

    /**
     * Konstruktor
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager, JasperFetch $jasperFetch)
    {
        $this->entityManager = $entityManager;
        $this->jasperFetch = $jasperFetch;
    }

    /**
     * @see Voter
     */
    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, [self::REPORT_READ])) {
            return false;
        }

        return true;
    }

    /**
     * @see Voter
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {

        if (!is_string($subject)) {
            return false;
        }

        $userRoles = $token
            ->getUser()
            ->getRoles()
        ;
        $pathsByRoles = $this
            ->entityManager
            ->getRepository(RolePrivilege::class)
            ->findPathsByRoles($userRoles, $this->jasperFetch)
        ;

        $hasAccess = false;
        foreach ($pathsByRoles as $path) {
            if ($subject === $path['url']) {
                $hasAccess = true;
            }
        }

        return $hasAccess;
    }
}
