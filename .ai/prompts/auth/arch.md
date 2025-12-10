Jesteś doświadczonym full-stack web developerem specjalizującym się we wdrażaniu modułu rejestracji, logowania i
odzyskiwania hasła użytkowników. Opracuj szczegółową architekturę tej funkcjonalności na podstawie wymagań z pliku
@.ai/prd.md oraz stack z @.ai/tech-stack.md.

Zadbaj o zgodność z pozostałymi wymaganiami - nie możesz naruszyć istniejącego działania aplikacji opisanego w
dokumentacji.

Specyfikacja powinna zawierać następujące elementy:

1. ARCHITEKTURA INTERFEJSU UŻYTKOWNIKA

- Dokładny opis zmian w warstwie frontendu (stron, komponentów i layoutów w trybie auth i non-auth), w tym opis nowych
  elementów oraz tych do rozszerzenia o wymagania autentykacji
- Dokładne rozdzielenie odpowiedzialności pomiędzy formularze i komponenty client-side, biorąc pod
  uwagę ich integrację z backendem autentykacji oraz nawigacją i akcjami użytkownika
- Opis przypadków walidacji i komunikatów błędów
- Obsługę najważniejszych scenariuszy

2. LOGIKA BACKENDOWA

- Struktura endpointów API i modeli danych zgodnych z nowymi elementami interfejsu użytkownika
- Mechanizm walidacji danych wejściowych
- Obsługa wyjątków
- Aktualizacja sposobu renderowania wybranych stron server-side biorąc pod uwagę @astro.config.mjs

3. SYSTEM AUTENTYKACJI

- Wykorzystanie symfony security do realizacji funkcjonalności rejestracji, logowania, wylogowywania i odzyskiwania konta 
Do resetowania hasła możemy wykorzystać: symfonycasts/reset-password-bundle
Wykorzystaj LexikJWTAuthenticationBundle

Przedstaw kluczowe wnioski w formie opisowej technicznej specyfikacji w języku polskim - bez docelowej implementacji,
ale ze wskazaniem poszczególnych komponentów, modułów, serwisów i kontraktów. Po ukończeniu zadania, utwórz plik
.ai/auth-spec.md i dodaj tam całą specyfikację.


Ważne. Nie umieszczaj fragmentów kodu ani nie umieszczaj szczegółów implementacyjnych, tym zajmiemy się w kolejnych etapach.
