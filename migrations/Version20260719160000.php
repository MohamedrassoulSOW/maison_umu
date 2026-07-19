<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260719160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add order.payment_method (cod, wave, orange_money, stripe)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE `order` ADD payment_method VARCHAR(32) NOT NULL DEFAULT 'cod'");
        $this->addSql("UPDATE `order` SET payment_method = 'cod' WHERE pay_on_delivery = 1");
        $this->addSql("UPDATE `order` SET payment_method = 'stripe' WHERE pay_on_delivery = 0 OR pay_on_delivery IS NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` DROP payment_method');
    }
}
