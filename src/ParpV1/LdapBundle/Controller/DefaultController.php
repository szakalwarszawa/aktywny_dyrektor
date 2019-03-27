<?php

namespace ParpV1\LdapBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use ParpV1\MainBundle\Entity\Entry;
use DateTime;
use Symfony\Component\VarDumper\VarDumper;
use ParpV1\LdapBundle\MessageCollector\Constants\Types;
use ParpV1\LdapBundle\MessageCollector\Collector;


class DefaultController extends Controller
{
    /**
     * @Route("/hellox/{name}")
     */
    public function indexAction($name)
    {
        $ldapFetch = $this->get('ldap_fetch');

        //$ldapFetch->findAllAdGroups();

        $updateFromEntry = $this->get('update_from_entry');
        $testEntry = new Entry();
        //$messagesCollector = new Collector();
        $testEntry->setSamaccountname('konrad_szelepusta');
        $testEntry->setAccountExpires(new DateTime())
        ->setInfo('Nowa sedkcdsjfas')
        ->setTitle('Młodszy dsspecjalidsta')
        ->setMemberOf('-duppa,-SG-PRINT-BPR-LXT430M01-P,-SGG-BI-Wewn-BI.SOR-RW,-SGG-BI-Wewn-BI.SAT-RW,-DLP-gg-USB_CD_DVD-DENY,-SGG-BI-Wewn-Wsp-RW,-SG-BI-VPN-Admins,-SG-BI-VPN-Access,-Pracownicy,-INT-Kierownicy,+INT-BI-Administratorzy_zasobow,+dupa')
        ->setInitials('KSdd')
        ;



        $updates = $updateFromEntry->update($testEntry);

        $messages = $updates
            ->getCollector()
            ->getMessages()
        ;

        VarDumper::dump($messages);

        die;

       /* $ldapUser = $ldapFetch
            ->fetchAdUser('konrad_szelepusta', SearchBy::LOGIN, LdapFetch::FULL_USER_OBJECT)
        ;

        $ldapUser->updateAttribute(AdUserConstants::SEKCJA_SKROT, 'BI.SORO');

            VarDumper::dump($ldapUser);
        die;*/
    }
}
