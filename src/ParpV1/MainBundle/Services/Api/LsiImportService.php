<?php

namespace ParpV1\MainBundle\Services\Api;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use ParpV1\AuthBundle\Security\ParpUser;
use ParpV1\MainBundle\Entity\LsiImportToken;
use Doctrine\ORM\EntityManager;
use ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow;
use Doctrine\ORM\EntityNotFoundException;
use ParpV1\MainBundle\Entity\Wniosek;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\UnexpectedResultException;
use ParpV1\MainBundle\Entity\UserZasoby;
use ParpV1\MainBundle\Entity\Zasoby;

/**
 * Klasa serwisu LsiImportService
 */
class LsiImportService
{
    const TOKEN_EXIST = 'token_exist';
    const NAZWA_ZASOBU_LSI1420 = 'LSI1420';

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var ParpUser
     */
    private $currentUser;

    /**
     * @var RecursiveValidator
     */
    private $validator;

    /**
     * Czas do wygaśnięcia tokena w minutach.
     *
     * @var int
     */
    private $tokenExpireTime;

    /**
     * Publiczny konstruktor.
     *
     * @param TokenStorage $tokenStorage
     */
    public function __construct(EntityManager $entityManager, TokenStorage $tokenStorage, $tokenExpireTime)
    {
        $this->entityManager = $entityManager;
        $this->currentUser = $tokenStorage->getToken()->getUser();
        $this->tokenExpireTime = $tokenExpireTime;
    }



    /**
     * Rozpoczyna sekwencję szukania lub tworzenia nowego Tokena Importu.
     *
     * @param array $serializedForm
     */
    public function createOrFindToken(array $serializedForm)
    {
        $wniosekId = (int) current($serializedForm)['wniosek'];

        if ($this->validateWniosekId($wniosekId)) {
            $wniosek = $this->findWniosekByWniosekNadanieOdebranieId($wniosekId, true);

            $activeToken = $this->findTokensByWniosek($wniosek);

            if (null === $activeToken) {
                $activeToken = $this->createNewImportToken($wniosek);
            }

            $jsonResponse = $this->prepareJsonResponse($activeToken);

            return $jsonResponse;

        }

        //throw niewłaściwy ID WNIOSKU

    }

    private function prepareJsonResponse(array $typeAndToken)
    {
        $jsonResponse = new JsonResponse();
        $jsonResponse
            ->setData($this->jsonMessageArrayByType($typeAndToken));

        return $jsonResponse;
    }

    /**
     * Zwraca tablicę json z komunikatem dla użytkownika.
     *
     * @param array $typeAndToken
     *
     * @return array
     */
    private function jsonMessageArrayByType(array $typeAndToken)
    {
        $message = '';
        switch ($typeAndToken[0]) {
            case LsiImportToken::NEW_TOKEN:
                $message = 'Utworzono nowy token importu.';
                break;
            case LsiImportToken::SUCCESSFULLY_USED_TOKEN:
                $message = 'Token importu do tego wniosku został już użyty.';
                break;
            case LsiImportToken::ACTIVE_TOKEN:
                $message = 'Jest już aktywny token importu dla tego wniosku.';
                break;
        }

        $responseArray = array(
            'message' => $message,
            'token'   => $typeAndToken[1]->getToken()
        );

        return json_encode($responseArray);
    }

    /**
     * Tworzy nowy Import Token do wniosku.
     *
     * @param Wniosek
     *
     * @return array
     */
    private function createNewImportToken(Wniosek $wniosek)
    {
        $expireDate = new DateTime();
        $expireDate->modify('+' . $this->tokenExpireTime . 'minutes');

        $newLsiImportToken = new LsiImportToken();
        $newLsiImportToken
            ->setRequestedBy($this->currentUser->getUsername())
            ->setWniosek($wniosek)
            ->setStatus(LsiImportToken::NEW_TOKEN)
            ->setExpireAt($expireDate)
            ->setToken($this->generateRandomString())
        ;

        $entityManager = $this->entityManager;
        $entityManager->persist($newLsiImportToken);
        $entityManager->flush();

        return array(
            LsiImportToken::NEW_TOKEN,
            $newLsiImportToken
        );
    }

    /**
     * Znajduje obiekty LsiImportToken na podstawie Wniosek.
     *
     * @param Wniosek $wniosek
     *
     * @return array
     */
    private function findTokensByWniosek(Wniosek $wniosek)
    {
        $entityManager = $this->entityManager;
        $importTokens = $entityManager
            ->getRepository(LsiImportToken::class)
            ->findBy(array(
                'wniosek' => $wniosek->getId()
            ));

        if (count($importTokens) > 0) {
            foreach ($importTokens as $token) {
                if (LsiImportToken::SUCCESSFULLY_USED_TOKEN === $token->getStatus()) {
                    return array(
                        LsiImportToken::SUCCESSFULLY_USED_TOKEN,
                        $token
                    );
                }

                if ($token->getExpireAt() > new DateTime()) {
                    return array(
                        LsiImportToken::ACTIVE_TOKEN,
                        $token
                    );
                }
            }
        }

        return null;
    }

    /**
     * Sprawdza przekazany z formularza numer wniosku o nadanie uprawnień.
     */
    private function validateWniosekId($wniosekId)
    {
        $validator = Validation::createValidator();
        $violations = $validator
            ->validate($wniosekId, array(
                new Assert\NotNull(),
                new Assert\Type('integer'),
                new Assert\GreaterThan(0)
            ));

        if (count($violations) > 0) {
            return false;
        }

        return true;
    }

    /**
     * Szuka obiektu WniosekNadanieOdebranieZasobow
     * jeżeli istnieje to zwraca przypisany do niego obiekt Wniosek
     *
     * @param int $wniosekId
     * @param bool $isLsiWniosekCheck dodatkowo sprawdza czy we wniosek jest o nadanie zasobu LSI1420
     *
     * @return Wniosek
     *
     * @throws EntityNotFoundException gdy nie ma obiektu WniosekNadanieOdebranieZasobow, UserZasoby, Zasob
     * @throws UnexpectedResultException gdy we wniosku nie są same uprawnienia do LSI1420
     */
    private function findWniosekByWniosekNadanieOdebranieId($wniosekId, $isLsiWniosekCheck = false)
    {
        $entityManager = $this->entityManager;

        $wniosekNadanieOdebranieZasobow = $entityManager
            ->getRepository(WniosekNadanieOdebranieZasobow::class)
            ->findOneById($wniosekId)
        ;

        if (null !== $wniosekNadanieOdebranieZasobow && $isLsiWniosekCheck) {
            $userZasoby = $entityManager
                ->getRepository(UserZasoby::class)
                ->findByWniosekWithZasob($wniosekNadanieOdebranieZasobow);
            if (null === $userZasoby) {
                throw new EntityNotFoundException('Nie znaleziono obiektu UserZasoby.');
            }

            $zasobLsi1420 = $entityManager
                ->getRepository(Zasoby::class)
                ->findOneBy(array(
                    'nazwa' => self::NAZWA_ZASOBU_LSI1420,
                    'published' => true
                ));

            if (null === $zasobLsi1420) {
                throw new EntityNotFoundException('Nie znaleziono obiektu Zasob - ' . self::NAZWA_ZASOBU_LSI1420);
            }

            $wniosekLsi = true;
            foreach ($userZasoby as $zasob) {
                 if ($zasob->getZasobId() !== $zasobLsi1420->getId()) {
                     throw new UnexpectedResultException('Wniosek nie zawiera ' .
                     'samych uprawnień ' . self::NAZWA_ZASOBU_LSI1420);
                 }
            }
        }

        if (null !== $wniosekNadanieOdebranieZasobow) {
            return $wniosekNadanieOdebranieZasobow->getWniosek();
        }

        throw new EntityNotFoundException('Nie znaleziono obiektu WniosekNadanieOdebranieZasobow.');
    }


    /**
     * Generuje losowy ciąg znaków.
     *
     * @return string
     */
    private function generateRandomString()
    {
        return bin2hex(openssl_random_pseudo_bytes(10));
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
