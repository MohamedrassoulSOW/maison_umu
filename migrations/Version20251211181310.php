<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251211181310 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE city (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(191) NOT NULL, shipping_const DOUBLE PRECISION NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(191) NOT NULL, last_name VARCHAR(191) NOT NULL, phone VARCHAR(191) NOT NULL, adress VARCHAR(191) NOT NULL, created_at DATETIME NOT NULL, pay_on_delivery TINYINT(1) NOT NULL, total_price DOUBLE PRECISION NOT NULL, city_id INT DEFAULT NULL, INDEX IDX_F52993988BAC62AF (city_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE order_products (id INT AUTO_INCREMENT NOT NULL, qte INT NOT NULL, _order_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_5242B8EBA35F2858 (_order_id), INDEX IDX_5242B8EB4584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993988BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
        $this->addSql('ALTER TABLE order_products ADD CONSTRAINT FK_5242B8EBA35F2858 FOREIGN KEY (_order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE order_products ADD CONSTRAINT FK_5242B8EB4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE add_product_history ADD CONSTRAINT FK_EDEB7BDE4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product CHANGE name name VARCHAR(191) NOT NULL, CHANGE image image VARCHAR(191) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04AD5E237E06 ON product (name)');
        $this->addSql('ALTER TABLE product_sub_category ADD CONSTRAINT FK_3147D5F34584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_sub_category ADD CONSTRAINT FK_3147D5F3F7BFE87C FOREIGN KEY (sub_category_id) REFERENCES sub_category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sub_category CHANGE name name VARCHAR(191) NOT NULL');
        $this->addSql('ALTER TABLE sub_category ADD CONSTRAINT FK_BCE3F79812469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993988BAC62AF');
        $this->addSql('ALTER TABLE order_products DROP FOREIGN KEY FK_5242B8EBA35F2858');
        $this->addSql('ALTER TABLE order_products DROP FOREIGN KEY FK_5242B8EB4584665A');
        $this->addSql('DROP TABLE city');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE order_products');
        $this->addSql('ALTER TABLE add_product_history DROP FOREIGN KEY FK_EDEB7BDE4584665A');
        $this->addSql('DROP INDEX UNIQ_D34A04AD5E237E06 ON product');
        $this->addSql('ALTER TABLE product CHANGE name name VARCHAR(255) NOT NULL, CHANGE image image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE product_sub_category DROP FOREIGN KEY FK_3147D5F34584665A');
        $this->addSql('ALTER TABLE product_sub_category DROP FOREIGN KEY FK_3147D5F3F7BFE87C');
        $this->addSql('ALTER TABLE sub_category DROP FOREIGN KEY FK_BCE3F79812469DE2');
        $this->addSql('ALTER TABLE sub_category CHANGE name name VARCHAR(255) NOT NULL');
    }
}
