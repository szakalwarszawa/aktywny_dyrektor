<?php

namespace ParpV1\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use ParpV1\MainBundle\Entity\Departament;

/**
 * Api controller.
 *
 * @Route("/api")
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
        $em = $this->getDoctrine()->getManager();
        $departamenty = $em->getRepository(Departament::class)->findBy(['nowaStruktura' => '1']);
        $response = new Response();

        if (empty($departamenty)) {

            $response = new JsonResponse(array(
                'komunikat' => 'Nie znaleziono departamentów.',
            ));
            $response->setStatusCode(404);

            return $response;
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
        $response = new Response();

        if (empty($usersFromAD)) {
            $response = new JsonResponse(array(
                'komunikat' => 'Nie znaleziono użytkowników.',
            ));
            $response->setStatusCode(404);

            return $response;
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
        $response = new Response();

        if (empty($userFromLdap)) {
            $response = new JsonResponse(array(
                'komunikat' => 'Nie znaleziono uzytkownika o podanym \"samaccountname\".',
            ));
            $response->setStatusCode(404);

            return $response;
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
}
