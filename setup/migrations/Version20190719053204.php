<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190719053204 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE userzasoby_grupy_poziomow (userzasob_id INT NOT NULL, grupa_poziomow_dostepu INT NOT NULL, INDEX IDX_623AE540C7CD564E (userzasob_id), INDEX IDX_623AE54047DAE701 (grupa_poziomow_dostepu), PRIMARY KEY(userzasob_id, grupa_poziomow_dostepu)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE userzasoby_grupy_poziomow ADD CONSTRAINT FK_623AE540C7CD564E FOREIGN KEY (userzasob_id) REFERENCES userzasoby (id)');
        $this->addSql('ALTER TABLE userzasoby_grupy_poziomow ADD CONSTRAINT FK_623AE54047DAE701 FOREIGN KEY (grupa_poziomow_dostepu) REFERENCES access_level_group (id)');
    }

    public function down(Schema $schema) : void
    {
    }
}
