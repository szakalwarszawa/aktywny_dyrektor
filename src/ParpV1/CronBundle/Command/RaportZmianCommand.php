<?php

namespace ParpV1\CronBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use ParpV1\MainBundle\Services\ParpMailerService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border as ExcelBorder;
use PhpOffice\PhpSpreadsheet\Style\Fill as ExcelFill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\IWriter;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;

class RaportZmianCommand extends ContainerAwareCommand
{
    const POKAZ_ZMIANY_UPRAWNIEN = false;

    protected function configure()
    {
        $this
                ->setName('parp:raportzmian')
                ->setDescription('Generuje plik excela i wysyła mail z raportem zmian w AKD względem AD')
                ->addArgument('plik1', InputArgument::OPTIONAL, 'Plik 1 do porownania.')
                ->addArgument('plik2', InputArgument::OPTIONAL, 'Plik 2 do porownania.');
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
        } else {
            $this->generujRaport();
        }
    }

    /**
     * Generuje raport, tworzy excela i wysyła do określonej osoby.
     *
     * @param string|null $plik1
     * @param string|null $plik2
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
        } else {
            $nazwyPlikow = $this->getListaPlikow($katalogPlikowJson);
            $json1 = file_get_contents($katalogPlikowJson . $nazwyPlikow[0]);
            $json2 = file_get_contents($katalogPlikowJson . $nazwyPlikow[1]);
        }

        $tablicaPorownaj1 = json_decode($json1, true);
        $tablicaPorownaj2 = json_decode($json2, true);

        $roznice = $this->diff($tablicaPorownaj1, $tablicaPorownaj2);

        $wszystkieRoznice = array();

        foreach($roznice as $klucz => $roznica) {
            $rozniceKlucze = $this->formatujRoznice($roznica);
            $wszystkieRoznice[$klucz] = $rozniceKlucze;
        }

        $wszystkieRoznice = array_filter($wszystkieRoznice);

        $plik = $this->zapiszDoExcela($wszystkieRoznice);
        $plikZeSciezka = $porownaniaJson['katalog_raportow'] . $plik;
        $this->wyslijMail($plikZeSciezka, $porownaniaJson['mail_do_raportu']);
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
        $transport = \Swift_MailTransport::newInstance();
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
    private function zapiszDoExcela($diffes)
    {
        $spreadsheet = new Spreadsheet();
        $writer = new Xlsx($spreadsheet);
        $sheet = $spreadsheet->getActiveSheet();

        $wierszIndex = 3;
        $uzytkownikIndex = 0;

        $sheet->setCellValue('A1', 'Nazwa użytkownika');
        $sheet->setCellValue('B1', 'Zmiana');
        $sheet->setCellValue('C1', 'Stara wartość');
        $sheet->setCellValue('D1', 'Nowa wartość');

        $sheet->getStyle('A1:D1')->getFill()
            ->setFillType(ExcelFill::FILL_SOLID)
            ->getStartColor()->setARGB('D1D1D1D1');

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);


        foreach ($diffes as $key => $diff) {
            $sheet->setCellValue('A'. $wierszIndex, $key);
            $poczatekTabeliWiersz = $wierszIndex;

            foreach ($diff as $pojedynczaZmiana) {
                $sheet->setCellValue('B'. $wierszIndex, $pojedynczaZmiana['status']);
                $sheet->setCellValue('C'. $wierszIndex, $pojedynczaZmiana['stare']);
                $sheet->setCellValue('D'. $wierszIndex, $pojedynczaZmiana['nowe']);
                $wierszIndex++;
            }

            $sheet->getStyle('A' . $poczatekTabeliWiersz .':D' . $wierszIndex)
                ->getBorders()
                ->getBottom()
                ->setBorderStyle(ExcelBorder::BORDER_THICK);

            $wierszIndex++;
        }

        $dataTeraz = new \DateTime();

        $katalog = $this->getContainer()->getParameter('porownania_json')['katalog_raportow'];
        $nazwaPliku = 'raport_' . $dataTeraz->format('Y-m-d') .'.xlsx';
        $writer->save($katalog . $nazwaPliku);

        return $nazwaPliku;
    }

    /**
     * Formatuje roznice i nadaje im odpowiedni status.
     *
     * @param array $diff
     *
     * @return array
     */
    private function formatujRoznice($diff)
    {
        $outputDiff = array();

        if ((isset($diff['old']) || isset($diff['new']))) {
            $outputDiff = array(array(
                'status' => $this->formatujStatus($diff),
                'stare' => '',
                'nowe' => '',
            ));
        } elseif(isset($diff['upd']['isDisabled'])){
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
            if (count($aktualizacje) > 0) {
                $outputDiff = $aktualizacje;
            }
        }
        return $outputDiff;
    }

    /**
     * Zwraca tablicę zaktualizowanych elementów z odpowiednim statusem.
     * Filtruje klucze i zwraca tylko te które są potrzebne do raportu.
     *
     * @param array $diff
     *
     * @return array
     */
    private function formatujZaktualizowaneKlucze($diff)
    {
        $aktualizacje = array();

        $zbedneDoRaportu = array(
            'distinguishedname',
            'memberOf',
            'manager',
        );

        foreach ($diff as $klucz => $element) {
            if(!in_array($klucz, $zbedneDoRaportu)) {
                $tymczasowaTablica = array();
                $tymczasowaTablica['status'] = $this->getKomunikat($klucz);
                $tymczasowaTablica['stare'] = $element['old'];
                $tymczasowaTablica['nowe'] = $element['new'];

                $aktualizacje[] = $tymczasowaTablica;
            }
        }

        return $aktualizacje;
    }

    /**
     * Formatuje status jeżeli zaszła zmiana typu dodanie lub
     * usunięcie konta
     *
     * @param array $diff
     *
     * @return string
     */
    private function formatujStatus($diff)
    {
        if (isset($diff['old'])) {
            return $this->getKomunikat('ACCOUNT_REMOVED');
        } elseif (isset($diff['new'])) {
            return $this->getKomunikat('ACCOUNT_CREATED');
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
            'title'                 => 'ZMIANA STATUSU KONTA',
            'name'                  => 'ZMIANA NAZWY STANOWISKA',
            'accountExpires'        => 'Zmieniono date wygaśnięcia konta',
            'department'            => 'ZMIENIONO DEPARTAMENT',
            'info'                  => 'ZMIENIONO SEKCJĘ',
            'description'           => 'ZMIENIONO OPIS',
            'isDisabled'            => 'ZMIANA STATUSU URUCHOMIENIA KONTA WŁ/WYŁ',
            'initials'              => 'ZMIENIONO INICJAŁY',
            'division'              => 'ZMIENIONO COŚTAM',
            'accountexpires'        => 'ZMIENIONO KONTO EXPIRES??',
            'ACCOUNT_CREATED'       => 'Utworzono konto',
            'ACCOUNT_REMOVED'       => 'Usunieto konto',
            'memberOf'              => 'Zmieniono uprawnienia',
            'manager'               => 'Zmieniono managera',
            'ACCOUNT_ENABLED'       => 'Włączono konto',
            'ACCOUNT_DISABLED'      => 'Wyłączono konto',
            'useraccountcontrol'    => 'ZMIENIONO USER COŚTAM',
            'cn'                    => 'Zmieniono nazwę',
        );

        return $komunikaty[$klucz];
    }

    /**
     * Zwraca nazwy dwóch najnowszych plików .json w katalogu
     *
     * @return array
     */
    private function getListaPlikow($katalogPlikowJson)
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
        if (count($nazwyPlikow) > 2) {
            $nazwyPlikow = array_splice($nazwyPlikow, -2);
        } elseif (count($nazwyPlikow) < 2) {
            throw new FileNotFoundException("Brakuje plików do porównania");
        }

        return $nazwyPlikow;
    }

    /**
     * Porównuje dwie tablice (json_decode)
     *
     * @see https://gist.github.com/wrey75/c631f6fe9c975354aec7
     *
     * @param array $arr1
     * @param array $arr2
     *
     * @return array
     */
    private function diff($arr1, $arr2)
    {
        $diff = array();
        foreach ($arr1 as $k1 => $v1) {
            if (isset($arr2[$k1])) {
                $v2 = $arr2[$k1];
                if (is_array($v1) && is_array($v2)) {
                    $changes = self::diff($v1, $v2);
                    if (count($changes) > 0) {
                        $diff[$k1] = array('upd' => $changes);
                    }
                    unset($arr2[$k1]);
                } elseif ($v2 === $v1) {
                    unset($arr2[$k1]);
                } else {
                    $diff[$k1] = array( 'old' => $v1, 'new' => $v2 );
                    unset($arr2[$k1]);
                }
            } else {
                $diff[$k1] = array('old' => $v1);
            }
        }
        reset($arr2);
        foreach ($arr2 as $k => $v) {
            $diff[$k] = array('new' => $v);
        }

        return $diff;
    }
}
