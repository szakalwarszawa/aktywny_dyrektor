<?php

namespace ParpV1\MainBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ChangelogControllerTest extends WebTestCase
{
    public function testShowchangelog()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/changelog');
    }

    public function testShowversion()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/changelog/{wersja}');
    }

}
