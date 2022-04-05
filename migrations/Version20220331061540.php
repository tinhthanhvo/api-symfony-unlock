<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220331061540 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cart (id INT AUTO_INCREMENT NOT NULL, product_item_id INT NOT NULL, user_id INT NOT NULL, amount INT NOT NULL, price BIGINT NOT NULL, create_at DATETIME NOT NULL, update_at DATETIME DEFAULT NULL, delete_at DATETIME DEFAULT NULL, INDEX IDX_BA388B7C3B649EE (product_item_id), INDEX IDX_BA388B7A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, name VARCHAR(50) NOT NULL, create_at DATETIME NOT NULL, update_at DATETIME DEFAULT NULL, delete_at DATETIME DEFAULT NULL, INDEX IDX_64C19C1727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE color (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, create_at DATETIME NOT NULL, update_at DATETIME DEFAULT NULL, delete_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE gallery (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, path VARCHAR(255) NOT NULL, create_at DATETIME NOT NULL, update_at DATETIME DEFAULT NULL, delete_at DATETIME DEFAULT NULL, INDEX IDX_472B783A4584665A (product_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, sku VARCHAR(50) NOT NULL, status VARCHAR(25) NOT NULL, create_at DATETIME NOT NULL, update_at DATETIME DEFAULT NULL, payment_at DATETIME DEFAULT NULL, total_price BIGINT NOT NULL, delete_at DATETIME DEFAULT NULL, amount INT NOT NULL, address_delivery LONGTEXT NOT NULL, recipient_name VARCHAR(150) NOT NULL, recipient_phone VARCHAR(11) NOT NULL, recipient_email VARCHAR(100) NOT NULL, INDEX IDX_F52993989395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE order_detail (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, product_item_id INT NOT NULL, amount INT NOT NULL, price BIGINT NOT NULL, create_at DATETIME NOT NULL, update_at DATETIME DEFAULT NULL, delete_at DATETIME DEFAULT NULL, INDEX IDX_ED896F468D9F6D38 (order_id), INDEX IDX_ED896F46C3B649EE (product_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, category_id INT NOT NULL, color_id INT NOT NULL, name VARCHAR(200) NOT NULL, description LONGTEXT DEFAULT NULL, price BIGINT NOT NULL, create_at DATETIME NOT NULL, update_at DATETIME DEFAULT NULL, delete_at DATETIME DEFAULT NULL, INDEX IDX_D34A04AD12469DE2 (category_id), INDEX IDX_D34A04AD7ADA1FB5 (color_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE product_item (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, size_id INT DEFAULT NULL, amount INT NOT NULL, create_at DATETIME NOT NULL, update_at DATETIME DEFAULT NULL, delete_at DATETIME DEFAULT NULL, INDEX IDX_92F307BF4584665A (product_id), INDEX IDX_92F307BF498DA827 (size_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE size (id INT AUTO_INCREMENT NOT NULL, value VARCHAR(10) NOT NULL, create_at DATETIME NOT NULL, update_at DATETIME DEFAULT NULL, delete_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, full_name VARCHAR(150) NOT NULL, phone_number VARCHAR(11) DEFAULT NULL, create_at DATETIME NOT NULL, update_at DATETIME DEFAULT NULL, delete_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT FK_BA388B7C3B649EE FOREIGN KEY (product_item_id) REFERENCES product_item (id)');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT FK_BA388B7A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C1727ACA70 FOREIGN KEY (parent_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE gallery ADD CONSTRAINT FK_472B783A4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F52993989395C3F3 FOREIGN KEY (customer_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE order_detail ADD CONSTRAINT FK_ED896F468D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE order_detail ADD CONSTRAINT FK_ED896F46C3B649EE FOREIGN KEY (product_item_id) REFERENCES product_item (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD7ADA1FB5 FOREIGN KEY (color_id) REFERENCES color (id)');
        $this->addSql('ALTER TABLE product_item ADD CONSTRAINT FK_92F307BF4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product_item ADD CONSTRAINT FK_92F307BF498DA827 FOREIGN KEY (size_id) REFERENCES size (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C1727ACA70');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD7ADA1FB5');
        $this->addSql('ALTER TABLE order_detail DROP FOREIGN KEY FK_ED896F468D9F6D38');
        $this->addSql('ALTER TABLE gallery DROP FOREIGN KEY FK_472B783A4584665A');
        $this->addSql('ALTER TABLE product_item DROP FOREIGN KEY FK_92F307BF4584665A');
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY FK_BA388B7C3B649EE');
        $this->addSql('ALTER TABLE order_detail DROP FOREIGN KEY FK_ED896F46C3B649EE');
        $this->addSql('ALTER TABLE product_item DROP FOREIGN KEY FK_92F307BF498DA827');
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY FK_BA388B7A76ED395');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F52993989395C3F3');
        $this->addSql('DROP TABLE cart');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE color');
        $this->addSql('DROP TABLE gallery');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE order_detail');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE product_item');
        $this->addSql('DROP TABLE size');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
