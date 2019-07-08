<?php declare(strict_types=1);

namespace ParpV1\CronBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Helper\TableCell;
use ParpV1\MainBundle\Entity\Entry;
use ParpV1\MainBundle\Entity\Zasoby;
use ParpV1\MainBundle\Entity\UserZasoby;
use DateTime;

/**
 * Klasa komendy OdnotowanieTerminowychCommand
 * Odnotowuje w AkD i odbiera uprawnienia po terminie z AD.
 */
class OdnotowanieTerminowychCommand extends ContainerAwareCommand
{
    /**
     * @var int
     */
    private $ileZasobow = 0;

    /**
     * @var int
     */
    private $ileOdnotowanych = 0;

    /**
     * @var int
     */
    private $ileOdebranych = 0;

    /**
     * @var int
     */
    private $ilePominietych = 0;

    /**
     * @var array
     */
    private $podumowanieTabela = [];

    protected function configure()
    {
        $this
            ->setName('parp:odbierz-terminowe-z-ad')
            ->setDescription('Odnotowuje w AkD i odbiera uprawnienia po terminie z AD.')
            ->addArgument('id_zasobow', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'ID zasobów oddzielone spacją.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Tylko wyświetla dane, bez zapisywania zmian do AkD i AD.')
            ->addOption('nie-pytaj', null, InputOption::VALUE_NONE, 'Wykonuje od razu skrypt, bez wyświetlania pytań.')
            ->setHelp('Komenda służy do odnotowania odebrania uprawnień dla zasobów opartych o grupy AD. ' .
                'Jednocześnie dla uprawnień przeterminowanych odbierane są grupy w AD. ' .
                'Po komendzie należy podać ID zasobów oddzielone spacją.');
    }

    /**
     * Wywołanie komendy.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output = new SymfonyStyle($input, $output);
        $zasob = $input->getArgument('id_zasobow');
        $tylkoTest = $input->getOption('dry-run');
        $niePytaj = $input->getOption('nie-pytaj');

        if (true === $tylkoTest) {
            $output->note('Wykonanie testowe. Żadne uprawnienia nie zostaną odebrane.');
        }

        if (count($zasob) > 0) {
            $zasoby = implode(', ', $zasob);
            $czyKontynuowac = $this->getHelper('question');
            $pytanie = new ConfirmationQuestion('Odebranie zostaną uprawnienia do zasobów: '.$zasoby.'. Czynności nie da się cofnąć. Kontynuować?', false, '/^(y|t)/i');

            if (true === $niePytaj || $czyKontynuowac->ask($input, $output, $pytanie)) {
                $output->writeln('<comment>Rozpoczynam odbieranie...</comment>');
                foreach ($zasob as $zasobyDoOdebrania) {
                    $this->ileZasobow ++;
                    $this->odbierzUprawnieniaPrzeterminowane($output, (int) $zasobyDoOdebrania, $tylkoTest);
                }

                $output->writeln([
                    '',
                    '===========================',
                    '<fg=cyan;options=bold>       PODSUMOWANIE</>',
                    '===========================',
                    '',
                    '<fg=cyan>Ogólnie: </>',
                ]);

                $table = new Table($output);
                $table->setColumnWidths([15, 4]);
                $table
                    ->setRows([
                        ['Zasobow', $this->ileZasobow],
                        new TableSeparator(),
                        [new TableCell('Uprawnień: ', ['colspan' => 2])],
                        new TableSeparator(),
                        ['Pominietych', $this->ilePominietych],
                        ['Odnotowanych', $this->ileOdnotowanych],
                        ['Odebranych', $this->ileOdebranych],
                    ]);
                $table->render();

                $output->writeln('');
                $output->writeln('<fg=cyan>Szczegóły: </>');
                $tableSzczegoly = new Table($output);
                $tableSzczegoly
                    ->setHeaders(array('zasób', 'odnotowanych', 'odebranych', 'pominietych'))
                    ->setRows($this->podumowanieTabela)
                ;
                $tableSzczegoly->render();
            }
        }
    }

    /**
     * Wyszukuje uprawnenia przeterminowane, odnotowuje odebranie
     * oraz odbiera przynależność do grup w AD
     *
     * @param int $idZasobu
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function odbierzUprawnieniaPrzeterminowane(OutputInterface $output, int $idZasobu, bool $tylkoTest)
    {
        $pomijajKonta = [];
        $ileOdnotowanychZasob = $ilePominietychZasob = $ileOdebranychZasob = 0;

        // pomijaj Zarząd:
        $pomijajKonta = $this->pracownicyDepartmentu('ZA');

        $entityManager = $this
                            ->getContainer()
                            ->get('doctrine')
                            ->getManager();

        $zasob = $entityManager->getRepository('ParpMainBundle:Zasoby')->find($idZasobu);
        $output->section('Odnotowuję dla zasobu: '. $idZasobu . ' ' . $zasob->getNazwa());

        $userZasobyAktywne = $entityManager
            ->getRepository('ParpMainBundle:UserZasoby')
            ->createQueryBuilder('uz')
            ->where('uz.zasobId = ' . $idZasobu)
            ->andWhere('(uz.aktywneDo is null and uz.bezterminowo = 1) or uz.aktywneDo > :now')
            ->andWhere('uz.deletedAt is NULL')
            ->andWhere('uz.czyAktywne = true')
            ->setParameter('now', date('Y-m-d H:i:s'))
            ->getQuery()
            ->getResult();

        foreach ($userZasobyAktywne as $uzAktywne) {
            $pomijajKonta[] = $uzAktywne->getSamaccountname();
        }

        $userZasoby = $entityManager
            ->getRepository('ParpMainBundle:UserZasoby')
            ->createQueryBuilder('uz')
            ->where('uz.zasobId = '.$idZasobu)
            ->andWhere('uz.aktywneDo <= :now')
            ->andWhere('uz.deletedAt is NULL')
            ->andWhere('uz.czyAktywne = true')
            ->setParameter('now', date('Y-m-d H:i:s'))
            ->getQuery()
            ->getResult();

        if (!empty($userZasoby)) {
            foreach ($userZasoby as $userZasob) {
                $login = $userZasob->getSamaccountname();

                // pomijam wnioski dla osób zewnętrznych
                if (null !== $userZasob->getWniosek() && $userZasob->getWniosek()->getPracownikSpozaParp()) {
                    $output->writeln('<bg=blue>Pomijam ZEWNĘTRZNEGO:</> <fg=cyan>('. $userZasob->getId().')</><info> ' . $login.'</info>');
                    $this->ilePominietych++;
                    $ilePominietychZasob++;
                    continue;
                }

                $this->ileOdnotowanych++;
                $ileOdnotowanychZasob++;

                $userZasob->setModul($userZasob->getmodul());
                $userZasob->setPoziomdostepu($userZasob->getpoziomDostepu());
                $userZasob->setCzyAktywne(false);
                $userZasob->setCzyOdebrane(true);
                $userZasob->setKtoOdebral('demon_akd');
                $userZasob->setDataOdebrania(new \Datetime());

                if ($idZasobu === Zasoby::ID_HOME_OFFICE) {
                    $userZasob->setPowodOdebrania('Uprawnienie wykorzystane');
                } else {
                    $userZasob->setPowodOdebrania('Upłynął termin ważności uprawnień');
                }

                $entityManager->persist($userZasob);

                $output->writeln('Odnotowuję odebranie uprawnień: <fg=cyan>('. $userZasob->getId().')</><info> ' . $login.'</info>');

                $grupyDoAd = '';
                if (!in_array($login, $pomijajKonta, true)) {
                    if ($idZasobu === Zasoby::ID_DLP_USB_DVD) {
                        $grupyDoAd = '+DLP-gg-USB_CD_DVD-DENY,';
                    }

                    $grupaDoOdebrania = $this->znajdzGrupeAD($userZasob, $zasob);
                    $czyGrupaWUpp = $grupaDoOdebrania !== 'brak' ? $this->sprawdzCzyGrupaNalezyDoUpp($login, $grupaDoOdebrania, $output) : true;

                    if (!$czyGrupaWUpp) {
                        $grupyDoAd .= '-'.$grupaDoOdebrania;
                    }
                }

                if (!empty($grupyDoAd)) {
                    $entry = new Entry();
                    $entry->setFromWhen(new \Datetime());
                    $entry->setSamaccountname($login);
                    $entry->setCreatedBy('SYSTEM');
                    $entry->setMemberOf($grupyDoAd);

                    if ($idZasobu === Zasoby::ID_HOME_OFFICE) {
                        $entry->setOpis('Zmieniono administracyjnie: Uprawnienia zostały wykorzystane');
                    } else {
                        $entry->setOpis('Odebrane administracyjnie: Upłynął termin ważności uprawnień');
                    }

                    $entityManager->persist($entry);

                    $output->writeln("\t".'<comment>Odbieram uprawnienia w AD: ' . $grupyDoAd.'</comment>');

                    $this->ileOdebranych++;
                    $ileOdebranychZasob++;
                }
            }
        } else {
            $output->writeln("\t".'Brak uprawnień do odebrania.');
        }

        $this->podumowanieTabela[] = [$idZasobu . ' ' . $zasob->getNazwa(), $ileOdnotowanychZasob, $ileOdebranychZasob, $ilePominietychZasob];

        if (false === $tylkoTest) {
            $entityManager->flush();
        }
    }

    /**
     * Zwraca nazwy kont pracowników D/B
     *
     * @param string $departament Skrót D/B
     *
     * @return array
     */
    protected function pracownicyDepartmentu(string $departament): array
    {
        $ldapService = $this->getContainer()->get('ldap_service');
        $pracownicyOu = $ldapService->getUsersFromOU($departament);

        if (null === $pracownicyOu) {
            throw new \Exception("Nie znaleziono departamentu: $departament.");
        }

        $pracownicyOuLoginy = [];

        foreach ($pracownicyOu as $user) {
            $pracownicyOuLoginy[] = $user['samaccountname'];
        }

        return $pracownicyOuLoginy;
    }

    /**
     * Znajduje grupę AD na podstawie poziomu dostępu.
     *
     * @param Zasoby $zasob
     * @param UserZasoby $userZasoby
     *
     * @return string Nazwa grupy w AD
     */
    protected function znajdzGrupeAD(UserZasoby $userZasoby, Zasoby $zasob): string
    {
        $grupy = explode(';', $zasob->getGrupyAD());
        $poziomy = explode(';', $zasob->getPoziomDostepu());
        $ktoryPoziom = $this->znajdzPoziom($poziomy, $userZasoby->getPoziomDostepu());

        return ($ktoryPoziom >= 0 && $ktoryPoziom < count($grupy) ? $grupy[$ktoryPoziom] : 'brak');
    }

    /**
     * Zwraca indeks poziomu dla grupy AD
     *
     * @param array $poziomy
     * @param string $poziom
     *
     * @return int
     */
    protected function znajdzPoziom($poziomy, $poziom): int
    {
        $i = -1;
        for ($i = 0; $i < count($poziomy); $i++) {
            if (trim($poziomy[$i]) == trim($poziom) || strstr(trim($poziomy[$i]), trim($poziom)) !== false) {
                return $i;
            }
        }

        return $i;
    }

    /**
     * Sprawdza czy grupa AD należy do oprawnień podstawowych
     *
     * @param string $login
     * @param string $grupa
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function sprawdzCzyGrupaNalezyDoUpp($login, $grupa, OutputInterface $output): bool
    {
        $ldapService = $this->getContainer()->get('ldap_service');
        $ADUser = $ldapService->getUserFromAD($login, null, null, 'wszyscyWszyscy');

        if (false === strpos($ADUser[0]['distinguishedname'], 'Zespoly_2016')) {
            $output->writeln("\t".'<comment>Konto zablokowane w AD.</comment>');
            return false;
        }

        try {
            $grupyNaPodstawieSekcjiOrazStanowiska = $ldapService->getGrupyUsera($ADUser[0], $ADUser[0]['description'], $ADUser[0]['division']);

            if (in_array($grupa, $grupyNaPodstawieSekcjiOrazStanowiska)) {
                $output->writeln("\t" . '<comment>Pomijam odebranie uprawnienia w AD: UPP pracownika</comment>');
                return true;
            }
        } catch (\Exception $e) {
            $output->error('Problem z weryfikacją UPP pracownika: ' . $login . "\nError: " . $e->getMessage());
        }

        return false;
    }
}
