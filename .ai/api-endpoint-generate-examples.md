# POST /generate - Przykłady użycia i testowania

## Przegląd

Endpoint do tworzenia zadania AI generującego fiszki z tekstu źródłowego.

- **URL:** `POST /generate`
- **Autoryzacja:** Wymagana (użytkownik musi być zalogowany)
- **Content-Type:** `application/json`
- **Response:** 202 Accepted (asynchroniczne)

---

## Przykłady cURL

### 1. Happy Path - Sukces (202 Accepted)

```bash
curl -X POST http://localhost:8000/generate \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your-session-id" \
  -d '{
    "source_text": "'"$(python3 -c "print('A' * 1500)")"'"
  }'
```

**Odpowiedź:**
```json
{
  "job_id": "123e4567-e89b-12d3-a456-426614174000",
  "status": "queued"
}
```

**Status:** `202 Accepted`

---

### 2. Błąd - Tekst za krótki (422 Unprocessable Entity)

```bash
curl -X POST http://localhost:8000/generate \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your-session-id" \
  -d '{
    "source_text": "This text is too short"
  }' \
  -v
```

**Odpowiedź:**
```json
{
  "error": "Validation failed",
  "details": {
    "sourceText": [
      "Source text must be at least 1000 characters long"
    ]
  }
}
```

**Status:** `422 Unprocessable Entity`

---

### 3. Błąd - Tekst za długi (422 Unprocessable Entity)

```bash
# Generowanie tekstu > 10000 znaków
curl -X POST http://localhost:8000/generate \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your-session-id" \
  -d '{
    "source_text": "'"$(python3 -c "print('A' * 10001)")"'"
  }' \
  -v
```

**Odpowiedź:**
```json
{
  "error": "Validation failed",
  "details": {
    "sourceText": [
      "Source text cannot be longer than 10000 characters"
    ]
  }
}
```

**Status:** `422 Unprocessable Entity`

---

### 4. Błąd - Brak pola source_text (400 Bad Request)

```bash
curl -X POST http://localhost:8000/generate \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your-session-id" \
  -d '{}' \
  -v
```

**Odpowiedź:**
```json
{
  "error": "Bad Request",
  "message": "Missing required field: source_text"
}
```

**Status:** `400 Bad Request`

---

### 5. Błąd - Nieprawidłowy JSON (400 Bad Request)

```bash
curl -X POST http://localhost:8000/generate \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your-session-id" \
  -d 'invalid json' \
  -v
```

**Odpowiedź:**
```json
{
  "error": "Bad Request",
  "message": "Invalid JSON format"
}
```

**Status:** `400 Bad Request`

---

### 6. Błąd - Brak autoryzacji (401 Unauthorized)

```bash
curl -X POST http://localhost:8000/generate \
  -H "Content-Type: application/json" \
  -d '{
    "source_text": "'"$(python3 -c "print('A' * 1500)")"'"
  }' \
  -v
```

**Odpowiedź:**
```json
{
  "error": "Authentication required",
  "message": "You must be logged in to generate flashcards"
}
```

**Status:** `401 Unauthorized`

---

## Przykłady HTTPie

HTTPie ma bardziej czytelną składnię niż cURL.

### 1. Happy Path

```bash
# Prosty request z tekstem 1500 znaków
http POST :8000/generate \
  Cookie:PHPSESSID=your-session-id \
  source_text="$(python3 -c "print('Test content for flashcard generation. ' * 100)")"
```

### 2. Walidacja - tekst za krótki

```bash
http POST :8000/generate \
  Cookie:PHPSESSID=your-session-id \
  source_text="Too short"
```

### 3. Walidacja - tekst za długi

```bash
http POST :8000/generate \
  Cookie:PHPSESSID=your-session-id \
  source_text="$(python3 -c "print('A' * 10001)")"
```

---

## Testowanie z przykładowym tekstem edukacyjnym

### Tekst o odpowiedniej długości (1000-10000 znaków)

```bash
curl -X POST http://localhost:8000/generate \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=your-session-id" \
  -d @- <<'EOF'
{
  "source_text": "Fotosynteza to proces biochemiczny zachodzący w chloroplastach roślin, glonów i niektórych bakterii, w którym energia świetlna jest przekształcana w energię chemiczną. Proces ten można podzielić na dwie główne fazy: fazę jasną i fazę ciemną. W fazie jasnej, która zachodzi w błonach tylakoidów, energia świetlna jest pochłaniana przez chlorofil i inne barwniki fotosyntetyczne. Ta energia jest następnie wykorzystywana do rozszczepiania cząsteczek wody (fotoliza wody) na tlen, protony i elektrony. Tlen jest uwalniany do atmosfery jako produkt uboczny, podczas gdy elektrony i protony są wykorzystywane do produkcji ATP i NADPH. W fazie ciemnej, znanej również jako cykl Calvina, która zachodzi w stromie chloroplastów, ATP i NADPH wyprodukowane w fazie jasnej są wykorzystywane do redukcji dwutlenku węgla i syntezy glukozy. Cykl Calvina składa się z trzech głównych etapów: fiksacji dwutlenku węgla, redukcji i regeneracji akceptora CO2. W wyniku całego procesu fotosyntezy powstaje glukoza, która może być następnie wykorzystana przez roślinę do produkcji energii lub jako budulec do syntezy innych związków organicznych. Fotosynteza jest kluczowym procesem dla życia na Ziemi, ponieważ dostarcza tlen do atmosfery i stanowi podstawę większości łańcuchów pokarmowych. Równanie sumaryczne fotosyntezy można zapisać jako: 6CO2 + 6H2O + energia świetlna → C6H12O6 + 6O2. Efektywność fotosyntezy zależy od wielu czynników, w tym od natężenia światła, temperatury, dostępności wody i stężenia dwutlenku węgla w atmosferze."
}
EOF
```

---

## Weryfikacja utworzonego zadania

Po pomyślnym utworzeniu zadania (202), możesz sprawdzić jego status w bazie danych:

```sql
-- Sprawdzenie ostatnio utworzonego zadania
SELECT
    id,
    user_id,
    status,
    created_at,
    char_length(request_prompt) as prompt_length
FROM ai_jobs
ORDER BY created_at DESC
LIMIT 1;
```

**Oczekiwany wynik:**
- `status` = 'queued'
- `prompt_length` między 1000 a 10000
- `created_at` = timestamp utworzenia

---

## Testowanie RLS (Row Level Security)

### Weryfikacja izolacji danych między użytkownikami

1. **Zaloguj się jako użytkownik A:**
```bash
curl -X POST http://localhost:8000/generate \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=user-a-session" \
  -d '{"source_text": "'"$(python3 -c "print('A' * 1500)")"'"}'
```

2. **Zaloguj się jako użytkownik B:**
```bash
curl -X POST http://localhost:8000/generate \
  -H "Content-Type: application/json" \
  -H "Cookie: PHPSESSID=user-b-session" \
  -d '{"source_text": "'"$(python3 -c "print('B' * 1500)")"'"}'
```

3. **Sprawdź w bazie, że użytkownicy widzą tylko swoje zadania:**
```sql
-- Jako użytkownik A (ustaw session variable)
SET app.current_user_id = '<user-a-uuid>';
SELECT id, status FROM ai_jobs; -- powinien zwrócić tylko zadania user A

-- Jako użytkownik B
SET app.current_user_id = '<user-b-uuid>';
SELECT id, status FROM ai_jobs; -- powinien zwrócić tylko zadania user B
```

---

## Sprawdzenie Analytics Events

Po utworzeniu zadania, w tabeli `analytics_events` powinien pojawić się event:

```sql
SELECT
    event_type,
    user_id,
    payload,
    occurred_at
FROM analytics_events
WHERE event_type = 'ai_generate_started'
ORDER BY occurred_at DESC
LIMIT 5;
```

**Oczekiwany payload:**
```json
{
  "job_id": "uuid-zadania",
  "text_length": 1500
}
```

---

## Debugowanie

### 1. Sprawdzenie logów aplikacji

```bash
# Symfony dev logs
tail -f var/log/dev.log | grep -i "generate\|ai_job"
```

### 2. Sprawdzenie czy RLS session variable jest ustawiona

```sql
-- W PostgreSQL, po wykonaniu requestu
SELECT current_setting('app.current_user_id', true);
-- Powinno zwrócić UUID zalogowanego użytkownika
```

### 3. Sprawdzenie routingu

```bash
php bin/console debug:router flashcard_generate
```

### 4. Sprawdzenie czy kontroler jest zarejestrowany

```bash
php bin/console debug:container FlashcardGeneratorController
```

---

## Kolejne kroki

Po pomyślnym utworzeniu zadania (job_id otrzymany), następne endpointy będą:
1. **GET /generate/{job_id}/status** - sprawdzenie statusu zadania
2. **GET /generate/{job_id}/result** - pobranie wygenerowanych fiszek (gdy status = succeeded)

---

## Uwagi dotyczące środowiska dev

- **Session:** W środowisku dev możesz symulować sesję poprzez cookie `PHPSESSID`
- **Logowanie:** Najpierw musisz się zalogować przez endpoint logowania (jeśli został zaimplementowany)
- **Docker:** Jeśli używasz Dockera, port może być inny (sprawdź `docker-compose.yml`)
- **HTTPS:** W produkcji wszystkie requesty powinny używać HTTPS

---

## Checklist testowy przed deploymentem

- [ ] Happy path zwraca 202 z job_id i status="queued"
- [ ] Walidacja długości tekstu (< 1000 znaków) zwraca 422
- [ ] Walidacja długości tekstu (> 10000 znaków) zwraca 422
- [ ] Brak source_text zwraca 400
- [ ] Nieprawidłowy JSON zwraca 400
- [ ] Brak autoryzacji zwraca 401
- [ ] Zadanie zapisane w tabeli `ai_jobs` ze statusem 'queued'
- [ ] Analytics event `ai_generate_started` zapisany
- [ ] RLS session variable ustawiona (current_app_user() działa)
- [ ] Użytkownik A nie widzi zadań użytkownika B (RLS isolation)
- [ ] Request prompt w bazie ma długość 1000-10000 znaków
- [ ] Logi nie zawierają wrażliwych danych (pełny tekst tylko w DB)
