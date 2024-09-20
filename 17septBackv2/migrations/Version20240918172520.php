<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240918172520 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE visite_user (visite_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_E279F8C1C5DC59 (visite_id), INDEX IDX_E279F8A76ED395 (user_id), PRIMARY KEY(visite_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE visite_user ADD CONSTRAINT FK_E279F8C1C5DC59 FOREIGN KEY (visite_id) REFERENCES visite (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE visite_user ADD CONSTRAINT FK_E279F8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE visite_user DROP FOREIGN KEY FK_E279F8C1C5DC59');
        $this->addSql('ALTER TABLE visite_user DROP FOREIGN KEY FK_E279F8A76ED395');
        $this->addSql('DROP TABLE visite_user');
    }
}
