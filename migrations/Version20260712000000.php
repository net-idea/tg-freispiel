<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial schema for a fresh installation, built with the portable DBAL
 * Schema API so it runs on MariaDB/MySQL, PostgreSQL and SQLite alike.
 * Form-related tables share the form_ prefix. The user table is quoted
 * because "user" is a reserved word on PostgreSQL.
 */
final class Version20260712000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema: form_* tables (contact, registration, booking, submission meta), user, date (with seed data) and messenger_messages';
    }

    public function isTransactional(): bool
    {
        // DDL causes implicit commits on MySQL/MariaDB
        return false;
    }

    public function up(Schema $schema): void
    {
        // Shared submission metadata, referenced by the form_* tables
        $meta = $schema->createTable('form_submission_meta');
        $meta->addOption('charset', 'utf8mb4');
        $meta->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $meta->addColumn('ip', Types::STRING, ['length' => 64, 'notnull' => false]);
        $meta->addColumn('user_agent', Types::STRING, ['length' => 400, 'notnull' => false]);
        $meta->addColumn('time', Types::STRING, ['length' => 40, 'notnull' => false]);
        $meta->addColumn('host', Types::STRING, ['length' => 200, 'notnull' => false]);
        $meta->addPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create());

        $contact = $schema->createTable('form_contact');
        $contact->addOption('charset', 'utf8mb4');
        $contact->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $contact->addColumn('name', Types::STRING, ['length' => 160]);
        $contact->addColumn('email_address', Types::STRING, ['length' => 200]);
        $contact->addColumn('phone', Types::STRING, ['length' => 40, 'notnull' => false]);
        $contact->addColumn('consent', Types::BOOLEAN, []);
        $contact->addColumn('message', Types::TEXT, []);
        $contact->addColumn('copy', Types::BOOLEAN, []);
        $contact->addColumn('created_at', Types::DATETIME_IMMUTABLE, []);
        $contact->addColumn('meta_id', Types::INTEGER, ['notnull' => false]);
        $contact->addPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create());
        $contact->addUniqueIndex(['meta_id']);
        $contact->addForeignKeyConstraint('form_submission_meta', ['meta_id'], ['id'], ['onDelete' => 'SET NULL']);

        $registration = $schema->createTable('form_registration');
        $registration->addOption('charset', 'utf8mb4');
        $registration->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $registration->addColumn('name', Types::STRING, ['length' => 160]);
        $registration->addColumn('email_address', Types::STRING, ['length' => 200]);
        $registration->addColumn('phone', Types::STRING, ['length' => 40]);
        $registration->addColumn('motivation', Types::TEXT, []);
        $registration->addColumn('role_types', Types::JSON, []);
        $registration->addColumn('role_reason', Types::TEXT, []);
        $registration->addColumn('expectations', Types::TEXT, []);
        $registration->addColumn('consent', Types::BOOLEAN, []);
        $registration->addColumn('copy', Types::BOOLEAN, []);
        $registration->addColumn('created_at', Types::DATETIME_IMMUTABLE, []);
        $registration->addColumn('meta_id', Types::INTEGER, ['notnull' => false]);
        $registration->addPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create());
        $registration->addUniqueIndex(['meta_id']);
        $registration->addForeignKeyConstraint('form_submission_meta', ['meta_id'], ['id'], ['onDelete' => 'SET NULL']);

        $booking = $schema->createTable('form_booking');
        $booking->addOption('charset', 'utf8mb4');
        $booking->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $booking->addColumn('email', Types::STRING, ['length' => 200]);
        $booking->addColumn('name', Types::STRING, ['length' => 160]);
        $booking->addColumn('confirmation_token', Types::STRING, ['length' => 64]);
        $booking->addColumn('created_at', Types::DATETIME_IMMUTABLE, []);
        $booking->addPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create());

        // Accounts for the upcoming admin area
        $user = $schema->createTable('`user`');
        $user->addOption('charset', 'utf8mb4');
        $user->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $user->addColumn('email', Types::STRING, ['length' => 200]);
        $user->addColumn('name', Types::STRING, ['length' => 160]);
        $user->addColumn('roles', Types::JSON, []);
        $user->addColumn('password', Types::STRING, ['length' => 255]);
        $user->addColumn('created_at', Types::DATETIME_IMMUTABLE, []);
        $user->addPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create());
        $user->addUniqueIndex(['email'], 'UNIQ_user_EMAIL');

        // Public dates shown on /termine
        $date = $schema->createTable('date');
        $date->addOption('charset', 'utf8mb4');
        $date->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $date->addColumn('title', Types::STRING, ['length' => 200]);
        $date->addColumn('description', Types::TEXT, ['notnull' => false]);
        $date->addColumn('starts_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $date->addColumn('recurrence', Types::STRING, ['length' => 200, 'notnull' => false]);
        $date->addColumn('active', Types::BOOLEAN, []);
        $date->addColumn('sort_order', Types::INTEGER, []);
        $date->addColumn('created_at', Types::DATETIME_IMMUTABLE, []);
        $date->addColumn('created_by_id', Types::INTEGER, ['notnull' => false]);
        $date->addPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create());
        $date->addIndex(['created_by_id']);
        $date->addForeignKeyConstraint('`user`', ['created_by_id'], ['id'], ['onDelete' => 'SET NULL']);

        $messenger = $schema->createTable('messenger_messages');
        $messenger->addOption('charset', 'utf8mb4');
        $messenger->addColumn('id', Types::BIGINT, ['autoincrement' => true]);
        $messenger->addColumn('body', Types::TEXT, []);
        $messenger->addColumn('headers', Types::TEXT, []);
        $messenger->addColumn('queue_name', Types::STRING, ['length' => 190]);
        $messenger->addColumn('created_at', Types::DATETIME_IMMUTABLE, []);
        $messenger->addColumn('available_at', Types::DATETIME_IMMUTABLE, []);
        $messenger->addColumn('delivered_at', Types::DATETIME_IMMUTABLE, ['notnull' => false]);
        $messenger->addPrimaryKeyConstraint(PrimaryKeyConstraint::editor()->setUnquotedColumnNames('id')->create());
        $messenger->addIndex(['queue_name', 'available_at', 'delivered_at', 'id']);

    }

    /**
     * Seed data lives in postUp because the DDL generated from the Schema
     * API is only executed after up() returns; parameters are converted
     * per platform by DBAL.
     */
    public function postUp(Schema $schema): void
    {
        $now = new \DateTimeImmutable();
        $seedTypes = [
            'title' => Types::STRING,
            'description' => Types::TEXT,
            'starts_at' => Types::DATETIME_IMMUTABLE,
            'recurrence' => Types::STRING,
            'active' => Types::BOOLEAN,
            'sort_order' => Types::INTEGER,
            'created_at' => Types::DATETIME_IMMUTABLE,
        ];
        $this->connection->insert('date', [
            'title' => 'Probestunde zum Reinschnuppern',
            'description' => 'Schnupper ganz unverbindlich rein – du lernst die Gruppe kennen und kannst direkt mitmachen. Alle sind herzlich willkommen, bitte melde dich vorher kurz an.',
            'starts_at' => new \DateTimeImmutable('2026-09-12 10:30:00'),
            'recurrence' => null,
            'active' => true,
            'sort_order' => 1,
            'created_at' => $now,
        ], $seedTypes);
        $this->connection->insert('date', [
            'title' => 'Proben',
            'description' => 'Unsere regelmäßigen Proben. Schau nach Absprache gerne einmal zu.',
            'starts_at' => null,
            'recurrence' => 'jeden Dienstag um 19 Uhr (ca. 2–3 Stunden)',
            'active' => true,
            'sort_order' => 2,
            'created_at' => $now,
        ], $seedTypes);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('date');
        $schema->dropTable('user');
        $schema->dropTable('form_booking');
        $schema->dropTable('form_registration');
        $schema->dropTable('form_contact');
        $schema->dropTable('form_submission_meta');
        $schema->dropTable('messenger_messages');
    }
}
