<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251229095209 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscription DROP CONSTRAINT fk_a3c664d312469de2');
        $this->addSql('DROP INDEX idx_a3c664d312469de2');
        $this->addSql('ALTER TABLE subscription ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE subscription ADD updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE subscription ADD start_date DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE subscription DROP category_id');
        $this->addSql('ALTER TABLE subscription ALTER periodicity TYPE VARCHAR(20)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subscription ADD category_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE subscription DROP created_at');
        $this->addSql('ALTER TABLE subscription DROP updated_at');
        $this->addSql('ALTER TABLE subscription DROP start_date');
        $this->addSql('ALTER TABLE subscription ALTER periodicity TYPE VARCHAR(30)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT fk_a3c664d312469de2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_a3c664d312469de2 ON subscription (category_id)');
    }
}
