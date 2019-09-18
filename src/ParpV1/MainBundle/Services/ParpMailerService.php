<?php

namespace ParpV1\MainBundle\Services;

use Doctrine\ORM\EntityManager;
use ParpV1\MainBundle\Entity\Email;
use ParpV1\MainBundle\Entity\Departament;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Validator\Exception\ValidatorException;
use Swift_Mailer;
use Swift_SmtpTransport;

/**
 * Klasa ParpMailerService.
 *
 * Klasa odpowiada za obsługę wychodzących wiadomości e-mail.
 *
 * @package ParpV1\MainBundle\Services
 */
class ParpMailerService
{
    const DEBUG_ZAMIAST_WYSYLKI = false;
    const OSOBA_NA_KTOREJ_TYLKO_TEST_MAILA = '';
    const DEFAULT_PRIORITY = '3';
    const DEFAULT_SENDER = ['aktywnydyrektor@parp.gov.pl' => 'Aktywny Dyrektor'];
    const RETURN_PATH = 'aktywnydyrektor@parp.gov.pl';
    const EMAIL_DO_AUMS_AD = 'jaroslaw_bednarczyk';
    const EMAIL_DO_HELPDESK = 'INT-BI-HELPDESK';
    const EMAIL_DO_GLPI = 'pomoc';

    const TEMPLATE_PRACOWNIKPRZYJECIEIMPORT = 'pracownikPrzyjecieImport.html.twig';
    const TEMPLATE_PRACOWNIKPRZYJECIENADANIEUPRAWNIEN = 'pracownikPrzyjecieNadanieUprawnien.html.twig';
    const TEMPLATE_PRACOWNIKPRZYJECIEBI = 'pracownikPrzyjecieBi.html.twig';
    const TEMPLATE_PRACOWNIKPRZYJECIEBIEXCHANGE = 'pracownikPrzyjecieBiEx.html.twig';
    const TEMPLATE_PRACOWNIKPRZYJECIEFORM = 'pracownikPrzyjecieForm.html.twig';
    const TEMPLATE_PRACOWNIK_NIEOBECNY_POWROT_BI = 'pracownikNieobecnyPowrotBi.html.twig';
    const TEMPLATE_PRACOWNIK_NIEOBECNY_POWROT_FORM = 'pracownikNieobecnyPowrotForm.html.twig';
    const TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN1 = 'pracownikWygasniecieUprawnien1.html.twig';
    const TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN2 = 'pracownikWygasniecieUprawnien2.html.twig';
    const TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN3 = 'pracownikWygasniecieUprawnien3.html.twig';
    const TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN4 = 'pracownikWygasniecieUprawnien4.html.twig';
    const TEMPLATE_PRACOWNIKZMIANASTANOWISKA = 'pracownikZmianaStanowiska.html.twig';
    const TEMPLATE_PRACOWNIKZMIANAZAANGAZOWANIA = 'pracownikZmianaZaangazowania.html.twig';
    const TEMPLATE_PRACOWNIKZWOLNIENIE1 = 'pracownikZwolnienie1.html.twig';
    const TEMPLATE_PRACOWNIKZWOLNIENIE2 = 'pracownikZwolnienie2.html.twig';
    const TEMPLATE_PRACOWNIKZWOLNIENIE3 = 'pracownikZwolnienie3.html.twig';
    const TEMPLATE_PRACOWNIKZWOLNIENIE4 = 'pracownikZwolnienie4.html.twig';
    const TEMPLATE_RAPORTZBIORCZY = 'raportZbiorczy.html.twig';
    const TEMPLATE_WNIOSEKNADANIEUPRAWNIEN = 'wniosekNadanieUprawnien.html.twig';
    const TEMPLATE_WNIOSEKODEBRANIEUPRAWNIEN = 'wniosekOdebranieUprawnien.html.twig';
    const TEMPLATE_WNIOSEKODRZUCENIE = 'wniosekOdrzucenie.html.twig';
    const TEMPLATE_WNIOSEKZWROCENIE = 'wniosekZwrocenie.html.twig';
    const TEMPLATE_WNIOSEKZASOBODRZUCENIE = 'wniosekZasobOdrzucenie.html.twig';
    const TEMPLATE_WNIOSEKZASOBZREALIZOWANIE = 'wniosekZasobZrealizowanie.html.twig';
    const TEMPLATE_WNIOSEKZASOBZWROCENIE = 'wniosekZasobZwrocenie.html.twig';
    const TEMPLATE_PRACOWNIKZWOLNIENIEBI = 'pracownikWylaczenieKontaAd.html.twig';
    const TEMPLATE_OCZEKUJACYWNIOSEK = 'wniosekOczekujacyPrzelozony.html.twig';
    const TEMPLATE_ODEBRANIE_UPRAWNIEN__JEDNORAZOWY = 'odebranie_uprawnien_bez_grup_jednorazowy.html.twig';
    const TEMPLATE_ODEBRANIE_UPRAWNIEN = 'odebranie_uprawnien_bez_grup.html.twig';
    const TEMPLATE_ODEBRANIE_UPRAWNIEN_ROZWIAZANIE_UMOWY = 'odebranie_uprawnien_bez_grup_rozwiazanie_umowy.html.twig';
    const TEMPLATE_ZMIANA_NAZWISKA = 'zmiana_nazwiska.html.twig';
    const ZMIANY_KADROWE_RESET_UPRAWNIEN = 'zmiany_kadrowe_reset_uprawnien.html.twig';

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var Swift_Mailer
     */
    private $mailer;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    private $templating;

    private $ldap;

    private $idSrodowiska;

    private $doFlush = true;

    /**
     * EmailerService constructor.
     *
     * @param EntityManager $entityManager
     * @param Swift_Mailer $mailer
     * @param TokenStorage $tokenStorage
     * @param string $mailerHost
     * @param string $mailerPort
     */
    public function __construct(
        EntityManager $entityManager,
        TokenStorage $tokenStorage,
        $templating,
        $ldap,
        $idSrodowiska,
        string $mailerHost,
        string $mailerPort
    ) {
        $this->entityManager = $entityManager;
        $transport = new Swift_SmtpTransport($mailerHost, $mailerPort);
        $this->mailer = new Swift_Mailer($transport);
        $this->tokenStorage = $tokenStorage;
        $this->templating = $templating;
        $this->ldap = $ldap;
        $this->idSrodowiska = $idSrodowiska;
    }

    /**
     * @param mixed  $recipient
     * @param string $subject
     * @param string $contentTxt
     * @param string $contentHtml
     * @param array  $sender
     * @param string $priority
     *
     * @return int
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function sendEmail(
        $recipient,
        $subject,
        $contentHtml,
        $sender = self::DEFAULT_SENDER,
        $priority = self::DEFAULT_PRIORITY
    ) {
        $mailer = $this->mailer;
        $contentTxt = strip_tags($contentHtml);
        $contentTxt .= "\n\n\nWiadomość została wygenerowana automatycznie. Prosimy na nią nie odpowiadać.";
        $contentHtml .= "<br><br><div style='width: 100%;'>Wiadomość została wygenerowana automatycznie. Prosimy na nią nie odpowiadać.</div>";

        // umożiwia testowanie wysyłki maili ze środowiska testowego,
        if ($this->idSrodowiska == 'test') {
            $odbiorcy = implode(", ", $this->getRecipient($recipient));
            $contentHtml .= "<br><hr><div style='width: 100%;'>Odbiorcy: " . $odbiorcy . "</div>";
            $contentTxt .= "\n\n===================================\nOdbiorcy:". $odbiorcy;
            $recipient = [
                'pawel_fedoruk',
                'hubert_gorecki',
                'katarzyna_wypich',
                'maciej_rogulski',
                'marcin_laskowski',
            ];
        }

        $recipientArray= $this->getRecipient($recipient);

        /** @var \Swift_Message $message */
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($sender)
            ->setSender($sender)
            ->setTo($recipientArray)
            ->setBody($contentTxt, 'text/plain')// ->setId(time().'.'.md5($recipient.time()).'.'.$recipientForId)
        ;

        $message->addPart($contentHtml, 'text/html');
        $message->setReturnPath(self::RETURN_PATH);
        $message->setPriority($priority);
        $failures = null;

        $sent = $mailer->send($message);

        $email = new Email();
        $email
            ->setDataWysylki(new \DateTime())
            ->setTemat($subject)
            ->setTrescTxt($contentTxt)
            ->setTrescHtml($contentHtml)
            ->setLiczbaMaili($sent)
            ->setOdbiorca($recipient);

        if ($this->tokenStorage->getToken()) {
            $uzytkownik = $this->tokenStorage->getToken()->getUser()->getUsername();
            $email->setUzytkownik($uzytkownik);
        }
        $this->entityManager->persist($email);
        if ($this->doFlush) {
            $this->entityManager->flush();
        }

        return $sent;
    }

    public function getUserMail($login)
    {
        if (strpos($login, ',')) {
            $explode = explode(',', $login);

            // Nieprawidłowy adres e-mail - sprawdzamy, czy nie jest to np. tablica wielu loginów
            // jeżeli tak - rekurencyjnie odpalamy i zwracamy
            $emails = [];
            foreach ($explode as $item) {
                $emails[] = $this->getUserMail($item);
            }

            return implode(';', $emails);
        }

        $user = $this->ldap->getUserFromAD($login);
        $email = (!empty($user[0]['mailnickname']) ? $user[0]['mailnickname'] : $login).'@parp.gov.pl';

        return $email;
    }

    protected function getManagerLoginFromDN($manager)
    {
        $parts = explode('=', $manager);
        $parts2 = explode(',', $parts[1]);

        return $parts2[0];
    }

    public function sendEmailZmianaStanowiska($user, $noweStanowisko, $dyrektor)
    {
        $now = new \Datetime();
        $dane = [
            'odbiorcy'         => $dyrektor,
            'imie_nazwisko'    => $user['name'],
            'login'            => $user['samaccountname'],
            'stare_stanowisko' => $user['title'],
            'nowe_stanowisko'  => $noweStanowisko,
            'data_zmiany'      => $now,
        ];
        $this->sendEmailByType(ParpMailerService::TEMPLATE_PRACOWNIKZMIANASTANOWISKA, $dane);
    }

    public function sendEmailWniosekOczekujacy($wniosek, $template)
    {
        $odbiorcy = [];
        $data = [];
        $loginy = [];

        foreach ($wniosek->getWniosek()->getEditors() as $editor) {
            $odbiorcy[] = $this->getUserMail($editor->getSamaccountname());
        }

        $odbiorcy = array_unique($odbiorcy);

        if (!$wniosek->getPracownikSpozaParp()) {
            foreach ($wniosek->getUserZasoby() as $userZasob) {
                $loginy[] = $userZasob->getSamaccountname();
            }
        }
        $loginy = array_unique($loginy);
        $data = [
            'odbiorcy'                           => $odbiorcy,
            'login'                              => $loginy,
            'numer_wniosku'                      => $wniosek->getWniosek()->getNumer(),
            'wniosek' => $wniosek->getWniosek()
        ];

        $this->sendEmailByType($template, $data);
    }

    public function sendEmailWniosekNadanieOdebranieUprawnien($wniosek, $template)
    {
        $odbiorcy = [
            $this->getUserMail($wniosek->getWniosek()->getCreatedBy()),
        ];
        if (in_array($template, [ParpMailerService::TEMPLATE_WNIOSEKZWROCENIE])) {
            //dodac obecnych editors
            foreach ($wniosek->getWniosek()->getEditors() as $e) {
                $odbiorcy[] = $this->getUserMail($e->getSamaccountname());
            }
        } elseif (in_array($template, [ParpMailerService::TEMPLATE_OCZEKUJACYWNIOSEK])) {
            // wysyłamy tylko do przełożonego
            $odbiorcy = [];
            foreach ($wniosek->getWniosek()->getEditors() as $editor) {
                $odbiorcy[] = $this->getUserMail($editor->getSamaccountname());
            }
        }

        $odbiorcy = array_values(array_unique($odbiorcy));

        if (!$wniosek->getPracownikSpozaParp()) {
            $maile = [];
            foreach ($wniosek->getUserZasoby() as $key => $userZasob) {
                $dataZasob = [];

                $user = $this->ldap->getUserFromAD($userZasob->getSamaccountname());
                if (!isset($user[0])) {
                    return false;
                }
                $zasob = $this->entityManager->getRepository('ParpMainBundle:Zasoby')->find($userZasob->getZasobId());
                $usermail = $this->getUserMail($userZasob->getSamaccountname());

                $dyrektor = $this->entityManager->getRepository(Departament::class)->findOneByName($user[0]['department']);
                $dyrektorMail = $this->getUserMail($dyrektor->getDyrektor());

                $dataZasob = [
                    'odbiorcy'                 => array_unique(array_merge($odbiorcy, [$usermail])),
                    'imie_nazwisko'            => $user[0]['name'],
                    'login'                    => $userZasob->getSamaccountname(),
                    'departament'              => $user[0]['department'],
                    'numer_wniosku'            => $wniosek->getWniosek()->getNumer(),
                    'nazwa_zasobu'             => $zasob->getNazwa(),
                    'powod'                    => '',
                ];

                switch ($template) {
                    case ParpMailerService::TEMPLATE_WNIOSEKZWROCENIE:
                        $dataZasob['powod'] = $wniosek->getPowodZwrotu();
                        $dataZasob['odbiorcy'] = $odbiorcy;
                        unset($dataZasob['imie_nazwisko']);
                        unset($dataZasob['login']);
                        unset($dataZasob['departament']);
                        unset($dataZasob['nazwa_zasobu']);
                        break;
                    case ParpMailerService::TEMPLATE_WNIOSEKODRZUCENIE:
                        unset($dataZasob['nazwa_zasobu']);
                        $dataZasob['powod'] = $wniosek->getPowodZwrotu();
                        $dataZasob['odbiorcy'] = array_unique(array_merge($dataZasob['odbiorcy'], [$dyrektorMail]));
                        break;
                    case ParpMailerService::TEMPLATE_OCZEKUJACYWNIOSEK:
                        $dataZasob['odbiorcy'] = $odbiorcy;
                        break;
                    case ParpMailerService::TEMPLATE_WNIOSEKODEBRANIEUPRAWNIEN:
                        $dataZasob['odbiorcy'] = array_unique(array_merge($dataZasob['odbiorcy'], [$dyrektorMail]));
                        break;
                }

                $maile[] = $dataZasob;
            }

            $maileBezDuplikatow = array_map("unserialize", array_unique(array_map("serialize", $maile)));

            foreach ($maileBezDuplikatow as $maileDoWysylki) {
                $this->sendEmailByType($template, $maileDoWysylki);
            }
        }
    }

    public function sendEmailWniosekZasoby($wniosek, $template)
    {
        $odbiorcy = [
            $this->getUserMail($wniosek->getWniosek()->getCreatedBy()),
        ];
        if (in_array($template, [ParpMailerService::TEMPLATE_WNIOSEKZASOBODRZUCENIE])) {
            //dodac wszystkich ktorzy procesowali wniosek
            foreach ($wniosek->getWniosek()->getEditors() as $e) {
                $odbiorcy[] = $this->getUserMail($e->getSamaccountname());
            }
            foreach ($wniosek->getWniosek()->getViewers() as $v) {
                $odbiorcy[] = $this->getUserMail($v->getSamaccountname());
            }
        } elseif (in_array($template, [ParpMailerService::TEMPLATE_WNIOSEKZASOBZWROCENIE])) {
            //dodac obecnych editors
            foreach ($wniosek->getWniosek()->getEditors() as $e) {
                $odbiorcy[] = $this->getUserMail($e->getSamaccountname());
            }
        }

        $zasob = $wniosek->getZasob();
        if ($wniosek->getTyp() === 'nowy') {
            $zasob = $wniosek->getZasob();
        } elseif ($wniosek->getTyp() === 'zmiana' || 'kasowanie' === $wniosek->getTyp()) {
            $zasob = $wniosek->getZmienianyZasob();
        }
        $dodatkowyMailWlascicielZasobu = $this->getUserMail($zasob->getWlascicielZasobu());

        $data = [
            'odbiorcy'                           => array_merge($odbiorcy, [$dodatkowyMailWlascicielZasobu]),
            'imie_nazwisko'                      => $wniosek->getImienazwisko(),
            'login'                              => $wniosek->getLogin(),
            'numer_wniosku'                      => $wniosek->getWniosek()->getNumer(),
            'nazwa_zasobu'                       => $zasob->getNazwa(),
            'data_odrzucenia'                    => $this->formatDateForDisplay(new \Datetime()),
            'data_dzien_rejestracji_w_rejestrze' => $this->formatDateForDisplay(new \Datetime()),
            'data_zwrocenia'                     => $this->formatDateForDisplay(new \Datetime()),
            'powod'                              => $wniosek->getPowodZwrotu(),
        ];
        $this->sendEmailByType($template, $data);
    }

    /**
     * @param mixed $template
     * @param array $data
     *
     * @return int
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function sendEmailByType($template, $data)
    {
        $wymaganePola = $this->getWymaganePola($template);

        if (!empty($data['tytul'])) {
            $tytul = $this->getTytulMaila($template) . $data['tytul'];
        } else {
            $tytul = 'Aktywny Dyrektor komunikat: ' . $this->getTytulMaila($template);
        }

        if (count(array_intersect_key(array_flip($wymaganePola), $data)) === count($wymaganePola)) {
            //Mamy wszystkie wymagane dane
            if ($template == ParpMailerService::TEMPLATE_RAPORTZBIORCZY) {
                $view = $data['html'];
            } else {
                $view = $this->templating->render(
                    'maile/'.$template,
                    $data
                );
            }

            if (!empty($data['nadawca'])) {
                $this->sendEmail($data['odbiorcy'], $tytul, $view, $data['nadawca']);
            } else {
                $this->sendEmail($data['odbiorcy'], $tytul, $view);
            }
        } else {
            $braki = array_diff($wymaganePola, array_keys($data));
            $msg =
                'Błąd brakuje danych do wygenerowania maila o tytule "'.
                $tytul.
                '" z szablonu "'.
                $template.
                '"!! Brakujące dane : '.
                implode(', ', $braki);
            echo($msg);
        }
    }

    protected function getWymaganePola($template)
    {
        $wymaganePola = ['odbiorcy', 'imie_nazwisko', 'login'];
        switch ($template) {
            case ParpMailerService::TEMPLATE_RAPORTZBIORCZY:
                unset($wymaganePola[2]);//login
                unset($wymaganePola[1]);//imie_nazwisko
                $wymaganePola[] = 'html';
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKPRZYJECIEIMPORT:
            case ParpMailerService::TEMPLATE_PRACOWNIKPRZYJECIENADANIEUPRAWNIEN:
                //Pracownik jest przyjmowany do pracy w PARP 1
                $wymaganePola = array_merge($wymaganePola, ['departament', 'data_nadania_uprawnien_poczatkowych']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN1:
                $wymaganePola = array_merge($wymaganePola, ['nazwa_zasobu', 'data_wygasania_uprawnien']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN2:
                $wymaganePola =
                    array_merge($wymaganePola, ['departament', 'data_wygasania_uprawnien', 'nazwy_grup_ad']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN3:
            case ParpMailerService::TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN4:
                $wymaganePola = array_merge($wymaganePola, ['departament', 'data_wygasania_uprawnien', 'nazwa_zasobu']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKZMIANASTANOWISKA:
                $wymaganePola = array_merge($wymaganePola, ['stare_stanowisko', 'nowe_stanowisko', 'data_zmiany']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKZWOLNIENIE3:
            case ParpMailerService::TEMPLATE_PRACOWNIKZWOLNIENIE4:
                $wymaganePola = array_merge($wymaganePola, ['departament', 'data_zmiany']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKZWOLNIENIE1:
            case ParpMailerService::TEMPLATE_PRACOWNIKZWOLNIENIE2:
                $wymaganePola = array_merge($wymaganePola, ['data_zmiany']);
                break;
            case ParpMailerService::TEMPLATE_WNIOSEKNADANIEUPRAWNIEN:
            case ParpMailerService::TEMPLATE_WNIOSEKODEBRANIEUPRAWNIEN:
                $wymaganePola =
                    array_merge($wymaganePola, ['departament', 'numer_wniosku', 'nazwa_zasobu']);
                break;
            case ParpMailerService::TEMPLATE_WNIOSEKODRZUCENIE:
                $wymaganePola =
                    array_merge($wymaganePola, ['departament', 'numer_wniosku', 'powod']);
                break;
            case ParpMailerService::TEMPLATE_WNIOSEKZASOBODRZUCENIE:
                $wymaganePola =
                    array_merge($wymaganePola, ['data_odrzucenia', 'numer_wniosku', 'nazwa_zasobu', 'powod']);
                break;
            case ParpMailerService::TEMPLATE_WNIOSEKZASOBZREALIZOWANIE:
                $wymaganePola =
                    array_merge($wymaganePola, ['data_dzien_rejestracji_w_rejestrze', 'numer_wniosku', 'nazwa_zasobu']);
                break;
            case ParpMailerService::TEMPLATE_WNIOSEKZASOBZWROCENIE:
                $wymaganePola =
                    array_merge($wymaganePola, ['data_zwrocenia', 'numer_wniosku', 'nazwa_zasobu', 'powod']);
                break;
            case ParpMailerService::TEMPLATE_WNIOSEKZWROCENIE:
                unset($wymaganePola[2]);//login
                unset($wymaganePola[1]);//imie_nazwisko
                $wymaganePola = array_merge($wymaganePola, ['numer_wniosku', 'powod']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKZMIANAZAANGAZOWANIA:
                $wymaganePola = array_merge($wymaganePola, ['departament']);
                unset($wymaganePola[1]);
                break;
            case ParpMailerService::TEMPLATE_OCZEKUJACYWNIOSEK:
            case ParpMailerService::ZMIANY_KADROWE_RESET_UPRAWNIEN:
                unset($wymaganePola[1]);
                break;
            case ParpMailerService::TEMPLATE_ODEBRANIE_UPRAWNIEN:
            case ParpMailerService::TEMPLATE_ODEBRANIE_UPRAWNIEN_ROZWIAZANIE_UMOWY:
                unset($wymaganePola[1]);
                unset($wymaganePola[2]);
                break;
        }

        return $wymaganePola;
    }

    protected function getTytulMaila($template)
    {
        $tytuly = [
            ParpMailerService::TEMPLATE_PRACOWNIKPRZYJECIEIMPORT           => 'Pracownik jest przyjmowany do pracy w PARP',
            ParpMailerService::TEMPLATE_PRACOWNIKPRZYJECIENADANIEUPRAWNIEN => 'Pracownik jest przyjmowany do pracy w PARP',
            ParpMailerService::TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN1     => 'Bliski termin wygaśnięcia ważności uprawnień',
            ParpMailerService::TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN2     => 'Wygaśnięcie ważności uprawnień: Zasób: Sieć PARP- poziomy dostępu: [UMG];[UMP];[UPP];[OU]',
            ParpMailerService::TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN3     => 'Wygaśnięcie ważności uprawnień: Zasób: grupa w MS-AD – uprawnienia nadane wnioskiem',
            ParpMailerService::TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN4     => 'Wygaśnięcie ważności uprawnień: Zasoby inne niż INT, SG(G) i EXT:',
            ParpMailerService::TEMPLATE_PRACOWNIKZMIANASTANOWISKA         => 'Pracownik zmienia stanowisko pozostając w dotychczasowym D/B',
            ParpMailerService::TEMPLATE_PRACOWNIKZMIANAZAANGAZOWANIA       => 'Pracownikowi zmieniono zaangażowanie',
            ParpMailerService::TEMPLATE_PRACOWNIKZWOLNIENIE1               => 'Pracownik odchodzi z PARP (pracuje do ostatniego dnia)',
            ParpMailerService::TEMPLATE_PRACOWNIKZWOLNIENIE2               => 'Pracownik odchodzi z PARP (pracuje do ostatniego dnia)',
            ParpMailerService::TEMPLATE_PRACOWNIKZWOLNIENIE3               => 'Pracownik odchodzi z PARP (pracuje do ostatniego dnia)',
            ParpMailerService::TEMPLATE_PRACOWNIKZWOLNIENIE4               => 'Pracownik odchodzi z PARP (pracuje do ostatniego dnia)',
            ParpMailerService::TEMPLATE_RAPORTZBIORCZY                     => 'Raport zbiorczy',
            ParpMailerService::TEMPLATE_WNIOSEKNADANIEUPRAWNIEN            => 'Nadanie uprawnień użytkownikowi (na wniosek)',
            ParpMailerService::TEMPLATE_WNIOSEKODEBRANIEUPRAWNIEN          => 'Odebranie uprawnień użytkownikowi (na wniosek)',
            ParpMailerService::TEMPLATE_WNIOSEKODRZUCENIE                  => 'Odrzucenie wniosku o nadanie/odebranie uprawnień',
            ParpMailerService::TEMPLATE_WNIOSEKZASOBODRZUCENIE             => 'Odrzucenie wniosku o umieszczenie/zmianę/wycofanie zasobu',
            ParpMailerService::TEMPLATE_WNIOSEKZASOBZREALIZOWANIE          => 'Zrealizowanie wniosku o umieszczenie/zmianę/wycofanie zasobu',
            ParpMailerService::TEMPLATE_WNIOSEKZASOBZWROCENIE              => 'Zwrócenie do poprawy wniosku o umieszczenie/zmianę/wycofanie zasobu',
            ParpMailerService::TEMPLATE_WNIOSEKZWROCENIE                   => 'Zwrócenie do poprawy wniosku o nadanie/odebranie uprawnień',
            ParpMailerService::TEMPLATE_PRACOWNIKPRZYJECIEBI               => '[BI] Nowy pracownik: ',
            ParpMailerService::TEMPLATE_PRACOWNIKPRZYJECIEBIEXCHANGE       => '[BI] Nowe konto Exchange: ',
            ParpMailerService::TEMPLATE_PRACOWNIKPRZYJECIEFORM             => '[Formularz] Nowy pracownik: ',
            ParpMailerService::TEMPLATE_PRACOWNIKZWOLNIENIEBI              => 'Wyłączenie konta w Exchange: ',
            ParpMailerService::TEMPLATE_OCZEKUJACYWNIOSEK                  => 'Akceptacja przełożonego, wniosek o nadanie/odebranie uprawnień',
            self::TEMPLATE_ODEBRANIE_UPRAWNIEN__JEDNORAZOWY                => 'Weryfikacja wniosków o nadanie uprawnień',
            self::TEMPLATE_ODEBRANIE_UPRAWNIEN                             => 'Zmiany kadrowe użytkownika - reset uprawnień',
            self::TEMPLATE_ODEBRANIE_UPRAWNIEN_ROZWIAZANIE_UMOWY           => 'Zmiany kadrowe użytkownika - rozwiązanie umowy',
            self::TEMPLATE_ZMIANA_NAZWISKA                                 => '[BI] Zmiana nazwiska',
            self::ZMIANY_KADROWE_RESET_UPRAWNIEN                           => 'Zmiany kadrowe użytkownika - reset uprawnień',
            self::TEMPLATE_PRACOWNIK_NIEOBECNY_POWROT_BI                   => '[BI] Powrót z długotrwałej nieobecności: ',
            self::TEMPLATE_PRACOWNIK_NIEOBECNY_POWROT_FORM                 => '[Formularz] Powracający pracownik: ',
        ];

        return isset($tytuly[$template]) ? $tytuly[$template] : 'Domyślny tytuł maila';
    }

    protected function formatDateForDisplay($dt)
    {
        return $dt ? $dt->format('Y-m-d') : '';
    }


    /**
     * Funkcja przebudowuje dane na poprawną tablicę lub string z adresami/adresem email
     *
     * @param array|string $recipient
     *
     * @return array
     */
    protected function getRecipient($recipient): array
    {
        if (is_array($recipient)) {
            $recipientArr = [];
            foreach ($recipient as $recipientSingle) {
                if (strstr($recipientSingle, '@') === false) {
                    $recipientSingle = $this->getUserMail($recipientSingle);
                }
                $recipientArr = array_merge($recipientArr, explode(';', $recipientSingle));
            }
            $recipientArr = array_unique($recipientArr);

            return $recipientArr;
        } else {
            if (strstr($recipient, '@') === false) {
                $recipient = $this->getUserMail($recipient);
            }

            return [$recipient];
        }
    }

    /**
     * Wyłączenie flusha
     *
     * @return self
     */
    public function disableFlush(): self
    {
        $this->doFlush = false;

        return $this;
    }
}
