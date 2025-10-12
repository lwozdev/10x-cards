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