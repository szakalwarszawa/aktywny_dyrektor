<?php
namespace Parp\MainBundle\Services;

use Doctrine\ORM\EntityManager;
use Parp\MainBundle\Entity\Email;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Klasa ParpMailerService.
 *
 * Klasa odpowiada za obsługę wychodzących wiadomości e-mail.
 *
 * @package Parp\MainBundle\Services
 */
class ParpMailerService
{
    const DEBUG_ZAMIAST_WYSYLKI = false;
    const DEFAULT_PRIORITY = '3';
    const DEFAULT_SENDER = ['aktywnydyrektor@parp.gov.pl' => 'Aktywny Dyrektor'];
    const RETURN_PATH = 'aktywnydyrektor@parp.gov.pl'; 

    const TEMPLATE_PRACOWNIKMIGRACJA1 = 'pracownikMigracja1.html.twig';
    const TEMPLATE_PRACOWNIKMIGRACJA2 = 'pracownikMigracja2.html.twig';
    const TEMPLATE_PRACOWNIKMIGRACJA3 = 'pracownikMigracja3.html.twig';
    const TEMPLATE_PRACOWNIKMIGRACJA4 = 'pracownikMigracja4.html.twig';
    const TEMPLATE_PRACOWNIKMIGRACJA5 = 'pracownikMigracja5.html.twig';
    const TEMPLATE_PRACOWNIKPRZYJECIEIMPORT = 'pracownikPrzyjecieImport.html.twig';
    const TEMPLATE_PRACOWNIKPRZYJECIENADANIEUPRAWNIEN = 'pracownikPrzyjecieNadanieUprawnien.html.twig';
    const TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN1 = 'pracownikWygasniecieUprawnien1.html.twig';
    const TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN2 = 'pracownikWygasniecieUprawnien2.html.twig';
    const TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN3 = 'pracownikWygasniecieUprawnien3.html.twig';
    const TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN4 = 'pracownikWygasniecieUprawnien4.html.twig';
    const TEMPLATE_PRACOWNIKZMIANASEKCJI1 = 'pracownikZmianaSekcji1.html.twig';
    const TEMPLATE_PRACOWNIKZMIANASEKCJI2 = 'pracownikZmianaSekcji2.html.twig';
    const TEMPLATE_PRACOWNIKZMIANASTANOWISKA1 = 'pracownikZmianaStanowiska1.html.twig';
    const TEMPLATE_PRACOWNIKZMIANASTANOWISKA2 = 'pracownikZmianaStanowiska2.html.twig';
    const TEMPLATE_PRACOWNIKZMIANASTANOWISKA3 = 'pracownikZmianaStanowiska3.html.twig';
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

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;

    /**
     * @var \Swift_Mailer
     */
    private $mailer;

    /**
     * @var \Symfony\Component\Security\Core\SecurityContext
     */
    private $securityContext;

    private $templating;

    private $ldap;

    /**
     * EmailerService constructor.
     *
     * @param \Doctrine\ORM\EntityManager                      $entityManager
     * @param \Swift_Mailer                                    $mailer
     * @param \Symfony\Component\Security\Core\SecurityContext $securityContext
     */
    public function __construct(
        EntityManager $entityManager,
        \Swift_Mailer $mailer,
        SecurityContext $securityContext,
        $templating,
        $ldap
    ) {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
        $this->securityContext = $securityContext;
        $this->templating = $templating;
        $this->ldap = $ldap;
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
     *
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function sendEmail(
        $recipient,
        $subject,
        $contentHtml,
        $sender = SELF::DEFAULT_SENDER,
        $priority = SELF::DEFAULT_PRIORITY
    ) {
        $mailer = $this->mailer;
        $contentTxt = strip_tags($contentHtml);
        $contentTxt .= "\n\n\nWiadomość została wygenerowana automatycznie. Prosimy na nią nie odpowiadać.";
        $contentHtml .= "<br><br><div style='width: 100%;'>Wiadomość została wygenerowana automatycznie. Prosimy na nią nie odpowiadać.</div>";
        $recipientForId = is_array($recipient) ? 'array' : $recipient;

        if(ParpMailerService::DEBUG_ZAMIAST_WYSYLKI){
            $recipient = 'kamil_jakacki@parp.gov.pl';//dev only
        }
        if(is_array($recipient)){
            for($i = 0; $i < count($recipient); $i++){
                if(strstr($recipient[$i], "@") === false) {
                    $recipient[$i] = $this->getUserMail($recipient[$i]);
                }
            }
        }else{
            if(strstr($recipient, "@") === false) {
                $recipient = $this->getUserMail($recipient);
            }
        }

        /** @var \Swift_Message $message */
        $message = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom($sender)
            ->setSender($sender)
            ->setTo($recipient)
            ->setBody($contentTxt, 'text/plain')
            // ->setId(time().'.'.md5($recipient.time()).'.'.$recipientForId)
        ;

        $message->addPart($contentHtml, 'text/html');
        $message->setReturnPath(SELF::RETURN_PATH);
        $message->setPriority($priority);

        if(ParpMailerService::DEBUG_ZAMIAST_WYSYLKI){
            var_dump($recipient);
        }
        $sent = $mailer->send($message);


        $email = new Email();
        $email
            ->setDataWysylki(new \DateTime())
            ->setTemat($subject)
            ->setTrescTxt($contentTxt)
            ->setTrescHtml($contentHtml)
            ->setLiczbaMaili($sent)
            ->setOdbiorca($recipient)
        ;

        if ($this->securityContext->getToken()) {
            $uzytkownik = $this->securityContext->getToken()->getUser()->getUsername();
            $email->setUzytkownik($uzytkownik);
        }

        $this->entityManager->persist($email);
        $this->entityManager->flush();

        return $sent;
    }
    protected function getUserMail($login){
        return $login."@parp.gov.pl";
    }
    public function sendEmailWniosekNadanieOdebranieUprawnien($wniosek, $template){
        $odbiorcy = [
            $this->getUserMail($wniosek->getWniosek()->getCreatedBy())
        ];
        if(in_array($template, [ParpMailerService::TEMPLATE_WNIOSEKODRZUCENIE])){
            //dodac wszystkich ktorzy procesowali wniosek
            foreach($wniosek->getWniosek()->getEditors() as $e){
                $odbiorcy[] = $this->getUserMail($e->getSamaccountname());
            }
            foreach($wniosek->getWniosek()->getViewers() as $v){
                $odbiorcy[] = $this->getUserMail($v->getSamaccountname());
            }
        }
        elseif(in_array($template, [ParpMailerService::TEMPLATE_WNIOSEKZWROCENIE])){
            //dodac obecnych editors
            foreach($wniosek->getWniosek()->getEditors() as $e){
                $odbiorcy[] = $this->getUserMail($e->getSamaccountname());
            }
        }
        $odbiorcy = array_unique($odbiorcy);
        
        if(!$wniosek->getPracownikSpozaParp()){             
            foreach($wniosek->getUserZasoby() as $userZasob) {
                $user = $this->ldap->getUserFromAD($userZasob->getSamaccountname());
                $zasob = $this->entityManager->getRepository('ParpMainBundle:Zasoby')->find($userZasob->getZasobId());
                $usermail = $this->getUserMail($userZasob->getSamaccountname());
                $data = [
                    'odbiorcy' => array_merge($odbiorcy, [$usermail]),
                    'imie_nazwisko' => $user[0]['name'],
                    'login' => $userZasob->getSamaccountname(),
                    'departament' => $user[0]['department'],
                    'data_zmiany' => $this->formatDateForDisplay($userZasob->getAktywneOd()),
                    'numer_wniosku' => $wniosek->getWniosek()->getNumer(),
                    'nazwa_zasobu' => $zasob->getNazwa(),
                    'data_odebrania_uprawnien' => $this->formatDateForDisplay($userZasob->getAktywneDo()),
                    'data_zwrocenia' => $this->formatDateForDisplay(new \Datetime()),
                    'powod' => "" //$userZasob->getPowodOdebrania(),
                ];
                switch($template){
                    case ParpMailerService::TEMPLATE_WNIOSEKZWROCENIE:
                        $data['powod'] = $wniosek->getPowodZwrotu();
                        break;
                    case ParpMailerService::TEMPLATE_WNIOSEKODRZUCENIE:
                        $data['powod'] = $wniosek->getPowodZwrotu();
                        break;
                }
                $this->sendEmailByType($template, $data);
            }
        }
    }
    public function sendEmailWniosekZasoby($wniosek, $template){
        $odbiorcy = [
            $this->getUserMail($wniosek->getWniosek()->getCreatedBy())
        ];
        if(in_array($template, [ParpMailerService::TEMPLATE_WNIOSEKZASOBODRZUCENIE])){
            //dodac wszystkich ktorzy procesowali wniosek
            foreach($wniosek->getWniosek()->getEditors() as $e){
                $odbiorcy[] = $this->getUserMail($e->getSamaccountname());
            }
            foreach($wniosek->getWniosek()->getViewers() as $v){
                $odbiorcy[] = $this->getUserMail($v->getSamaccountname());
            }
        }
        elseif(in_array($template, [ParpMailerService::TEMPLATE_WNIOSEKZASOBZWROCENIE])){
            //dodac obecnych editors
            foreach($wniosek->getWniosek()->getEditors() as $e){
                $odbiorcy[] = $this->getUserMail($e->getSamaccountname());
            }
        }


        $zasob = $wniosek->getZasob();
        if($wniosek->getTyp() == "nowy"){
            $zasob = $wniosek->getZasob();
        }
        elseif($wniosek->getTyp() == "zmiana"){
            $zasob = $wniosek->getZmienianyZasob();
        }
        elseif($wniosek->getTyp() == "kasowanie"){
            $zasob = $wniosek->getZmienianyZasob();
        }
        $dodatkowyMailWlascicielZasobu = $this->getUserMail($zasob->getWlascicielZasobu());

        $data = [
            'odbiorcy' => array_merge($odbiorcy, [$dodatkowyMailWlascicielZasobu]),
            'imie_nazwisko' => $wniosek->getImienazwisko(),
            'login' => $wniosek->getLogin(),
            'numer_wniosku' => $wniosek->getWniosek()->getNumer(),
            'nazwa_zasobu' => $zasob->getNazwa(),
            'data_odrzucenia' => $this->formatDateForDisplay(new \Datetime()),
            'data_dzien_rejestracji_w_rejestrze' => $this->formatDateForDisplay(new \Datetime()),
            'data_zwrocenia' => $this->formatDateForDisplay(new \Datetime()),
            'powod' => $wniosek->getPowodZwrotu(),
        ];
        $this->sendEmailByType($template, $data);

    }

    /**
     * @param mixed  $template
     *
     * @return int
     *
     * @throws \Doctrine\ORM\ORMInvalidArgumentException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function sendEmailByType($template, $data)
    {
        $wymaganePola = $this->getWymaganePola($template);
        $tytul = "Aktywny Dyrektor komunikat: ".$this->getTytulMaila($template);
        if(count(array_intersect_key(array_flip($wymaganePola), $data)) === count($wymaganePola)) {
            //Mamy wszystkie wymagane dane
            if($template == ParpMailerService::TEMPLATE_RAPORTZBIORCZY){
                $view = $data['html'];
            }else {
                $view = $this->templating->render(
                    'maile/' . $template,
                    $data
                );
            }
            $this->sendEmail($data['odbiorcy'], $tytul, $view);
        }else{
            $braki = array_diff($wymaganePola, array_keys($data));
            $msg = 'Błąd brakuje danych do wygenerowania maila o tytule "'.$tytul.'" z szablonu "'.$template.'"!! Brakujące dane : '.implode(', ', $braki);
            echo ($msg);
        }
    }

    protected function getWymaganePola($template){
        $wymaganePola = ['odbiorcy', 'imie_nazwisko', 'login'];
        switch($template){
            case ParpMailerService::TEMPLATE_RAPORTZBIORCZY:
                unset($wymaganePola[2]);//login
                unset($wymaganePola[1]);//imie_nazwisko
                $wymaganePola[] = 'html';
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKMIGRACJA1:
                $wymaganePola[] = 'data_dzien_rozpoczecia_pracy_w_nowym_db';
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKMIGRACJA2:
                $wymaganePola = array_merge($wymaganePola, ['data_dzien_rozpoczecia_pracy_w_nowym_db', 'nazwa_zasobu', 'nowy_db']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKMIGRACJA3:
                $wymaganePola = array_merge($wymaganePola, ['data_dzien_rozpoczecia_pracy_w_nowym_db', 'stary_db', 'nowy_db']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKMIGRACJA4:
                $wymaganePola = array_merge($wymaganePola, ['data_dzien_rozpoczecia_pracy_w_nowym_db', 'stary_db', 'nowy_db']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKMIGRACJA5:
                $wymaganePola = array_merge($wymaganePola, ['data_dzien_rozpoczecia_pracy_w_nowym_db', 'stary_db', 'nowy_db']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKPRZYJECIEIMPORT:
                $wymaganePola = array_merge($wymaganePola, ['departament', 'data_nadania_uprawnien_poczatkowych']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKPRZYJECIENADANIEUPRAWNIEN:
                $wymaganePola = array_merge($wymaganePola, ['departament', 'data_nadania_uprawnien_poczatkowych']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN1:
                $wymaganePola = array_merge($wymaganePola, ['nazwa_zasobu', 'data_wygasania_uprawnien']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN2:
                $wymaganePola = array_merge($wymaganePola, ['departament', 'data_wygasania_uprawnien', 'nazwy_grup_ad']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN3:
                $wymaganePola = array_merge($wymaganePola, ['departament', 'data_wygasania_uprawnien', 'nazwa_zasobu']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN4:
                $wymaganePola = array_merge($wymaganePola, ['departament', 'data_wygasania_uprawnien', 'nazwa_zasobu']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKZMIANASEKCJI1:
                $wymaganePola = array_merge($wymaganePola, ['stara_sekcja', 'nowa_sekcja', 'data_zmiany']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKZMIANASEKCJI2:
                $wymaganePola = array_merge($wymaganePola, ['stara_sekcja', 'nowa_sekcja', 'data_zmiany']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKZMIANASTANOWISKA1:
                $wymaganePola = array_merge($wymaganePola, ['stare_stanowisko', 'nowe_stanowisko', 'data_zmiany']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKZMIANASTANOWISKA2:
                $wymaganePola = array_merge($wymaganePola, ['stare_stanowisko', 'nowe_stanowisko', 'data_zmiany']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKZMIANASTANOWISKA3:
                $wymaganePola = array_merge($wymaganePola, ['stare_stanowisko', 'nowe_stanowisko', 'data_zmiany']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKZMIANAZAANGAZOWANIA:
                $wymaganePola = array_merge($wymaganePola, ['departament', 'data_zmiany']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKZWOLNIENIE1:
                $wymaganePola = array_merge($wymaganePola, ['data_zmiany']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKZWOLNIENIE2:
                $wymaganePola = array_merge($wymaganePola, ['data_zmiany']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKZWOLNIENIE3:
                $wymaganePola = array_merge($wymaganePola, ['departament', 'data_zmiany']);
                break;
            case ParpMailerService::TEMPLATE_PRACOWNIKZWOLNIENIE4:
                $wymaganePola = array_merge($wymaganePola, ['departament', 'data_zmiany']);
                break;
            case ParpMailerService::TEMPLATE_WNIOSEKNADANIEUPRAWNIEN:
                $wymaganePola = array_merge($wymaganePola, ['departament', 'data_zmiany', 'numer_wniosku', 'nazwa_zasobu']);
                break;
            case ParpMailerService::TEMPLATE_WNIOSEKODEBRANIEUPRAWNIEN:
                $wymaganePola = array_merge($wymaganePola, ['departament', 'data_zmiany', 'numer_wniosku', 'nazwa_zasobu']);
                break;
            case ParpMailerService::TEMPLATE_WNIOSEKODRZUCENIE:
                $wymaganePola = array_merge($wymaganePola, ['departament', 'data_odebrania_uprawnien', 'numer_wniosku', 'powod']);
                break;
            case ParpMailerService::TEMPLATE_WNIOSEKZASOBODRZUCENIE:
                $wymaganePola = array_merge($wymaganePola, ['data_odrzucenia', 'numer_wniosku', 'nazwa_zasobu', 'powod']);
                break;
            case ParpMailerService::TEMPLATE_WNIOSEKZASOBZREALIZOWANIE:
                $wymaganePola = array_merge($wymaganePola, ['data_dzien_rejestracji_w_rejestrze', 'numer_wniosku', 'nazwa_zasobu']);
                break;
            case ParpMailerService::TEMPLATE_WNIOSEKZASOBZWROCENIE:
                $wymaganePola = array_merge($wymaganePola, ['data_zwrocenia', 'numer_wniosku', 'nazwa_zasobu', 'powod']);
                break;
            case ParpMailerService::TEMPLATE_WNIOSEKZWROCENIE:
                $wymaganePola = array_merge($wymaganePola, ['departament', 'data_zwrocenia', 'numer_wniosku', 'powod']);
                break;
        }
        return $wymaganePola;
    }
    protected function getTytulMaila($template){
        $tytuly = [
            ParpMailerService::TEMPLATE_PRACOWNIKMIGRACJA1 => "Pracownik migruje do innego D/B",
            ParpMailerService::TEMPLATE_PRACOWNIKMIGRACJA2 => "Pracownik migruje do innego D/B",
            ParpMailerService::TEMPLATE_PRACOWNIKMIGRACJA3 => "Pracownik migruje do innego D/B",
            ParpMailerService::TEMPLATE_PRACOWNIKMIGRACJA4 => "Pracownik migruje do innego D/B",
            ParpMailerService::TEMPLATE_PRACOWNIKMIGRACJA5 => "Pracownik migruje do innego D/B",
            ParpMailerService::TEMPLATE_PRACOWNIKPRZYJECIEIMPORT => "Pracownik jest przyjmowany do pracy w PARP",
            ParpMailerService::TEMPLATE_PRACOWNIKPRZYJECIENADANIEUPRAWNIEN => "Pracownik jest przyjmowany do pracy w PARP",
            ParpMailerService::TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN1 => "Bliski termin wygaśnięcia ważności uprawnień",
            ParpMailerService::TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN2 => "Wygaśnięcie ważności uprawnień: Zasób: Sieć PARP- poziomy dostępu: [UMG];[UMP];[UPP];[OU]",
            ParpMailerService::TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN3 => "Wygaśnięcie ważności uprawnień: Zasób: grupa w MS-AD – uprawnienia nadane wnioskiem",
            ParpMailerService::TEMPLATE_PRACOWNIKWYGASNIECIEUPRAWNIEN4 => "Wygaśnięcie ważności uprawnień: Zasoby inne niż INT, SG(G) i EXT:",
            ParpMailerService::TEMPLATE_PRACOWNIKZMIANASEKCJI1 => "Pracownik zmienia sekcję pozostając w dotychczasowym D/B",
            ParpMailerService::TEMPLATE_PRACOWNIKZMIANASEKCJI2 => "Pracownik zmienia sekcję pozostając w dotychczasowym D/B",
            ParpMailerService::TEMPLATE_PRACOWNIKZMIANASTANOWISKA1 => "Pracownik zmienia stanowisko pozostając w dotychczasowym D/B",
            ParpMailerService::TEMPLATE_PRACOWNIKZMIANASTANOWISKA2 => "Pracownik zmienia stanowisko pozostając w dotychczasowym D/B",
            ParpMailerService::TEMPLATE_PRACOWNIKZMIANASTANOWISKA3 => "Pracownik zmienia stanowisko pozostając w dotychczasowym D/B",
            ParpMailerService::TEMPLATE_PRACOWNIKZMIANAZAANGAZOWANIA => "Pracownikowi zmieniono zaangażowanie",
            ParpMailerService::TEMPLATE_PRACOWNIKZWOLNIENIE1 => "Pracownik odchodzi z PARP (pracuje do ostatniego dnia)",
            ParpMailerService::TEMPLATE_PRACOWNIKZWOLNIENIE2 => "Pracownik odchodzi z PARP (pracuje do ostatniego dnia)",
            ParpMailerService::TEMPLATE_PRACOWNIKZWOLNIENIE3 => "Pracownik odchodzi z PARP (pracuje do ostatniego dnia)",
            ParpMailerService::TEMPLATE_PRACOWNIKZWOLNIENIE4 => "Pracownik odchodzi z PARP (pracuje do ostatniego dnia)",
            ParpMailerService::TEMPLATE_RAPORTZBIORCZY => "Raport zbiorczy",
            ParpMailerService::TEMPLATE_WNIOSEKNADANIEUPRAWNIEN => "Nadanie uprawnień użytkownikowi (na wniosek)",
            ParpMailerService::TEMPLATE_WNIOSEKODEBRANIEUPRAWNIEN => "Odebranie uprawnień użytkownikowi (na wniosek)",
            ParpMailerService::TEMPLATE_WNIOSEKODRZUCENIE => "Odrzucenie wniosku o nadanie/odebranie uprawnień",
            ParpMailerService::TEMPLATE_WNIOSEKZASOBODRZUCENIE => "Odrzucenie wniosku o umieszczenie/zmianę/wycofanie zasobu",
            ParpMailerService::TEMPLATE_WNIOSEKZASOBZREALIZOWANIE => "Zrealizowanie wniosku o umieszczenie/zmianę/wycofanie zasobu",
            ParpMailerService::TEMPLATE_WNIOSEKZASOBZWROCENIE => "Zwrócenie do poprawy wniosku o umieszczenie/zmianę/wycofanie zasobu",
            ParpMailerService::TEMPLATE_WNIOSEKZWROCENIE => "Zwrócenie do poprawy wniosku o nadanie/odebranie uprawnień",
        ];
        return isset($tytuly[$template]) ? $tytuly[$template] : "Domyślny tytuł maila";
    }

    protected function formatDateForDisplay($dt){
        return $dt ? $dt->format("Y-m-d") : "";
    }
}
