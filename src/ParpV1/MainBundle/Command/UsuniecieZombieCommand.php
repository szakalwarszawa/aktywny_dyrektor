<?php

namespace ParpV1\MainBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use ParpV1\MainBundle\Entity\Zasoby;
use ParpV1\MainBundle\Entity\Departament;
use ParpV1\MainBundle\Entity\AclRole;
use ParpV1\MainBundle\Entity\AclUserRole;

class UsuniecieZombieCommand extends ContainerAwareCommand
{
    const UZYTKOWNIK_BEZ_ZAMIANY = 1;
    const UZYTKOWNIK_Z_ZAMIANA   = 2;
    const UZYTKOWNICY_Z_PLIKU    = 3;

    /**
     * @var array
     *
     * Tablica użytowników usuniętych
     */
    private $uzytkownicyZombie = array();

    /**
     * @var SymfonyStyle
     */
    private $inputOutput;

    /**
     * @var string
     *
     * Nazwa użytkownika który będzie wstawiony w miejsce poprzedniego.
     */
    private $uzytkownikDoZamiany = false;

    protected function configure()
    {
        $this
                ->setName('parp:usunzombie')
                ->setDescription('Usuwa z zasobów i wniosków nieistniejących już użytkowników.')
                ->addOption('user', null, InputOption::VALUE_OPTIONAL, 'Login użytkownika do usunięcia.')
                ->addOption('replace', null, InputOption::VALUE_OPTIONAL, 'Zastępstwo usuniętego użytkownika.')
                ->setHelp('Komenda służy do usunięcia z wnisków o nadanie zasobu użytkowników którzy nie istnieją. ' .
                    'Można również zamienić użytkownika innym podając opcje --user [nazwa] i --replace [nazwa] ' .
                    'Najpierw musi być uruchomiona komenda parp:raportzmian która zapisuje do pliku json ' .
                    'nazwy użytkowników zombie.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $uzytkownik = $input->getOption('user');
        $uzytkownikDoZamiany = $input->getOption('replace');
        $this->uzytkownicyZombie = array($uzytkownik);
        $this->inputOutput = new SymfonyStyle($input, $output);

        if (null === $uzytkownik && null === $uzytkownikDoZamiany) {
            $status = $this::UZYTKOWNICY_Z_PLIKU;
        } elseif (null !== $uzytkownik && null === $uzytkownikDoZamiany) {
            $status = $this::UZYTKOWNIK_BEZ_ZAMIANY;
        } elseif (null !== $uzytkownik && null !== $uzytkownikDoZamiany) {
            $this->uzytkownikDoZamiany = $uzytkownikDoZamiany;
            $status = $this::UZYTKOWNIK_Z_ZAMIANA;
            $this->inputOutput->table(
                array('Uzytkownik do usuniecia', 'Uzytkownik ktory go zastapi'),
                array(array($uzytkownik, $this->uzytkownikDoZamiany))
            );
        }

        $this->rozpocznijUsuwanieZombie($status);
    }

    private function rozpocznijUsuwanieZombie($status)
    {
        $kontynuowacWykonywanie = $this
                                ->inputOutput
                                ->confirm('Czynności nie da się cofnąć. Kontynuować?');

        if (false === $kontynuowacWykonywanie) {
            $this
                ->inputOutput
                ->error('Przerwano');
        } elseif (true === $kontynuowacWykonywanie) {
            $this->wytnijUzytkownikowZWnioskow($status);
        }
    }

    /**
     * Jeżeli konto użytkownika posiadające konkretne role zostanie usunięte
     * trzeba wyciąć go z każdego zasobu w AkD.
     * Jeżeli uzytkownik pełnij określoną rolę, trzeba go wyciąć z roli
     * i nadać określonej osobie.
     *
     * @param int $status
     */
    private function wytnijUzytkownikowZWnioskow($status)
    {

        if ($this::UZYTKOWNICY_Z_PLIKU === $status) {
            $this->uzytkownicyZombie =  $this->findOstatniZrzut();
        }

        $entityManager = $this
                            ->getContainer()
                            ->get('doctrine')
                            ->getEntityManager();

        $zasoby = $entityManager
                        ->getRepository(Zasoby::class)
                        ->findEdytorzyWnioskow();

        foreach ($this->uzytkownicyZombie as $uzytkownik) {
            $this->modyfikujRoleOsoby($uzytkownik);
            foreach ($zasoby as $zasob) {
                if (true === $this->sprawdzCzyEdytor($zasob, $uzytkownik)) {
                    $this->inputOutput->text('ID ZASOBU: ' . $zasob->getId());
                }
            }
        }

        $entityManager->flush();
    }

    /**
     * Sprawdza czy dany użytkownik jest jednym z edytorów zasobu.
     * WS- właściciel zasobu
     * PW- powiernicy zasobu
     * AZ- administrator zasobu
     * ATZ- administrator techniczny zasobu
     *
     * @param Zasoby $zasob
     * @param string $uzytkownik
     *
     * @return bool
     */
    private function sprawdzCzyEdytor(Zasoby $zasob, $uzytkownik)
    {
        $powiernicyWlascicielaZasobu = explode(',', $zasob->getPowiernicyWlascicielaZasobu());
        $administratorZasobu = explode(',', $zasob->getAdministratorZasobu());
        $administratorTechnicznyZasobu = explode(',', $zasob->getAdministratorTechnicznyZasobu());
        $czyJestWZasobie = false;

        if ($uzytkownik === $zasob->getWlascicielZasobu()) {
            $this->nadpiszDaneZasobu($zasob, array(), $uzytkownik, 'WS');
            $czyJestWZasobie = true;
        }

        if (in_array($uzytkownik, $powiernicyWlascicielaZasobu)) {
            $this->nadpiszDaneZasobu($zasob, $powiernicyWlascicielaZasobu, $uzytkownik, 'PW');
            $czyJestWZasobie = true;
        }

        if (in_array($uzytkownik, $administratorZasobu)) {
            $this->nadpiszDaneZasobu($zasob, $administratorZasobu, $uzytkownik, 'AZ');
            $czyJestWZasobie = true;
        }

        if (in_array($uzytkownik, $administratorTechnicznyZasobu)) {
            $this->nadpiszDaneZasobu($zasob, $administratorTechnicznyZasobu, $uzytkownik, 'ATZ');
            $czyJestWZasobie = true;
        }

        return $czyJestWZasobie;
    }

    /**
     * @todo
     */
    private function modyfikujGrupeOsob($uzytkownik, array $edytorzyZasobu, Zasoby $zasob, $rola)
    {
        $indexUzytkownika = array_search($uzytkownik, $edytorzyZasobu);
        unset($edytorzyZasobu[$indexUzytkownika]);
        if (null !== $this->uzytkownikDoZamiany) {
            $indexZmiennika = array_search($this->uzytkownikDoZamiany, $edytorzyZasobu);
            if (false !== $indexZmiennika) {
                $this
                    ->inputOutput
                    ->text(
                        'Uzytkownik juz istnieje w roli zasobu: ' . $rola .
                        '; Nazwa zasobu: ' . $zasob->getNazwa() .
                        ' (' . $zasob->getId() . ')'
                    );
            } else {
                $edytorzyZasobu[] = $this->uzytkownikDoZamiany;
            }
        }

        return $edytorzyZasobu;
    }

    /**
     * Zmienia wartości zapisane w bazie danych.
     *
     * @param Zasoby $zasob
     * @param array $edytorzyZasobu
     * @param string $uzytkownik
     * @param string $rola
     * @param bool|string $zamien
     *
     * @return void
     */
    private function nadpiszDaneZasobu(Zasoby $zasob, array $edytorzyZasobu, $uzytkownik, $rola)
    {
        if (count($edytorzyZasobu) > 1) {
            $edytorzyZasobu = $this->modyfikujGrupeOsob($uzytkownik, $edytorzyZasobu, $zasob, $rola);
            $stringDoZapisu = implode(',', $edytorzyZasobu);
        } else {
            if ('WS' === $rola) {
                if (null !== $this->uzytkownikDoZamiany) {
                    $stringDoZapisu = $this->uzytkownikDoZamiany;
                } else {
                    $stringDoZapisu = $this->findIbi();
                }
            } elseif ('AZ' === $rola || 'ATZ' === $rola) {
                if (null !== $this->uzytkownikDoZamiany) {
                    $stringDoZapisu = $this->uzytkownikDoZamiany;
                } else {
                    $stringDoZapisu = $zasob->getWlascicielZasobu();
                }
            } elseif ('PW' === $rola) {
                if (null !== $this->uzytkownikDoZamiany) {
                    $stringDoZapisu = $this->uzytkownikDoZamiany;
                } else {
                    $stringDoZapisu = '';
                }
            }
        }

        if (isset($stringDoZapisu)) {
            switch ($rola) {
                case 'PW':
                    $this->inputOutput->text('Zmiana z : ' . $zasob->getPowiernicyWlascicielaZasobu());
                    $zasob->setPowiernicyWlascicielaZasobu($stringDoZapisu);
                    break;
                case 'AZ':
                    $this->inputOutput->text('Zmiana z : ' . $zasob->getAdministratorZasobu());
                    $zasob->setAdministratorZasobu($stringDoZapisu);
                    break;
                case 'ATZ':
                    $this->inputOutput->text('Zmiana z : ' . $zasob->getAdministratorTechnicznyZasobu());
                    $zasob->setAdministratorTechnicznyZasobu($stringDoZapisu);
                    break;
                case 'WS':
                    $this->inputOutput->text('Zmiana z : ' . $zasob->getWlascicielZasobu());
                    $zasob->setWlascicielZasobu($stringDoZapisu);
                    break;
            }

            $this->inputOutput->text('Zmiana na : ' . $stringDoZapisu);
            $this->inputOutput->text('*******');
        }
    }

    /**
     * Usuwa role użytkownika i przypisuje je przełożonemu lub dyrektorowi DKM.
     *
     * @param string $nazwaUzytkownika
     *
     * @return void
     */
    private function modyfikujRoleOsoby($nazwaUzytkownika)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();

        $roleUzytkownika = $entityManager
                ->getRepository(AclUserRole::class)
                ->findBy(
                    array('samaccountname' => $nazwaUzytkownika)
                );

        foreach ($roleUzytkownika as $rola) {
            $nazwaRoli = $rola->getRole()->getName();
            if ('PARP_IBI' === $nazwaRoli || 'PARP_NADZORCA_DOMEN' === $nazwaRoli) {
                $this->dodajNowyWpisRoli($nazwaUzytkownika, $rola->getRole(), $nazwaRoli);
                $entityManager->remove($rola);
            }
        }
    }

    /**
     * Dodaje przełożonego użytkownika do roli PARP_IBI lub
     * dyrektora DKM jako PARP_NADZORCA_DOMEN
     *
     * @param string $nazwaUzytkownika
     * @param AclRole $rola
     * @param string $nazwaRoli
     *
     * @return void
     */
    private function dodajNowyWpisRoli($nazwaUzytkownika, AclRole $rola, $nazwaRoli)
    {
        $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();
        $ldapService = $this->getContainer()->get('ldap_service');

        if ('PARP_IBI' === $nazwaRoli) {
            $przelozonyUzytkownika = $ldapService->getPrzelozony($nazwaUzytkownika)['samaccountname'];
        } elseif ('PARP_NADZORCA_DOMEN' === $nazwaRoli) {
            $departament = $entityManager
                    ->getRepository(Departament::class)
                    ->findOneBy(
                        array(
                            'shortname' => 'DKM'
                        )
                    );
            $przelozonyUzytkownika = $departament->getDyrektor();
        }

        $czyJestWRoli = $entityManager
                ->getRepository(AclUserRole::class)
                ->findBy(
                    array(
                        'samaccountname' => $przelozonyUzytkownika,
                        'role' => $rola->getId()
                        )
                );

        if (empty($czyJestWRoli)) {
            $nowyWpisRoli = new AclUserRole();
            $nowyWpisRoli
                ->setSamaccountName($przelozonyUzytkownika)
                ->setRole($rola);

            $entityManager->persist($nowyWpisRoli);
            $entityManager->flush($nowyWpisRoli);
        }
    }

    /**
     * Zwraca aktualnego IBI
     *
     * @return AclUserRole
     */
    private function findIbi()
    {
        $entityManager = $this->getContainer()->get('doctrine')->getEntityManager();
        $ibi = $entityManager
                ->getRepository(AclUserRole::class)
                ->findOneBy(
                    array('role' => 9),
                    array('deletedAt' => 'ASC')
                );

        return $ibi;
    }

    /**
     * Zwraca nazwę najnowszego pliku zawierającego użytkowników zombie.
     *
     * @throws FileNotFoundException jeżeli w folderze nie ma żadnego pliku .json
     *
     * @return array
     *
     */
    private function findOstatniZrzut()
    {
        $katalogRaportow = $this
                            ->getContainer()
                            ->getParameter('porownania_json')['katalog_raportow'];

        $finder = new Finder();
        $pliki = $finder
            ->files()
            ->in($katalogRaportow)
            ->name('*.json')
            ->sortByName();

        $nazwaPliku = array();

        if (count($pliki) < 1) {
            throw new FileNotFoundException();
        }

        foreach ($pliki as $plik) {
            $nazwaPliku = $plik->getFileName();
        }

        return json_decode(file_get_contents($katalogRaportow . \DIRECTORY_SEPARATOR . $nazwaPliku));
    }
}
