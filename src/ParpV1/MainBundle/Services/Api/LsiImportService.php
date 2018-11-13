<?php

namespace ParpV1\MainBundle\Services\Api;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use ParpV1\AuthBundle\Security\ParpUser;
use ParpV1\MainBundle\Entity\LsiImportToken;
use Doctrine\ORM\EntityManager;
use ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow;
use Doctrine\ORM\EntityNotFoundException;
use ParpV1\MainBundle\Entity\Wniosek;

/**
 * Klasa serwisu LsiImportService
 */
class LsiImportService
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ParpUser
     */
    private $currentUser;

    /**
     * Publiczny konstruktor.
     *
     * @param TokenStorage $tokenStorage
     */
    public function __construct(EntityManager $entityManager, TokenStorage $tokenStorage)
    {
        $this->entityManager = $entityManager;
        $this->currentUser = $tokenStorage->getToken()->getUser();
    }

    public function generateImportToken(array $data)
    {
        $this->checkUser();

        $entityManager = $this->entityManager;

        $wniosek = $this->getWniosekByWniosekNadanie(current($data)['wniosek']);
        if (false === $this->isTokenExist($wniosek)) {
            $this->persistNewToken($wniosek);
        }

        //echo $this->generateNewTokenString();

        $entityManager->flush();

    }

    private function persistNewToken(Wniosek $wniosek)
    {
        $lsiImportToken = new LsiImportToken();
        $lsiImportToken
            ->setRequestedBy($this->currentUser->getUsername())
            ->setWniosek($wniosek)
            ->setStatus(LsiImportToken::NEW_TOKEN)
            ->setToken($this->generateNewTokenString())
        ;

        $this
            ->entityManager
            ->persist($lsiImportToken);

        return $lsiImportToken;
    }

    /**
     * @return string
     */
    private function generateNewTokenString()
    {
        return bin2hex(openssl_random_pseudo_bytes(10));
    }

    /**
     *
     * @return Wniosek
     */
    private function getWniosekByWniosekNadanie($idWniosku)
    {
        $entityManager = $this->entityManager;

        $wniosekNadanieOdebranieUprawnen = $entityManager
            ->getRepository(WniosekNadanieOdebranieZasobow::class)
            ->findOneById($idWniosku);

        if (null === $wniosekNadanieOdebranieUprawnen) {
            throw new EntityNotFoundException('Obiekt WniosekNadanieOdebranieZasobow nie istnieje.');
        }

        return $wniosekNadanieOdebranieUprawnen->getWniosek();
    }

    private function isTokenExist(Wniosek $wniosek)
    {
        return null === $this->getLsiImportTokenByWniosek($wniosek->getId()) ? false : true;
    }

    /**
     * @param Wniosek|string
     *
     * @return LsiImportToken|null
     */
    private function getLsiImportTokenByWniosek($wniosek)
    {
        $entityManager = $this->entityManager;

        if ($wniosek instanceof Wniosek) {
            $wniosek = $wniosek->getId();
        }

        $lsiImportToken = $entityManager
            ->getRepository(LsiImportToken::class)
            ->findOneBy(array(
                'wniosek' => $wniosek
        ));

        return $lsiImportToken;
    }

    /**
     * Sprawdza czy poprawnie przypisano użytkownika.
     *
     * @throws \Exception gdy nie przypisano użytkownika.
     *
     * @return void
     */
    private function checkUser()
    {
        if (null === $this->currentUser) {
            throw new \Exception('brak tokena');
        }
    }
}
