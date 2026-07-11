<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260710190114 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sync schema with entities: create form_contact, form_booking, form_registration (Anmeldung zur Probestunde), form_submission_meta and messenger_messages; drop the legacy empty contact table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE form_booking (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(200) NOT NULL, name VARCHAR(160) NOT NULL, confirmation_token VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        // form_submission_meta first: form_contact and form_registration reference it inline
        $this->addSql('CREATE TABLE form_submission_meta (id INT AUTO_INCREMENT NOT NULL, ip VARCHAR(64) DEFAULT NULL, user_agent VARCHAR(400) DEFAULT NULL, time VARCHAR(40) DEFAULT NULL, host VARCHAR(200) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE form_contact (name VARCHAR(160) NOT NULL, email_address VARCHAR(200) NOT NULL, phone VARCHAR(40) DEFAULT NULL, consent TINYINT NOT NULL, message LONGTEXT NOT NULL, copy TINYINT NOT NULL, id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, meta_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_7D0E860339FCA6F9 (meta_id), PRIMARY KEY (id), CONSTRAINT FK_7D0E860339FCA6F9 FOREIGN KEY (meta_id) REFERENCES form_submission_meta (id) ON DELETE SET NULL) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE form_registration (name VARCHAR(160) NOT NULL, email_address VARCHAR(200) NOT NULL, phone VARCHAR(40) NOT NULL, motivation LONGTEXT NOT NULL, role_types JSON NOT NULL, role_reason LONGTEXT NOT NULL, expectations LONGTEXT NOT NULL, consent TINYINT NOT NULL, id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, meta_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_8EFECC539FCA6F9 (meta_id), PRIMARY KEY (id), CONSTRAINT FK_8EFECC539FCA6F9 FOREIGN KEY (meta_id) REFERENCES form_submission_meta (id) ON DELETE SET NULL) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('DROP TABLE contact');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, message LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE form_booking');
        $this->addSql('DROP TABLE form_contact');
        $this->addSql('DROP TABLE form_registration');
        $this->addSql('DROP TABLE form_submission_meta');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
