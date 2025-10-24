<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial database schema migration for AI Flashcard Generator MVP
 *
 * Purpose: Creates complete database structure for flashcard application
 * Tables affected: users, sets, cards, review_states, review_events, ai_jobs, analytics_events
 * Special notes:
 *   - Enables Row Level Security (RLS) on all tables
 *   - Uses CITEXT for case-insensitive email/name fields
 *   - Implements soft delete pattern for sets and cards
 *   - Includes denormalization trigger for card_count in sets table
 */
final class Version20251024000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial database schema with users, flashcard sets, cards, review system, AI jobs tracking, and analytics';
    }

    public function up(Schema $schema): void
    {
        // ========================================
        // 1. ENABLE REQUIRED POSTGRESQL EXTENSIONS
        // ========================================

        // pgcrypto: provides gen_random_uuid() for UUID generation
        $this->addSql('create extension if not exists "pgcrypto"');

        // citext: case-insensitive text type for emails and names
        // enables unique constraints that ignore case without lower() functions
        $this->addSql('create extension if not exists "citext"');

        // pg_trgm: trigram-based text search (optional, for future search features)
        // enables fast LIKE queries and fuzzy matching on flashcard set names
        $this->addSql('create extension if not exists "pg_trgm"');

        // ========================================
        // 2. CREATE CUSTOM ENUM TYPES
        // ========================================

        // card_origin: tracks whether flashcard was created by AI or manually
        // used for analytics to measure AI adoption rate (target: 75%)
        $this->addSql("create type card_origin as enum ('ai', 'manual')");

        // ai_job_status: tracks lifecycle of AI generation requests
        // queued -> running -> succeeded/failed
        $this->addSql("create type ai_job_status as enum ('queued', 'running', 'succeeded', 'failed')");

        // ========================================
        // 3. CREATE HELPER FUNCTIONS FOR RLS
        // ========================================

        // current_app_user(): returns UUID of currently authenticated user
        // reads from PostgreSQL session variable set by application (Symfony)
        // usage: SET app.current_user_id = '<uuid>' at start of each request
        // this function is used in all RLS policies to enforce user ownership
        $this->addSql('
            create or replace function current_app_user() returns uuid language sql stable as $$
                select current_setting(\'app.current_user_id\', true)::uuid;
            $$
        ');

        // ========================================
        // 4. CREATE TABLES
        // ========================================

        // ----------------------------------------
        // 4.1. users table
        // ----------------------------------------
        // stores user authentication data
        // email is case-insensitive (citext) for user-friendly login
        // password_hash must be at least 60 chars (bcrypt/argon2 requirement)
        $this->addSql('
            create table users (
                id uuid primary key default gen_random_uuid(),
                email citext unique not null,
                password_hash text not null,
                created_at timestamptz not null default now(),
                last_login_at timestamptz null,

                -- ensure password hash is long enough for bcrypt/argon2
                constraint users_password_hash_length check (char_length(password_hash) >= 60)
            )
        ');

        // ----------------------------------------
        // 4.2. sets table (flashcard sets)
        // ----------------------------------------
        // groups flashcards into named collections owned by users
        // implements soft delete pattern (deleted_at column)
        // stores AI generation metadata for analytics
        // card_count is denormalized for fast listing (maintained by trigger)
        $this->addSql('
            create table sets (
                id uuid primary key default gen_random_uuid(),
                owner_id uuid not null,
                name citext not null,

                -- denormalized count for performance (updated by trigger)
                -- only counts active (non-deleted) cards
                card_count int not null default 0,

                -- ai generation metadata (null if created manually)
                generated_at timestamptz null,
                generated_model text null,
                generated_tokens_in int null,
                generated_tokens_out int null,

                -- soft delete and audit timestamps
                deleted_at timestamptz null,
                created_at timestamptz not null default now(),
                updated_at timestamptz not null default now(),

                -- foreign key to user who owns this set
                -- cascade delete: when user is deleted, all their sets are deleted
                constraint sets_owner_fk foreign key (owner_id)
                    references users(id) on delete cascade,

                -- set name must be unique per owner (case-insensitive via citext)
                -- allows different users to have sets with same name
                constraint sets_owner_name_unique unique (owner_id, name),

                -- set name cannot be empty string
                constraint sets_name_not_empty check (name <> \'\')
            )
        ');

        // ----------------------------------------
        // 4.3. cards table (flashcards)
        // ----------------------------------------
        // individual flashcards belonging to sets
        // front = question, back = answer (both max 1000 chars)
        // tracks origin (ai vs manual) for analytics
        // edited_by_user_at tracks if AI-generated card was modified (for quality metrics)
        // implements soft delete pattern
        $this->addSql('
            create table cards (
                id uuid primary key default gen_random_uuid(),
                set_id uuid not null,
                origin card_origin not null,
                front text not null,
                back text not null,

                -- timestamp when user manually edited this card (null if never edited)
                -- used to track if AI-generated cards needed corrections
                edited_by_user_at timestamptz null,

                -- soft delete and audit timestamps
                deleted_at timestamptz null,
                created_at timestamptz not null default now(),
                updated_at timestamptz not null default now(),

                -- foreign key to parent set
                -- cascade delete: when set is deleted, all its cards are deleted
                constraint cards_set_fk foreign key (set_id)
                    references sets(id) on delete cascade,

                -- card text length limits (student-friendly, concise flashcards)
                constraint cards_front_length check (char_length(front) <= 1000),
                constraint cards_back_length check (char_length(back) <= 1000)
            )
        ');

        // ----------------------------------------
        // 4.4. review_states table (spaced repetition state)
        // ----------------------------------------
        // stores spaced repetition algorithm state per user per card
        // composite primary key (user_id, card_id)
        // due_at: when card should be shown next
        // ease, interval_days, reps: spaced repetition algorithm parameters
        // last_grade: 0 = "Don't Know", 1 = "Know"
        $this->addSql('
            create table review_states (
                user_id uuid not null,
                card_id uuid not null,

                -- when this card is next due for review
                due_at timestamptz not null,

                -- spaced repetition algorithm parameters
                -- ease: difficulty factor (higher = easier, intervals grow faster)
                ease numeric(4,2) not null default 2.50,

                -- current interval in days between reviews
                interval_days int not null default 0,

                -- number of times this card has been reviewed
                reps int not null default 0,

                -- last user response: 0 = "Nie wiem" (don\'t know), 1 = "Wiem" (know)
                last_grade smallint null,

                -- last update timestamp for this review state
                updated_at timestamptz not null default now(),

                -- composite primary key: one review state per user per card
                primary key (user_id, card_id),

                -- foreign key to user
                -- cascade delete: when user deleted, their review progress is deleted
                constraint review_states_user_fk foreign key (user_id)
                    references users(id) on delete cascade,

                -- foreign key to card
                -- restrict delete: cannot delete card if it has review state
                -- protects against accidental data loss of learning progress
                constraint review_states_card_fk foreign key (card_id)
                    references cards(id) on delete restrict
            )
        ');

        // ----------------------------------------
        // 4.5. review_events table (review history log)
        // ----------------------------------------
        // immutable log of all review attempts for analytics
        // records every time user reviews a card with their response
        // duration_ms optional: how long user took to answer
        $this->addSql('
            create table review_events (
                id bigserial primary key,
                user_id uuid not null,

                -- nullable: card might be deleted later but we keep event history
                card_id uuid null,

                answered_at timestamptz not null default now(),

                -- user response: 0 = "Nie wiem", 1 = "Wiem"
                grade smallint not null,

                -- optional: response time in milliseconds (for future analytics)
                duration_ms int null,

                -- foreign key to user
                -- cascade delete: when user deleted, their review history is deleted
                constraint review_events_user_fk foreign key (user_id)
                    references users(id) on delete cascade,

                -- foreign key to card (nullable)
                -- set null on delete: keep event history even if card is deleted
                constraint review_events_card_fk foreign key (card_id)
                    references cards(id) on delete set null,

                -- grade must be 0 or 1 (binary know/don\'t know)
                constraint review_events_grade_valid check (grade in (0, 1))
            )
        ');

        // ----------------------------------------
        // 4.6. ai_jobs table (AI generation job tracking)
        // ----------------------------------------
        // tracks AI flashcard generation requests and responses
        // stores request/response for debugging and analytics
        // status lifecycle: queued -> running -> succeeded/failed
        $this->addSql('
            create table ai_jobs (
                id uuid primary key default gen_random_uuid(),
                user_id uuid not null,

                -- nullable: set_id might be null if job failed before set creation
                set_id uuid null,

                status ai_job_status not null,

                -- error message if status = failed
                error_message text null,

                -- user\'s input text (1000-10000 chars per PRD requirement)
                request_prompt text null,

                -- raw JSON response from AI API (OpenRouter.ai)
                response_raw jsonb null,

                -- AI model used (e.g. "gpt-4", "claude-3")
                model_name text null,

                -- token usage for cost tracking
                tokens_in int null,
                tokens_out int null,

                created_at timestamptz not null default now(),
                updated_at timestamptz not null default now(),
                completed_at timestamptz null,

                -- foreign key to user who requested generation
                -- cascade delete: when user deleted, their job history is deleted
                constraint ai_jobs_user_fk foreign key (user_id)
                    references users(id) on delete cascade,

                -- foreign key to resulting set (nullable)
                -- set null on delete: keep job history even if set is deleted
                constraint ai_jobs_set_fk foreign key (set_id)
                    references sets(id) on delete set null,

                -- validate request prompt length matches PRD requirements
                constraint ai_jobs_prompt_length check (
                    request_prompt is null or
                    char_length(request_prompt) between 1000 and 10000
                )
            )
        ');

        // ----------------------------------------
        // 4.7. analytics_events table (event tracking)
        // ----------------------------------------
        // generic event log for business metrics and user behavior analytics
        // event_type examples: "fiszka_usunięta_w_edycji", "set_created", etc.
        // payload: flexible JSONB for event-specific data
        $this->addSql('
            create table analytics_events (
                id bigserial primary key,
                event_type text not null,
                user_id uuid not null,

                -- optional context: which set/card the event relates to
                set_id uuid null,
                card_id uuid null,

                -- flexible event data as JSON object
                payload jsonb not null default \'{}\'::jsonb,

                occurred_at timestamptz not null default now(),

                -- foreign key to user
                -- cascade delete: when user deleted, their analytics are deleted
                constraint analytics_events_user_fk foreign key (user_id)
                    references users(id) on delete cascade,

                -- foreign key to set (nullable)
                -- set null on delete: keep event history even if set deleted
                constraint analytics_events_set_fk foreign key (set_id)
                    references sets(id) on delete set null,

                -- foreign key to card (nullable)
                -- set null on delete: keep event history even if card deleted
                constraint analytics_events_card_fk foreign key (card_id)
                    references cards(id) on delete set null,

                -- ensure payload is always a JSON object, not array or primitive
                constraint analytics_events_payload_is_object check (
                    jsonb_typeof(payload) = \'object\'
                )
            )
        ');

        // ========================================
        // 5. CREATE INDEXES FOR PERFORMANCE
        // ========================================

        // ----------------------------------------
        // 5.1. users indexes
        // ----------------------------------------
        // unique index on email for login lookups (already created via unique constraint)
        $this->addSql('create unique index users_email_unique on users (email)');

        // ----------------------------------------
        // 5.2. sets indexes
        // ----------------------------------------
        // composite index for "My Sets" listing with soft delete filter
        // supports query: where owner_id = ? and deleted_at is null order by updated_at desc
        $this->addSql('create index sets_owner_listing on sets (owner_id, deleted_at)');

        // partial index for ordering by most recently updated (excludes deleted)
        // optimizes "My Sets" page with recency sorting
        $this->addSql('
            create index sets_owner_updated_at
            on sets (owner_id, updated_at desc)
            where deleted_at is null
        ');

        // trigram gin index for fast fuzzy search by set name (optional, requires pg_trgm)
        // enables fast LIKE queries: where name like \'%search%\'
        $this->addSql('
            create index sets_name_trgm
            on sets using gin (name gin_trgm_ops)
            where deleted_at is null
        ');

        // ----------------------------------------
        // 5.3. cards indexes
        // ----------------------------------------
        // index for listing active cards in a set (excludes soft-deleted)
        // supports query: where set_id = ? and deleted_at is null
        $this->addSql('
            create index cards_set_active
            on cards (set_id)
            where deleted_at is null
        ');

        // index for ordering cards by most recently updated within set
        // supports card editing interface with recency sorting
        $this->addSql('
            create index cards_set_updated
            on cards (set_id, updated_at desc)
            where deleted_at is null
        ');

        // ----------------------------------------
        // 5.4. review_states indexes
        // ----------------------------------------
        // composite index for finding next card due for review
        // critical for learning session query: where user_id = ? and due_at <= now() order by due_at
        $this->addSql('create index review_states_due on review_states (user_id, due_at)');

        // ----------------------------------------
        // 5.5. review_events indexes
        // ----------------------------------------
        // index for user review history ordered by time (for analytics dashboard)
        // supports query: where user_id = ? order by answered_at desc
        $this->addSql('create index review_events_user_time on review_events (user_id, answered_at desc)');

        // index for per-card review history (for card difficulty analytics)
        // supports query: where card_id = ? order by answered_at
        $this->addSql('create index review_events_card_time on review_events (card_id, answered_at)');

        // ----------------------------------------
        // 5.6. ai_jobs indexes
        // ----------------------------------------
        // index for user\'s AI job history ordered by time
        // supports "My AI Generations" page
        $this->addSql('create index ai_jobs_user_time on ai_jobs (user_id, created_at desc)');

        // index for background job processing queue
        // supports query: where status = \'queued\' order by created_at
        $this->addSql('create index ai_jobs_status_time on ai_jobs (status, created_at)');

        // index for finding jobs related to a set
        $this->addSql('create index ai_jobs_set on ai_jobs (set_id)');

        // gin index for searching within JSON responses (optional, for debugging)
        // enables queries like: where response_raw @> \'{"key": "value"}\'
        $this->addSql('create index ai_jobs_response_gin on ai_jobs using gin (response_raw)');

        // ----------------------------------------
        // 5.7. analytics_events indexes
        // ----------------------------------------
        // index for user analytics history ordered by time
        // supports analytics dashboard queries
        $this->addSql('create index analytics_user_time on analytics_events (user_id, occurred_at desc)');

        // ========================================
        // 6. ENABLE ROW LEVEL SECURITY (RLS)
        // ========================================

        // enable RLS on all tables to enforce multi-tenant data isolation
        // users can only access their own data through RLS policies
        // application must set app.current_user_id session variable

        $this->addSql('alter table users enable row level security');
        $this->addSql('alter table sets enable row level security');
        $this->addSql('alter table cards enable row level security');
        $this->addSql('alter table review_states enable row level security');
        $this->addSql('alter table review_events enable row level security');
        $this->addSql('alter table ai_jobs enable row level security');
        $this->addSql('alter table analytics_events enable row level security');

        // ========================================
        // 7. CREATE RLS POLICIES
        // ========================================
        // policies are granular: separate policy for each operation (select/insert/update/delete)
        // this allows fine-grained control and easier auditing

        // ----------------------------------------
        // 7.1. users table policies
        // ----------------------------------------

        // users_select: users can only select their own user record
        // used for profile page, account settings
        $this->addSql('
            create policy users_select on users
                for select
                using (id = current_app_user())
        ');

        // users_update: users can only update their own user record
        // used for profile updates, password changes
        $this->addSql('
            create policy users_update on users
                for update
                using (id = current_app_user())
                with check (id = current_app_user())
        ');

        // ----------------------------------------
        // 7.2. sets table policies
        // ----------------------------------------

        // sets_select: users can only select their own sets that are not soft-deleted
        // enforces ownership and filters soft-deleted sets
        $this->addSql('
            create policy sets_select on sets
                for select
                using (owner_id = current_app_user() and deleted_at is null)
        ');

        // sets_insert: users can only insert sets where they are the owner
        // prevents users from creating sets for other users
        $this->addSql('
            create policy sets_insert on sets
                for insert
                with check (owner_id = current_app_user())
        ');

        // sets_update: users can only update their own non-deleted sets
        // cannot change ownership to another user
        $this->addSql('
            create policy sets_update on sets
                for update
                using (owner_id = current_app_user() and deleted_at is null)
                with check (owner_id = current_app_user())
        ');

        // sets_delete: users can only delete their own sets
        // note: this is hard delete; soft delete uses update
        $this->addSql('
            create policy sets_delete on sets
                for delete
                using (owner_id = current_app_user())
        ');

        // ----------------------------------------
        // 7.3. cards table policies
        // ----------------------------------------

        // cards_select: users can select cards belonging to their own non-deleted sets
        // checks both card and parent set are not soft-deleted
        // joins to sets table to verify ownership
        $this->addSql('
            create policy cards_select on cards
                for select
                using (
                    deleted_at is null and
                    exists (
                        select 1 from sets s
                        where s.id = cards.set_id
                          and s.owner_id = current_app_user()
                          and s.deleted_at is null
                    )
                )
        ');

        // cards_insert: users can only insert cards into their own non-deleted sets
        // prevents users from adding cards to other users\' sets
        $this->addSql('
            create policy cards_insert on cards
                for insert
                with check (
                    exists (
                        select 1 from sets s
                        where s.id = cards.set_id
                          and s.owner_id = current_app_user()
                          and s.deleted_at is null
                    )
                )
        ');

        // cards_update: users can only update cards in their own non-deleted sets
        // cannot move cards between sets (set_id is checked)
        $this->addSql('
            create policy cards_update on cards
                for update
                using (
                    deleted_at is null and
                    exists (
                        select 1 from sets s
                        where s.id = cards.set_id
                          and s.owner_id = current_app_user()
                          and s.deleted_at is null
                    )
                )
                with check (
                    exists (
                        select 1 from sets s
                        where s.id = cards.set_id
                          and s.owner_id = current_app_user()
                          and s.deleted_at is null
                    )
                )
        ');

        // cards_delete: users can only delete cards from their own sets
        // note: this is hard delete; soft delete uses update
        $this->addSql('
            create policy cards_delete on cards
                for delete
                using (
                    exists (
                        select 1 from sets s
                        where s.id = cards.set_id
                          and s.owner_id = current_app_user()
                    )
                )
        ');

        // ----------------------------------------
        // 7.4. review_states table policies
        // ----------------------------------------

        // review_states_select: users can only select their own review states
        // used in learning session to fetch next card due for review
        $this->addSql('
            create policy review_states_select on review_states
                for select
                using (user_id = current_app_user())
        ');

        // review_states_insert: users can only create review states for themselves
        // prevents users from manipulating other users\' learning progress
        $this->addSql('
            create policy review_states_insert on review_states
                for insert
                with check (user_id = current_app_user())
        ');

        // review_states_update: users can only update their own review states
        // ensures users cannot modify others\' learning progress
        $this->addSql('
            create policy review_states_update on review_states
                for update
                using (user_id = current_app_user())
                with check (user_id = current_app_user())
        ');

        // review_states_delete: users can only delete their own review states
        // allows users to reset their learning progress for specific cards
        $this->addSql('
            create policy review_states_delete on review_states
                for delete
                using (user_id = current_app_user())
        ');

        // ----------------------------------------
        // 7.5. review_events table policies
        // ----------------------------------------

        // review_events_select: users can only select their own review events
        // used for personal learning analytics and progress tracking
        $this->addSql('
            create policy review_events_select on review_events
                for select
                using (user_id = current_app_user())
        ');

        // review_events_insert: users can only create review events for themselves
        // logs each review attempt with user_id from session
        $this->addSql('
            create policy review_events_insert on review_events
                for insert
                with check (user_id = current_app_user())
        ');

        // ----------------------------------------
        // 7.6. ai_jobs table policies
        // ----------------------------------------

        // ai_jobs_select: users can only select their own AI generation jobs
        // used for "My Generations" history page
        $this->addSql('
            create policy ai_jobs_select on ai_jobs
                for select
                using (user_id = current_app_user())
        ');

        // ai_jobs_insert: users can only create AI jobs for themselves
        // prevents users from triggering jobs for other users (cost/quota abuse)
        $this->addSql('
            create policy ai_jobs_insert on ai_jobs
                for insert
                with check (user_id = current_app_user())
        ');

        // ai_jobs_update: users can only update their own AI jobs
        // background worker updates job status using app user context
        $this->addSql('
            create policy ai_jobs_update on ai_jobs
                for update
                using (user_id = current_app_user())
                with check (user_id = current_app_user())
        ');

        // ----------------------------------------
        // 7.7. analytics_events table policies
        // ----------------------------------------

        // analytics_events_select: users can only select their own analytics events
        // used for personal analytics dashboard
        $this->addSql('
            create policy analytics_events_select on analytics_events
                for select
                using (user_id = current_app_user())
        ');

        // analytics_events_insert: users can only create analytics events for themselves
        // application tracks events with user_id from session
        $this->addSql('
            create policy analytics_events_insert on analytics_events
                for insert
                with check (user_id = current_app_user())
        ');

        // ========================================
        // 8. CREATE TRIGGERS FOR DENORMALIZATION
        // ========================================

        // trigger function to maintain card_count in sets table
        // increments on card insert, decrements on soft delete (deleted_at set)
        // only counts active (non-deleted) cards

        $this->addSql('
            create or replace function maintain_set_card_count() returns trigger as $$
            begin
                -- handle INSERT: increment card_count if card is not deleted
                if (tg_op = \'INSERT\') then
                    if new.deleted_at is null then
                        update sets
                        set card_count = card_count + 1,
                            updated_at = now()
                        where id = new.set_id;
                    end if;
                    return new;
                end if;

                -- handle UPDATE: adjust card_count if deleted_at changes
                if (tg_op = \'UPDATE\') then
                    -- card was soft-deleted (active -> deleted)
                    if old.deleted_at is null and new.deleted_at is not null then
                        update sets
                        set card_count = card_count - 1,
                            updated_at = now()
                        where id = new.set_id;
                    end if;

                    -- card was restored (deleted -> active)
                    if old.deleted_at is not null and new.deleted_at is null then
                        update sets
                        set card_count = card_count + 1,
                            updated_at = now()
                        where id = new.set_id;
                    end if;

                    return new;
                end if;

                -- handle DELETE: decrement card_count if card was active
                -- WARNING: this is for hard delete, soft delete uses UPDATE above
                if (tg_op = \'DELETE\') then
                    if old.deleted_at is null then
                        update sets
                        set card_count = card_count - 1,
                            updated_at = now()
                        where id = old.set_id;
                    end if;
                    return old;
                end if;

                return null;
            end;
            $$ language plpgsql
        ');

        // attach trigger to cards table for all relevant operations
        $this->addSql('
            create trigger maintain_set_card_count_trigger
            after insert or update or delete on cards
            for each row
            execute function maintain_set_card_count()
        ');
    }

    public function down(Schema $schema): void
    {
        // ========================================
        // ROLLBACK: DROP ALL OBJECTS IN REVERSE ORDER
        // ========================================
        // WARNING: this will destroy all data in the database
        // only use in development or if absolutely necessary

        // drop triggers first
        $this->addSql('drop trigger if exists maintain_set_card_count_trigger on cards');
        $this->addSql('drop function if exists maintain_set_card_count()');

        // drop tables (foreign keys will cascade)
        $this->addSql('drop table if exists analytics_events cascade');
        $this->addSql('drop table if exists ai_jobs cascade');
        $this->addSql('drop table if exists review_events cascade');
        $this->addSql('drop table if exists review_states cascade');
        $this->addSql('drop table if exists cards cascade');
        $this->addSql('drop table if exists sets cascade');
        $this->addSql('drop table if exists users cascade');

        // drop helper function
        $this->addSql('drop function if exists current_app_user()');

        // drop custom types
        $this->addSql('drop type if exists ai_job_status');
        $this->addSql('drop type if exists card_origin');

        // drop extensions (only if no other database objects use them)
        // commented out to avoid breaking other schemas that might use these
        // $this->addSql('drop extension if exists pg_trgm');
        // $this->addSql('drop extension if exists citext');
        // $this->addSql('drop extension if exists pgcrypto');
    }
}