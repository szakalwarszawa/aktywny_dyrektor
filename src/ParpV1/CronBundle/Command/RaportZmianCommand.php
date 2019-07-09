<?php

namespace ParpV1\CronBundle\Command;

use Symfony\Component\Finder\Finder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use ParpV1\MainBundle\Services\ParpMailerService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use PhpOffice\PhpSpreadsheet\Style\Fill as ExcelFill;
use Symfony\Component\Console\Output\OutputInterface;
use PhpOffice\PhpSpreadsheet\Style\Border as ExcelBorder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Klasa RaportZmianCommand
 * Zmiana metody pobierania sekcji Redmine #63578
 */
class RaportZmianCommand extends ContainerAwareCommand
{
    /**
     * @var array
     *
     * Lista usuniętych użytkowników która zostanie zapisana do json.
     * Używane w usuwaniu użytkowników zombie.
     */
    private $listaUsunietychUzytkownikow = array();

    /**
     * @var string
     *
     * Aktualnie sprawdzany użytkownik.
     */
    private $aktualnyUzytkownik;

    /**
     * Ustawienia wywołania komendy.
     *
     * @return void
     */
    protected function configure()
    {
        $this
                ->setName('parp:raportzmian')
                ->setDescription('Generuje plik excela i wysyła mail z raportem zmian w AKD względem AD')
                ->addArgument('plik1', InputArgument::OPTIONAL, 'Plik 1 do porownania.')
                ->addArgument('plik2', InputArgument::OPTIONAL, 'Plik 2 do porownania.')
                ->addOption('start', null, InputOption::VALUE_OPTIONAL, 'Od jakiej daty.');
    }

    /**
    * @param InputInterface  $input  An InputInterface instance
    * @param OutputInterface $output An OutputInterface instance
    */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(array(
            'Generuję raport...',
            '============',
            '',
        ));

        if ($input->getArgument('plik1') && $input->getArgument('plik2')) {
            $this->generujRaport($input->getArgument('plik1'), $input->getArgument('plik2'));
        } elseif (null === $input->getOption('start')) {
            $this->generujRaport();
        } else {
            $this->generujSerieRaportow($input->getOption('start'));
        }
    }

    /**
     * W przypadku podania opcji --start *nazwa pliku* generuje X raportów.
     * Generuje porównania dzień po dniu.
     *
     * @param string $nazwaPlikuStartowego
     *
     * @return void
     */
    private function generujSerieRaportow($nazwaPlikuStartowego)
    {
        $porownaniaJson = $this->getContainer()->getParameter('porownania_json');
        $katalogPlikowJson = $porownaniaJson['katalog_plikow_json'];
        $listaPlikow = $this->getListaPlikow($katalogPlikowJson, true);

        $indexStart = array_search($nazwaPlikuStartowego, $listaPlikow);

        $nazwyPlikowDoRaportu = array_slice($listaPlikow, $indexStart);

        $jsonPliki = array();
        $tempArray = array();

        foreach ($nazwyPlikowDoRaportu as $nazwa) {
            if (count($tempArray) < 2) {
                $tempArray[] = $nazwa;
            }
            if (count($tempArray) == 2) {
                $jsonPliki[] = $tempArray;
                $tempArray = array();
                $tempArray[] = $nazwa;
            }
        }

        foreach ($jsonPliki as $plik) {
            $this->generujRaport($plik[0], $plik[1]);
        }
    }

    /**
     * Generuje raport, tworzy excela i wysyła do określonej osoby.
     *
     * @param string|null $plik1
     * @param string|null $plik2
     *
     * @throws FileNotFoundException jeżeli plik nie istnieje
     *
     * @return void
     */
    private function generujRaport($plik1 = null, $plik2 = null)
    {
        $porownaniaJson = $this->getContainer()->getParameter('porownania_json');
        $katalogPlikowJson = $porownaniaJson['katalog_plikow_json'];
        $file1 = $katalogPlikowJson . $plik1;
        $file2 = $katalogPlikowJson . $plik2;


        if (null !== $plik1) {
            if (!file_exists($file1) || !file_exists($file2)) {
                throw new FileNotFoundException("Plik nie istnieje.");
            }
            $json1 = file_get_contents($file1);
            $json2 = file_get_contents($file2);

            $file1 = $plik1;
            $file2 = $plik2;
        } else {
            $nazwyPlikow = $this->getListaPlikow($katalogPlikowJson, false);
            $json1 = file_get_contents($katalogPlikowJson . $nazwyPlikow[0]);
            $json2 = file_get_contents($katalogPlikowJson . $nazwyPlikow[1]);

            $file1 = $nazwyPlikow[0];
            $file2 = $nazwyPlikow[1];
        }

        $tablicaPorownaj1 = json_decode($json1, true);
        $tablicaPorownaj2 = json_decode($json2, true);

        $roznice = $this->porownajTablice($tablicaPorownaj1, $tablicaPorownaj2);

        $wszystkieRoznice = array();

        foreach ($roznice as $klucz => $roznica) {
            $this->aktualnyUzytkownik = $klucz;
            $rozniceKlucze = $this->formatujRoznice($roznica);
            $wszystkieRoznice[$klucz] = $rozniceKlucze;
        }

        $wszystkieRoznice = array_filter($wszystkieRoznice);
        $zakresCzasowy = $this->ekstrahujDateZNazwyPliku(array($file1, $file2));

        $plik = $this->zapiszDoExcela($wszystkieRoznice, $zakresCzasowy);
        $plikZeSciezka = $porownaniaJson['katalog_raportow'] . $plik;
        $this->wyslijMail($plikZeSciezka, $porownaniaJson['mail_do_raportu']);
        $this->zapiszUzytkownikowZombieJson();
    }

     /**
     * Zapisuje do pliku `.json` nazwy użytkowników zombie.
     *
     * @return void
     */
    private function zapiszUzytkownikowZombieJson()
    {
        $katalog = $this
                    ->getContainer()
                    ->getParameter('porownania_json')['katalog_raportow'];
        $dataDzisiaj = new \DateTime();
        $nazwaPliku = 'ZombieALL_' . $dataDzisiaj->format('Y-m-d') . '.json';

        $fileSystem = new FileSystem();
        $data = json_encode($this->listaUsunietychUzytkownikow);
        $fileSystem->dumpFile($katalog . \DIRECTORY_SEPARATOR . $nazwaPliku, $data);
    }

    /**
     * Nazwa pliku to `users-ad_2018-06-14-060501.json`
     * Zwraca samą datę z pliku.
     *
     * @param array $nazwyPlikow
     *
     * @throws \Exception jeżeli Plik nie zawiera daty w nazwie w formacie YYYY/MM/DD
     *
     * @return array
     *
     */
    private function ekstrahujDateZNazwyPliku(array $nazwyPlikow)
    {
        $nazwyDoZwrotu = array();
        foreach ($nazwyPlikow as $nazwa) {
            if (preg_match("/\d{4}-\d{2}-\d{2}/", $nazwa, $dopasowane)) {
                $nazwyDoZwrotu[] = $dopasowane[0];
            } else {
                throw new \Exception("Plik nie zawiera daty w nazwie w formacie YYYY/MM/DD");
            }
        }

        return $nazwyDoZwrotu;
    }

    /**
     * Wysyła mail z załącznikiem.
     *
     * @param string $plik
     * @param string $odbiorca
     *
     * @return void
     */
    private function wyslijMail($plik, $odbiorca)
    {
        $mailerHost = $this->getContainer()->getParameter('mailer_host');
        $mailerPort = $this->getContainer()->getParameter('mailer_port');

        $transport = (new \Swift_SmtpTransport($mailerHost, $mailerPort));
        $message = \Swift_Message::newInstance();
        $message->setTo(array($odbiorca));
        $message->setSubject("Raport zmian AD - AKD");
        $message->setBody("W załączniku raport.");
        $message->setFrom("akd@akd", "AktywnyDyrektor");

        $message->attach(\Swift_Attachment::fromPath($plik));

        $mailer = \Swift_Mailer::newInstance($transport);
        $mailer->send($message);
    }

    /**
     * Zapisuje różnice do pliku excel
     *
     * @param array $diffes
     *
     * @return string
     */
    private function zapiszDoExcela(array $diffes, $zakresCzasowy)
    {
        $spreadsheet = new Spreadsheet();
        $writer = new Xlsx($spreadsheet);
        $sheet = $spreadsheet->getActiveSheet();

        $wierszIndex = 3;

        $sheet->setCellValue('A1', 'Nazwa użytkownika');
        $sheet->setCellValue('B1', 'Nazwa użytkownika');
        $sheet->setCellValue('C1', 'Zmiana');
        $sheet->setCellValue('D1', sprintf('Stara wartość (%s)', $zakresCzasowy[0]));
        $sheet->setCellValue('E1', sprintf('Nowa wartość (%s)', $zakresCzasowy[1]));

        $sheet->getStyle('A1:E1')->getFill()
            ->setFillType(ExcelFill::FILL_SOLID)
            ->getStartColor()->setARGB('D1D1D1D1');

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);

        $ldapService = $this->getContainer()->get('ldap_service');

        foreach ($diffes as $key => $diff) {
            $sheet->setCellValue('A'. $wierszIndex, $key);
            $poczatekTabeliWiersz = $wierszIndex;
            if (isset($ldapService->getUserFromAD($key)[0])) {
                $imieNazwisko = $ldapService->getUserFromAD($key)[0]['name'];
                $sheet->setCellValue('B'. $wierszIndex, $imieNazwisko);
            }

            foreach ($diff as $pojedynczaZmiana) {
                if (empty($pojedynczaZmiana['stare']) && empty($pojedynczaZmiana['nowe'])) {
                    $sheet->setCellValue('E'. $wierszIndex, $pojedynczaZmiana['status']);
                    $wierszIndex++;
                    continue;
                }
                $sheet->setCellValue('C'. $wierszIndex, $pojedynczaZmiana['status']);
                $sheet->setCellValue('D'. $wierszIndex, $this->tlumaczWyrazy($pojedynczaZmiana['stare']));
                $sheet->setCellValue('E'. $wierszIndex, $this->tlumaczWyrazy($pojedynczaZmiana['nowe']));
                $wierszIndex++;
            }

            $sheet->getStyle('A' . $poczatekTabeliWiersz .':E' . $wierszIndex)
                ->getBorders()
                ->getBottom()
                ->setBorderStyle(ExcelBorder::BORDER_THICK);

            $wierszIndex++;
        }

        $dataTeraz = new \DateTime();

        $katalog = $this->getContainer()->getParameter('porownania_json')['katalog_raportow'];
        $nazwaPliku = 'raport_' . $zakresCzasowy[0] . '__' .$zakresCzasowy[1] .'.xlsx';
        $writer->save($katalog . $nazwaPliku);

        return $nazwaPliku;
    }

    /**
     * Zamienia nic nieznaczące wyrazy na zrozumiałe dla człowieka.
     *
     * @param string $wartosc
     *
     * @return string
     */
    private function tlumaczWyrazy($wartosc)
    {
        $wartosc = str_replace("INTERDOMAIN_TRUST_ACCOUNT", "", $wartosc);
        $wartosc = str_replace("PASSWD_CANT_CHANGE", "Nie może zmienić hasła", $wartosc);
        $wartosc = str_replace("ACCOUNTDISABLE", "Konto wyłączone", $wartosc);

        return $wartosc;
    }

    /**
     * Formatuje roznice i nadaje im odpowiedni status.
     *
     * @param array $diff
     *
     * @return array
     */
    private function formatujRoznice(array $diff)
    {
        $outputDiff = array();
        if ((isset($diff['old']))) {
            $outputDiff = array(array(
                'status' => $this->formatujStatus($diff),
                'stare' => '',
                'nowe' => '',
            ));
            $this->listaUsunietychUzytkownikow[] = $this->aktualnyUzytkownik;
        } elseif (isset($diff['upd']['isDisabled'])) {
            // nie zwracamy reszty zmian w koncie jeżeli zostało dopiero włączone/wyłączone
            $status = $diff['upd']['isDisabled']['new'];
            $komunikat = $this->getKomunikat(($status === 1? 'ACCOUNT_DISABLED' : 'ACCOUNT_ENABLED'));
            $outputDiff = array(array(
                'status' => $komunikat,
                'stare' => '',
                'nowe' => '',
            ));
        } elseif (isset($diff['upd'])) {
            $aktualizacje = $this->formatujZaktualizowaneKlucze($diff['upd']);
            $outputDiff = $aktualizacje;
        } elseif (isset($diff['new'])) {
            $zwrotTablica = array(
                array(
                    'status' => $this->getKomunikat('N_ACCOUNT_CREATED'),
                    'nowe' => ' ',
                    'stare' => ' ',
                ),
                array(
                    'status' => $this->getKomunikat('N_DIVISION'),
                    'nowe' => $diff['new']['info'],
                    'stare' => '',
                ),
                array(
                    'status' => $this->getKomunikat('N_DEPARTMENT'),
                    'nowe' => $diff['new']['department'],
                    'stare' => '',
                ),
                array(
                    'status' => $this->getKomunikat('N_TITLE'),
                    'nowe' => $diff['new']['title'],
                    'stare' => '',
                )
            );

            return $zwrotTablica;
        }

        return $outputDiff;
    }

    /**
     * Zwraca tablicę zaktualizowanych elementów z odpowiednim statusem.
     * Filtruje klucze i zwraca tylko te które są potrzebne do raportu.
     * Sprawdza też czy zaszła zmiana sekcji (warunkiem jest zmiana
     * klucza `info` oraz `division` jednocześnie - wypluwamy tylko skrót).
     *
     * @param array $diff
     *
     * @return array
     */
    private function formatujZaktualizowaneKlucze(array $diff)
    {
        $aktualizacje = array();

        $zbedneDoRaportu = array(
            'distinguishedname',
            'memberOf',
            'manager',
            'accountExpires',
            'accountexpires',
            'info',
            'division',
            'mailnickname'
        );

        if (!empty($diff['info']) && !empty($diff['division'])) {
            $indexDivision = array_search('info', $zbedneDoRaportu);
            unset($zbedneDoRaportu[$indexDivision]);
        }

        foreach ($diff as $klucz => $element) {
            if (!in_array($klucz, $zbedneDoRaportu)) {
                $tymczasowaTablica = array();
                $tymczasowaTablica['status'] = $this->getKomunikat($klucz);
                $tymczasowaTablica['stare'] = $element['old'];
                $tymczasowaTablica['nowe'] = $element['new'];

                if (('initials' === $klucz && empty($element['new'])) ||
                    ('initials' === $klucz && !empty($element['old']))) {
                    continue;
                }
                $aktualizacje[] = $tymczasowaTablica;
            }
        }

        return $aktualizacje;
    }

    /**
     * Formatuje status jeżeli zaszła zmiana typu usunięcie konta
     *
     * @param array $diff
     *
     * @return string
     */
    private function formatujStatus(array $diff)
    {
        if (isset($diff['old'])) {
            return $this->getKomunikat('ACCOUNT_REMOVED');
        }
    }

    /**
     * Zwraca komunikat dodany do statusu.
     *
     * @param string $klucz
     *
     * @return string
     */
    private function getKomunikat($klucz)
    {
        /**
         * Kluczami tej tabeli są (głównie) nazwy takie jak w raporcie .json
         * Są tu też zdefiniowane customowe komunikaty
         * (zapisane wielką literą SNAKE_CASE)
         */
        $komunikaty = array(
            'title'                 => 'Zmieniono nazwę stanowiska',
            'name'                  => 'Zmieniono imię i nazwisko',
            'accountExpires'        => 'Zmieniono date wygaśnięcia konta',
            'department'            => 'Zmieniono departament',
            'info'                  => 'ZMIENIONO SEKCJĘ',
            'description'           => 'ZMIENIONO OPIS',
            'isDisabled'            => 'ZMIANA STATUSU URUCHOMIENIA KONTA WŁ/WYŁ',
            'initials'              => 'Zmieniono inicjały',
            'division'              => 'Zmieniono sekcję',
            'accountexpires'        => 'ZMIENIONO KONTO EXPIRES??',
            'N_ACCOUNT_CREATED'       => 'Utworzono konto',
            'ACCOUNT_REMOVED'       => 'Usunięto konto',
            'memberOf'              => 'Zmieniono uprawnienia',
            'manager'               => 'Zmieniono managera',
            'ACCOUNT_ENABLED'       => 'Włączono konto',
            'ACCOUNT_DISABLED'      => 'Wyłączono konto',
            'useraccountcontrol'    => 'Zmieniono parametry konta',
            'cn'                    => 'Zmieniono nazwę',
            'N_DEPARTMENT'          => 'Nazwa departamentu/biura',
            'N_DIVISION'            => 'Sekcja',
            'N_TITLE'               => 'Stanowisko'
        );

        return $komunikaty[$klucz];
    }

  /**
     * Zwraca nazwy dwóch najnowszych plików .json w katalogu
     * Jeżeli $pokazWszystkie jest true to zwraca listę wszystkich plików z katalogu.
     *
     * @param string $katalogPlikowJson
     * @param bool $pokazWszystkie
     *
     * @throws FileNotFoundException jeżeli w folderze nie ma dwóch plików do porównania
     *
     * @return array
     *
     */
    private function getListaPlikow($katalogPlikowJson, $pokazWszystkie)
    {
        $finder = new Finder();

        $pliki = $finder
            ->files()
            ->in($katalogPlikowJson)
            ->name('*.json')
            ->sortByName();

        $nazwyPlikow = array();

        foreach ($pliki as $plik) {
            $fileName = $plik->getFileName();
            if (!(strpos($fileName, 'ALL') > 0)) {
                 $nazwyPlikow[] = $fileName;
            }
        }
        if (false === $pokazWszystkie) {
            if (count($nazwyPlikow) > 2) {
                $nazwyPlikow = array_splice($nazwyPlikow, -2);
            } elseif (count($nazwyPlikow) < 2) {
                throw new FileNotFoundException("Brakuje plików do porównania");
            }
        }


        return $nazwyPlikow;
    }

    /**
     * Porównuje dwie tablice (json_decode)
     *
     * @param array $arr1
     * @param array $arr2
     *
     * @return array
     */
    private function porownajTablice(array $tablica1, array $tablica2)
    {
        $zaktualizowane = array();

        foreach ($tablica1 as $key1 => $value) {
            if (isset($tablica2[$key1])) {
                $zaktualizowane[$key1] = array(
                    'upd' => $this->aktualizacjeTablicy($tablica1[$key1], $tablica2[$key1])
                );
                unset($tablica1[$key1]);
                unset($tablica2[$key1]);
            } elseif (!isset($tablica2[$key1])) {
                $zaktualizowane[$key1] = array('old' => $tablica1[$key1]);
                unset($tablica1[$key1]);
            }
        }

        foreach ($tablica2 as $key2 => $value) {
            $zaktualizowane[$key2] = array('new' => $tablica2[$key2]);
        }

        return array_filter($zaktualizowane);
    }

    /**
     * Jeżeli klucz1 z tablicy1 istnieje w tablicy2
     * to wyciąga zmiany jakie wystąpiły w tych kluczach.
     * Zapisuje je jako old/new.
     *
     * @param array $element1
     * @param array $element2
     *
     * @return array
     */
    private function aktualizacjeTablicy($element1, $element2)
    {
        $zmiany = array();
        foreach ($element1 as $key => $value) {
            if ($element1[$key] == $element2[$key]) {
                unset($element1[$key]);
                unset($element2[$key]);
            } elseif ($element1[$key] != $element2[$key]) {
                $zmiany[$key] = array(
                            'old' => $element1[$key],
                            'new' => $element2[$key],
                );
            }
        }
        return $zmiany;
    }
}
