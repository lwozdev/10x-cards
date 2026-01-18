<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add new fields to ai_jobs table for preview functionality.
 *
 * Purpose: Extends ai_jobs to store preview cards and statistics
 * Tables affected: ai_jobs
 * Changes:
 *   - Add cards JSONB column (stores preview cards before save)
 *   - Add generated_count INT (number of cards AI produced)
 *   - Add edited_count INT (number of cards user edited in preview)
 *   - Add deleted_count INT (number of cards user deleted in preview)
 *   - Add suggested_name TEXT (AI-suggested set name)
 */
final class Version20251028193900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add preview cards storage and statistics fields to ai_jobs table';
    }

    public function up(Schema $schema): void
    {
        // Add cards JSONB column for storing preview cards
        // Structure: [{ tmp_id: uuid, front: string, back: string, edited: bool, deleted: bool }]
        $this->addSql("
            ALTER TABLE ai_jobs
            ADD COLUMN cards JSONB NOT NULL DEFAULT '[]'::jsonb
        ");

        // Add generated_count: tracks how many cards AI produced (right after completion)
        // Includes CHECK constraint as per db-plan.md
        $this->addSql('
            ALTER TABLE ai_jobs
            ADD COLUMN generated_count INT NOT NULL DEFAULT 0
                CHECK (generated_count >= 0)
        ');

        // Add edited_count: tracks how many cards user edited in preview
        $this->addSql('
            ALTER TABLE ai_jobs
            ADD COLUMN edited_count INT NOT NULL DEFAULT 0
        ');

        // Add deleted_count: tracks how many cards user deleted in preview
        $this->addSql('
            ALTER TABLE ai_jobs
            ADD COLUMN deleted_count INT NOT NULL DEFAULT 0
        ');

        // Add suggested_name: AI-suggested name for the set
        $this->addSql('
            ALTER TABLE ai_jobs
            ADD COLUMN suggested_name TEXT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        // Remove added columns in reverse order
        $this->addSql('ALTER TABLE ai_jobs DROP COLUMN IF EXISTS suggested_name');
        $this->addSql('ALTER TABLE ai_jobs DROP COLUMN IF EXISTS deleted_count');
        $this->addSql('ALTER TABLE ai_jobs DROP COLUMN IF EXISTS edited_count');
        $this->addSql('ALTER TABLE ai_jobs DROP COLUMN IF EXISTS generated_count');
        $this->addSql('ALTER TABLE ai_jobs DROP COLUMN IF EXISTS cards');
    }
}
