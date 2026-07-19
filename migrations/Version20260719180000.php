<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260719180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Order tracking token + satisfaction survey fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` ADD tracking_token VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD satisfaction_score SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD satisfaction_comment LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE `order` ADD satisfaction_submitted_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F5299398B3E9E49C ON `order` (tracking_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_F5299398B3E9E49C ON `order`');
        $this->addSql('ALTER TABLE `order` DROP tracking_token, DROP satisfaction_score, DROP satisfaction_comment, DROP satisfaction_submitted_at');
    }
}
