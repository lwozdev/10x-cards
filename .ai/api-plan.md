# API Plan

> Architecture note: Monolithic Symfony app with server‑side rendering (Twig) and Symfony UX (Turbo/Stimulus). No
> separate public REST API in MVP; however, endpoints are designed with clean, resource‑oriented URLs and content
> negotiation (`Accept: text/html` vs `application/json`) to keep a low-friction path to future extraction of a standalone
> API. fileciteturn0file0

## 1. Resources

Each resource maps 1:1 to a primary table and related structures.

- **User** → `users` (account, login) fileciteturn0file3
- **Set** → `sets` (owner-scoped, unique name per owner, soft delete, denormalized `card_count`) fileciteturn0file3
  fileciteturn0file2
- **Card** → `cards` (belongs to set, origin `ai|manual`, 1000-char limits, soft delete) fileciteturn0file3
  fileciteturn0file2
- **Study State** → `review_states` (scheduler state per user+card, `due_at`-based selection) fileciteturn0file3
- **Study Event** → `review_events` (answers log; immutable history) fileciteturn0file3
- **AI Job** → `ai_jobs` (request/response bookkeeping, status, errors) fileciteturn0file3 fileciteturn0file2
- **Analytics Event** → `analytics_events` (instrumentation for KPIs and UX) fileciteturn0file1 fileciteturn0file3

## 2. Endpoints

> Conventions
> - **HTML** responses for regular navigation (Twig views).
> - **JSON** responses for progressive enhancement (Turbo/Stimulus) using the **same URLs** with
    `Accept: application/json` or `?format=json`.
> - All endpoints are **owner-scoped** (RLS ensures per-row isolation) and return **404** for unauthorized/missing
    records to avoid enumeration. fileciteturn0file3

### 2.1 Authentication

#### Register

- **POST** `/auth/register`
- **Description**: Create account; auto-login on success. fileciteturn0file1
- **Request (JSON or form)**
  ```json
  { "email": "user@example.com", "password": "secret123", "password_confirm": "secret123" }
  ```
- **Response (JSON, 201)**
  ```json
  { "id": "uuid", "email": "user@example.com", "created_at": "2025-10-25T10:00:00Z" }
  ```
- **Errors**: 400 (validation), 409 (email exists). Email unique (case-insensitive via `CITEXT`). fileciteturn0file3

#### Login / Logout

- **POST** `/auth/login` → 200 on success; 401 on invalid credentials. fileciteturn0file1
- **POST** `/auth/logout` → 204 (clears session cookie).

### 2.2 Sets

#### List My Sets

- **GET** `/sets`
- **Query**: `page` (default 1), `size` (default 20, max 100), `q` (search in name), `sort` (`updated_at desc|asc`,
  default `desc`)
- **Response (200)**
  ```json
  {
    "items": [
      { "id":"uuid","name":"Biology Basics","card_count":42,"updated_at":"...","created_at":"..." }
    ],
    "page":1,"size":20,"total":123
  }
  ```
- **Notes**: Indexes `sets(owner_id, deleted_at)` and `sets(owner_id, updated_at desc)` support this view. Optional
  trigram search on `name`. fileciteturn0file3

#### Create Manual Set

- **POST** `/sets`
- **Request**
  ```json
  { "name": "My New Set" }
  ```
- **Response (201)**
  ```json
  { "id":"uuid","name":"My New Set","card_count":0 }
  ```
- **Errors**: 400 (name empty), 409 (duplicate name per owner; case-insensitive). fileciteturn0file2
  fileciteturn0file3

#### Get Set

- **GET** `/sets/{setId}`
- **Response (200)**
  ```json
  {
    "id":"uuid","name":"...","card_count":2,
    "cards":[{"id":"...","origin":"ai","front":"...","back":"..."}]
  }
  ```

#### Rename Set

- **PUT** `/sets/{setId}`
- **Request** `{ "name": "New Name" }`
- **Response** `200` with full resource.
- **Errors**: 409 on duplicate `(owner_id,name)`. fileciteturn0file3

#### Soft Delete Set

- **DELETE** `/sets/{setId}` → `204` (sets `deleted_at`). Cards cascade soft via application logic, count decremented.
  fileciteturn0file2

### 2.3 Cards (manual only after set is saved)

> Business rule: manual cards **can only be added after saving** a generated set (no mixing in preview). Enforced in
> controller + UI. fileciteturn0file2

#### Add Card

- **POST** `/sets/{setId}/cards`
- **Request**
  ```json
  { "front":"...","back":"..." }
  ```
- **Response (201)**
  ```json
  { "id":"uuid","origin":"manual","front":"...","back":"...","edited_by_user_at":null }
  ```
- **Validation**: `front`/`back` ≤ 1000 chars; non-empty. fileciteturn0file2 fileciteturn0file3

#### Edit Card

- **PATCH** `/sets/{setId}/cards/{cardId}`
- **Request** (any subset):
  ```json
  { "front":"...","back":"..." }
  ```
- **Response (200)**: updated card, sets `edited_by_user_at` to now.

#### Soft Delete Card

- **DELETE** `/sets/{setId}/cards/{cardId}` → `204` (updates `deleted_at`, decrements `card_count`).
  fileciteturn0file3

### 2.4 AI Generation Flow

#### Submit Text for Generation

- **POST** `/generate`
- **Description**: Enqueue AI job and start synchronous generation worker; returns job handle immediately for UI
  progress; server may long-poll and inline preview when ready.
- **Request**
  ```json
  { "source_text": "<1000..10000 chars>" }
  ```
- **Response (202)** (accepted; job created)
  ```json
  { "job_id":"uuid","status":"queued" }
  ```
- **Validation**: `source_text` length 1000–10000; otherwise 422. Tracked in `ai_jobs.request_prompt` with the same
  limits. fileciteturn0file1 fileciteturn0file3

#### Poll Job / Fetch Preview

- **GET** `/generate/{jobId}`
- **Response (200)** when `succeeded`:
  ```json
  {
    "job_id":"uuid","status":"succeeded",
    "preview": { "suggested_name":"...", "cards":[{"front":"...","back":"..."}] }
  }
  ```
- **Response (200)** when `running|queued`: `{ "job_id":"...","status":"running" }`
- **Response (200)** when `failed`: `{ "job_id":"...","status":"failed","error":"..." }`
- **Notes**: No DB persistence of preview cards before save (held in session/cache). `ai_jobs` stores raw response &
  metadata. fileciteturn0file2

#### Save Generated Set

- **POST** `/generate/{jobId}/accept`
- **Request**
  ```json
  { "name":"Suggested or custom", "cards":[{"front":"...","back":"..."}] }
  ```
- **Response (201)**
  ```json
  { "set_id":"uuid","card_count":N }
  ```
- **Rules**: Creates `sets` + `cards(origin='ai')` in a transaction; enforces `(owner_id,name)` uniqueness; failures
  return 409. fileciteturn0file3

### 2.5 Study (Spaced Repetition)

#### Start Session

- **POST** `/study/sessions`
- **Request** `{ "set_id":"uuid" }`
- **Response (201)**
  ```json
  {
    "session_id":"uuid",
    "next_card":{"card_id":"uuid","front":"..."}
  }
  ```

#### Show Answer & Grade

- **POST** `/study/sessions/{sessionId}/answer`
- **Request**
  ```json
  { "card_id":"uuid","grade":1 } // 1=I know, 0=I don't
  ```
- **Response (200)**
  ```json
  { "next_card":{"card_id":"uuid","front":"..."},"reviewed":12,"correct_rate":0.75 }
  ```
- **Notes**: Updates `review_states` and appends to `review_events`; next card selected by
  `WHERE user_id = current AND due_at <= now() ORDER BY due_at ASC LIMIT 1`. fileciteturn0file3

#### End Session / Summary

- **GET** `/study/sessions/{sessionId}/summary`
- **Response (200)**
  ```json
  { "reviewed": N, "correct_rate": 0.72 }
  ```
- **UX**: Matches PRD flow (“Show answer”, then “I know / I don’t know”, summary). fileciteturn0file1

### 2.6 Analytics

#### Track Event (optional client-side endpoint)

- **POST** `/analytics/events`
- **Request**
  ```json
  { "event_type":"card_deleted_in_preview","payload":{"card_index":3} }
  ```
- **Response** `202`
- **Notes**: Canonical KPIs: acceptance rate of AI cards; AI adoption ratio. Events persisted in `analytics_events`.
  fileciteturn0file1 fileciteturn0file3

### 2.7 AI Jobs (debug/UX)

- **GET** `/ai-jobs/{id}` → status/details (owner-scoped). Useful for robust UI error handling. fileciteturn0file3

---

## 3. Authentication & Authorization

- **Auth mechanism**: Symfony Security with session cookies; CSRF protection on state-changing HTML forms. For JSON,
  require SameSite cookies + CSRF header/token. (MVP does not expose public tokens/JWT.)
- **Authorization**: All data is **owner-only**. PostgreSQL **RLS** enforces row access using
  `current_setting('app.current_user_id')` via helper `current_app_user()`. Application must
  `SET app.current_user_id = '<uuid>'` per request/transaction. fileciteturn0file3 fileciteturn0file2
- **Errors**: Unauthorized → 401; forbidden/missing → 404 to avoid leaks.

**Rate limiting & abuse protection**

- Global + per-user rate limits on `/generate` and session actions.
- Size limits on payloads; backpressure on AI queue (`ai_jobs.status`). fileciteturn0file3

---

## 4. Validation & Business Logic

### 4.1 Validation Rules (by resource)

- **Register/Login**: Email format; password min length; email uniqueness (case-insensitive). fileciteturn0file3
  fileciteturn0file1
- **Set**: `name` non-empty; unique per owner (`(owner_id, name)` with `CITEXT`). fileciteturn0file3
  fileciteturn0file2
- **Card**: `front`/`back` required; each ≤ **1000** chars. fileciteturn0file2
- **Generate**: `source_text` length **1000–10000** chars; actionable error messages when out of range.
  fileciteturn0file1 fileciteturn0file3
- **Study Answer**: `grade` ∈ {0,1}; update of scheduling fields (`due_at`, `interval_days`, `ease`, `reps`,
  `last_grade`). fileciteturn0file3

### 4.2 Business Logic & Constraints

- **Preview → Save boundary**: No persistence of preview cards; only after acceptance do we create `sets` +
  `cards(origin='ai')`. Manual cards are allowed **only after** set exists. fileciteturn0file2
- **Soft delete**: Use `deleted_at`; all listings filter out deleted; history in `review_*` remains intact (FK`RESTRICT`
  on `review_states`, `SET NULL` on `review_events.card_id`). fileciteturn0file3
- **Denormalized `card_count`**: Maintained by triggers (+1 on insert of active card; −1 on soft delete).
  fileciteturn0file3
- **Analytics**: Track `card_deleted_in_preview`, `ai_generation_failed`, `ai_generation_succeeded`, `set_saved`,
  `study_answered` to compute KPI targets from PRD. fileciteturn0file1

---

## 5. Pagination, Filtering, Sorting

- **Pagination**: `page` + `size` on list endpoints; `"Link"` header for prev/next; default size 20.
- **Filtering**: soft-deleted filtered by default; owner scope implicit; search `q` on set names (optional trigram
  index). fileciteturn0file3
- **Sorting**: `updated_at` or `created_at` desc/asc where relevant.

---

## 6. Performance & Indexing

- **Sets listing** backed by `sets(owner_id, deleted_at)` and `sets(owner_id, updated_at desc)`; `card_count` avoids
  N+1. fileciteturn0file3
- **Study next-card** query uses `review_states(user_id, due_at)` index. fileciteturn0file3
- **AI jobs** dashboard/status use `ai_jobs(user_id, created_at desc)` and `ai_jobs(status, created_at)`.
  fileciteturn0file3
- **Analytics** summary by `analytics_events(user_id, occurred_at desc)`; consider monthly partitioning as volume grows.
  fileciteturn0file2

---

## 7. Error Model (JSON)

```json
{
    "error": {
        "code": "validation_error",
        "message": "Front must be ≤ 1000 chars.",
        "field_errors": {
            "front": "too_long"
        }
    }
}
```

Standard codes: `validation_error` (400/422), `conflict` (409), `not_found` (404), `unauthorized` (401),`rate_limited` (
429), `server_error` (500).

---

## 8. Security Considerations

- **CSRF**: Tokens on all state-changing endpoints; `SameSite=Lax` cookies.
- **RLS as backstop**: Policies on `sets/cards/review_* /analytics_events/ai_jobs` ensure row isolation regardless of
  ORM mistakes; application sets `app.current_user_id`. fileciteturn0file3
- **Input sanitation**: server-side validation mirrors DB `CHECK`s to fail fast; consistent error messages.
  fileciteturn0file3
- **Rate limiting**: protect `/generate` and `/study/*` from abuse.
- **PII**: Keep `ai_jobs.response_raw` retention minimal (policy TBD per PRD scope); consider redaction/anonymization.
  fileciteturn0file2

---

## 9. Future API Extraction (Guidelines)

- Keep resource URLs stable (`/sets`, `/sets/{id}`, `/study/*`).
- Ensure serializers return explicit resource IDs and timestamps to support external clients later.
- Maintain content negotiation; adding a dedicated `/api/*` surface remains trivial when needed. fileciteturn0file0
