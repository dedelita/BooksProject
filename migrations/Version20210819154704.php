<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210819154704 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C4EAFAD8B');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C4EAFAD8B FOREIGN KEY (user_book_id) REFERENCES user_book (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526C4EAFAD8B');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526C4EAFAD8B FOREIGN KEY (user_book_id) REFERENCES user_book (id)');
    }
}
