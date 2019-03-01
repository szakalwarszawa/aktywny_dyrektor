<?php

namespace ParpV1\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityNotFoundException;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use ParpV1\MainBundle\Entity\Departament;
use ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow;
use ParpV1\MainBundle\Entity\Wniosek;
use ParpV1\MainBundle\Entity\UserZasoby;
use ParpV1\MainBundle\Api\Type\UprawnienieLsi1420;
use ParpV1\MainBundle\Api\Response\Json404NotFoundResponse;
use ParpV1\MainBundle\Api\Response\Json403ForbiddenResponse;
use ParpV1\MainBundle\Api\Response\Json422UnprocessableEntityResponse;
use ParpV1\MainBundle\Api\Exception\InvalidContentException;
use ParpV1\MainBundle\Entity\LsiImportToken;

/**
 * Api controller.
 *
 * @Route("/api/v1")
 */
class ApiController extends Controller
{
    /**
     * @Route("/departaments")
     *
     * @Method({"GET"})
     *
     * @ApiDoc(
     *  description="Returns a collection of ParpV1\MainBundle\Entity\Departament",
     *  output={"collection"=true, "collectionName"="departaments", "class"="ParpV1\MainBundle\Entity\Departament"},
     *  statusCodes={
     *    200="Returned when successful",
     *    404="Returned when the collection is empty"}
     * )
     */
    public function getDepartamentAction()
    {
        $entityManager = $this->getDoctrine()->getManager();
        $departamenty = $entityManager
            ->getRepository(Departament::class)
            ->findBy(['nowaStruktura' => '1'])
        ;
        if (empty($departamenty)) {
            return new Json404NotFoundResponse('Nie znaleziono departamentów.');
        }

        $departamentyArr = [];
        foreach ($departamenty as $departament) {
            $dapartamentArr = [];
            $dapartamentArr['id'] = $departament->getId();
            $dapartamentArr['name'] = $departament->getName();
            $dapartamentArr['shortname'] = $departament->getShortname();
            $dapartamentArr['nameInRekord'] = $departament->getNameInRekord();
            $dapartamentArr['dyrektor'] = $departament->getDyrektor();

            $departamentyArr[] = $dapartamentArr;
        }

        $response = new JsonResponse(array(
            'departaments' => $departamentyArr,
        ));

        return $response;
    }

    /**
     * @Route("/users")
     *
     * @Method({"GET"})
     *
     * @ApiDoc(
     *  description="Returns a collection of ParpV1\SoapBundle\Entity\ADUser",
     *  output={"collection"=true, "collectionName"="users", "class"="ParpV1\SoapBundle\Entity\ADUser"},
     *  statusCodes={
     *         200="Returned when successful",
     *         404="Returned when the collection is empty"}
     * )
     */
    public function getUsersAction()
    {
        $ldap = $this->container->get('ldap_service');
        $usersFromAD = $ldap->getAllFromAD();
        if (empty($usersFromAD)) {
            return new Json404NotFoundResponse('Nie znaleziono użytkowników.');
        }

        $users = [];
        foreach ($usersFromAD as $user) {
            unset($user['thumbnailphoto']);
            unset($user['isDisabled']);
            unset($user['accountExpires']);
            unset($user['accountexpires']);
            unset($user['lastlogon']);
            unset($user['useraccountcontrol']);
            unset($user['memberOf']);
            unset($user['roles']);
            $users[] = $user;
        }

        $response = new JsonResponse(array(
            'users' => $users,
        ));

        return $response;
    }

    /**
     * @Route("/users/{samacountname}")
     *
     * @Method({"GET"})
     *
     * @ApiDoc(
     *  description="Returns a ParpV1\SoapBundle\Entity\ADUser",
     *  parameters={
     *      {"name"="samacountname", "dataType"="string", "required"=true, "description"="Domenowa nazwa uzytkownika"}
     *  },
     *  output={"class"="ParpV1\SoapBundle\Entity\ADUser"},
     *  statusCodes={
     *         200="Returned when successful",
     *         404="Returned when the user is not found"}
     * )
     */
    public function getUserAction($samacountname)
    {
        $ldap = $this->container->get('ldap_service');
        $userFromLdap = $ldap->getUserFromAD($samacountname);
        if (empty($userFromLdap)) {
            return new Json404NotFoundResponse('Nie znaleziono uzytkownika o podanym \"samaccountname\".');
        }

        $user = $userFromLdap[0];
        unset($user['thumbnailphoto']);
        unset($user['isDisabled']);
        unset($user['accountExpires']);
        unset($user['accountexpires']);
        unset($user['lastlogon']);
        unset($user['useraccountcontrol']);
        unset($user['memberOf']);
        unset($user['roles']);

        $response = new JsonResponse(array(
            'user' => $user
        ));

        return $response;
    }

    /**
     * Po sukcesywnym imporcie uprawnień w LSI zmienia status ImportToken na wykorzystany.
     *
     * @Route("/tokenSuccess/{importToken}", requirements={"importToken"=".+"})
     *
     * @Method({"PUT"})
     *
     * @ApiDoc(
     *      description="Zmienia status obiektu LsiImportToken na wykorzystany.",
     *      parameters={
     *          {
     *              "name"="importToken",
     *                 "dataType"="string",
     *              "required"=true,
     *              "description"="Token Importu LSI"
     *          }
     *      },
     *      statusCodes={
     *          200="Returned when successful",
     *          404="Returned when there is no object in database",
     *          422="Returned when resource can not be processed"
     *      }
     * )
     */
    public function markImportTokenSuccess(Request $request, $importToken)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $lsiImportToken = $entityManager
            ->getRepository(LsiImportToken::class)
            ->findOneBy(array(
                'token' => $importToken
            ));
        if (null === $lsiImportToken) {
            return new Json404NotFoundResponse('Token Importu nieznaleziony.');
        }

        $lsiImportToken->markTokenAsUsed();

        $entityManager->persist($lsiImportToken);
        $entityManager->flush();

        return new JsonResponse(null, 200);
    }

    /**
     * Zwraca odpowiedź zawierającą JSON z uprawnieniami do dodania w LSI1420.
     *
     * @Route("/uprawnieniaLsi1420/{numerWniosku}", requirements={"numerWniosku"=".+"})
     *
     * @Method({"GET"})
     *
     * @ApiDoc(
     *      description="Returns a collection of ParpV1\MainBundle\Api\Type\UprawnienieLsi1420",
     *      parameters={
     *          {
     *              "name"="numerWniosku",
     *              "dataType"="string",
     *              "required"=true,
     *              "description"="Numer wniosku o nadanie uprawnień w LSI1420."
     *          }
     *      },
     *      output={
     *          "collection"=true,
     *          "collectionName"="uprawnieniaLsi1420",
     *          "class"="ParpV1\MainBundle\Api\Type\UprawnienieLsi1420"
     *      },
     *      statusCodes={
     *          200="Returned when successful",
     *          404="Returned when the collection is empty",
     *          422="Returned when resource can not be processed"
     *      }
     * )
     *
     * @param Request $request
     * @param string $numerWniosku
     *
     * @return JsonResponse
     */
    public function eksportUprawnienDlsLsi1420Action(Request $request, $numerWniosku)
    {
        $entityManager = $this->getDoctrine()->getManager();

        $lsiImportToken = $entityManager
            ->getRepository(LsiImportToken::class)
            ->findOneBy(array(
                'token' => $numerWniosku
            ));
        if (null === $lsiImportToken) {
            return new Json404NotFoundResponse('Token Importu nieznaleziony.');
        }

        if ($lsiImportToken->isValid()) {
            return new Json403ForbiddenResponse('Ten Token stracił ważność lub został użyty.');
        }

        $wniosek = $lsiImportToken->getWniosek();
        if (null === $wniosek) {
            return new Json404NotFoundResponse('Nie znaleziono wniosku o nadanie uprawnień.');
        }

        $statusWniosku = $wniosek
            ->getStatus()
            ->getNazwaSystemowa()
        ;

        if (!in_array($statusWniosku, ['07_ROZPATRZONY_POZYTYWNIE', '05_EDYCJA_ADMINISTRATOR'])) {
            $komunikat = 'Wniosek nie jest w statusie "07_ROZPATRZONY_POZYTYWNIE" lub "05_EDYCJA_ADMINISTRATOR".';

            return new Json403ForbiddenResponse($komunikat);
        }

        $wnioskowanyDostep = $wniosek
            ->getWniosekNadanieOdebranieZasobow()
            ->getUserZasoby()
        ;
        try {
            $eksport = $this->parseWnioskowanyDostep($wnioskowanyDostep, $wniosek->getNumer());
        } catch (InvalidContentException $e) {
            $komunikat = 'Wniosek o nadanie uprawnień zawiera niepoprawne dane.';
            return new Json422UnprocessableEntityResponse($komunikat);
        }

        if (empty($eksport)) {
            return new Json404NotFoundResponse('We wniosku nie znaleziono uprawnień do nadania.');
        }

        $lsiImportToken->incrementUseCount();
        $entityManager->persist($lsiImportToken);
        $entityManager->flush();

        return new JsonResponse($eksport);
    }

    /**
     * Tworzy z kolekcji obiektów tablicę danych z uprawnieniami do wysłania do LSI1420.
     *
     * @param Collection Kolekcja obiektów UserZasoby.
     *
     * @return array
     *
     * @throws InvalidContentException Jeśli dane do umieszczenia w odpowiedzi nie są poprawne.
     */
    private function parseWnioskowanyDostep(Collection $wnioskowanyDostep, $numerWniosku)
    {
        $eksport = array();

        $ldapService = $this->get('ldap_service');
        $usersToCreate = [];
        foreach ($wnioskowanyDostep as $dostep) {
            if (! $dostep instanceof UserZasoby) {
                throw InvalidContentException('Oczekiwano kolekcji obiektów UserZasoby.');
            }

            $zasob = trim((string) $dostep->getZasobOpis());
            if ($zasob === 'LSI1420') {
                $nabory = explode(';', $dostep->getModul());
                $uprawnienia = explode(';', $dostep->getPoziomDostepu());
                $userName = $dostep->getSamaccountname();
                $bezterminowo = $dostep->getBezterminowo();
                $aktywneDo = $dostep->getAktywneDo();

                if (!isset($usersToCreate[$userName])) {
                    $usersToCreate[$userName] = !empty($ldapService->getUserFromAd($userName)[0]['email']);
                }

                foreach ($nabory as $nabor) {
                    $naborArr = array_filter(explode('/', $nabor));
                    if (count($naborArr) >= 1) {
                        $dzialanie = isset($naborArr[0])? $naborArr[0] : 'TYLKO_ROLA';
                        $nrNaboru = isset($naborArr[1])? $naborArr[1] : 'TYLKO_ROLA';

                        if ('do wypełnienia przez właściciela zasobu' === $dzialanie) {
                            $dzialanie = 'TYLKO_ROLA';
                        }
                        foreach ($uprawnienia as $role) {
                            $uprawnienieLsi1420 = new UprawnienieLsi1420(
                                $numerWniosku,
                                $userName,
                                $role,
                                $dzialanie,
                                $nrNaboru,
                                UprawnienieLsi1420::GRANT_PRIVILAGE,
                                $bezterminowo,
                                $aktywneDo,
                                $usersToCreate[$userName]
                            );
                            if (false === $uprawnienieLsi1420->isValid()) {
                                throw new InvalidContentException('Dane uprawnienia nie są pełne.');
                            }
                            $eksport[] = $uprawnienieLsi1420;
                        }
                    }
                }
            }
        }

        return $eksport;
    }
}
