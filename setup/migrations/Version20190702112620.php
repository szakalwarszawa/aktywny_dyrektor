<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190702112620 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE jasper_role_privilege (id INT AUTO_INCREMENT NOT NULL, role_id INT DEFAULT NULL, UNIQUE INDEX unique_role (role_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE jasper_role_paths (role_privilege_id INT NOT NULL, path_id INT NOT NULL, INDEX IDX_4D463FE0A6ADE636 (role_privilege_id), INDEX IDX_4D463FE0D96C566B (path_id), PRIMARY KEY(role_privilege_id, path_id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE jasper_path (id INT AUTO_INCREMENT NOT NULL, url VARCHAR(255) NOT NULL, is_repository TINYINT(1) NOT NULL, title VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D90BD1FF47645AE (url), PRIMARY KEY(id)) DEFAULT CHARACTER SET UTF8 COLLATE UTF8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE jasper_role_privilege ADD CONSTRAINT FK_B97BE7EED60322AC FOREIGN KEY (role_id) REFERENCES acl_role (id)');
        $this->addSql('ALTER TABLE jasper_role_paths ADD CONSTRAINT FK_4D463FE0A6ADE636 FOREIGN KEY (role_privilege_id) REFERENCES jasper_role_privilege (id)');
        $this->addSql('ALTER TABLE jasper_role_paths ADD CONSTRAINT FK_4D463FE0D96C566B FOREIGN KEY (path_id) REFERENCES jasper_path (id)');
    }

    public function down(Schema $schema) : void
    {
    }
}
