<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify your needs!
 */
final class Version20251207120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add createdBy field to store_product table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it as you need
        $this->addSql('ALTER TABLE store_product ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE store_product ADD CONSTRAINT FK_9E37D9FFB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_9E37D9FFB03A8386 ON store_product (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it as you need
        $this->addSql('ALTER TABLE store_product DROP FOREIGN KEY FK_9E37D9FFB03A8386');
        $this->addSql('DROP INDEX IDX_9E37D9FFB03A8386 ON store_product');
        $this->addSql('ALTER TABLE store_product DROP COLUMN created_by_id');
    }
}
