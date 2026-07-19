<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260718233000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Product gallery: product_image table + migrate existing cover images';
    }

    public function up(Schema $schema): void
    {
        // Sans FK : tables produit souvent en MyISAM (erreur 1824 avec REFERENCES)
        $this->addSql('DROP TABLE IF EXISTS product_image');
        $this->addSql('CREATE TABLE product_image (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, filename VARCHAR(191) NOT NULL, position INT NOT NULL, INDEX IDX_PRODUCT_IMAGE_PRODUCT (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("INSERT INTO product_image (product_id, filename, position) SELECT id, image, 0 FROM product WHERE image IS NOT NULL AND image != ''");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS product_image');
    }
}
