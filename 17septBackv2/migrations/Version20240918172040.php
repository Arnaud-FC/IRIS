<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240918172040 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, visite_id INT NOT NULL, billing_id INT DEFAULT NULL, status VARCHAR(255) NOT NULL, INDEX IDX_42C84955C1C5DC59 (visite_id), UNIQUE INDEX UNIQ_42C849553B025C87 (billing_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955C1C5DC59 FOREIGN KEY (visite_id) REFERENCES visite (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849553B025C87 FOREIGN KEY (billing_id) REFERENCES billing (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955C1C5DC59');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C849553B025C87');
        $this->addSql('DROP TABLE reservation');
    }
}
