<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220413081635 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE purchase_order ADD user_cancel_id INT DEFAULT NULL, ADD canceled_reason VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE purchase_order ADD CONSTRAINT FK_21E210B2738D62E6 FOREIGN KEY (user_cancel_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_21E210B2738D62E6 ON purchase_order (user_cancel_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE purchase_order DROP FOREIGN KEY FK_21E210B2738D62E6');
        $this->addSql('DROP INDEX IDX_21E210B2738D62E6 ON purchase_order');
        $this->addSql('ALTER TABLE purchase_order DROP user_cancel_id, DROP canceled_reason');
    }
}
