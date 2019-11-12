<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191106150910 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE wniosek_nadanie_odebranie_zasobow ADD odpowiedzialnyDepartament_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE wniosek_nadanie_odebranie_zasobow ADD CONSTRAINT FK_978C5E6729F1160C FOREIGN KEY (odpowiedzialnyDepartament_id) REFERENCES departament (id)');
        $this->addSql('CREATE INDEX IDX_978C5E6729F1160C ON wniosek_nadanie_odebranie_zasobow (odpowiedzialnyDepartament_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
