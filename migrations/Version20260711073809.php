<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260711073809 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user (future admin area) and termin tables; seed the initial Termine (Probestunde 12.09.2026, weekly Proben)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(200) NOT NULL, name VARCHAR(160) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_user_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        // FK inline instead of a separate ALTER TABLE (avoids a MariaDB crash on macOS bind mounts)
        $this->addSql('CREATE TABLE termin (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(200) NOT NULL, description LONGTEXT DEFAULT NULL, starts_at DATETIME DEFAULT NULL, recurrence VARCHAR(200) DEFAULT NULL, active TINYINT NOT NULL, sort_order INT NOT NULL, created_at DATETIME NOT NULL, created_by_id INT DEFAULT NULL, INDEX IDX_EFAFBA9CB03A8386 (created_by_id), PRIMARY KEY (id), CONSTRAINT FK_EFAFBA9CB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON DELETE SET NULL) DEFAULT CHARACTER SET utf8mb4');

        // Initial content
        $this->addSql(<<<'SQL'
            INSERT INTO termin (title, description, starts_at, recurrence, active, sort_order, created_at) VALUES
            ('Probestunde zum Reinschnuppern', 'Schnupper ganz unverbindlich rein – du lernst die Gruppe kennen und kannst direkt mitmachen. Alle sind herzlich willkommen, bitte melde dich vorher kurz an.', '2026-09-12 10:30:00', NULL, 1, 1, NOW()),
            ('Proben', 'Unsere regelmäßigen Proben. Schau nach Absprache gerne einmal zu.', NULL, 'jeden Dienstag um 19 Uhr (ca. 2–3 Stunden)', 1, 2, NOW())
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE termin');
        $this->addSql('DROP TABLE user');
    }
}
