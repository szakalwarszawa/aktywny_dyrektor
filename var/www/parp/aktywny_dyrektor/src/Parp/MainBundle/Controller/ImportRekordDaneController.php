<?php

namespace Parp\MainBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * Klaster controller.
 *
 * @Route("/import_rekord")
 */
class ImportRekordDaneController extends Controller
{
    /**
     * Lists all Klaster entities.
     *
     * @Route("/", name="importfirebird", defaults={})
     * @Method("GET")
     */
    public function importfirebirdAction()
    {
        $sciecha = "";
        if ($db = \ibase_connect('localhost:/var/www/parp/PARP_KP.FDB', 'SYSDBA',
            'masterkey')) {
            echo 'Connected to the database.';
            $sql = "select
p.IMIE, 
p.NAZWISKO, 
departament.OPIS  DEPARTAMENT,
stanowisko.OPIS STANOWISKO,
rodzaj.NAZWA RODZAJ_UMOWY,
(mpr.DATA_OD),
mpr.DATA_DO
from P_PRACOWNIK p
join PV_MP_PRA mpr on mpr.SYMBOL = p.SYMBOL
join P_MPRACY departament on departament.KOD = mpr.KOD
JOIN P_ST_PRA stjoin on stjoin.SYMBOL= p.SYMBOL
join P_STANOWISKO stanowisko on stanowisko.KOD = stjoin.KOD
join P_UMOWA umowa on umowa.SYMBOL= p.SYMBOL
join P_RODZUMOWY rodzaj on rodzaj.RODZAJ_UM = umowa.RODZAJ_UM
where 
--mpr.DATA_DO IS NULL AND UMOWA.DATA_DO IS NULL
--AND 
p.NAZWISKO like '%TROC%'; 

";
            $result = \ibase_query($db, $sql); // assume $tr is a transaction

            $count = 0;
            while ($row = ibase_fetch_assoc($result)){
                $count++;
                $rows[] = $row;
            }

            \ibase_close($db);
            //die('a');
            echo "<pre>";
            print_r($rows);
        } else {
            echo 'Connection failed.';
        }


        die('testfirebird');
    }
} 