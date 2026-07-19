<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260718230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Google OAuth fields on user (google_id, nullable password)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD google_id VARCHAR(191) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE password password VARCHAR(191) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D64976F5C865 ON user (google_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_8D93D64976F5C865 ON user');
        $this->addSql('ALTER TABLE user DROP google_id');
        $this->addSql('ALTER TABLE user CHANGE password password VARCHAR(191) NOT NULL');
    }
}
