<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251018144305 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wine_inventory DROP FOREIGN KEY FK_28A4B6C54584665A');
        $this->addSql('ALTER TABLE wine_inventory ADD CONSTRAINT FK_28A4B6C54584665A FOREIGN KEY (product_id) REFERENCES store_product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE wine_inventory DROP FOREIGN KEY FK_28A4B6C54584665A');
        $this->addSql('ALTER TABLE wine_inventory ADD CONSTRAINT FK_28A4B6C54584665A FOREIGN KEY (product_id) REFERENCES store_product (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
    }
}
