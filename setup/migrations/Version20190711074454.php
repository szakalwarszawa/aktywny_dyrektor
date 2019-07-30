<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190711074454 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE access_level_group (id INT AUTO_INCREMENT NOT NULL, zasob_id INT DEFAULT NULL, group_name VARCHAR(100) NOT NULL, description TEXT NOT NULL, access_levels LONGTEXT NOT NULL, INDEX IDX_9D8CCCE2EB88A939 (zasob_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE access_level_group ADD CONSTRAINT FK_9D8CCCE2EB88A939 FOREIGN KEY (zasob_id) REFERENCES zasoby (id)');
    }

    public function down(Schema $schema) : void
    {

    }
}
