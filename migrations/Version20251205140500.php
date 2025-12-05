<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration to make created_by_id nullable for customer orders
 */
final class Version20251205140500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make created_by_id nullable to support customer orders without admin creator';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` MODIFY COLUMN created_by_id INT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` MODIFY COLUMN created_by_id INT NOT NULL');
    }
}
