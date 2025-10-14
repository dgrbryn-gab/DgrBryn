<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251014070414 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE inventory (id INT AUTO_INCREMENT NOT NULL, store_product_id INT NOT NULL, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_B12D4A366B6D3DB7 (store_product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE inventory ADD CONSTRAINT FK_B12D4A366B6D3DB7 FOREIGN KEY (store_product_id) REFERENCES store_product (id)');
        $this->addSql('ALTER TABLE category CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE store_product DROP FOREIGN KEY FK_CA42254A12469DE2');
        $this->addSql('ALTER TABLE store_product CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE store_product ADD CONSTRAINT FK_CA42254A12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE inventory DROP FOREIGN KEY FK_B12D4A366B6D3DB7');
        $this->addSql('DROP TABLE inventory');
        $this->addSql('ALTER TABLE store_product DROP FOREIGN KEY FK_CA42254A12469DE2');
        $this->addSql('ALTER TABLE store_product CHANGE description description LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE store_product ADD CONSTRAINT FK_CA42254A12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE category CHANGE description description LONGTEXT NOT NULL');
    }
}
