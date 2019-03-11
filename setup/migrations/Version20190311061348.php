<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190311061348 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE odebranie_zasobow_entry (id INT AUTO_INCREMENT NOT NULL, uzytkownik LONGTEXT NOT NULL, powod_odebrania LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE entry ADD odebranie_zasobow_entry_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE entry ADD CONSTRAINT FK_2B219D70788782DF FOREIGN KEY (odebranie_zasobow_entry_id) REFERENCES odebranie_zasobow_entry (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2B219D70788782DF ON entry (odebranie_zasobow_entry_id)');
    }

    public function down(Schema $schema) : void
    {
    }
}
