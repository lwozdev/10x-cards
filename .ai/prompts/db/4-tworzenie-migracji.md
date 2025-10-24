Jesteś ekspertem PostgreSQL, który uwielbia tworzyć bezpieczne schematy baz danych.

Ten projekt używa migracji dostarczanych przez Symfony oraz Doctrine ORM.

Utwórz migracje dla następującego db-plan:
<db-plan>
@.ai/db-plan.md
</db-plan>

## Tworzenie pliku migracji

Biorąc pod uwagę kontekst wiadomości użytkownika, utwórz plik migracji bazy danych wewnątrz folderu `migrations/`.



## Wytyczne SQL

Napisz kod SQL kompatybilny z PostgreSQL dla plików migracji Doctrine ORM, który:

- Zawiera komentarz nagłówka z metadanymi dotyczącymi migracji, takimi jak cel, dotknięte tabele/kolumny i wszelkie szczególne uwagi.
- Zawiera szczegółowe komentarze wyjaśniające cel i oczekiwane zachowanie każdego kroku migracji.
- Pisz cały SQL małymi literami.
- Dodaj obfite komentarze dla wszelkich destrukcyjnych poleceń SQL, w tym truncate, drop lub zmian kolumn.
- Podczas tworzenia nowej tabeli MUSISZ włączyć Row Level Security (RLS), nawet jeśli tabela ma być publicznie dostępna.
- Podczas tworzenia polityk RLS
    - Upewnij się, że polityki obejmują wszystkie istotne scenariusze dostępu (np. select, insert, update, delete) w oparciu o cel tabeli i wrażliwość danych.
    - Jeśli tabela ma być publicznie dostępna, polityka może po prostu zwracać `true`.
    - Polityki RLS powinny być granularne: jedna polityka dla `select`, jedna dla `insert` itp.) i dla każdej roli. NIE łącz polityk, nawet jeśli funkcjonalność jest taka sama dla obu ról.
    - Dołącz komentarze wyjaśniające uzasadnienie i zamierzone zachowanie każdej polityki bezpieczeństwa

Wygenerowany kod SQL powinien być gotowy do produkcji, dobrze udokumentowany i zgodny z najlepszymi praktykami.
