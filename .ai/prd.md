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
