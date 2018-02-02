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
        $wniosek = $entityManager
            ->getRepository(Wniosek::class)
            ->findOneByNumer($numerWniosku)
        ;
        if (null === $wniosek) {
            return new Json404NotFoundResponse('Nie znaleziono wniosku o nadanie uprawnień.');
        }

        $statusWniosku = $wniosek
            ->getStatus()
            ->getNazwaSystemowa()
        ;

        if ($statusWniosku !== '07_ROZPATRZONY_POZYTYWNIE') {
            $komunikat = 'Wniosek o nadanie uprawnień nie posiada statusu "07_ROZPATRZONY_POZYTYWNIE".';
            return new Json403ForbiddenResponse($komunikat);
        }

        $wnioskowanyDostep = $wniosek
            ->getWniosekNadanieOdebranieZasobow()
            ->getUserZasoby()
        ;
        try {
            $eksport = $this->parseWnioskowanyDostep($wnioskowanyDostep, $numerWniosku);
        } catch (InvalidContentException $e) {
            $komunikat = 'Wniosek o nadanie uprawnień zawiera niepoprawne dane.';
            return new Json422UnprocessableEntityResponse($komunikat);
        }

        if (empty($eksport)) {
            return new Json404NotFoundResponse('We wniosku nie znaleziono uprawnień do nadania.');
        }

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

                foreach ($nabory as $nabor) {
                    $naborArr = array_filter(explode('/', $nabor));
                    if (count($naborArr) >= 2) {
                        $dzialanie = $naborArr[0];
                        $nrNaboru = $naborArr[1];
        
                        foreach ($uprawnienia as $role) {
                            $uprawnienieLsi1420 = new UprawnienieLsi1420(
                                $numerWniosku,
                                $userName,
                                $role,
                                $dzialanie,
                                $nrNaboru,
                                UprawnienieLsi1420::GRANT_PRIVILAGE,
                                $bezterminowo,
                                $aktywneDo
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
