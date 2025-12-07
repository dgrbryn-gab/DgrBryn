<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251207152224 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` CHANGE total_amount total_amount NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE order_item CHANGE subtotal subtotal NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE store_product ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE store_product ADD CONSTRAINT FK_CA42254AB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_CA42254AB03A8386 ON store_product (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` CHANGE total_amount total_amount NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE order_item CHANGE subtotal subtotal NUMERIC(10, 2) NOT NULL');
        $this->addSql('ALTER TABLE store_product DROP FOREIGN KEY FK_CA42254AB03A8386');
        $this->addSql('DROP INDEX IDX_CA42254AB03A8386 ON store_product');
        $this->addSql('ALTER TABLE store_product DROP created_by_id');
    }
}
