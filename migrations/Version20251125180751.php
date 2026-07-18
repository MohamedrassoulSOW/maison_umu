<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251125180751 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE add_product_history ADD CONSTRAINT FK_EDEB7BDE4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product CHANGE name name VARCHAR(191) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04AD5E237E06 ON product (name)');
        $this->addSql('ALTER TABLE product_sub_category ADD CONSTRAINT FK_3147D5F34584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_sub_category ADD CONSTRAINT FK_3147D5F3F7BFE87C FOREIGN KEY (sub_category_id) REFERENCES sub_category (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sub_category ADD CONSTRAINT FK_BCE3F79812469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE add_product_history DROP FOREIGN KEY FK_EDEB7BDE4584665A');
        $this->addSql('DROP INDEX UNIQ_D34A04AD5E237E06 ON product');
        $this->addSql('ALTER TABLE product CHANGE name name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE product_sub_category DROP FOREIGN KEY FK_3147D5F34584665A');
        $this->addSql('ALTER TABLE product_sub_category DROP FOREIGN KEY FK_3147D5F3F7BFE87C');
        $this->addSql('ALTER TABLE sub_category DROP FOREIGN KEY FK_BCE3F79812469DE2');
    }
}
