<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260719120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Footer settings: optional brand / navigation / account / categories';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE footer_settings (
            id INT AUTO_INCREMENT NOT NULL,
            show_brand TINYINT(1) NOT NULL,
            show_navigation TINYINT(1) NOT NULL,
            show_account TINYINT(1) NOT NULL,
            show_categories TINYINT(1) NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('INSERT INTO footer_settings (show_brand, show_navigation, show_account, show_categories) VALUES (0, 0, 0, 1)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS footer_settings');
    }
}
