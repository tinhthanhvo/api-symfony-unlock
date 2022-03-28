<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220326162429 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gallery DROP FOREIGN KEY FK_472B783AC3B649EE');
        $this->addSql('DROP INDEX IDX_472B783AC3B649EE ON gallery');
        $this->addSql('ALTER TABLE gallery CHANGE product_item_id product_id INT NOT NULL');
        $this->addSql('ALTER TABLE gallery ADD CONSTRAINT FK_472B783A4584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('CREATE INDEX IDX_472B783A4584665A ON gallery (product_id)');
        $this->addSql('ALTER TABLE product ADD color_id INT NOT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD7ADA1FB5 FOREIGN KEY (color_id) REFERENCES color (id)');
        $this->addSql('CREATE INDEX IDX_D34A04AD7ADA1FB5 ON product (color_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE gallery DROP FOREIGN KEY FK_472B783A4584665A');
        $this->addSql('DROP INDEX IDX_472B783A4584665A ON gallery');
        $this->addSql('ALTER TABLE gallery CHANGE product_id product_item_id INT NOT NULL');
        $this->addSql('ALTER TABLE gallery ADD CONSTRAINT FK_472B783AC3B649EE FOREIGN KEY (product_item_id) REFERENCES product_item (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_472B783AC3B649EE ON gallery (product_item_id)');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD7ADA1FB5');
        $this->addSql('DROP INDEX IDX_D34A04AD7ADA1FB5 ON product');
        $this->addSql('ALTER TABLE product DROP color_id');
    }
}
