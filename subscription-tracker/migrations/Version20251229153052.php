<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251229153052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification ADD type VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE notification ADD amount DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE notification ADD scheduled_for TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE notification ADD metadata JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_history ADD currency VARCHAR(10) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_history ADD transaction_id VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_history ADD metadata JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_history ADD notes TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment_history ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE payment_history ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE subscription ADD failed_payment_attempts INT DEFAULT NULL');
        $this->addSql('ALTER TABLE subscription ADD last_payment_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE subscription ADD last_payment_amount DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE subscription ADD payment_method VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE subscription ADD auto_renew BOOLEAN NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification DROP type');
        $this->addSql('ALTER TABLE notification DROP amount');
        $this->addSql('ALTER TABLE notification DROP scheduled_for');
        $this->addSql('ALTER TABLE notification DROP metadata');
        $this->addSql('ALTER TABLE payment_history DROP currency');
        $this->addSql('ALTER TABLE payment_history DROP transaction_id');
        $this->addSql('ALTER TABLE payment_history DROP metadata');
        $this->addSql('ALTER TABLE payment_history DROP notes');
        $this->addSql('ALTER TABLE payment_history DROP created_at');
        $this->addSql('ALTER TABLE payment_history DROP updated_at');
        $this->addSql('ALTER TABLE subscription DROP failed_payment_attempts');
        $this->addSql('ALTER TABLE subscription DROP last_payment_date');
        $this->addSql('ALTER TABLE subscription DROP last_payment_amount');
        $this->addSql('ALTER TABLE subscription DROP payment_method');
        $this->addSql('ALTER TABLE subscription DROP auto_renew');
    }
}
