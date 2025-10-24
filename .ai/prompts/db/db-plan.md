Jesteś architektem baz danych, którego zadaniem jest stworzenie schematu bazy danych PostgreSQL na podstawie informacji dostarczonych z sesji planowania, dokumentu wymagań produktu (PRD) i stacku technologicznym. Twoim celem jest zaprojektowanie wydajnej i skalowalnej struktury bazy danych, która spełnia wymagania projektu.

1. <prd>
# Dokument wymagań produktu (PRD) - Generator Fiszek AI
## 1. Przegląd produktu
Generator Fiszek AI to aplikacja internetowa (web-app) zaprojektowana w celu usprawnienia procesu nauki dla uczniów szkół podstawowych i średnich. Główne zadanie aplikacji polega na automatycznym generowaniu fiszek edukacyjnych na podstawie tekstu dostarczonego przez użytkownika. Rozwiązanie to ma na celu zminimalizowanie czasu i wysiłku potrzebnego na ręczne tworzenie materiałów do nauki, jednocześnie promując efektywną metodę powtórek w interwałach (spaced repetition). Użytkownicy mogą wkleić swoje notatki, a aplikacja, wykorzystując sztuczną inteligencję, przekształci je w zestaw gotowych do nauki fiszek. W ramach Minimal Viable Product (MVP), aplikacja oferuje również możliwość manualnego tworzenia i edycji fiszek, prosty system kont do ich przechowywania oraz uproszczony moduł nauki oparty na gotowym algorytmie powtórek.

## 2. Problem użytkownika
Głównym problemem, który rozwiązuje aplikacja, jest czasochłonność i pracochłonność manualnego tworzenia wysokiej jakości fiszek edukacyjnych. Uczniowie, mimo świadomości korzyści płynących z metody spaced repetition, często rezygnują z jej stosowania z powodu bariery, jaką stanowi przygotowanie materiałów. Proces ten odciąga ich od właściwej nauki i bywa demotywujący. Aplikacja ma na celu usunięcie tej bariery, czyniąc efektywną naukę bardziej dostępną i mniej obciążającą.

## 3. Wymagania funkcjonalne
1.  System Kont Użytkowników
    *   Rejestracja nowych użytkowników za pomocą adresu e-mail i hasła.
    *   Logowanie do istniejących kont.
    *   Możliwość zresetowania zapomnianego hasła.
2.  Generowanie Fiszek przez AI
    *   Pole tekstowe do wklejania treści źródłowych o długości od 1000 do 10 000 znaków.
    *   Dynamiczna walidacja limitu znaków z informacją zwrotną dla użytkownika.
    *   Obsługa i komunikowanie ewentualnych błędów generowania.
    *   Instrukcje dla modelu AI zapewniające tworzenie fiszek w prostym, zwięzłym języku, odpowiednim dla docelowej grupy wiekowej.
3.  Manualne Tworzenie i Edycja Fiszek
    *   Możliwość stworzenia nowego, pustego zestawu fiszek.
    *   Formularz do manualnego dodawania fiszek składających się z dwóch pól: Awers i Rewers.
    *   Możliwość dodawania manualnych fiszek do zestawów wygenerowanych przez AI.
4.  Zarządzanie Zestawami Fiszek
    *   Ekran podglądu wygenerowanego zestawu przed zapisaniem, prezentujący fiszki w formie listy.
    *   Funkcja edycji dla awersu i rewersu każdej fiszki na liście podglądu,
    *   Możliwość usunięcia pojedynczych, niechcianych fiszek z wygenerowanego zestawu.
    *   Opcja nadania nazwy zestawowi przed jego finalnym zapisaniem (z automatyczną sugestią opartą na treści).
    *   Strona "Moje zestawy" zawierająca listę wszystkich zapisanych zestawów użytkownika, z widoczną nazwą i liczbą fiszek.
    *   Możliwość usunięcia całego zestawu z listy.
5.  Moduł Nauki
    *   Prosty interfejs do nauki wyświetlający awers fiszki.
    *   Przycisk lub gest do odsłonięcia rewersu.
    *   Dwa przyciski oceny odpowiedzi: "Wiem" i "Nie wiem".
    *   Integracja z gotowym, zewnętrznym algorytmem spaced repetition do określania kolejności wyświetlania fiszek.
6.  Analityka
    *   Implementacja śledzenia zdarzeń niezbędnych do pomiaru kryteriów sukcesu (np. liczba wygenerowanych, usuniętych i zaakceptowanych fiszek; źródło utworzenia zestawu - AI vs manualnie).

## 4. Granice produktu
Poniższe funkcjonalności i cechy świadomie nie wchodzą w zakres MVP, aby umożliwić szybkie wdrożenie i weryfikację kluczowych hipotez:
*   Stworzenie własnego, zaawansowanego algorytmu powtórek (np. na wzór SuperMemo czy Anki). W MVP zostanie wykorzystany gotowy, prosty algorytm open-source.
*   Import fiszek lub materiałów źródłowych z różnych formatów plików (np. PDF, DOCX, CSV).
*   Funkcje społecznościowe, takie jak współdzielenie zestawów fiszek między użytkownikami.
*   Integracje z zewnętrznymi platformami edukacyjnymi i systemami (np. Google Classroom, Moodle).
*   Dedykowane aplikacje mobilne. Tylko aplikacja internetowa (web-app).
*   Obsługa fiszek z zawartością inną niż tekst (np. obrazy, dźwięk).

## 5. Historyjki użytkowników
### System Kont i Onboarding
---
*   ID: US-001
*   Tytuł: Rejestracja nowego użytkownika
*   Opis: Jako nowy użytkownik, chcę móc założyć konto w aplikacji przy użyciu mojego adresu e-mail i hasła, aby móc zapisywać swoje zestawy fiszek.
*   Kryteria akceptacji:
    1.  Formularz rejestracji zawiera pola na adres e-mail, hasło i potwierdzenie hasła.
    2.  System waliduje poprawność formatu adresu e-mail.
    3.  System sprawdza, czy hasła w obu polach są identyczne.
    4.  Hasło musi spełniać minimalne wymogi bezpieczeństwa (np. 8 znaków).
    5.  Po pomyślnej rejestracji jestem automatycznie zalogowany i przekierowany do onboardingu lub głównego ekranu aplikacji.
    6.  Jeśli adres e-mail jest już zajęty, wyświetlany jest stosowny komunikat błędu.

---
*   ID: US-002
*   Tytuł: Logowanie użytkownika
*   Opis: Jako zarejestrowany użytkownik, chcę móc zalogować się na swoje konto, podając e-mail i hasło, aby uzyskać dostęp do moich zestawów fiszek.
*   Kryteria akceptacji:
    1.  Formularz logowania zawiera pola na adres e-mail i hasło.
    2.  Po poprawnym wprowadzeniu danych jestem przekierowany do panelu głównego ("Moje zestawy").
    3.  W przypadku podania błędnych danych, wyświetlany jest komunikat o nieprawidłowym loginie lub haśle.
    4.  Na stronie logowania znajduje się link do mechanizmu resetowania hasła.

---
### Główny Przepływ - Generowanie Fiszek AI
---
*   ID: US-003
*   Tytuł: Generowanie zestawu fiszek z tekstu
*   Opis: Jako uczeń, chcę wkleić fragment moich notatek do aplikacji i uruchomić proces generowania, aby automatycznie otrzymać zestaw fiszek do nauki.
*   Kryteria akceptacji:
    1.  Na stronie głównej znajduje się duże pole tekstowe.
    2.  Przycisk "Generuj fiszki" jest aktywny tylko wtedy, gdy wklejony tekst ma długość od 1000 do 10 000 znaków.
    3.  Pod polem tekstowym wyświetla się licznik znaków i informacja o obowiązujących limitach.
    4.  Po kliknięciu przycisku "Generuj fiszki" wyświetlana jest animacja ładowania, informująca o trwającym procesie.
    5.  Po pomyślnym zakończeniu generowania, jestem przekierowany na ekran edycji i podglądu nowego zestawu.

---
*   ID: US-005
*   Tytuł: Przeglądanie i edycja wygenerowanych fiszek
*   Opis: Jako użytkownik, po wygenerowaniu fiszek, chcę je przejrzeć, poprawić ewentualne błędy w treści lub usunąć te, które mi nie odpowiadają, zanim zapiszę zestaw.
*   Kryteria akceptacji:
    1.  Wygenerowane fiszki są wyświetlane w formie listy (pytanie-odpowiedź lub awers-rewers).
    2.  Każdy awers i rewers fiszki jest edytowalny.
    3.  Przy każdej fiszce znajduje się przycisk do jej trwałego usunięcia z bieżącego zestawu.
    4.  Edycja fiszki jest równoznaczna z jej "zaakceptowaniem".
    5.  Usunięcie fiszki jest śledzone przez system analityczny.

---
*   ID: US-006
*   Tytuł: Zapisywanie nowego zestawu fiszek
*   Opis: Jako użytkownik, po przejrzeniu i ewentualnej edycji fiszek, chcę zapisać zestaw pod własną nazwą, aby móc do niego wrócić w przyszłości.
*   Kryteria akceptacji:
    1.  Na ekranie edycji znajduje się pole do wpisania nazwy zestawu.
    2.  Aplikacja automatycznie sugeruje nazwę na podstawie analizy wklejonego tekstu.
    3.  Przycisk "Zapisz zestaw" jest aktywny, gdy nazwa zestawu została podana.
    4.  Po zapisaniu zestawu jestem przekierowywany na stronę "Moje zestawy", gdzie widzę nowo dodany element.

---
*   ID: US-007
*   Tytuł: Obsługa błędów generowania
*   Opis: Jako użytkownik, chcę otrzymać jasny komunikat, jeśli AI nie będzie w stanie wygenerować fiszek z dostarczonego przeze mnie tekstu.
*   Kryteria akceptacji:
    1.  W przypadku błędu po stronie API, stan ładowania kończy się, a na ekranie pojawia się komunikat o błędzie (np. "Nie udało się wygenerować fiszek. Spróbuj ponownie lub zmień tekst źródłowy.").
    2.  Komunikat zawiera sugestie, co można zrobić dalej.
    3.  Użytkownik pozostaje na stronie z polem tekstowym, aby móc łatwo ponowić próbę.

---
### Przepływ Manualny i Zarządzanie Zestawami
---
*   ID: US-008
*   Tytuł: Tworzenie nowego, pustego zestawu
*   Opis: Jako użytkownik, chcę mieć możliwość stworzenia zestawu fiszek od zera, bez użycia AI, abym mógł ręcznie dodać własne pytania i odpowiedzi.
*   Kryteria akceptacji:
    1.  Na stronie "Moje zestawy" znajduje się przycisk "Stwórz nowy zestaw".
    2.  Po kliknięciu jestem przekierowany na ekran tworzenia zestawu, gdzie mogę nadać mu nazwę.
    3.  Ekran zawiera formularz do dodania pierwszej fiszki (Awers/Rewers) oraz przycisk "Dodaj kolejną fiszkę".

---
*   ID: US-009
*   Tytuł: Zarządzanie listą zestawów
*   Opis: Jako użytkownik, chcę widzieć wszystkie moje zapisane zestawy na jednej liście, aby móc łatwo nimi zarządzać i rozpoczynać naukę.
*   Kryteria akceptacji:
    1.  Strona "Moje zestawy" wyświetla listę wszystkich zestawów użytkownika.
    2.  Każdy element na liście pokazuje nazwę zestawu i liczbę zawartych w nim fiszek.
    3.  Przy każdym zestawie znajdują się przyciski "Ucz się" i "Usuń".
    4.  Kliknięcie "Usuń" powoduje wyświetlenie monitu z prośbą o potwierdzenie, a następnie usunięcie zestawu.

---
### Moduł Nauki
---
*   ID: US-010
*   Tytuł: Rozpoczynanie sesji nauki
*   Opis: Jako użytkownik, chcę móc rozpocząć sesję nauki z wybranego zestawu, klikając odpowiedni przycisk na liście moich zestawów.
*   Kryteria akceptacji:
    1.  Na liście zestawów, kliknięcie przycisku "Ucz się" przenosi mnie do interfejsu nauki.
    2.  System, opierając się na zintegrowanym algorytmie powtórek, wybiera fiszkę do wyświetlenia.
    3.  Na ekranie pojawia się awers pierwszej fiszki.

---
*   ID: US-011
*   Tytuł: Interakcja z fiszką podczas nauki
*   Opis: Jako użytkownik w trakcie sesji nauki, chcę móc odsłonić odpowiedź na fiszce, a następnie ocenić swoją wiedzę, aby system mógł zaplanować kolejne powtórki.
*   Kryteria akceptacji:
    1.  Po wyświetleniu awersu, kliknięcie przycisku "Pokaż odpowiedź" (lub samej fiszki) odsłania rewers.
    2.  Po odsłonięciu rewersu pojawiają się dwa przyciski: "Wiem" i "Nie wiem".
    3.  Kliknięcie jednego z przycisków powoduje zapisanie mojej odpowiedzi i załadowanie kolejnej fiszki zgodnie z logiką algorytmu.
    4.  Sesja trwa, dopóki algorytm nie zdecyduje o jej zakończeniu (np. po przejrzeniu określonej liczby kart).

---
*   ID: US-012
*   Tytuł: Zakończenie sesji nauki
*   Opis: Jako użytkownik, po zakończeniu sesji nauki, chcę zobaczyć ekran podsumowujący moje postępy.
*   Kryteria akceptacji:
    1.  Po ostatniej fiszce w sesji wyświetlany jest ekran podsumowania.
    2.  Podsumowanie zawiera podstawowe informacje, np. liczbę przejrzanych fiszek i procent poprawnych odpowiedzi.
    3.  Na ekranie podsumowania znajduje się przycisk umożliwiający powrót do listy moich zestawów.

## 6. Metryki sukcesu
Sukces projektu MVP będzie mierzony za pomocą dwóch kluczowych wskaźników, które odzwierciedlają jakość generowanych treści oraz przyjęcie głównej funkcjonalności przez użytkowników.
*   Kryterium 1: Jakość generowania przez AI
    *   Metryka: Procent akceptacji fiszek wygenerowanych przez AI.
    *   Cel: 75% fiszek wygenerowanych przez AI jest akceptowanych przez użytkownika.
    *   Sposób pomiaru: Za "akceptację" uznaje się każdą fiszkę, która nie została jawnie usunięta przez użytkownika podczas sesji edycji przed zapisaniem zestawu. Wskaźnik będzie obliczany jako: `1 - (suma usuniętych fiszek / suma wszystkich wygenerowanych fiszek w danych sesjach)`. Dane będą zbierane za pomocą analityki śledzącej zdarzenie `fiszka_usunięta_w_edycji`.

*   Kryterium 2: Adopcja funkcji AI
    *   Metryka: Udział fiszek stworzonych przy pomocy AI w ogólnej liczbie fiszek w systemie.
    *   Cel: 75% wszystkich fiszek tworzonych przez użytkowników powstaje z wykorzystaniem generatora AI.
    *   Sposób pomiaru: System analityczny będzie zliczał łączną liczbę fiszek w nowo tworzonych zestawach i oznaczał ich pochodzenie. Wskaźnik będzie obliczany jako: `(łączna liczba fiszek w zestawach stworzonych przez AI) / (łączna liczba fiszek we wszystkich nowo utworzonych zestawach)`.

</prd>

Jest to dokument wymagań produktu, który określa cechy, funkcjonalności i wymagania projektu.

2. <session_notes>
   <conversation_summary>
   <decisions>

Zestawy są prywatne i przypisane do jednego właściciela; brak współdzielenia w MVP.

Fiszka należy do dokładnie jednego zestawu; ma tylko pola tekstowe: awers/rewers; limit 1–1000 znaków.

Na start wystarczy model „per sesja nauki” (bez trwałego harmonogramu); warstwa długoterminowa do rozważenia później.

Oddzielamy etap generowania/recenzji AI od finalnego zapisu zestawu (robocze dane w osobnych tabelach).

Atrybut origin w kartach: ENUM('ai','manual','ai-corrected'); przechowujemy pochodzenie bez dodatkowych JOIN-ów.

Nazwy zestawów nie są unikalne; zestaw identyfikowany UUID; rezygnujemy ze slugów.

Potwierdzenie potrzeby zdarzeń analitycznych; ustalony zestaw przykładowych eventów.

Autoryzacja po stronie aplikacji (Symfony Voter); RLS w DB pomijamy w MVP.

Identyfikatory jako UUID; znaczniki czasu created_at/updated_at TIMESTAMPTZ; globalnie przyjęte.

Indeksy minimalne w MVP (PK/FK); bez dodatkowych poza niezbędnymi; brak unikatowości na nazwach, brak slugów.

</decisions>

<matched_recommendations>

Dodanie owner_user_id i spójnych FK we wszystkich tabelach domenowych – przyjęte.

Dwustopniowy model nauki (sesje + recenzje) z możliwością późniejszego dołożenia trwałego harmonogramu – przyjęte w wariancie MVP (na razie tylko sesje).

Oddzielenie ai_generations i generated_cards od sets/cards dla czystej analityki i prostego „acceptance rate” – przyjęte.

cards.origin = ENUM('ai','manual','ai-corrected') + ewentualne source_generation_id – przyjęte.

Minimalny event stream w analytics_events (event_type, metadata JSONB) – przyjęte wraz z przykładami.

Twarde usuwanie (cascade) dla zestawów i powiązanych kart; opcja snapshotów w reviews dla audytu – kierunek zaakceptowany (twarde usuwanie), snapshoty do rozważenia.

Rezygnacja z RLS w DB na rzecz Voterów w Symfony – przyjęte.

Spójne UUID, TIMESTAMPTZ, NOT NULL, CHECK długości pól tekstowych – przyjęte.

Minimalne indeksowanie pod główne listowania i spójność FK – przyjęte.

Ewentualne limity/quoty na generacje AI – kierunek zaakceptowany (do parametrów później).
</matched_recommendations>

<database_planning_summary>
[Główne wymagania dotyczące schematu]

Prywatny model właścicielski: każdy rekord domenowy posiada owner_user_id (FK → users.id), a zapytania filtrowane są aplikacyjnie.

Prosty CRUD na zestawach i fiszkach z walidacją długości treści.

Przepływ AI rozdzielony na: wejście → wyniki robocze → zapis finalny.

Nauka oparta na sesjach i ocenach („Wiem/Nie wiem”); bez długoterminowego scheduler’a w MVP.

Analityka minimalna, oparta na zdarzeniach z metadata JSONB.

[Kluczowe encje i relacje]

users(id UUID PK, email UNIQUE, password_hash, …)

sets(id UUID PK, owner_user_id FK→users, name, created_at, updated_at) — relacja 1:N z cards.

cards(id UUID PK, owner_user_id FK→users, set_id FK→sets ON DELETE CASCADE, front_text, back_text, origin ENUM('ai','manual','ai-corrected'), source_generation_id UUID NULL, created_at) — 1 karta należy do 1 zestawu.

ai_generations(id UUID PK, owner_user_id FK→users, input_text TEXT, model TEXT, status ENUM('pending','succeeded','failed'), error_message, created_at).

generated_cards(id UUID PK, owner_user_id FK→users, generation_id FK→ai_generations ON DELETE CASCADE, front_text, back_text, is_deleted_in_review BOOL, created_at) — dane robocze przed „Zapisz”.

study_sessions(id UUID PK, owner_user_id FK→users, set_id FK→sets, started_at, ended_at)

reviews(id UUID PK, owner_user_id FK→users, session_id FK→study_sessions ON DELETE CASCADE, set_id FK→sets, card_id FK→cards [ON DELETE SET NULL rozważane], shown_at, revealed_at, answer ENUM('know','dont_know'))

analytics_events(id UUID PK, owner_user_id FK→users, event_type TEXT, entity_type TEXT, entity_id UUID NULL, metadata JSONB, occurred_at TIMESTAMPTZ DEFAULT now())

(opcjonalnie) users_quotas(owner_user_id PK, ai_generations_monthly_limit INT, ai_generations_monthly_used INT, window_start TIMESTAMPTZ)

[Ważne kwestie bezpieczeństwa i skalowalności]

Bez RLS w DB: egzekwowanie dostępu w warstwie aplikacyjnej (Voters); wszystkie tabele mają owner_user_id.

CHECK-i długości treści kart (1–1000 znaków), NOT NULL na kluczowych kolumnach; CITEXT dla e-mail.

Twarde usuwanie zestawów z ON DELETE CASCADE; dla reviews.card_id rozważane SET NULL + ewentualne snapshoty treści, aby nie tracić historii.

Minimalne indeksy: PK/FK, sets(owner_user_id, created_at DESC), cards(set_id), oraz pod podstawowe listowania; dalsze indeksy po weryfikacji użycia.

Rozszerzalność: możliwość późniejszego dodania card_schedulers (due_at, ease, interval) bez łamania istniejącego schematu.

Koszt AI: opcjonalne limity/quoty i czyszczenie danych roboczych (ai_generations, generated_cards) wg TTL.

[Nierozwiązane kwestie / do doprecyzowania]

Retencja danych roboczych AI (dokładny TTL; np. 7/14/30 dni) i polityka automatycznego czyszczenia.

Decyzja o snapshotach w reviews (kopie front/back) versus ON DELETE SET NULL dla zachowania pełnej historii.

Parametry limitów/quot (wartości, okno czasowe) na generacje AI.

Zakres danych dot. bezpieczeństwa kont (weryfikacja e-mail, reset hasła: schemat tokenów i ich TTL).

Czy source_generation_id w cards ma być obligatoryjne dla origin IN ('ai','ai-corrected').

Finalna lista eventów i ich minimalne metadata (schemat kontraktowy pod analitykę oraz dashboardy).
</database_planning_summary>

<unresolved_issues>

TTL i polityka cleanup dla ai_generations/generated_cards.

Ostateczny wybór: snapshot treści w reviews vs. ON DELETE SET NULL dla reviews.card_id (lub oba).

Konkretne limity/quoty na generacje AI (wartości domyślne i reset okna).

Specyfika mechanizmu resetu hasła i weryfikacji e-mail (pola, indeksy, TTL tokenów).

Czy source_generation_id jest wymagane przy origin='ai'/'ai-corrected'.

Ostateczna specyfikacja katalogu zdarzeń analitycznych i minimalnego schematu metadata.
</unresolved_issues>
</conversation_summary>

   </session_notes>

Są to notatki z sesji planowania schematu bazy danych. Mogą one zawierać ważne decyzje, rozważania i konkretne wymagania omówione podczas spotkania.

3. <tech_stack>
   ### Podsumowanie rekomendacji

Główną rekomendacją jest **uproszczenie architektury** w celu maksymalnego przyspieszenia prac nad pierwszą wersją produktu (MVP). Zamiast budować dwa oddzielne byty (frontend i backend API), proponuję stworzenie **zintegrowanej aplikacji monolitycznej**, która jest szybsza w rozwoju i łatwiejsza w zarządzaniu na wczesnym etapie projektu.

1.  **Architektura:** Zrezygnuj z architektury headless (oddzielny frontend Astro komunikujący się z API) na rzecz **monolitu renderowanego po stronie serwera**. Pozwoli to na szybszy development, ponieważ cała logika aplikacji, łącznie z wyświetlaniem widoków, będzie zarządzana w jednym miejscu – w projekcie Symfony.
2.  **Backend:** Potwierdzam, że **Symfony i Doctrine** to doskonały wybór, zwłaszcza biorąc pod uwagę doświadczenie zespołu. W architekturze monolitycznej **rekomenduję rezygnację z API Platform**. Jego funkcje nie są potrzebne, gdy widoki są renderowane przez Twig, a standardowe kontrolery i formularze Symfony będą prostszym i bardziej naturalnym narzędziem do tego zadania.
3.  **Frontend:** Zamiast Astro i React, wykorzystaj natywny dla Symfony silnik szablonów **Twig** do generowania HTML. Do obsługi interaktywności (której w MVP nie ma wiele) użyj lekkich rozwiązań, takich jak **Symfony UX (Turbo/Stimulus)** lub minimalna ilość czystego JavaScriptu. To drastycznie zmniejszy złożoność frontendu.
4.  **Pozostałe komponenty:** Wybory dotyczące bazy danych (**PostgreSQL**), usług AI (**OpenRouter.ai**) oraz infrastruktury (**GitHub Actions, Docker, DigitalOcean**) są bardzo dobre i w pełni pasują również do uproszczonej architektury. Nie ma potrzeby ich zmieniać.

---

### Sugerowany Stos Technologiczny (Wersja zoptymalizowana dla MVP)

**Architektura: Monolit renderowany po stronie serwera**
*   **Cel:** Szybkie tworzenie, proste wdrożenie i łatwe utrzymanie na etapie MVP.

**Backend**
*   **PHP / Symfony:**
    *   **Rola:** Rdzeń aplikacji, obsługa logiki biznesowej, uwierzytelnianie, routing.
    *   **Uzasadnienie:** Wykorzystanie istniejącego doświadczenia zespołu, co gwarantuje wysoką produktywność. Framework jest dojrzały, bezpieczny i skalowalny.
*   **Doctrine ORM:**
    *   **Rola:** Mapowanie obiektowo-relacyjne, komunikacja z bazą danych.
    *   **Uzasadnienie:** Standard branżowy w ekosystemie Symfony, zapewnia bezpieczeństwo i wygodę pracy z danymi.

**Frontend**
*   **Twig:**
    *   **Rola:** Renderowanie widoków (HTML) po stronie serwera.
    *   **Uzasadnienie:** Głęboka integracja z Symfony, prosty i wydajny silnik szablonów, idealny dla architektury monolitycznej.
*   **Tailwind CSS:**
    *   **Rola:** Stylizowanie aplikacji.
    *   **Uzasadnienie:** Nowoczesne i szybkie podejście do pisania CSS, które świetnie integruje się z każdym frameworkiem, w tym z Symfony/Twig.
*   **Symfony UX (Turbo & Stimulus) *lub* minimalny JavaScript:**
    *   **Rola:** Dodanie interaktywności bez przeładowywania całej strony.
    *   **Uzasadnienie:** Pozwala na tworzenie dynamicznych interfejsów (np. walidacja formularzy, odświeżanie fragmentów strony) bez konieczności budowania pełnej aplikacji SPA w Reakcie. To idealny kompromis między prostotą a nowoczesnym doświadczeniem użytkownika.

**Baza Danych**
*   **PostgreSQL:**
    *   **Rola:** Przechowywanie danych użytkowników, zestawów fiszek itp.
    *   **Uzasadnienie:** Potężna, niezawodna i skalowalna baza danych open-source.

**Sztuczna Inteligencja (AI)**
*   **OpenRouter.ai:**
    *   **Rola:** Dostęp do modeli językowych w celu generowania fiszek.
    *   **Uzasadnienie:** Elastyczność i kontrola kosztów. Pozostaje bez zmian.

**CI/CD i Hosting**
*   **GitHub Actions, Docker, DigitalOcean:**
    *   **Rola:** Automatyzacja, konteneryzacja i hosting aplikacji.
    *   **Uzasadnienie:** Nowoczesny i sprawdzony zestaw narzędzi, który zapewnia płynny proces wdrażania i skalowalność. Pozostaje bez zmian.


Argumenty za usunięciem API Platform w monolicie z Twig:
Uproszczenie architektury: Eliminuje się całą warstwę abstrakcji związaną z serializacją danych do formatu JSON, negocjacją treści, generowaniem dokumentacji API i obsługą endpointów. Zmniejsza to próg wejścia dla nowych deweloperów i upraszcza utrzymanie kodu.
Bezpośrednia integracja z komponentami Symfony: Można w pełni wykorzystać standardowe komponenty Symfony, takie jak symfony/form do obsługi formularzy i walidacji, co jest naturalnym i potężnym rozwiązaniem w aplikacjach renderowanych po stronie serwera.[4] Próba połączenia formularzy Symfony z endpointami API Platform może być skomplikowana i nieintuicyjna.[5]
Mniejszy narzut i większa wydajność: Chociaż API Platform jest zoptymalizowane, jego usunięcie oznacza mniej zależności i potencjalnie szybsze działanie aplikacji, ponieważ pomija się proces serializacji/deserializacji danych.
Zgodność z celem MVP: Celem MVP jest szybkie dostarczenie produktu. Usunięcie niekluczowej w tym scenariuszu technologii przyspieszy development, ponieważ zespół będzie mógł skupić się na logice biznesowej i interfejsie renderowanym w Twig.
   </tech_stack>

Opisuje stack technologiczny, który zostanie wykorzystany w projekcie, co może wpłynąć na decyzje dotyczące projektu bazy danych.

Wykonaj następujące kroki, aby utworzyć schemat bazy danych:

1. Dokładnie przeanalizuj notatki z sesji, identyfikując kluczowe jednostki, atrybuty i relacje omawiane podczas sesji planowania.
2. Przejrzyj PRD, aby upewnić się, że wszystkie wymagane funkcje i funkcjonalności są obsługiwane przez schemat bazy danych.
3. Przeanalizuj stack technologiczny i upewnij się, że projekt bazy danych jest zoptymalizowany pod kątem wybranych technologii.

4. Stworzenie kompleksowego schematu bazy danych, który obejmuje
   a. Tabele z odpowiednimi nazwami kolumn i typami danych
   b. Klucze podstawowe i klucze obce
   c. Indeksy poprawiające wydajność zapytań
   d. Wszelkie niezbędne ograniczenia (np. unikalność, not null)

5. Zdefiniuj relacje między tabelami, określając kardynalność (jeden-do-jednego, jeden-do-wielu, wiele-do-wielu) i wszelkie tabele łączące wymagane dla relacji wiele-do-wielu.

6. Opracowanie zasad PostgreSQL dla zabezpieczeń na poziomie wiersza (RLS), jeśli dotyczy, w oparciu o wymagania określone w notatkach z sesji lub PRD.

7. Upewnij się, że schemat jest zgodny z najlepszymi praktykami projektowania baz danych, w tym normalizacji do odpowiedniego poziomu (zwykle 3NF, chyba że denormalizacja jest uzasadniona ze względu na wydajność).

Ostateczny wynik powinien mieć następującą strukturę:
```markdown
1. Lista tabel z ich kolumnami, typami danych i ograniczeniami
2. Relacje między tabelami
3. Indeksy
4. Zasady PostgreSQL (jeśli dotyczy)
5. Wszelkie dodatkowe uwagi lub wyjaśnienia dotyczące decyzji projektowych
```

W odpowiedzi należy podać tylko ostateczny schemat bazy danych w formacie markdown, który zapiszesz w pliku .ai/db-plan.md bez uwzględniania procesu myślowego lub kroków pośrednich. Upewnij się, że schemat jest kompleksowy, dobrze zorganizowany i gotowy do wykorzystania jako podstawa do tworzenia migracji baz danych.
