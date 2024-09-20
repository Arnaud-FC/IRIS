<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240918165439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE languages_available_user (languages_available_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_A91D02027D855A31 (languages_available_id), INDEX IDX_A91D0202A76ED395 (user_id), PRIMARY KEY(languages_available_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE languages_available_user ADD CONSTRAINT FK_A91D02027D855A31 FOREIGN KEY (languages_available_id) REFERENCES languages_available (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE languages_available_user ADD CONSTRAINT FK_A91D0202A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE languages_available_user DROP FOREIGN KEY FK_A91D02027D855A31');
        $this->addSql('ALTER TABLE languages_available_user DROP FOREIGN KEY FK_A91D0202A76ED395');
        $this->addSql('DROP TABLE languages_available_user');
    }
}
