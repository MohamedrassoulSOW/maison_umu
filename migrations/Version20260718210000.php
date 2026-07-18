<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260718210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create hero_settings table for editable homepage hero';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE hero_settings (
            id INT AUTO_INCREMENT NOT NULL,
            brand_label VARCHAR(120) NOT NULL,
            headline VARCHAR(255) NOT NULL,
            text LONGTEXT NOT NULL,
            primary_cta_label VARCHAR(120) NOT NULL,
            primary_cta_url VARCHAR(255) NOT NULL,
            secondary_cta_label VARCHAR(120) NOT NULL,
            secondary_cta_url VARCHAR(255) NOT NULL,
            image VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql("INSERT INTO hero_settings (brand_label, headline, text, primary_cta_label, primary_cta_url, secondary_cta_label, secondary_cta_url, image) VALUES (
            'Maison UMU',
            'L’essentiel, choisi avec soin',
            'Découvrez une collection élégante pour votre intérieur et votre quotidien.',
            'Découvrir',
            '#collection',
            'Voir le panier',
            '/cart',
            NULL
        )");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE hero_settings');
    }
}
