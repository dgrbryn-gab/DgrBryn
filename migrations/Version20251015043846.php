<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251015043846 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE store_product DROP stock_quantity');
        $this->addSql('ALTER TABLE wine_inventory CHANGE quantity quantity INT DEFAULT 0 NOT NULL, CHANGE acquired_date acquired_date DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE store_product ADD stock_quantity INT NOT NULL');
        $this->addSql('ALTER TABLE wine_inventory CHANGE quantity quantity INT NOT NULL, CHANGE acquired_date acquired_date DATE NOT NULL');
    }
}
