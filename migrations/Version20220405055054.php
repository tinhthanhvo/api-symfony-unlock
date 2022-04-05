<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220405055054 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_detail ADD CONSTRAINT FK_ED896F46A45D7E6A FOREIGN KEY (purchase_order_id) REFERENCES purchase_order (id)');
        $this->addSql('ALTER TABLE order_detail ADD CONSTRAINT FK_ED896F46C3B649EE FOREIGN KEY (product_item_id) REFERENCES product_item (id)');
        $this->addSql('ALTER TABLE order_detail RENAME INDEX idx_ed896f468d9f6d38 TO IDX_ED896F46A45D7E6A');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD7ADA1FB5 FOREIGN KEY (color_id) REFERENCES color (id)');
        $this->addSql('ALTER TABLE product_item ADD CONSTRAINT FK_92F307BF4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product_item ADD CONSTRAINT FK_92F307BF498DA827 FOREIGN KEY (size_id) REFERENCES size (id)');
        $this->addSql('ALTER TABLE purchase_order ADD CONSTRAINT FK_21E210B29395C3F3 FOREIGN KEY (customer_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE purchase_order RENAME INDEX idx_f52993989395c3f3 TO IDX_21E210B29395C3F3');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE order_detail DROP FOREIGN KEY FK_ED896F46A45D7E6A');
        $this->addSql('ALTER TABLE order_detail DROP FOREIGN KEY FK_ED896F46C3B649EE');
        $this->addSql('ALTER TABLE order_detail RENAME INDEX idx_ed896f46a45d7e6a TO IDX_ED896F468D9F6D38');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD7ADA1FB5');
        $this->addSql('ALTER TABLE product_item DROP FOREIGN KEY FK_92F307BF4584665A');
        $this->addSql('ALTER TABLE product_item DROP FOREIGN KEY FK_92F307BF498DA827');
        $this->addSql('ALTER TABLE purchase_order DROP FOREIGN KEY FK_21E210B29395C3F3');
        $this->addSql('ALTER TABLE purchase_order RENAME INDEX idx_21e210b29395c3f3 TO IDX_F52993989395C3F3');
    }
}
