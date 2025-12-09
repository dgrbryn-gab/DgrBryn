<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251208115154 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Username column and index already exist in the database
        // This migration is intentionally empty
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE store_product RENAME INDEX idx_ca42254ab03a8386 TO IDX_9E37D9FFB03A8386');
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_USERNAME ON `user`');
        $this->addSql('ALTER TABLE `user` DROP username');
    }
}
