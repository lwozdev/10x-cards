<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Refactor ai_jobs for synchronous generation (no async queue)
 *
 * Purpose: Remove server-side preview storage, simplify to KPI tracking only
 * Tables affected: ai_jobs
 * Changes:
 *   - Remove cards JSONB column (preview managed client-side)
 *   - Remove deleted_count (calculated as generated_count - accepted_count)
 *   - Rename edited_count to accepted_count (tracks cards saved by user)
 *   - Add accepted_count INT (number of cards user saved)
 *   - Update ai_job_status ENUM to only 'succeeded' and 'failed' (no queuing)
 *
 * Migration strategy for ENUM:
 *   - Delete all rows with status 'queued' or 'running' (stale async jobs)
 *   - Create new ENUM type with only 'succeeded' and 'failed'
 *   - Alter column to use new type
 *   - Drop old ENUM type
 */
final class Version20251102160547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refactor ai_jobs for synchronous generation - remove preview storage, update status ENUM';
    }

    public function up(Schema $schema): void
    {
        // 1. Clean up any queued/running jobs (from old async system)
        //    In production, verify there are no important running jobs first!
        $this->addSql("DELETE FROM ai_jobs WHERE status IN ('queued', 'running')");

        // 2. Create new ENUM type with only synchronous statuses
        $this->addSql("CREATE TYPE ai_job_status_new AS ENUM ('succeeded', 'failed')");

        // 3. Alter status column to use new type
        //    Cast existing values (succeeded/failed already exist in new type)
        $this->addSql("
            ALTER TABLE ai_jobs
            ALTER COLUMN status TYPE ai_job_status_new
            USING status::text::ai_job_status_new
        ");

        // 4. Drop old ENUM type and rename new one
        $this->addSql("DROP TYPE ai_job_status");
        $this->addSql("ALTER TYPE ai_job_status_new RENAME TO ai_job_status");

        // 5. Remove preview-related columns (client-side preview now)
        $this->addSql('ALTER TABLE ai_jobs DROP COLUMN IF EXISTS cards');

        // 6. Remove deleted_count (calculated as generated_count - accepted_count)
        $this->addSql('ALTER TABLE ai_jobs DROP COLUMN IF EXISTS deleted_count');

        // 7. Add accepted_count (replaces edited_count with different semantics)
        //    accepted_count = how many cards user saved (regardless of edits)
        $this->addSql("
            ALTER TABLE ai_jobs
            ADD COLUMN accepted_count INT NOT NULL DEFAULT 0
                CHECK (accepted_count >= 0)
        ");

        // 8. Keep edited_count but update its meaning
        //    edited_count = how many of the SAVED cards were edited before saving
        //    (filled when user calls POST /api/sets with edited flag)
        // Note: No migration of old data - old edited_count tracked preview edits,
        //       new edited_count tracks final saved edits (different concept)
    }

    public function down(Schema $schema): void
    {
        // Reverse order of operations

        // 1. Remove accepted_count
        $this->addSql('ALTER TABLE ai_jobs DROP COLUMN IF EXISTS accepted_count');

        // 2. Re-add deleted_count
        $this->addSql('ALTER TABLE ai_jobs ADD COLUMN deleted_count INT NOT NULL DEFAULT 0');

        // 3. Re-add cards JSONB column
        $this->addSql("ALTER TABLE ai_jobs ADD COLUMN cards JSONB NOT NULL DEFAULT '[]'::jsonb");

        // 4. Recreate old ENUM type with queue statuses
        $this->addSql("CREATE TYPE ai_job_status_new AS ENUM ('queued', 'running', 'succeeded', 'failed')");

        // 5. Alter status column back to old type
        $this->addSql("
            ALTER TABLE ai_jobs
            ALTER COLUMN status TYPE ai_job_status_new
            USING status::text::ai_job_status_new
        ");

        // 6. Drop current type and rename
        $this->addSql("DROP TYPE ai_job_status");
        $this->addSql("ALTER TYPE ai_job_status_new RENAME TO ai_job_status");
    }
}
