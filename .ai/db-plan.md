# Schemat bazy danych (PostgreSQL) — Generator Fiszek AI (MVP)

## 1. Lista tabel (kolumny, typy, ograniczenia)

### 1.1. `users`
- `id` UUID **PK** `DEFAULT gen_random_uuid()`
- `email` **CITEXT** **UNIQUE NOT NULL**
- `password_hash` TEXT **NOT NULL**
- `created_at` TIMESTAMPTZ **NOT NULL DEFAULT now()**
- `last_login_at` TIMESTAMPTZ **NULL**
- Ograniczenia:
    - `CHECK (char_length(password_hash) >= 60)` — hash np. bcrypt/argon2.

---

### 1.2. `sets`
- `id` UUID **PK** `DEFAULT gen_random_uuid()`
- `owner_id` UUID **NOT NULL** **FK → users(id) ON DELETE CASCADE**
- `name` **CITEXT** **NOT NULL**
- `card_count` INT **NOT NULL DEFAULT 0** — denormalizacja do szybkiego listowania.
- **Metadane generowania (minimalne):**
    - `generated_at` TIMESTAMPTZ **NULL**
    - `generated_model` TEXT **NULL**
    - `generated_tokens_in` INT **NULL**
    - `generated_tokens_out` INT **NULL**
- **Soft delete & audyt:**
    - `deleted_at` TIMESTAMPTZ **NULL**
    - `created_at` TIMESTAMPTZ **NOT NULL DEFAULT now()`
    - `updated_at` TIMESTAMPTZ **NOT NULL DEFAULT now()`
- Ograniczenia:
    - **UNIQUE** `(owner_id, name)` — unikalna nazwa zestawu w obrębie właściciela (case-insensitive dzięki `citext`).
    - `CHECK (name <> '')`.

---

### 1.3. `cards`
- `id` UUID **PK** `DEFAULT gen_random_uuid()`
- `set_id` UUID **NOT NULL** **FK → sets(id) ON DELETE CASCADE**
- `origin` **ENUM** `'ai' | 'manual'` **NOT NULL**
- `front` TEXT **NOT NULL**
- `back` TEXT **NOT NULL**
- `edited_by_user_at` TIMESTAMPTZ **NULL**
- **Soft delete & audyt:**
    - `deleted_at` TIMESTAMPTZ **NULL**
    - `created_at` TIMESTAMPTZ **NOT NULL DEFAULT now()`
    - `updated_at` TIMESTAMPTZ **NOT NULL DEFAULT now()`
- Ograniczenia:
    - `CHECK (char_length(front) <= 1000)`
    - `CHECK (char_length(back)  <= 1000)`

---

### 1.4. `review_states`
- `user_id` UUID **NOT NULL** **FK → users(id) ON DELETE CASCADE**
- `card_id` UUID **NOT NULL** **FK → cards(id) ON DELETE RESTRICT**
- `due_at` TIMESTAMPTZ **NOT NULL**
- `ease` NUMERIC(4,2) **NOT NULL DEFAULT 2.50**
- `interval_days` INT **NOT NULL DEFAULT 0**
- `reps` INT **NOT NULL DEFAULT 0**
- `last_grade` SMALLINT **NULL**  — 0 = „Nie wiem”, 1 = „Wiem”
- `updated_at` TIMESTAMPTZ **NOT NULL DEFAULT now()`
- Klucz główny: **PRIMARY KEY (`user_id`, `card_id`)**

---

### 1.5. `review_events`
- `id` BIGSERIAL **PK**
- `user_id` UUID **NOT NULL** **FK → users(id) ON DELETE CASCADE**
- `card_id` UUID **NULL** **FK → cards(id) ON DELETE SET NULL**
- `answered_at` TIMESTAMPTZ **NOT NULL DEFAULT now()`
- `grade` SMALLINT **NOT NULL** `CHECK (grade IN (0,1))`
- `duration_ms` INT **NULL** — opcjonalnie czas odpowiedzi.
- Indeks czasowy i po karcie dodany w sekcji indeksów.

---

### 1.6. `ai_jobs`
- `id` UUID **PK** `DEFAULT gen_random_uuid()`
- `user_id` UUID **NOT NULL** **FK → users(id) ON DELETE CASCADE**
- `set_id` UUID **NULL** **FK → sets(id) ON DELETE SET NULL**
- `status` **ENUM** `'queued' | 'running' | 'succeeded' | 'failed'` **NOT NULL**
- `error_message` TEXT **NULL**
- `request_prompt` TEXT **NULL** `CHECK (request_prompt IS NULL OR char_length(request_prompt) BETWEEN 1000 AND 10000)`
- `response_raw` JSONB **NULL**
- `model_name` TEXT **NULL**
- `tokens_in` INT **NULL**
- `tokens_out` INT **NULL**
- `created_at` TIMESTAMPTZ **NOT NULL DEFAULT now()`
- `updated_at` TIMESTAMPTZ **NOT NULL DEFAULT now()`
- `completed_at` TIMESTAMPTZ **NULL**

---

### 1.7. `analytics_events`
- `id` BIGSERIAL **PK**
- `event_type` TEXT **NOT NULL**
- `user_id` UUID **NOT NULL** **FK → users(id) ON DELETE CASCADE**
- `set_id` UUID **NULL** **FK → sets(id) ON DELETE SET NULL**
- `card_id` UUID **NULL** **FK → cards(id) ON DELETE SET NULL**
- `payload` JSONB **NOT NULL DEFAULT '{}'::jsonb**
- `occurred_at` TIMESTAMPTZ **NOT NULL DEFAULT now()`
- Ograniczenia:
    - `CHECK (jsonb_typeof(payload) = 'object')`

---

## 2. Relacje między tabelami
- **users (1) — (N) sets**: `sets.owner_id` → `users.id`
- **sets (1) — (N) cards**: `cards.set_id` → `sets.id`
- **users (1) — (N) review_states**: `review_states.user_id` → `users.id`
- **cards (1) — (N) review_states**: `review_states.card_id` → `cards.id` (RESTRICT)
- **users (1) — (N) review_events**: `review_events.user_id` → `users.id`
- **cards (1) — (N) review_events**: `review_events.card_id` → `cards.id` (SET NULL)
- **users (1) — (N) ai_jobs**: `ai_jobs.user_id` → `users.id`
- **sets (1) — (N) ai_jobs**: `ai_jobs.set_id` → `sets.id` (SET NULL)
- **users (1) — (N) analytics_events**: `analytics_events.user_id` → `users.id`
- **sets/cards (1) — (N) analytics_events**: opcjonalne FK do kontekstu zdarzenia

Kardynalności: wszystkie powyższe to **1:N**; relacji **M:N** brak w MVP.

---

## 3. Indeksy (wydajność i filtry soft-delete)
**Wymagane rozszerzenia**: `pgcrypto` (UUID: `gen_random_uuid()`), `citext` (unikalność case-insensitive), **opcjonalnie** `pg_trgm` (wyszukiwanie po nazwie zestawu).

### 3.1. `users`
- `UNIQUE INDEX users_email_unique ON users (email)`

### 3.2. `sets`
- `INDEX sets_owner_listing ON sets (owner_id, deleted_at)` — listowanie „Moje zestawy” z filtrem soft-delete.
- `INDEX sets_owner_updated_at ON sets (owner_id, updated_at DESC) WHERE deleted_at IS NULL`
- **Opcjonalnie** (wymaga `pg_trgm`):
    - `GIN INDEX sets_name_trgm ON sets USING gin (name gin_trgm_ops) WHERE deleted_at IS NULL`
- `UNIQUE (owner_id, name)` (case-insensitive dzięki `CITEXT`).

### 3.3. `cards`
- `INDEX cards_set_active ON cards (set_id) WHERE deleted_at IS NULL`
- `INDEX cards_set_updated ON cards (set_id, updated_at DESC) WHERE deleted_at IS NULL`

### 3.4. `review_states`
- `PRIMARY KEY (user_id, card_id)`
- `INDEX review_states_due ON review_states (user_id, due_at)` — wybór kolejnej karty do nauki.

### 3.5. `review_events`
- `INDEX review_events_user_time ON review_events (user_id, answered_at DESC)`
- `INDEX review_events_card_time ON review_events (card_id, answered_at)`

### 3.6. `ai_jobs`
- `INDEX ai_jobs_user_time ON ai_jobs (user_id, created_at DESC)`
- `INDEX ai_jobs_status_time ON ai_jobs (status, created_at)`
- `INDEX ai_jobs_set ON ai_jobs (set_id)`
- **Opcjonalnie**: `GIN INDEX ai_jobs_response_gin ON ai_jobs USING gin (response_raw)`

### 3.7. `analytics_events`
- `INDEX analytics_user_time ON analytics_events (user_id, occurred_at DESC)`
- **Opcjonalne partycjonowanie**: miesięczne po `occurred_at` po przekroczeniu progu wolumenu.

---

## 4. Zasady PostgreSQL (RLS, funkcje, typy)

### 4.1. Typy ENUM
- `CREATE TYPE card_origin AS ENUM ('ai', 'manual');`
- `CREATE TYPE ai_job_status AS ENUM ('queued','running','succeeded','failed');`

### 4.2. Funkcje pomocnicze
- `current_app_user()` → `UUID`
  ```sql
  CREATE OR REPLACE FUNCTION current_app_user() RETURNS uuid LANGUAGE sql AS $$
    SELECT current_setting('app.current_user_id', true)::uuid;
  $$;
  ```
- **Uwaga**: Aplikacja (Symfony/Doctrine) musi ustawiać `SET app.current_user_id = '<uuid>';` na początku żądania/transakcji.

### 4.3. Włączenie RLS
- `ALTER TABLE users ENABLE ROW LEVEL SECURITY;`
- `ALTER TABLE sets ENABLE ROW LEVEL SECURITY;`
- `ALTER TABLE cards ENABLE ROW LEVEL SECURITY;`
- `ALTER TABLE review_states ENABLE ROW LEVEL SECURITY;`
- `ALTER TABLE review_events ENABLE ROW LEVEL SECURITY;`
- `ALTER TABLE analytics_events ENABLE ROW LEVEL SECURITY;`
- `ALTER TABLE ai_jobs ENABLE ROW LEVEL SECURITY;`

### 4.4. Polityki RLS

**users** (dostęp tylko do własnego wiersza):
```sql
CREATE POLICY users_self_select ON users
  FOR SELECT USING (id = current_app_user());
```
*(Modyfikacje użytkownika realizowane aplikacyjnie; alternatywnie dodać analogiczne `UPDATE`/`DELETE` policy.)*

**sets** (własność + soft-delete):
```sql
CREATE POLICY sets_is_owner ON sets
  USING (owner_id = current_app_user() AND deleted_at IS NULL)
  WITH CHECK (owner_id = current_app_user());
```

**cards** (przez przynależność do zestawu + soft-delete):
```sql
CREATE POLICY cards_in_owned_sets ON cards
  USING (
    deleted_at IS NULL AND
    EXISTS (SELECT 1 FROM sets s
            WHERE s.id = cards.set_id
              AND s.owner_id = current_app_user()
              AND s.deleted_at IS NULL)
  )
  WITH CHECK (
    EXISTS (SELECT 1 FROM sets s
            WHERE s.id = cards.set_id
              AND s.owner_id = current_app_user()
              AND s.deleted_at IS NULL)
  );
```

**review_states** (po użytkowniku):
```sql
CREATE POLICY review_states_by_user ON review_states
  USING (user_id = current_app_user())
  WITH CHECK (user_id = current_app_user());
```

**review_events** (po użytkowniku):
```sql
CREATE POLICY review_events_by_user ON review_events
  USING (user_id = current_app_user())
  WITH CHECK (user_id = current_app_user());
```

**analytics_events** (po użytkowniku):
```sql
CREATE POLICY analytics_events_by_user ON analytics_events
  USING (user_id = current_app_user())
  WITH CHECK (user_id = current_app_user());
```

**ai_jobs** (po użytkowniku):
```sql
CREATE POLICY ai_jobs_by_user ON ai_jobs
  USING (user_id = current_app_user())
  WITH CHECK (user_id = current_app_user());
```

---

## 5. Dodatkowe uwagi projektowe
- **Soft delete**: wszystkie listowania w aplikacji powinny filtrować `deleted_at IS NULL`. Polityki RLS również filtrują wiersze miękko usunięte.
- **Denormalizacja `sets.card_count`**: utrzymywana triggerami:
    - +1 po `INSERT` karty aktywnej (`deleted_at IS NULL`), −1 po miękkim usunięciu (przejście `deleted_at` z `NULL` → `NOT NULL`).
- **Brak „generation_session”** w MVP — minimalne metadane w `sets`, statusy/błędy i szczegóły w `ai_jobs`.
- **Walidacje długości**: pola kart (≤1000 znaków); prompty AI w `ai_jobs.request_prompt` (1000–10000 znaków).
- **Algorytm nauki**: `review_states` + `review_events`; dobór kolejnej karty: `WHERE user_id = current_app_user() AND due_at <= now()` z indeksem `(user_id, due_at)`.
- **Rozszerzenia**: `citext`, `pgcrypto`; **opcjonalnie** `pg_trgm` dla szybkiego wyszukiwania nazw zestawów.
- **Ewolucja**: przy wprowadzeniu współdzielenia zestawów w przyszłości — rozszerzyć model o ACL/role i zaktualizować polityki RLS.

---

**Źródła założeń i decyzji**: fileciteturn0file1 fileciteturn0file2 fileciteturn0file0
