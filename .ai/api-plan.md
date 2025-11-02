# API Plan

Monolithic, server-rendered Symfony app with Twig (SSR) and a thin, internal JSON API for progressive enhancement now
and easy separation later. Symfony Forms/Controllers render HTML, while `/api/*` endpoints return JSON for XHR/HTMX. No
API Platform. This aligns with the chosen stack (Symfony + Doctrine, Twig, SSR monolith).

---

## 1. Resources

| Resource    | DB table(s)     | Notes                                                                                            |
|-------------|-----------------|--------------------------------------------------------------------------------------------------|
| User        | `users`         | Email (unique), password_hash; app uses session auth.                                            |
| Set         | `sets`          | Owned by user, unique name per owner (case-insensitive), soft-delete, denormalized `card_count`. |
| Card        | `cards`         | Belongs to a set; `origin` = `ai` or `manual`; `front`/`back` ≤ 1000 chars; soft-delete.         |
| ReviewState | `review_states` | Per-user scheduling state (due_at, reps, last_grade, etc.).                                      |
| ReviewEvent | `review_events` | Log of study answers (grade 0/1, answered_at, duration_ms).                                      |
| AI Job      | `ai_jobs`       | **Optional KPI tracking**. Fields: `status` (`succeeded|failed`), `generated_count` (cards produced by AI), `accepted_count` (cards saved), `edited_count` (saved cards that were edited), `set_id` (nullable, filled when user saves), `created_at`, `completed_at`. No preview storage needed. |

**Indices that inform list endpoints**: `sets(owner_id, deleted_at)`, `sets(owner_id, updated_at desc)`,
`review_states(user_id, due_at)`.  
**Data ownership & security**: RLS per-user on all tables; the app sets `SET app.current_user_id = <uuid>` per request.

---

## 2. Endpoints

Below, JSON endpoints live under `/api/*`. HTML SSR pages exist at similar non-`/api` paths (e.g., `/sets`,
`/sets/{id}`, `/learn`) using Symfony Controllers + Twig.

### Conventions (applies to all endpoints)

- **Auth**: Session cookie (Symfony Security). Unauthenticated requests to protected endpoints return `401`.
- **RLS context**: On every request, backend sets DB-local `app.current_user_id` before DB work.
- **Soft delete**: reads ignore `deleted_at` by default; deletes set `deleted_at` (for domain tables).
- **Errors**: Use `application/problem+json` (RFC 7807).
- **Pagination**: `page` (1-based), `per_page` (default 20, max 100). Response includes `total`, `page`, `per_page`,
  `items`.
- **Sorting**: `sort` e.g. `updated_at_desc|asc` (whitelist per endpoint).
- **Filtering**: Explicit, documented per endpoint.
- **CSRF**: All state-changing calls require a CSRF token (header: `X-CSRF-Token`) when called via XHR.

---

### 2.1 Auth

#### POST /api/auth/register

- **Description**: Create user account and sign in.
- **Request JSON**:
  ```json
  { "email": "user@example.com", "password": "<min 8 chars>", "password_confirm": "<same>" }
  ```
- **Response 201**:
  ```json
  { "id": "uuid", "email": "user@example.com" }
  ```
- **Errors**: `400` validation; `409` email in use.

#### POST /api/auth/login

- **Description**: Authenticate; sets session cookie.
- **Request**: `{ "email": "...", "password": "..." }`
- **Response 200**: `{ "ok": true }`
- **Errors**: `400`, `401`.

#### POST /api/auth/logout

- **Description**: Invalidate session.
- **Response 204**

#### POST /api/auth/password/reset

- **Description**: Issue reset token to email (no-op response to prevent probing).
- **Response 202**: `{ "ok": true }`

#### POST /api/auth/password/reset/confirm

- **Description**: Set new password using token.
- **Request**: `{ "token": "...", "password": "<min 8 chars>" }`
- **Response 200**: `{ "ok": true }`

---

### 2.2 Generate (AI) — Synchronous Generation

#### POST /api/generate

- **Description**: Synchronously generate flashcards from `source_text` (1,000–10,000 chars) using AI. Returns generated cards immediately (blocking call, timeout 30s).
- **Request JSON**:
  ```json
  { "source_text": "<1000..10000 chars>" }
  ```
- **Response 200**:
  ```json
  {
    "job_id": "uuid",
    "suggested_name": "Biologia - Fotosynteza",
    "cards": [
      { "front": "Co to jest fotosynteza?", "back": "Proces..." },
      { "front": "Gdzie zachodzi fotosynteza?", "back": "W chloroplastach..." }
    ],
    "generated_count": 15
  }
  ```
- **Validation**: Enforce length window server-side (mirrors DB check).
- **Errors**:
  - `422` length invalid or validation error
  - `504` AI timeout (>30s)
  - `500` AI service error
- **Notes**:
  - Frontend manages card editing/deletion in local state
  - `job_id` is returned for optional KPI tracking linkage when user saves the set
  - User can edit cards locally before calling `POST /api/sets` to persist

---

### 2.3 Sets

#### GET /api/sets

- **Description**: List “My Sets”. Supports search and sorting.
- **Query**: `q` (optional, case-insensitive name match), `page`, `per_page`, `sort=updated_at_desc|asc`.
- **Response 200**:
  ```json
  { "items": [ { "id":"uuid","name":"...","card_count":12,"updated_at":"..." } ], "total": 1, "page": 1, "per_page": 20 }
  ```

#### POST /api/sets

- **Description**: Create a set (empty for manual creation OR with cards from AI generation).
- **Request**:
  ```json
  {
    "name": "My Set Name",
    "cards": [
      { "front": "Question?", "back": "Answer", "origin": "ai", "edited": true }
    ],
    "job_id": "uuid"
  }
  ```
- **Notes**:
  - `cards` array is optional (omit for empty manual set)
  - `origin` must be "ai" or "manual" (defaults to "manual" if omitted)
  - `edited` (boolean, optional): `true` if user modified the card before saving. Backend sets `edited_by_user_at = now()` for edited cards.
  - `job_id` is optional, used for KPI tracking linkage to `ai_jobs` table
  - When `job_id` is provided, backend updates `ai_jobs` record with:
    - `set_id` = newly created set ID
    - `accepted_count` = count of cards with `origin='ai'` in request
    - `edited_count` = count of cards with `origin='ai'` AND `edited=true`
    - Deleted count can be calculated as: `generated_count - accepted_count`
- **Response 201**: `{ "id":"uuid","name":"...","card_count":15 }`
- **Errors**: `409` set name already used by owner; `422` validation errors

#### GET /api/sets/{set_id}

- **Description**: Get set details with cards (excluding soft-deleted).
- **Response 200**:
  ```json
  { "id":"uuid","name":"...","card_count":12,"cards":[{"id":"uuid","origin":"ai","front":"...","back":"..."}] }
  ```

#### PATCH /api/sets/{set_id}

- **Description**: Rename set.
- **Request**: `{ "name": "New Name" }`
- **Response 200**: `{ "id":"uuid","name":"New Name" }`

#### DELETE /api/sets/{set_id}

- **Description**: Soft-delete set (and cascade soft-delete cards); filtered from lists.
- **Response 204**

---

### 2.4 Cards (for saved sets)

#### POST /api/sets/{set_id}/cards

- **Description**: Add **manual** card to a saved set (allowed only **after** save).
- **Request**:
  ```json
  { "front": "...", "back": "..." }
  ```
- **Response 201**: `{ "id":"uuid","origin":"manual","front":"...","back":"..." }`

#### PATCH /api/sets/{set_id}/cards/{card_id}

- **Description**: Edit card (front/back); update `edited_by_user_at`.
- **Response 200**: Updated card.

#### DELETE /api/sets/{set_id}/cards/{card_id}

- **Description**: Soft-delete a card (decrement `card_count` via trigger or service).
- **Response 204**

---

### 2.5 Learn (Spaced Repetition)

#### GET /api/learn/next?set_id={uuid}

- **Description**: Fetch next due card for the current user and (optional) set.
- **Response 200**:
  ```json
  { "card": { "id":"uuid","front":"..." }, "due_at":"2025-10-27T09:00:00Z" }
  ```

#### POST /api/learn/{card_id}/grade

- **Description**: Submit study result; append `review_events`, update `review_states` (grade 0 = “Don’t know”, 1 =
  “Know”).
- **Request**:
  ```json
  { "grade": 0, "duration_ms": 1200 }
  ```
- **Response 200**:
  ```json
  { "next_due_at":"2025-10-27T11:00:00Z" }
  ```

#### GET /api/learn/summary?set_id={uuid}&since={iso}

- **Description**: Lightweight stats for current session (cards reviewed, % correct).
- **Response 200**:
  ```json
  { "reviewed": 20, "correct_pct": 0.85 }
  ```

---

### 2.6 Me

#### GET /api/me

- **Description**: Return current user profile minimal data.
- **Response 200**: `{ "id":"uuid","email":"..." }`

---

## 3. Authentication & Authorization

- **Mechanism**: Session-cookie auth via Symfony Security; password hashing with bcrypt/argon2.
- **RLS (defense-in-depth)**: Per-user policies; the app sets `app.current_user_id` at request start. Users only see
  their rows even if app checks fail.
- **Ownership model**: Single-tenant per user; no admin roles in MVP.
- **CSRF**: Required on state-changing HTML forms and XHR (header `X-CSRF-Token`).
- **Rate limiting** (Symfony RateLimiter):
    - `/api/generate`: e.g., 5/min per user + 100/day (tunable).
    - Auth endpoints: bruteforce protection (IP + user key).
- **Input size limits**:
    - `source_text`: 1,000–10,000 chars (server-side check mirrors DB check).
    - Card `front/back`: ≤ 1,000 chars.
- **Soft delete**: Implemented via `deleted_at` in domain tables; list endpoints filter it.

---

## 4. Validation & Business Logic

### Cross-cutting validations

- **Email** must be valid; **password** min length 8; **set.name** non-empty/unique per owner (case-insensitive).
- **source_text** length window enforced both at controller and DB layer (`ai_jobs.request_prompt` check).
- **Card fields** (`front/back`) ≤ 1000 chars.

### Domain rules mapping

1) **Generate → Edit (client-side) → Save**
    - `POST /api/generate` returns cards synchronously with `job_id` and `generated_count`
    - Frontend manages editing/deletion in local state (no server-side preview)
    - Frontend tracks which cards were edited (sets `edited: true` flag)
    - User calls `POST /api/sets` with cards array including `edited` flag
    - Backend processes KPI metrics when `job_id` is provided:
      - Sets `cards.origin = 'ai'` for AI-generated cards
      - Sets `cards.edited_by_user_at = now()` for cards with `edited: true`
      - Updates `ai_jobs` record:
        - `set_id` = newly created set ID
        - `accepted_count` = count of `origin='ai'` cards saved
        - `edited_count` = count of `origin='ai'` cards with `edited=true`
    - KPI calculations:
      - Acceptance rate = `accepted_count / generated_count` (target: 75%)
      - Deleted count = `generated_count - accepted_count`
      - Edit rate = `edited_count / accepted_count`

2) **Manual set and cards**
    - Users can create empty sets (`POST /api/sets` without cards array)
    - Add manual cards via `POST /api/sets/{set_id}/cards` with `origin='manual'`

3) **Study flow**
    - Learn interface shows front → reveal back; grading is binary and updates scheduling. Session summary supported.

### Error handling patterns

- `400 Bad Request`: invalid JSON shape, unsupported params.
- `401 Unauthorized`: not logged in.
- `403 Forbidden`: ownership/RLS violation detected at app-level (DB denies, too).
- `404 Not Found`: resource missing (or soft-deleted).
- `409 Conflict`: set name already used by the owner.
- `422 Unprocessable Entity`: validation (length windows, field constraints).
- `504 Gateway Timeout`: AI generation exceeded 30s timeout.

---

## 5. Performance & Pagination Notes

- **Sets list** uses `(owner_id, deleted_at)` + `(owner_id, updated_at DESC)` indices; optional trigram search on
  `name`.
- **Due selection** for learning uses `(user_id, due_at)` index.
- **AI generation**: Synchronous calls may take 10-30s; consider showing loading state to user. Client-side state management keeps UI responsive during card editing.

---

## 6. HTTP Method Use

- `GET` for retrieval (safe, idempotent).
- `POST` to create resources and to submit actions (`generate`, `grade`).
- `PATCH` for partial updates (set/card rename/edit).
- `DELETE` for soft deletes.

---

## 7. JSON Schemas (Representative)

### GenerateResponse (POST /api/generate)

```json
{
    "job_id": "uuid",
    "suggested_name": "Biologia - Fotosynteza",
    "cards": [
        { "front": "Question?", "back": "Answer" }
    ],
    "generated_count": 15
}
```

### CreateSetRequest (POST /api/sets)

```json
{
    "name": "My Biology Set",
    "cards": [
        { "front": "Question?", "back": "Answer", "origin": "ai", "edited": true }
    ],
    "job_id": "uuid"
}
```

**Notes:**
- `edited` flag indicates card was modified by user before saving
- Backend sets `edited_by_user_at` timestamp for cards with `edited: true`
- Backend updates `ai_jobs` KPI metrics when `job_id` provided

### Set

```json
{
    "id": "uuid",
    "name": "string",
    "card_count": 0,
    "created_at": "ISO-8601",
    "updated_at": "ISO-8601"
}
```

### Card

```json
{
    "id": "uuid",
    "set_id": "uuid",
    "origin": "ai|manual",
    "front": "string <= 1000",
    "back": "string <= 1000",
    "edited_by_user_at": "ISO-8601|null",
    "created_at": "ISO-8601",
    "updated_at": "ISO-8601"
}
```

### ReviewEvent

```json
{
    "id": 1,
    "user_id": "uuid",
    "card_id": "uuid|null",
    "answered_at": "ISO-8601",
    "grade": 0,
    "duration_ms": 1200
}
```

### AIJob (for KPI tracking)

```json
{
    "id": "uuid",
    "status": "succeeded|failed",
    "generated_count": 15,
    "accepted_count": 12,
    "edited_count": 3,
    "set_id": "uuid|null",
    "suggested_name": "Biologia - Fotosynteza",
    "model_name": "anthropic/claude-3.5-sonnet",
    "tokens_in": 1500,
    "tokens_out": 800,
    "created_at": "ISO-8601",
    "completed_at": "ISO-8601"
}
```

**KPI Metrics:**
- `generated_count`: Total cards generated by AI
- `accepted_count`: Cards saved by user (with `origin='ai'`)
- `edited_count`: Saved cards that were modified before saving
- Deleted count = `generated_count - accepted_count`
- Acceptance rate = `accepted_count / generated_count` (target: 75%)

---

## 8. Security Hardening

- **Input validation** mirrored at DB where practical: CHECK for `ai_jobs.request_prompt` window; domain constraints for
  unique email and `(owner_id, name)` for sets.
- **Ownership at DB**: RLS policies for sets/cards/reviews/ai_jobs.
- **Unique constraints**: email unique; `(owner_id, name)` unique for sets (case-insensitive).
- **Soft delete discipline**: all domain queries filter `deleted_at IS NULL`.
- **TTL**: purge old `ai_jobs` without `set_id` (e.g., >14 days).

---

## 9. Stack Alignment & Evolution

- **Stack fit**: Symfony + Doctrine + Twig SSR with optional HTMX/Stimulus; PostgreSQL.
- **No separate public API for MVP**: Internal `/api/*` endpoints power the SSR app now and can be exposed externally
  later with minimal changes.
- **Future analytics**: If richer funnels are needed, re-introduce lightweight `analytics_events` later without breaking
  these contracts.

---

## 10. Non-Goals (MVP)

- No advanced SRS algorithm (use simple, binary grade-based scheduling).
- No media cards, social features, external LMS integrations, or mobile apps in MVP.
