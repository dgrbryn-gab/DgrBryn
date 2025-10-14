<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Fixed Migration: Added safe default handling for created_at
 */
final class Version20251014134328 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add description and created_at columns to category with valid defaults';
    }

    public function up(Schema $schema): void
    {
        // Step 1: Add columns as nullable first
        $this->addSql('ALTER TABLE category ADD description LONGTEXT DEFAULT NULL, ADD created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');

        // Step 2: Set created_at for existing records
        $this->addSql('UPDATE category SET created_at = NOW() WHERE created_at IS NULL');

        // Step 3: Make the column NOT NULL
        $this->addSql('ALTER TABLE category MODIFY created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE category DROP description, DROP created_at');
    }
}
