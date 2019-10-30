<?php

declare(strict_types=1);

namespace Application\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20191028104142 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE acl_user_role SET deletedAt = now() WHERE role_id = 14');
        $this->addSql('INSERT INTO acl_user_role (role_id, samaccountname)
            SELECT \'14\', q2.samaccountname FROM (
            SELECT distinct(samaccountname) FROM (
                SELECT
                    LOWER(substring_index(substring_index(administratorZasobu,\',\',n),\',\',-1)) as samaccountname
                FROM
                    zasoby
                join
                    (select * from (select 1 n union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) cyfry_tmp) as cyfry
                    on
                    char_length(administratorZasobu)-char_length(replace(administratorZasobu,\',\',\'\')) >= (n-1)
                WHERE
                    administratorZasobu != \'\'
                    and zasoby.published = 1
                ORDER BY
                    administratorZasobu ASC
            ) administratorzy_zasobow
            ORDER BY samaccountname ASC
            ) as q2
            WHERE q2.samaccountname is not null and q2.samaccountname != \'\''
        );
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}
