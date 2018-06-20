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
    const WIERSZ_POCZATKOWY_TABELI = 3;

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

        $nazwyUzytkownikow = array_keys($roznice);
        $wszystkieRoznice = array();

        $index = 0;

        foreach ($roznice as $roznica) {
            $rozniceKlucze = $this->formatujRoznice($roznica);
            if (count($rozniceKlucze) > 0) {
                $wszystkieRoznice[$nazwyUzytkownikow[$index]] = $rozniceKlucze;
                $index++;
            }
        }

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

        $wierszIndex = $this::WIERSZ_POCZATKOWY_TABELI;
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

        foreach ($diffes as $diff) {
            $uzytkownik = array_keys($diffes)[$uzytkownikIndex];
            $sheet->setCellValue('A'. $wierszIndex, $uzytkownik);

            if (isset($diff['status'])) {
                $sheet->setCellValue('B'. $wierszIndex++, $diff['status']);
                $wierszIndex++;
            } else {
                for ($item = 0; $item < count($diff); $item++) {
                    $wierszIndexDodatkowy = $wierszIndex++;
                    $sheet->setCellValue('B'. $wierszIndexDodatkowy, $diff[$item]['status']);
                    $sheet->setCellValue('C'. $wierszIndexDodatkowy, $diff[$item]['stare']);
                    $sheet->setCellValue('D'. $wierszIndexDodatkowy, $diff[$item]['nowe']);
                    $wierszIndex++;
                }
            }

            $sheet->getStyle('A' . $wierszIndex .':D' . $wierszIndex)
                ->getBorders()
                ->getBottom()
                ->setBorderStyle(ExcelBorder::BORDER_THICK);
            $uzytkownikIndex++;
            $wierszIndex++;
        }

        $dataTeraz = new \DateTime();

        $katalog = $this->getContainer()->getParameter('porownania_json')['katalog_raportow'];
        $nazwaPliku = 'raport_' . $dataTeraz->format('Y-m-d') .'.xlsx';
        $writer->save($katalog . $nazwaPliku);

        return $nazwaPliku;
    }

    /**
     * Formatuje różnice i zapisuje je w tablicy a określonym komunikatym
     * czytelnym dla człowieka.
     *
     * @param array $diff
     *
     * @return array
     */
    private function formatujRoznice($diff)
    {
        $komunikaty = $this->getKomunikaty();

        /**
         * Lista elementów z tablicy które nie są wyświetlane
         * lub nie są wyświetlane automatycznie bo podlegają
         * dodatkowemu parsowaniu (np. memberOf musi pociąć string
         * z uprawnieniami i porównać go).
         */
        $zbedneDoRaportu = array(
            'distinguishedname',
            'memberOf',
            'manager'
        );

        $outputDiff = array();

        foreach ($diff as $zmienne) {
            $klucze = array_keys($zmienne);
            $uprawnieniaOdebrane = array();
            $uprawnieniaNadane = array();

            if (isset($zmienne['manager'])) {
                if (count($zmienne['manager']) > 1) {
                    $staryManager = explode(',', $zmienne['manager']['old']);
                    $nowyManager = explode(',', $zmienne['manager']['new']);
                    $outputDiff[] = array(
                        'status' => $komunikaty['manager'],
                        'stare' => str_replace('CN=', '', $staryManager[0]),
                        'nowe' => str_replace('CN=', '', $nowyManager[0]),
                    );
                }
            }

            for ($index=0; $index<count($klucze); $index++) {
                if (!in_array($klucze[$index], $zbedneDoRaportu)) {
                    if (isset($zmienne['isDisabled'])) {
                        if ($klucze[$index] == 'isDisabled') {
                            if ($zmienne['isDisabled']['new'] === 1) {
                                $outputDiff['status'] = $komunikaty['ACCOUNT_ENABLED'];
                            } elseif ($zmienne['isDisabled']['new'] === 0) {
                                $outputDiff['status'] = $komunikaty['ACCOUNT_DISABLED'];
                            }
                        }
                        continue;
                    }
                    if (isset($zmienne[$klucze[$index]]['old'])) {
                        $tempArr = array();
                        $tempArr['status'] = $komunikaty[$klucze[$index]];
                        $tempArr['stare'] = $zmienne[$klucze[$index]]['old'];
                        $tempArr['nowe'] = $zmienne[$klucze[$index]]['new'];
                        $outputDiff[] = $tempArr;
                    }
                }
            }
        }

        if (empty($outputDiff)) {
            if (count($diff) === 1) {
                if (isset($diff['old'])) {
                    $outputDiff['status'] = $komunikaty['ACCOUNT_REMOVED'];
                } elseif (isset($diff['new'])) {
                    $outputDiff['status'] = $komunikaty['ACCOUNT_CREATED'];
                }
            }
        }

        return $outputDiff;
    }

    /**
     * Zwraca komunikaty które będą wpisane w statusie
     * w dokumencie Excel
     *
     * @return array
     */
    private function getKomunikaty()
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

        return $komunikaty;
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
     * Porównuje 2 jsony i zwraca różnice pomiędzy nimi.
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
