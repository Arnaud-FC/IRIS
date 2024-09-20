<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240919090430 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE decision (id INT AUTO_INCREMENT NOT NULL, guide_id INT NOT NULL, reservation_id INT NOT NULL, status VARCHAR(255) NOT NULL, INDEX IDX_84ACBE48D7ED1D4B (guide_id), INDEX IDX_84ACBE48B83297E7 (reservation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE decision ADD CONSTRAINT FK_84ACBE48D7ED1D4B FOREIGN KEY (guide_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE decision ADD CONSTRAINT FK_84ACBE48B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE decision DROP FOREIGN KEY FK_84ACBE48D7ED1D4B');
        $this->addSql('ALTER TABLE decision DROP FOREIGN KEY FK_84ACBE48B83297E7');
        $this->addSql('DROP TABLE decision');
    }
}
