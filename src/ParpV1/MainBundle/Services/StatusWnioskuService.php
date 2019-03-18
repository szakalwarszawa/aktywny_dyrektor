<?php

namespace ParpV1\MainBundle\Services;

use ParpV1\MainBundle\Services\ParpMailerService;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\HttpFoundation\Session\Session;
use ParpV1\SoapBundle\Services\LdapService;
use ParpV1\MainBundle\Entity\WniosekStatus;
use ParpV1\MainBundle\Entity\Zastepstwo;
use ParpV1\MainBundle\Entity\UserZasoby;
use ParpV1\MainBundle\Entity\Zasoby;
use ParpV1\MainBundle\Entity\WniosekViewer;
use ParpV1\MainBundle\Entity\WniosekEditor;
use \ParpV1\MainBundle\Entity\WniosekHistoriaStatusow;
use \ParpV1\MainBundle\Entity\AclRole;
use \ParpV1\MainBundle\Entity\AclUserRole;
use \ParpV1\MainBundle\Entity\Departament;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Constraints\Email;
use ParpV1\MainBundle\Entity\WniosekNadanieOdebranieZasobow;
use ParpV1\MainBundle\Services\UprawnieniaService;
use ParpV1\MainBundle\Constants\TypWnioskuConstants;

/**
 * Klasa StatusWnioskuService.
 * Wszystko jest przeniesione z kontrolera.
 * Zmiana statusu będzie odbywać się równiez z CLI dlatego została wydzielona.
 * Do refaktoryzacji.
 * @todo
 */
class StatusWnioskuService
{
    private $mailerService;
    private $request;
    private $session;
    private $ldapService;
    private $entityManager;
    private $currentUser;
    private $debug = false;

    public function __construct(
        ParpMailerService $mailerService,
        RequestStack $requestStack,
        Session $session,
        LdapService $ldapService,
        EntityManager $entityManager,
        TokenStorage $tokenStorage
    ) {
        $this->mailerService = $mailerService;
        $this->request = $requestStack->getCurrentRequest();
        $this->session = $session;
        $this->ldapService = $ldapService;
        $this->entityManager = $entityManager;
        if (null !== $tokenStorage->getToken()) {
            $this->currentUser = $tokenStorage->getToken()->getUser();
        }
    }

    /**
     * @todo do refaktoryzacji tego sie nie da czytać ani wprowadzać zmian, tfu
     */
    public function setWniosekStatus($wniosek, $statusName, $rejected, $oldStatus = null, $komentarz = null)
    {
        $zastepstwo = $this->sprawdzCzyDzialaZastepstwo($wniosek);

        $entityManager = $this->entityManager;
        $status = $entityManager
            ->getRepository(WniosekStatus::class)
            ->findOneBy(
                array(
                    'nazwaSystemowa' => $statusName,
                )
            );
        $wniosek->getWniosek()->setStatus($status);
        $wniosek->getWniosek()->setLockedBy(null);
        $wniosek->getWniosek()->setLockedAt(null);
        $viewers = array();
        $editors = array();
        $vs = explode(',', $status->getViewers());
        foreach ($vs as $v) {
            $this->addViewersEditors($wniosek->getWniosek(), $viewers, $v);
        }

        $typWniosku = $wniosek->getWniosek()->getWniosekUtworzenieZasobu() ?
            TypWnioskuConstants::WNIOSEK_UTWORZENIE_ZASOBU:
            TypWnioskuConstants::WNIOSEK_NADANIE_ODEBRANIE_ZASOBOW;

        $czyLsi = false;
        $czyMaGrupyAD = false;

        if (TypWnioskuConstants::WNIOSEK_NADANIE_ODEBRANIE_ZASOBOW === $typWniosku) {
            foreach ($wniosek->getUserZasoby() as $uz) {
                $z = $entityManager->getRepository(Zasoby::class)->find($uz->getZasobId());
                if ($z->getGrupyAd()) {
                    $czyMaGrupyAD = true;
                    $czyLsi = $uz->getZasobId() == 4420;
                }
            }
        }
        if ($statusName == '07_ROZPATRZONY_POZYTYWNIE' && $oldStatus != null && ($czyMaGrupyAD || $czyLsi)) {
            //jak ma grupy AD do opublikowania to zostawiamy edytorow tych co byli
            $os = $entityManager->getRepository(WniosekStatus::class)->findOneByNazwaSystemowa($oldStatus);
            $es = explode(',', $os->getEditors());
        } else {
            $es = explode(',', $status->getEditors());
        }

        foreach ($es as $e) {
            $this->addViewersEditors($wniosek->getWniosek(), $editors, $e);
            //print_r($editors);
        }

        //kasuje viewerow
        foreach ($wniosek->getWniosek()->getViewers() as $v) {
            $wniosek->getWniosek()->removeViewer($v);
            $entityManager->remove($v);
        }
        //kasuje editorow
        foreach ($wniosek->getWniosek()->getEditors() as $v) {
            $wniosek->getWniosek()->removeEditor($v);
            $entityManager->remove($v);
        }

        //dodaje viewerow
        foreach ($viewers as $v) {
            $wv = new WniosekViewer();
            $wv->setWniosek($wniosek->getWniosek());
            $wniosek->getWniosek()->addViewer($wv);
            $wv->setSamaccountname($v);
            // if ($this->debug) {
                // echo '<br>dodaje usera viewra '.$v;
            // }
            $entityManager->persist($wv);
        }
        $wniosek->getWniosek()->setViewernamesSet();

        //dodaje editorow
        foreach ($editors as $v) {
//            die('MAM CIĘ');
            $wv = new WniosekEditor();
            $wv->setWniosek($wniosek->getWniosek());
            $wniosek->getWniosek()->addEditor($wv);
            $wv->setSamaccountname($v);
            // if ($this->debug) {
                // echo '<br>dodaje usera editora '.$v;
            // }
            $entityManager->persist($wv);
        }
        $wniosek->getWniosek()->setEditornamesSet();

        //wstawia historie statusow
        $sh = new WniosekHistoriaStatusow();
        $sh->setZastepstwo($zastepstwo);
        $sh->setWniosek($wniosek->getWniosek());
        $wniosek->getWniosek()->addStatusy($sh);
        $sh->setCreatedAt(new \Datetime());
        $sh->setRejected($rejected);
        $sh->setCreatedBy($this->currentUser);
        $sh->setStatus($status);
        $sh->setStatusName($status->getNazwa());
        $opis = null !== $komentarz? $komentarz : $status->getNazwa();
        $sh->setOpis($opis);
        $entityManager->persist($sh);

        $statusyAkceptujacePoKtorychWyslacMaila = ['07_ROZPATRZONY_POZYTYWNIE', '11_OPUBLIKOWANY'];
        if (in_array($statusName, $statusyAkceptujacePoKtorychWyslacMaila)) {
            if ($wniosek->getOdebranie()) {
                $this->mailerService
                    ->sendEmailWniosekNadanieOdebranieUprawnien(
                        $wniosek,
                        ParpMailerService::TEMPLATE_WNIOSEKODEBRANIEUPRAWNIEN
                    );
            } else {
                $this->mailerService
                    ->sendEmailWniosekNadanieOdebranieUprawnien(
                        $wniosek,
                        ParpMailerService::TEMPLATE_WNIOSEKNADANIEUPRAWNIEN
                    );
            }
        } elseif ($rejected) {
            if ($statusName == '08_ROZPATRZONY_NEGATYWNIE') {
                //odrzucenie
                $this->mailerService
                    ->sendEmailWniosekNadanieOdebranieUprawnien(
                        $wniosek,
                        ParpMailerService::TEMPLATE_WNIOSEKODRZUCENIE
                    );
            } else {
                //zwroct do poprzednika
                $this->mailerService
                    ->sendEmailWniosekNadanieOdebranieUprawnien($wniosek, ParpMailerService::TEMPLATE_WNIOSEKZWROCENIE);
            }
        } elseif ($statusName == '02_EDYCJA_PRZELOZONY') {
            $this->mailerService
                    ->sendEmailWniosekNadanieOdebranieUprawnien($wniosek, ParpMailerService::TEMPLATE_OCZEKUJACYWNIOSEK);
        }
    }

    /**
     * @param $wniosek
     * @return null
     */
    protected function sprawdzCzyDzialaZastepstwo($wniosek)
    {
        if ('cli' === PHP_SAPI) {
            return null;
        }
        $ret = $this->checkAccess($wniosek);
        //var_dump($wniosek, $ret);
        if ($wniosek->getId() && $ret['editorsBezZastepstw'] == null) {
            //dziala zastepstwo, szukamy ktore
            $zastepstwa =
                $this->entityManager->getRepository(Zastepstwo::class)->znajdzZastepstwa($this->currentUser
                    ->getUsername());
            foreach ($zastepstwa as $z) {
                //var_dump($ret);
                if ($ret['editor'] && $z->getKogoZastepuje() == $ret['editor']->getSamaccountname()) {
                    //var_dump($z); die();
                    return $z;
                }
            }
        } else {
            return null;
        }
    }

     /**
     * @param $wniosek
     * @param $where
     * @param $who
     */
    protected function addViewersEditors($wniosek, &$where, $who)
    {
        $entityManager = $this->entityManager;
        $typWniosku = $wniosek->getWniosekUtworzenieZasobu() ?
            TypWnioskuConstants::WNIOSEK_UTWORZENIE_ZASOBU:
            TypWnioskuConstants::WNIOSEK_NADANIE_ODEBRANIE_ZASOBOW;
        switch ($who) {
            case 'nadzorcaDomen':
                $role = $entityManager->getRepository(AclRole::class)->findBy(['name' => 'PARP_NADZORCA_DOMEN']);
                $users = $entityManager->getRepository(AclUserRole::class)->findByRole($role);
                foreach ($users as $u) {
                    $where[$u->getSamaccountname()] = $u->getSamaccountname();
                }
                break;
            case 'wnioskodawca':
                //
                $where[$wniosek->getCreatedBy()] = $wniosek->getCreatedBy();
                // if ($this->debug) {
                    // echo '<br>added '.$wniosek->getCreatedBy().'<br>';
                // }
                break;
            case 'podmiot':
                //
                foreach ($wniosek->getWniosekNadanieOdebranieZasobow()->getUserZasoby() as $u) {
                    $adres = $u->getSamaccountname() . '@parp.gov.pl';
                    if ($this->isValidEmail($adres)) {
                        $where[$u->getSamaccountname()] = $u->getSamaccountname();
                    }

                    if ($this->debug) {
                        echo '<br>added '.$u->getSamaccountname().'<br>';
                    }
                }
                break;
            case 'przelozony':
                //bierze managera tworzacego - jednak nie , ma byc po podmiotach
                //$ADUser = $this->getUserFromAD($wniosek->getCreatedBy());
                if ($wniosek->getWniosekNadanieOdebranieZasobow()->getPracownikSpozaParp()) {
                    //biore managera z pola managerSpoząParp
                    $ADManager =
                        $this->getUserFromAD($wniosek->getWniosekNadanieOdebranieZasobow()->getManagerSpozaParp());
                    if (count($ADManager) == 0) {
                        //die ("Blad 6578 Nie moge znalezc przelozonego dla osoby : ".$wniosek->getWniosekNadanieOdebranieZasobow()->getPracownicySpozaParp()." z managerem ".$wniosek->getWniosekNadanieOdebranieZasobow()->getManagerSpozaParp());
                    }
                    //$przelozeni[$ADManager[0]['samaccountname']][] = $uz;
                } else {
                    //bierze pierwszego z userow , bo zalozenie ze wniosek juz rozbity po przelozonych
                    $uss = explode(',', $wniosek->getWniosekNadanieOdebranieZasobow()->getPracownicy());
                    $ADUser = $this->getUserFromAD(trim($uss[0]));
                    $ADManager = $this->getManagerUseraDoWniosku($ADUser[0]);
                }

                if (count($ADManager) == 0 || $ADManager[0]['samaccountname'] == '') {
                    print_r($ADManager);
                    //print_r($uss);
                    //die ("Blad 5426342 Nie moge znalezc przelozonego dla osoby : ".$ADUser[0]['samaccountname']." z managerem ".$ADUser[0]['manager']);
                } else {
                    //print_r($ADManager[0]['samaccountname']);
                    $where[$ADManager[0]['samaccountname']] = $ADManager[0]['samaccountname'];
                    if ($this->debug) {
                        echo '<br>added '.$ADManager[0]['samaccountname'].'<br>';
                    }
                }
                break;
            case 'ibi':
                //
                $entityManager = $this->entityManager;
                $role = $entityManager->getRepository(AclRole::class)->findOneByName('PARP_IBI');
                $users = $entityManager->getRepository(AclUserRole::class)->findByRole($role);
                foreach ($users as $u) {
                    $where[$u->getSamaccountname()] = $u->getSamaccountname();
                    if ($this->debug) {
                        echo '<br>added '.$u->getSamaccountname().'<br>';
                    }
                }
                break;
            case 'wlasciciel':
                if (TypWnioskuConstants::WNIOSEK_UTWORZENIE_ZASOBU === $typWniosku) {
                    $userZasoby = [$wniosek->getWniosekUtworzenieZasobu()->getZmienianyZasob()];
                } elseif (TypWnioskuConstants::WNIOSEK_NADANIE_ODEBRANIE_ZASOBOW === $typWniosku) {
                    $userZasoby = $wniosek->getWniosekNadanieOdebranieZasobow()->getUserZasoby();
                }

                foreach ($userZasoby as $u) {
                    if (TypWnioskuConstants::WNIOSEK_UTWORZENIE_ZASOBU === $typWniosku) {
                        $zasob = $u;
                    } else {
                        $zasob = $entityManager->getRepository(Zasoby::class)->find($u->getZasobId());
                    }
                    $grupa1 = explode(',', $zasob->getWlascicielZasobu());
                    $grupa2 = explode(',', $zasob->getPowiernicyWlascicielaZasobu());
                    $grupa = array_merge($grupa1, $grupa2);

                    foreach ($grupa as $g) {
                        if ($g != '') {
                            $mancn = str_replace('CN=', '', substr($g, 0, stripos($g, ',')));
                            $g = trim($g);
                            //$g = $this->get('renameService')->fixImieNazwisko($g);
                            //$g = $this->get('renameService')->fixImieNazwisko($g);
                            $ADManager = $this->getUserFromAD($g);
                            if (count($ADManager) > 0) {
                                if ($this->debug) {
                                    echo '<br>added '.$ADManager[0]['name'].'<br>';
                                }
                                $where[$ADManager[0]['samaccountname']] = $ADManager[0]['samaccountname'];
                            } else {
                                //throw $this->createNotFoundException('Nie moge znalezc wlasciciel zasobu w AD : '.$g);
                                $message =
                                    "Nie udało się znaleźć właściciela '".
                                    $g.
                                    "' dla zasobu '".
                                    $zasob->getNazwa().
                                    "', dana osoba nie została znaleziona w rejestrze użytkowników PARP (prawdopodobnie jest na zwolnieniu lub została zwolniona).";
                                $this->session->getFlashBag()->add('warning', $message);

                                //NIE MA TAKIEJ METODY TUTAJ
                                //$this->sendMailToAdminRejestru($message);

                                //die ("!!!!!!!!!!blad 111 nie moge znalezc usera ".$g);
                            }
                            //echo "<br>dodaje wlasciciela ".$g;
                            //print_r($where);
                        }
                    }
                }
                break;
            case 'administratorZasobow':
            case 'administrator':
                if (TypWnioskuConstants::WNIOSEK_UTWORZENIE_ZASOBU === $typWniosku) {
                    $zasob = $wniosek->getWniosekUtworzenieZasobu()->getZmienianyZasob();
                    $grupa = explode(',', $zasob->getAdministratorZasobu());
                    foreach ($grupa as $osoba) {
                        $mancn = str_replace('CN=', '', substr($osoba, 0, stripos($osoba, ',')));
                        $osoba = trim($osoba);
                        $ADManager = $this->getUserFromAD($osoba);
                        if (count($ADManager) > 0) {
                            $where[$ADManager[0]['samaccountname']] = $ADManager[0]['samaccountname'];
                        }
                    }
                    break;
                }
                $wniosekNadanieOdebranie = $wniosek->getWniosekNadanieOdebranieZasobow();
                if ($wniosekNadanieOdebranie->getOdebranie() && null !== $wniosekNadanieOdebranie->getZasobId()) {
                    $userZasobId = $wniosekNadanieOdebranie ->getZasobId();
                    $userZasob = $entityManager->getRepository(UserZasoby::class)->find($userZasobId);
                    $zasob = $entityManager->getRepository(Zasoby::class)->find($userZasob->getZasobId());
                    $grupa = explode(',', $zasob->getAdministratorZasobu());
                    foreach ($grupa as $osoba) {
                        $mancn = str_replace('CN=', '', substr($osoba, 0, stripos($osoba, ',')));
                        $osoba = trim($osoba);
                        $ADManager = $this->getUserFromAD($osoba);
                        if (count($ADManager) > 0) {
                            $where[$ADManager[0]['samaccountname']] = $ADManager[0]['samaccountname'];
                        }
                    }
                    break;
                }
                foreach ($wniosek->getWniosekNadanieOdebranieZasobow()->getUserZasoby() as $u) {
                    $zasob = $entityManager->getRepository(Zasoby::class)->find($u->getZasobId());
                    $grupa = explode(',', $zasob->getAdministratorZasobu());
                    foreach ($grupa as $g) {
                        $mancn = str_replace('CN=', '', substr($g, 0, stripos($g, ',')));
                        $g = trim($g);
                        //$g = $this->get('renameService')->fixImieNazwisko($g);
                        $ADManager = $this->getUserFromAD($g);
                        if (count($ADManager) > 0) {
                            if ($this->debug) {
                                echo '<br>added '.$ADManager[0]['name'].'<br>';
                            }
                            $where[$ADManager[0]['samaccountname']] = $ADManager[0]['samaccountname'];
                        } else {
                            $message =
                                "Nie udało się znaleźć administratora '".
                                $g.
                                "' dla zasobu '".
                                $zasob->getNazwa().
                                "', dana osoba nie została znaleziona w rejestrze użytkowników PARP (prawdopodobnie jest na zwolnieniu lub została zwolniona).";
                            $this->session->getFlashBag()->add('warning', $message);

                            //NIE MA TAKIEJ METODY TUTAJ
                           // $this->sendMailToAdminRejestru($message);
                            //throw $this->createNotFoundException('Nie moge znalezc administrator zasobu w AD : '.$g);
                        }
                    }
                }
                break;
            case 'techniczny':
                if (TypWnioskuConstants::WNIOSEK_UTWORZENIE_ZASOBU === $typWniosku) {
                    $userZasoby = [$wniosek->getWniosekUtworzenieZasobu()->getZmienianyZasob()];
                } else {
                    $userZasoby = $wniosek->getWniosekNadanieOdebranieZasobow()->getUserZasoby();
                }
                foreach ($userZasoby as $u) {
                    $zasob = $u;
                    if (!$u instanceof Zasoby) {
                        $zasob = $entityManager->getRepository(Zasoby::class)->find($u->getZasobId());
                    }

                    $grupa = explode(',', $zasob->getAdministratorTechnicznyZasobu());
                    foreach ($grupa as $g) {
                        //$mancn = str_replace("CN=", "", substr($g, 0, stripos($g, ',')));
                        //$g = $this->get('renameService')->fixImieNazwisko($g);
                        $g = trim($g);
                        $ADManager = $this->getUserFromAD($g);
                        if (count($ADManager) > 0) {
                            $where[$ADManager[0]['samaccountname']] = $ADManager[0]['samaccountname'];
                            if ($this->debug) {
                                echo '<br>added '.$ADManager[0]['name'].'<br>';
                            }
                        } else {
                            $message =
                                "Nie udało się znaleźć administratora technicznego '".
                                $g.
                                "' dla zasobu '".
                                $zasob->getNazwa().
                                "', dana osoba nie została znaleziona w rejestrze użytkowników PARP (prawdopodobnie jest na zwolnieniu lub została zwolniona).";
                            $this->session->getFlashBag()->add('warning', $message);

                            //NIE MA TAKIEJ METODY TUTAJ
                            //$this->sendMailToAdminRejestru($message);
                        }
                    }
                }
                break;
        }
        foreach ($where as $k => $v) {
            if ($k == '') {
                die($who.' mam pustego usera !!!!!');
            }
        }
    }

        /**
     * @param $entity
     * @param bool $onlyEditors
     * @param null $username
     * @return array
     */
    public function checkAccess($entity, $onlyEditors = false, $username = null)
    {
        if ($username === null && 'cli' !== PHP_SAPI) {
            $username = $this->currentUser->getUsername();
        }

        $entityManager = $this->entityManager;
        $zastepstwa = $entityManager->getRepository(Zastepstwo::class)->znajdzKogoZastepuje($username);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find WniosekNadanieOdebranieZasobow entity.');
        }

        $editor = $entityManager->getRepository(WniosekEditor::class)->findOneBy(array(
            'samaccountname' => $zastepstwa,
            'wniosek'        => $entity->getWniosek(),
        ));

        //to sprawdza czy ma bezposredni dostep do edycji bez brania pod uwage zastepstw
        $editorsBezZastepstw = $entityManager->getRepository(WniosekEditor::class)->findOneBy(array(
                'samaccountname' => $username,
                'wniosek'        => $entity->getWniosek(),
            ));
        if ($entity->getWniosek()->getLockedBy()) {
            if ($entity->getWniosek()->getLockedBy() != $username) {
                $editor = null;
            }
        } elseif ($editor) {
            $entity->getWniosek()->setLockedBy($username);
            $entity->getWniosek()->setLockedAt(new \Datetime());
            $entityManager->flush();
        }

        $viewer = $entityManager->getRepository('ParpMainBundle:WniosekViewer')->findOneBy(array(
                'samaccountname' => $zastepstwa,
                'wniosek'        => $entity->getWniosek(),
            ));
        $ret = ['viewer' => $viewer, 'editor' => $editor, 'editorsBezZastepstw' => $editorsBezZastepstw];

        //var_dump($ret);
        return $ret;
    }

    protected function getUserFromAD($samaccountname)
    {
        $ldap = $this->ldapService;
        $aduser = $ldap->getUserFromAD($samaccountname);
        if ($aduser === null || count($aduser) === 0) {
            $aduser = $ldap->getUserFromAD($samaccountname, null, null, 'nieobecni');
        }

       /* if (empty($aduser)) {
            echo "Problem z ".$samaccountname."<br/>";
            echo "<pre>";
            var_dump(debug_backtrace(null, 1));
        }*/
        return $aduser;
    }


    /**
     * @param $ADUser
     * @return array
     */
    protected function getManagerUseraDoWniosku($ADUser)
    {
        $ldap = $this->ldapService;
        $manager = $this->entityManager;

        $kogoSzukac = $ldap->kogoBracJakoManageraDlaUseraDoWniosku($ADUser);

        switch ($kogoSzukac) {
            case 'manager':
            case 'prezes':
            case 'p.o. prezesa':
                $ADManager = $ldap->getPrzelozonyJakoTablica($ADUser['samaccountname']);
                break;
            case 'dyrektor':
            default:
                $skrotDepartamentu = $manager->getRepository(Departament::class)
                    ->findOneBy([
                        'name' => $ADUser['department']
                    ]);

                if (null === $skrotDepartamentu) {
                    throw new EntityNotFoundException('Nie mogę znaleźć skrótu departamentu dla '.$ADUser['department']);
                }

                $ADManager = [$ldap->getDyrektoraDepartamentu($skrotDepartamentu->getShortname())];
                break;
        }

        return $ADManager;
    }

    /**
     * Prywatna funkcja zwraca info czy podany tekst jest poprawnym adresem email
     *
     * @param string $text
     *
     * @return bolean
     */
    private function isValidEmail($text)
    {
        $validator = Validation::createValidator();
        $violations = $validator->validate($text, new Email(array('strict' => true)));

        return (0 !== count($violations)) ? false : true;
    }
}
