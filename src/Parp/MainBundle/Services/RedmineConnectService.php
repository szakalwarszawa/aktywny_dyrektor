<?php

/**
 * Klasa RedmineConnectService.
 *
 * @author Robert Muchacki robert_muchacki@parp.gov.pl
 *
 * @version 1.0.0
 */

namespace Parp\MainBundle\Services;

use Symfony\Component\DependencyInjection\Container;

/**
 * Usługa dostępna systemowo, której zadaniem jest komunikacja z systemem Redmine.
 *
 * Class RedmineConnectService
 */
class RedmineConnectService
{

    /**
     * Protokół (http|https) wg którego system ma się łączyć z serwerem Redmine.
     *
     * @var string
     */
    private $redmine_serwer;

    /**
     * URL serwera Redmine.
     *
     * @var string
     */
    private $redmine_uzytkownik;

    /**
     * Użytkownik Redmine z uprawnieniami do modyfikacji zgłoszeń.
     *
     * @var string
     */
    private $redmine_haslo;

    /**
     * Hasło użytkownika Redmine.
     *
     * @var string
     */
    private $redmine_protokol;

    /**
     * Numer ID projektu, do którego niniejsza usługa ma dokonywać zapis zgłoszeń.
     *
     * @var int
     */
    private $redmine_projekt;
    
    /*
     * Kontener
     * 
     */
    private $container;

    /**
     * Konstruktor klasy.
     *
     * @param string $redmine_protokol
     * @param string $redmine_serwer
     * @param string $redmine_uzytkownik
     * @param string $redmine_haslo
     * @param int    $redmine_projekt
     *
     * @todo Autoryzacja przy pomocy klucza API, zamiast loginu i hasła
     */
    public function __construct($redmine_protokol, $redmine_serwer, $redmine_uzytkownik, $redmine_haslo, $redmine_projekt, Container $container)
    {
        $this->redmine_protokol = $redmine_protokol;
        $this->redmine_serwer = $redmine_serwer;
        $this->redmine_uzytkownik = $redmine_uzytkownik;
        $this->redmine_haslo = $redmine_haslo;
        $this->redmine_projekt = $redmine_projekt;
        $this->container =  $container;
    }

    /**
     * Zwraca zgłoszenia przypisane do danego beneficjenta.
     *
     * @param int $id_beneficjenta
     *
     * @return mixed
     *
     * @throws \Exception W przypadku błędu spowodowanego przez cURL
     */
    public function getZgloszeniaBeneficjenta($id_beneficjenta)
    {
        $id_srodowiska = $this->container->getParameter('id_srodowiska');
        $adres = $this->redmine_protokol . '://' . $this->redmine_serwer . '/issues.json?cf_2=' . $id_beneficjenta."_".$id_srodowiska."&status_id=*";
//die($adres);
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $adres);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERPWD, $this->redmine_uzytkownik . ':' . $this->redmine_haslo);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        $odpowiedz = curl_exec($curl);

        if ($errno = curl_errno($curl)) {
            $error_message = curl_strerror($errno);
            throw new \Exception('Błąd cURL(' . $errno . '): ' . $error_message . ', adres: ' . $adres);
        }

        curl_close($curl);

        return $odpowiedz;
    }

    /**
     * Tworzy nowe (jeżeli $zgloszenie_id jest puste) lub uaktualnia stare (jeżeli $zgłoszenie_id nie jest puste) zgłoszenie
     * w systemie Redmine.
     *
     * @param int    $id_beneficjenta     ID beneficjenta, na podstawie którego będzie można odnaleźć go w bazie aby uzupełnić pozostałe dane takie jak imię i nazwisko
     * @param string $temat               Temat dokonywanego zgłoszenia
     * @param string $opis                Dokładny opis zgłoszenia
     * @param string $komunikat_systemowy Ewentualny komunikat systemowy (nie może być widoczny dla Beneficjenta)
     * @param int    $zgloszenie_id       Ewentualne ID zgłoszenia, w przypadku gdyby miało dojść do edycji
     *
     * @link http://www.redmine.org/issues/8951#note-9
     *
     * @return mixed Przy pomyślnym utworzeniu zgłoszenia, zwracany jest json zawierający dane nowoutworzonego zgłoszenia
     *
     * @throws \Exception W przypadku błędu spowodwanego przez cURL
     */
    public function putZgloszenieBeneficjenta(
        $id_beneficjenta,
        $temat,
        $opis,
        $kategoria,
        $uri = null,
        $czy_prywatna = true,
        $komunikat_systemowy = null,
        $zgloszenie_id = null,
        $podmiot = null,
        $email = null,
        $telefon = null,
        $imie_nazwisko = null
    ) {
        if ($zgloszenie_id) {
            $zgloszenie_id = '/' . $zgloszenie_id;
        }

        $adres = $this->redmine_protokol . '://' . $this->redmine_serwer . '/issues' . $zgloszenie_id . '.json';
        $id_srodowiska = $this->container->getParameter('id_srodowiska');

        // Składamy tablicę z danymi
        $data = array();
        $data['issue'] = array(
            'project_id' => $this->redmine_projekt,
            'subject' => substr($temat, 0, 254),
            'priority_id' => '4',
            'description' => $opis,
            'category_id' => $kategoria, # 21 - Zgłoszone przez system; 22 - Zgłoszone przez użytkownika
            'custom_fields' => array(
                array('id' => 2, 'value' => $id_beneficjenta."_".$id_srodowiska), # ID Beneficjenta
                array('id' => 3, 'value' => $uri), # ID wniosku
                array('id' => 14, 'value' => $komunikat_systemowy), # Komunikat systemowy (np. Błąd 500, który nie może być pokazany beneficjentowi ze względów bezpieczeństwa)
//                array("id" => 9, "value" => "Zgłoszenie LSI" ),   # Kanał komunikacji
                array('id' => 5 , 'value' => $podmiot), # instytucja 5
                array('id' => 7 , 'value' => $email),
                array('id' => 8 , 'value' => $telefon),
                array('id' => 4 , 'value' => $imie_nazwisko),
            ),
            'due_date' => date('Y-m-d', strtotime('+7 day')),
        );

        // Kodujemy ją do jsona
        $data_json = json_encode($data);

        /*
         * Niestety, nie jest proste wstawianie danych przy pomocy cURL. Poniższe wydaje się zupełnie niezgodne z tym, co
         * opisują w oficjalnej dokumentacji.
         *
         * @see http://www.redmine.org/issues/8951#note-9
         */

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_json);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Length: ' . strlen($data_json)));
        curl_setopt($curl, CURLOPT_USERPWD, $this->redmine_uzytkownik . ':' . $this->redmine_haslo);

        curl_setopt($curl, CURLOPT_URL, $adres);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        $odpowiedz = curl_exec($curl);
        
        if ($errno = curl_errno($curl)) {
            $error_message = curl_strerror($errno);
            throw new \Exception('Błąd cURL(' . $errno . '): ' . $error_message . ', adres: ' . $this->redmine_protokol . '://' . $this->redmine_uzytkownik . ':' . $this->redmine_haslo . '@' . $this->redmine_serwer . '/issues.json?cf_2=' . $id_beneficjenta);
        }


        curl_close($curl);
        return $odpowiedz;
    }

    public function dodajNotatke($zgloszenie_id, $notatka = null, $czy_prywatna = true)
    {
        
        if (!$notatka) {
            $notatka = $this->przygotujTresc();
        }
        
        $adres = $this->redmine_protokol . '://' . $this->redmine_serwer . '/issues/' . $zgloszenie_id . '.json';

        // Składamy tablicę z danymi
        $data = array();
        $data['issue'] = array(
            'notes' => $notatka,
            'private_notes' => $czy_prywatna,
        );

        // Kodujemy ją do jsona
        $data_json = json_encode($data);

        /*
         * Niestety, nie jest proste wstawianie danych przy pomocy cURL. Poniższe wydaje się zupełnie niezgodne z tym, co
         * opisują w oficjalnej dokumentacji.
         *
         * @see http://www.redmine.org/issues/8951#note-9
         */

        $curl = curl_init();
        //curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_json);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Length: ' . strlen($data_json)));
        curl_setopt($curl, CURLOPT_USERPWD, $this->redmine_uzytkownik . ':' . $this->redmine_haslo);

        curl_setopt($curl, CURLOPT_URL, $adres);
        curl_setopt($curl, CURLOPT_MAXREDIRS, 3);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_VERBOSE, 0);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        if ($errno = curl_errno($curl)) {
            $error_message = curl_strerror($errno);
            throw new \Exception('Błąd cURL(' . $errno . '): ' . $error_message . ', adres: ' . $this->redmine_protokol . '://' . $this->redmine_uzytkownik . ':' . $this->redmine_haslo . '@' . $this->redmine_serwer . '/issues.json?cf_2=' . $id_beneficjenta);
        }

        $odpowiedz = curl_exec($curl);

        curl_close($curl);
        return $odpowiedz;
    }

    private function przygotujTresc()
    {

        $clientIP = $this->container->get('request')->getClientIp();
              
        $path = $this->container->get('request')->get('_route');
        $request = $this->container->get('request')->headers->all();
        $browser = $this->container->get('request')->headers->get('User-Agent');

        $tresc = '<b>IP</b> : ' . $clientIP . "<br/>";
        $tresc .= '<b>path</b> : ' . $path . "<br/>";
        $tresc .= '<b>request</b> : ' . print_r($request, true) . "<br/>";
        $tresc .= '<b>browser</b> : ' . $browser . "<br/>";

        return $tresc;
    }
}
