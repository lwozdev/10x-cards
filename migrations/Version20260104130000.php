<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Add email verification support to User entity
 *
 * Changes:
 * - Add is_verified column to users table (default: false)
 *
 * Part of user registration backend implementation (auth-spec.md)
 */
final class Version20260104130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email verification support (is_verified column) to users table';
    }

    public function up(Schema $schema): void
    {
        // Add is_verified column (required for email verification)
        // Default: false - users must verify email before login
        $this->addSql('ALTER TABLE users ADD COLUMN IF NOT EXISTS is_verified BOOLEAN NOT NULL DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        // Remove is_verified column
        $this->addSql('ALTER TABLE users DROP COLUMN IF EXISTS is_verified');
    }
}
