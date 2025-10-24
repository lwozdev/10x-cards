# Database Planning Conversation Summary (MVP)

<conversation_summary>
<decisions>
1. Używamy RLS w PostgreSQL jako głównej warstwy egzekwowania własności danych (single-tenant per user).
2. Nazwa zestawu (`sets.name`) jest unikalna w obrębie właściciela (`owner_id`).
3. Fiszki manualne mogą być dodawane **dopiero po zapisaniu** wygenerowanego zestawu (brak mieszania w trakcie podglądu).
4. Limit długości tekstu na karcie: maks. **1000 znaków** na awers i rewers; tekst źródłowy do generowania kart: **1000–10000 znaków**.
5. Przyjmujemy model powtórek: `review_states` + `review_events` z prostym algorytmem opartym o `due_at`.
6. Wprowadzamy `analytics_events`; partycjonowanie wydarzeń jest **opcjonalne** (do włączenia po wzroście wolumenu).
7. Stosujemy **soft delete** (`deleted_at`) dla `sets` i `cards` (bez twardych usunięć na starcie).
8. Zestaw indeksów dla kluczowych ekranów (moje zestawy, nauka) – jak w rekomendacjach – zaakceptowany.
9. Brak bytu „generation_session” w MVP; minimalne metadane generowania trzymamy w `sets` + śledzenie statusu/błędów w `ai_jobs`.
10. Reguły FK/ON DELETE zgodnie z rekomendacją: kaskada z `sets` → `cards`, RESTRICT na `review_states` dla zachowania historii; szczegóły zaakceptowane.
    </decisions>

<matched_recommendations>
1. RLS z GUC `app.current_user_id` i polityką `owner_id = current_app_user()`; walidacje dodatkowo w Symfony.
2. Unikalność `(owner_id, name)` dla zestawów; indeksy pod listę „Moje zestawy” i wyszukiwanie.
3. Oznaczanie pochodzenia karty: `cards.origin ENUM('ai','manual')` + `edited_by_user_at`.
4. CHECK na długość pól kart i tekstu źródłowego; TEXT jako typ danych (UTF-8).
5. Model powtórek: `review_states` (due_at/ease/interval/reps/last_grade) + `review_events` (grade, answered_at).
6. `analytics_events` z `event_type` i `payload JSONB`; indeks `(user_id, occurred_at)`; opcjonalne partycjonowanie miesięczne.
7. Soft delete w `sets`/`cards` + uwzględnienie w RLS i zapytaniach (filtrowanie `deleted_at IS NULL`).
8. Indeksy: `sets(owner_id, deleted_at)`, `cards(set_id, deleted_at)`, `review_states(user_id, due_at)`, `analytics_events(user_id, occurred_at)`; opcjonalnie `pg_trgm` na `sets.name`.
9. `ai_jobs` do rejestrowania żądań/odpowiedzi modeli AI (status, error, model, tokens in/out); minimalne metadane w `sets` (`generated_*`).
10. Reguły FK: `sets.owner_id → users.id ON DELETE CASCADE`, `cards.set_id ON DELETE CASCADE`, `review_states.card_id ON DELETE RESTRICT`, `review_states.user_id ON DELETE CASCADE`; w śladach historycznych (`ai_jobs`) `ON DELETE SET NULL`.
    </matched_recommendations>

<database_planning_summary>
**Główne wymagania schematu:**
- Prywatne zestawy przypisane do jednego właściciela; brak ról administracyjnych w MVP.
- Generowanie AI → podgląd → zapis zestawu; manualne dodawanie dopiero po zapisie.
- Nauka w trybie powtórek „wiem/nie wiem” z prostym planowaniem po `due_at`.
- Analiza akceptacji/odrzuceń kart (w szczególności statystyki pochodzenia i akcji użytkownika).
- Soft delete i integralność historii nauki.

**Kluczowe encje i relacje:**
- `users(id, ...)` — właściciel zasobów (RLS po `id`).
- `sets(id, owner_id FK→users, name, status, generated_at, generated_model, generated_tokens_in, generated_tokens_out, card_count, deleted_at, created_at, updated_at)`.
    - Relacja 1:N z `cards`.
- `cards(id, set_id FK→sets, origin ENUM('ai','manual'), front TEXT, back TEXT, edited_by_user_at, deleted_at, created_at, updated_at)`.
- `review_states(user_id FK→users, card_id FK→cards, due_at, ease, interval_days, reps, last_grade, updated_at)` — klucz złożony `(user_id, card_id)`.
- `review_events(id, user_id FK→users, card_id FK→cards, answered_at, grade SMALLINT)` — log odpowiedzi.
- `ai_jobs(id, user_id FK→users, set_id FK→sets NULL, status ENUM, error_message, request_prompt TEXT, response_raw JSONB, model_name, tokens_in INT, tokens_out INT, created_at, updated_at)`.
- `analytics_events(id, event_type, user_id FK→users, set_id FK→sets NULL, card_id FK→cards NULL, payload JSONB, occurred_at)`.

**Bezpieczeństwo i skalowalność:**
- **RLS**: polityki `USING (owner_id = current_app_user() AND deleted_at IS NULL)` dla `sets`; dla `cards` `USING (EXISTS (SELECT 1 FROM sets s WHERE s.id = cards.set_id AND s.owner_id = current_app_user() AND s.deleted_at IS NULL) AND cards.deleted_at IS NULL)`; analogicznie dla `review_*` i `analytics_events` po `user_id`.
- **Kontekst użytkownika**: `SET app.current_user_id = '<uuid>'` na początku transakcji (połączenia) z poziomu Symfony/Doctrine.
- **Indeksowanie**: kluczowe indeksy zaakceptowane; `pg_trgm` na `sets.name` dla szybkiego wyszukiwania (opcjonalnie).
- **Denormalizacja**: `sets.card_count` utrzymywane triggerem dla O(1) listowania.
- **Partycjonowanie**: na początek brak; włączyć miesięczne dla `analytics_events` po przekroczeniu progów (np. >10M wierszy).
- **Wydajność**: nauka oparta o `review_states WHERE user_id = current_user AND due_at <= now()`; selekcja kolejnej karty po najstarszym `due_at` (indeks na `(user_id, due_at)`).

**Integralność i spójność:**
- CHECK: `char_length(front) <= 1000`, `char_length(back) <= 1000`, `char_length(request_prompt) <= 10000` (jeśli przechowywany) oraz walidacje aplikacyjne.
- FK i reguły kasowania: jak w dopasowanych rekomendacjach; soft delete nie narusza historii (`review_*` pozostają).
- Unikalność: indeks unikalny `(owner_id, name)` dla `sets`.

</database_planning_summary>

<unresolved_issues>
1. Precyzja i kolacja unikalności nazw zestawów (case-sensitive vs case-insensitive; rekomendacja: unikalność case-insensitive z `citext` lub normalizacja do lower-case).
2. Dokładna lista i taksonomia `analytics_events` (kanoniczne typy zdarzeń i minimalny `payload`).
3. Zakres przechowywania surowych promptów/odpowiedzi w `ai_jobs` (retencja, anonimizacja).
4. Decyzja o włączeniu rozszerzeń: `pg_trgm` i `citext` w środowisku produkcyjnym (zgody operacyjne).
5. Strategia migracji przy ewentualnym wprowadzeniu współdzielenia zestawów po MVP (ewolucja RLS i schematu).
   </unresolved_issues>
   </conversation_summary>
