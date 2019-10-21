<?php declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191021135055 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE tmp_ad_user');
        $this->addSql('DROP TABLE tmp_ad_user_wnioch');
        $this->addSql('ALTER TABLE komentarz CHANGE tytul tytul VARCHAR(100) NOT NULL, CHANGE opis opis VARCHAR(5000) NOT NULL');
        $this->addSql('ALTER TABLE zasoby CHANGE zasob_specjalny zasob_specjalny TINYINT(1) DEFAULT NULL');
        $this->addSql('ALTER TABLE position RENAME INDEX idx_462ce4f5cafa9a38 TO IDX_462CE4F5FE54D947');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tmp_ad_user (samaccountname VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, name VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, title VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, description VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, department VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, division VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, info VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, manager VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, distinguishedname TINYTEXT DEFAULT NULL COLLATE utf8_unicode_ci, PRIMARY KEY(samaccountname)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE tmp_ad_user_wnioch (imie_nazwisko VARCHAR(255) NOT NULL COLLATE utf8_unicode_ci, nazwisko_imie VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, samaccountname VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, PRIMARY KEY(imie_nazwisko)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE komentarz CHANGE tytul tytul VARCHAR(255) DEFAULT NULL COLLATE utf8_unicode_ci, CHANGE opis opis VARCHAR(5000) DEFAULT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE position RENAME INDEX idx_462ce4f5fe54d947 TO IDX_462CE4F5CAFA9A38');
        $this->addSql('ALTER TABLE zasoby CHANGE zasob_specjalny zasob_specjalny TINYINT(1) DEFAULT \'0\'');
    }
}
