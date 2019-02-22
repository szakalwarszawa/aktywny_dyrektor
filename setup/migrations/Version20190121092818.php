<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190121092818 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('INSERT INTO wniosek_status(nazwa,nazwaSystemowa,opis,viewers,finished,typWniosku,editors) VALUES(\'Odebrany administracyjnie\', \'102_ODEBRANO_ADMINISTRACYJNIE\', \'Odebrany administracyjnie\', \'wnioskodawca,wlasciciel,administratorZasobow,administrator,nadzorcaDomen\', 1, \'wniosekONadanieUprawnien\',\'\')');
        $this->addSql('INSERT INTO wniosek_status(nazwa,nazwaSystemowa,opis,viewers,finished,typWniosku,editors) VALUES(\'Anulowany Administracyjnie\', \'101_ANULOWANO_ADMINISTRACYJNIE\', \'Anulowany Administracyjnie\', \'wnioskodawca,wlasciciel,administratorZasobow,administrator,nadzorcaDomen\', 1, \'wniosekONadanieUprawnien\',\'\')');
    }

    public function down(Schema $schema) : void
    {
    }
}
