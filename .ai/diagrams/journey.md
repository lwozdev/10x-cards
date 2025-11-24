# Diagram Podróży Użytkownika - Generator Fiszek AI

Ten diagram przedstawia kompleksową podróż użytkownika przez aplikację Generator Fiszek AI, obejmując wszystkie główne przepływy: autentykację, generowanie fiszek przez AI, zarządzanie zestawami i naukę.

## Kluczowe Ścieżki

1. **Niezalogowany użytkownik** - może generować fiszki, ale nie może ich zapisać
2. **Rejestracja** - tworzenie nowego konta z walidacją
3. **Logowanie** - uwierzytelnianie z opcją resetowania hasła
4. **Generowanie AI** - główny przepływ z walidacją tekstu (1000-10000 znaków)
5. **Edycja i podgląd** - przegląd, modyfikacja i usuwanie wygenerowanych fiszek
6. **Tworzenie manualne** - alternatywna ścieżka bez AI
7. **Zarządzanie zestawami** - lista "Moje zestawy" z opcjami nauki i usuwania
8. **Moduł nauki** - spaced repetition z oceną "Wiem"/"Nie wiem"

## Diagram Mermaid

```mermaid
stateDiagram-v2
    [*] --> StronaGlowna

    state "Strona Główna" as StronaGlowna {
        [*] --> WyborDzialania
        state wyborAkcji <<choice>>
        WyborDzialania --> wyborAkcji
        wyborAkcji --> GenerowaniePubliczne: Generuj fiszki (niezalogowany)
        wyborAkcji --> Logowanie: Zaloguj się
        wyborAkcji --> Rejestracja: Zarejestruj się
    }

    state "Proces Rejestracji" as Rejestracja {
        [*] --> FormularzRejestracji
        FormularzRejestracji --> WalidacjaRejestracji

        state walidacjaRejOK <<choice>>
        WalidacjaRejestracji --> walidacjaRejOK
        walidacjaRejOK --> BladRejestracji: Email zajęty / Hasła różne
        walidacjaRejOK --> TworzenieKonta: Dane poprawne

        BladRejestracji --> FormularzRejestracji
        TworzenieKonta --> AutoLogowanie
        AutoLogowanie --> PanelGlowny
    }

    state "Proces Logowania" as Logowanie {
        [*] --> FormularzLogowania
        FormularzLogowania --> WalidacjaLogowania

        state walidacjaLogOK <<choice>>
        WalidacjaLogowania --> walidacjaLogOK
        walidacjaLogOK --> BladLogowania: Błędne dane
        walidacjaLogOK --> PanelGlowny: Dane poprawne

        BladLogowania --> FormularzLogowania
        FormularzLogowania --> ResetHasla: Link resetowania

        state "Reset Hasła" as ResetHasla {
            [*] --> FormularzResetHasla
            FormularzResetHasla --> WyslanieEmaila
            WyslanieEmaila --> PotwierdzenieMail
            PotwierdzenieMail --> FormularzNowegoHasla
            FormularzNowegoHasla --> ZapisNowegoHasla
            ZapisNowegoHasla --> [*]
        }

        ResetHasla --> FormularzLogowania: Powrót do logowania
    }

    state "Panel Główny (Zalogowany)" as PanelGlowny {
        [*] --> MojeZestawy

        state wyborAkcjiPanel <<choice>>
        MojeZestawy --> wyborAkcjiPanel
        wyborAkcjiPanel --> GenerowanieAI: Generuj z AI
        wyborAkcjiPanel --> TworzenieManualne: Utwórz ręcznie
        wyborAkcjiPanel --> WyborZestawu: Wybierz zestaw

        state "Wybór działania na zestawie" as WyborZestawu {
            [*] --> AkcjaZestaw
            state akcjaZestawChoice <<choice>>
            AkcjaZestaw --> akcjaZestawChoice
            akcjaZestawChoice --> ModulNauki: "Ucz się"
            akcjaZestawChoice --> PotwierdzenieUsuniecia: "Usuń"

            PotwierdzenieUsuniecia --> UsuniecieSukces: Tak
            PotwierdzenieUsuniecia --> AkcjaZestaw: Anuluj
            UsuniecieSukces --> [*]
        }

        WyborZestawu --> MojeZestawy: Powrót
    }

    state "Generowanie AI (Publiczne)" as GenerowaniePubliczne {
        [*] --> PoleTestowe
        PoleTestowe --> WalidacjaDlugosci

        state dlugoscOK <<choice>>
        WalidacjaDlugosci --> dlugoscOK
        dlugoscOK --> PoleTestowe: Poza zakresem (1000-10000)
        dlugoscOK --> ProcesGenerowania: Długość OK, kliknięto "Generuj"

        ProcesGenerowania --> AnimacjaLadowania

        state wynikGenerowania <<choice>>
        AnimacjaLadowania --> wynikGenerowania
        wynikGenerowania --> BladGenerowania: Błąd API
        wynikGenerowania --> InformacjaOLogowaniu: Sukces (niezalogowany)

        BladGenerowania --> PoleTestowe
        InformacjaOLogowaniu --> Logowanie: Zaloguj się aby zapisać
        InformacjaOLogowaniu --> Rejestracja: Zarejestruj się
    }

    state "Generowanie AI (Zalogowany)" as GenerowanieAI {
        [*] --> PoleTestoweZal
        PoleTestoweZal --> WalidacjaDlugosciZal

        state dlugoscZalOK <<choice>>
        WalidacjaDlugosciZal --> dlugoscZalOK
        dlugoscZalOK --> PoleTestoweZal: Poza zakresem
        dlugoscZalOK --> ProcesGenerowania: Długość OK

        ProcesGenerowania --> AnimacjaLadowaniaZal

        state wynikGenZal <<choice>>
        AnimacjaLadowaniaZal --> wynikGenZal
        wynikGenZal --> BladGenerowaniaZal: Błąd API
        wynikGenZal --> EdycjaIPodglad: Sukces

        BladGenerowaniaZal --> PoleTestoweZal
    }

    state "Edycja i Podgląd" as EdycjaIPodglad {
        [*] --> ListaFiszek
        ListaFiszek --> EdycjaFiszki: Edytuj awers/rewers
        ListaFiszek --> UsunieciFiszki: Usuń fiszkę
        EdycjaFiszki --> ListaFiszek
        UsunieciFiszki --> ListaFiszek

        ListaFiszek --> NadanieNazwy
        NadanieNazwy --> AutoSugestia
        AutoSugestia --> WpisanieNazwy

        state nazwaPodana <<choice>>
        WpisanieNazwy --> nazwaPodana
        nazwaPodana --> WpisanieNazwy: Brak nazwy
        nazwaPodana --> ZapisanieZestawu: Nazwa podana

        ZapisanieZestawu --> [*]
    }

    state "Tworzenie Manualne" as TworzenieManualne {
        [*] --> NazwaNowegoZestawu
        NazwaNowegoZestawu --> DodanieFiszki
        DodanieFiszki --> PoleAwers
        PoleAwers --> PoleRewers
        PoleRewers --> DodanieKolejnej

        state kolejnaFiszka <<choice>>
        DodanieKolejnej --> kolejnaFiszka
        kolejnaFiszka --> DodanieFiszki: Dodaj kolejną
        kolejnaFiszka --> ZapisManualnego: Zapisz zestaw

        ZapisManualnego --> [*]
    }

    state "Moduł Nauki" as ModulNauki {
        [*] --> WyswietlenieAwersu
        WyswietlenieAwersu --> OdsloniecieRewersu: Kliknięcie "Pokaż odpowiedź"
        OdsloniecieRewersu --> OcenaOdpowiedzi

        state ocena <<choice>>
        OcenaOdpowiedzi --> ocena
        ocena --> AlgorytmWiem: "Wiem"
        ocena --> AlgorytmNieWiem: "Nie wiem"

        AlgorytmWiem --> NastepnaFiszka
        AlgorytmNieWiem --> NastepnaFiszka

        state czyKoniec <<choice>>
        NastepnaFiszka --> czyKoniec
        czyKoniec --> WyswietlenieAwersu: Są jeszcze fiszki
        czyKoniec --> PodsumowanieNauki: Koniec sesji

        PodsumowanieNauki --> [*]
    }

    GenerowaniePubliczne --> Rejestracja: Zarejestruj aby zapisać
    GenerowanieAI --> EdycjaIPodglad: Sukces
    EdycjaIPodglad --> PanelGlowny: Po zapisaniu
    TworzenieManualne --> PanelGlowny: Po zapisaniu
    PanelGlowny --> ModulNauki: Rozpocznij naukę
    ModulNauki --> PanelGlowny: Zakończ sesję

    StronaGlowna --> PanelGlowny: Po zalogowaniu/rejestracji
    PanelGlowny --> StronaGlowna: Wyloguj

    note right of GenerowaniePubliczne
        Użytkownik MOŻE generować fiszki
        bez logowania (US-010), ale nie
        może ich zapisać bez konta
    end note

    note right of EdycjaIPodglad
        Każda edycja lub usunięcie fiszki
        jest śledzone dla analityki
        (metryka akceptacji 75%)
    end note

    note right of ModulNauki
        Używa gotowego algorytmu
        spaced repetition (nie custom)
    end note
```

## Szczegóły Implementacyjne

### Punkty Decyzyjne

1. **Czy użytkownik jest zalogowany?**
   - TAK → Dostęp do pełnej funkcjonalności (zapisywanie, zarządzanie, nauka)
   - NIE → Tylko generowanie fiszek bez możliwości zapisu

2. **Czy tekst ma odpowiednią długość?** (1000-10000 znaków)
   - TAK → Przycisk "Generuj" aktywny
   - NIE → Przycisk nieaktywny, wyświetlany licznik

3. **Czy dane logowania/rejestracji są poprawne?**
   - TAK → Przekierowanie do panelu głównego
   - NIE → Wyświetlenie komunikatu błędu

4. **Czy generowanie zakończyło się sukcesem?**
   - TAK → Przekierowanie do edycji
   - NIE → Komunikat błędu z sugestiami

### Metryki Analityczne

- **Wydarzenie:** `fiszka_usunięta_w_edycji` - śledzone dla obliczenia wskaźnika akceptacji
- **Cel:** 75% akceptacji fiszek AI (obliczane jako `1 - (usunięte/wygenerowane)`)
- **Źródło:** Każdy zestaw oznaczony jako "AI" lub "manual"

### Uwagi Techniczne

- Reset hasła wymaga wysłania emaila weryfikacyjnego
- Automatyczna sugestia nazwy zestawu oparta na analizie tekstu źródłowego
- Algorytm spaced repetition: użycie gotowego rozwiązania open-source (np. SM-2)
- Niezalogowani użytkownicy widzą komunikat o konieczności zalogowania po wygenerowaniu fiszek
