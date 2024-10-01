<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240930154021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE chat ADD reservation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE chat ADD CONSTRAINT FK_659DF2AAB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('CREATE INDEX IDX_659DF2AAB83297E7 ON chat (reservation_id)');
        $this->addSql('ALTER TABLE reservation ADD language INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation DROP language');
        $this->addSql('ALTER TABLE chat DROP FOREIGN KEY FK_659DF2AAB83297E7');
        $this->addSql('DROP INDEX IDX_659DF2AAB83297E7 ON chat');
        $this->addSql('ALTER TABLE chat DROP reservation_id');
    }
}
