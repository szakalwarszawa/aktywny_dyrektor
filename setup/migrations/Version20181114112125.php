<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20181114112125 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE lsi_import_tokens (id INT AUTO_INCREMENT NOT NULL, id_wniosku INT NOT NULL, requested_by VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, last_used_at DATETIME NOT NULL, expire_at DATETIME NOT NULL, token VARCHAR(255) NOT NULL, use_count INT DEFAULT NULL, status VARCHAR(255) NOT NULL, INDEX IDX_457009FA5F76E53E (id_wniosku), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE lsi_import_tokens ADD CONSTRAINT FK_457009FA5F76E53E FOREIGN KEY (id_wniosku) REFERENCES wniosek (id)');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {

    }
}
