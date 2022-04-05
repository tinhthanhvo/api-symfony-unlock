<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220405135443 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_detail DROP FOREIGN KEY FK_ED896F468D9F6D38');
        $this->addSql('CREATE TABLE purchase_order (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, status VARCHAR(25) NOT NULL, create_at DATETIME NOT NULL, update_at DATETIME DEFAULT NULL, payment_at DATETIME DEFAULT NULL, total_price BIGINT NOT NULL, delete_at DATETIME DEFAULT NULL, amount INT NOT NULL, address_delivery LONGTEXT NOT NULL, recipient_name VARCHAR(150) NOT NULL, recipient_phone VARCHAR(11) NOT NULL, recipient_email VARCHAR(100) NOT NULL, INDEX IDX_21E210B29395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE purchase_order ADD CONSTRAINT FK_21E210B29395C3F3 FOREIGN KEY (customer_id) REFERENCES user (id)');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP INDEX IDX_ED896F468D9F6D38 ON order_detail');
        $this->addSql('ALTER TABLE order_detail CHANGE order_id purchase_order_id INT NOT NULL');
        $this->addSql('ALTER TABLE order_detail ADD CONSTRAINT FK_ED896F46A45D7E6A FOREIGN KEY (purchase_order_id) REFERENCES purchase_order (id)');
        $this->addSql('CREATE INDEX IDX_ED896F46A45D7E6A ON order_detail (purchase_order_id)');
        $this->addSql('ALTER TABLE user CHANGE full_name full_name VARCHAR(150) NOT NULL, CHANGE email email VARCHAR(180) NOT NULL, CHANGE phone_number phone_number VARCHAR(11) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_detail DROP FOREIGN KEY FK_ED896F46A45D7E6A');
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, sku VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, status VARCHAR(25) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, create_at DATETIME NOT NULL, update_at DATETIME DEFAULT NULL, payment_at DATETIME DEFAULT NULL, total_price BIGINT NOT NULL, delete_at DATETIME DEFAULT NULL, amount INT NOT NULL, INDEX IDX_F52993989395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993989395C3F3 FOREIGN KEY (customer_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('DROP TABLE purchase_order');
        $this->addSql('DROP INDEX IDX_ED896F46A45D7E6A ON order_detail');
        $this->addSql('ALTER TABLE order_detail CHANGE purchase_order_id order_id INT NOT NULL');
        $this->addSql('ALTER TABLE order_detail ADD CONSTRAINT FK_ED896F468D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_ED896F468D9F6D38 ON order_detail (order_id)');
        $this->addSql('DROP INDEX UNIQ_8D93D649E7927C74 ON user');
        $this->addSql('ALTER TABLE user CHANGE email email VARCHAR(150) NOT NULL, CHANGE full_name full_name VARCHAR(100) NOT NULL, CHANGE phone_number phone_number VARCHAR(20) NOT NULL');
    }
}
