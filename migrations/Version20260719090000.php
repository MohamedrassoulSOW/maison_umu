<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260719090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Product likes: likes_count on product + product_like table (no FK for MyISAM)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD likes_count INT DEFAULT 0 NOT NULL');
        $this->addSql('DROP TABLE IF EXISTS product_like');
        $this->addSql('CREATE TABLE product_like (
            id INT AUTO_INCREMENT NOT NULL,
            product_id INT NOT NULL,
            user_id INT DEFAULT NULL,
            visitor_key VARCHAR(64) NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            INDEX idx_product_like_product (product_id),
            UNIQUE INDEX uniq_product_like_visitor (product_id, visitor_key),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS product_like');
        $this->addSql('ALTER TABLE product DROP likes_count');
    }
}
