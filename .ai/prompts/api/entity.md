Jesteś wykwalifikowanym programistą Symfony, którego zadaniem jest przygotowanie encji Doctrine.
Twoim zadaniem jest przeanalizowanie definicji bazy danych i planu API, a następnie utworzenie odpowiednich encji.

Najpierw dokładnie przejrzyj następujące dane wejściowe:

1. Modele bazy danych:
   <database_plan>
   @.ai/db-plan.md
   </database_plan>

2. Plan API:
   <api_plan>
   @.ai/api-plan.md
   </api_plan>

## Ważne uwagi dotyczące implementacji:

### Tabela `ai_jobs` (śledzenie KPI dla AI):
- **Cel**: Opcjonalne śledzenie metryk generowania AI
- **Synchroniczne generowanie**: Brak statusów 'queued' i 'running', tylko 'succeeded' i 'failed'
- **Pola KPI**:
  - `generated_count`: Ile kart wygenerowało AI
  - `accepted_count`: Ile kart użytkownik zapisał (wypełniane przy `POST /api/sets`)
  - `edited_count`: Ile zapisanych kart było edytowanych (wypełniane przy `POST /api/sets`)
- **Pole `set_id`**: NULL po generowaniu, wypełniane dopiero gdy użytkownik zapisze zestaw
- **Brak preview**: Karty nie są przechowywane w bazie podczas edycji (tylko po stronie frontendu)

### Tabela `cards`:
- Pole `origin`: ENUM ('ai', 'manual')
- Pole `edited_by_user_at`: Wypełniane gdy użytkownik edytował kartę pochodzącą z AI przed zapisem

### ENUM types:
- `card_origin`: 'ai', 'manual'
- `ai_job_status`: 'succeeded', 'failed' (BEZ 'queued' i 'running')

Twoim zadaniem jest utworzenie encji Doctrine oraz podstawowych repozytoriów do nich.

Podczas pracy stosuj zalecenia z @.ai/symfony.md
