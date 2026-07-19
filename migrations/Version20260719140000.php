<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260719140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Site settings: brand, contact, WhatsApp, map, about';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE site_settings (
            id INT AUTO_INCREMENT NOT NULL,
            brand_name VARCHAR(120) NOT NULL,
            brand_tagline VARCHAR(255) NOT NULL,
            brand_logo_path VARCHAR(255) NOT NULL,
            contact_email VARCHAR(180) NOT NULL,
            address VARCHAR(255) NOT NULL,
            hours VARCHAR(120) NOT NULL,
            response_sla VARCHAR(180) NOT NULL,
            contact_lead LONGTEXT NOT NULL,
            contact_info_text LONGTEXT NOT NULL,
            map_embed_url LONGTEXT NOT NULL,
            map_link_url LONGTEXT NOT NULL,
            footer_blurb VARCHAR(255) NOT NULL,
            whatsapp_numbers JSON NOT NULL,
            about_lead LONGTEXT NOT NULL,
            about_block1_title VARCHAR(120) NOT NULL,
            about_block1_text LONGTEXT NOT NULL,
            about_block2_title VARCHAR(120) NOT NULL,
            about_block2_text LONGTEXT NOT NULL,
            about_block3_title VARCHAR(120) NOT NULL,
            about_block3_text LONGTEXT NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS site_settings');
    }
}
