# Diagram Architektury Autentykacji

## Opis
Diagram przedstawia przepływy autentykacji w aplikacji Generator Fiszek AI zbudowanej na Symfony 7.3. System wykorzystuje sesje PHP oraz Symfony Security Component do zarządzania autentykacją użytkowników.

## Kluczowe elementy architektury
- **Sesje PHP**: Standardowy mechanizm autentykacji (nie JWT)
- **Security Component**: Komponent Symfony do walidacji credentials
- **Password Hashing**: Automatyczne hashowanie haseł (bcrypt/argon2)
- **Dostęp gościa**: Generowanie fiszek możliwe bez logowania
- **Chronionych zasoby**: Zapisywanie, zarządzanie zestawami wymaga logowania

<mermaid_diagram>
```mermaid
sequenceDiagram
    autonumber

    participant P as Przeglądarka
    participant N as Nginx
    participant S as Symfony App
    participant SC as Security Component
    participant DB as Baza Danych

    %% === REJESTRACJA ===
    Note over P,DB: Przepływ Rejestracji (US-001)

    P->>N: GET /register
    N->>S: Przekazanie żądania
    S->>P: Formularz rejestracji

    P->>N: POST /register<br/>(email, hasło, potwierdzenie)
    N->>S: Przekazanie danych

    activate S
    S->>S: Walidacja formularza<br/>(FormType)

    alt Email niepoprawny lub hasła niezgodne
        S->>P: Błąd walidacji<br/>(pozostań na formularzu)
    else Dane poprawne
        S->>DB: Sprawdź czy email istnieje

        alt Email już zajęty
            DB->>S: Email istnieje
            S->>P: Błąd: Email już zajęty
        else Email wolny
            DB->>S: Email dostępny
            S->>S: Hash hasła (bcrypt/argon2)
            S->>DB: INSERT INTO users<br/>(email, hashed_password)
            DB->>S: Użytkownik utworzony

            S->>SC: Auto-logowanie<br/>(setToken)
            activate SC
            SC->>SC: Utworzenie sesji
            SC->>S: Sesja aktywna
            deactivate SC

            S->>P: 302 Redirect + Cookie sesyjny<br/>→ /sets
        end
    end
    deactivate S

    %% === LOGOWANIE ===
    Note over P,DB: Przepływ Logowania (US-002)

    P->>N: GET /login
    N->>S: Przekazanie żądania
    S->>P: Formularz logowania<br/>(+ link do resetu hasła)

    P->>N: POST /login<br/>(email, hasło)
    N->>S: Przekazanie credentials

    activate S
    S->>SC: Autentykacja (email, hasło)
    activate SC

    SC->>DB: SELECT user WHERE email = ?

    alt Użytkownik nie istnieje
        DB->>SC: Brak użytkownika
        SC->>S: Authentication failed
        deactivate SC
        S->>P: Błąd: Nieprawidłowy email<br/>lub hasło
    else Użytkownik istnieje
        DB->>SC: user(id, email, hashed_password)
        SC->>SC: Weryfikacja hasła<br/>(password_verify)

        alt Hasło niepoprawne
            SC->>S: Authentication failed
            deactivate SC
            S->>P: Błąd: Nieprawidłowy email<br/>lub hasło
        else Hasło poprawne
            SC->>SC: Utworzenie sesji PHP
            SC->>S: Token autentykacji
            deactivate SC

            S->>P: 302 Redirect + Cookie sesyjny<br/>→ /sets (Moje zestawy)
        end
    end
    deactivate S

    %% === DOSTĘP DO CHRONIONEGO ZASOBU ===
    Note over P,DB: Dostęp do chronionego zasobu (US-006, US-009)

    P->>N: GET /sets + Cookie sesyjny
    N->>S: Przekazanie żądania

    activate S
    S->>SC: Weryfikacja sesji
    activate SC

    alt Sesja nieważna lub wygasła
        SC->>S: Brak autentykacji
        deactivate SC
        S->>P: 302 Redirect → /login
    else Sesja ważna
        SC->>DB: Pobierz dane użytkownika<br/>(UserProvider)
        DB->>SC: user(id, email, roles)
        SC->>S: User authenticated
        deactivate SC

        S->>DB: SELECT sets WHERE user_id = ?
        DB->>S: Lista zestawów
        S->>P: Widok "Moje zestawy"
    end
    deactivate S

    %% === DOSTĘP GOŚCIA DO GENEROWANIA ===
    Note over P,DB: Dostęp gościa do generowania (US-010)

    P->>N: GET /generate (bez cookie)
    N->>S: Przekazanie żądania

    activate S
    S->>S: Sprawdzenie autentykacji<br/>(opcjonalna)

    Note over S: Generator dostępny<br/>dla gości

    S->>P: Widok generatora fiszek
    deactivate S

    P->>N: POST /api/generate<br/>(text: 1000-10000 znaków)
    N->>S: Przekazanie tekstu

    activate S
    S->>S: Generowanie przez AI<br/>(OpenRouter)
    S->>P: JSON: wygenerowane fiszki
    deactivate S

    Note over P: Użytkownik edytuje fiszki

    P->>N: POST /sets/save (bez cookie)
    N->>S: Próba zapisu zestawu

    activate S
    S->>SC: Weryfikacja autentykacji
    activate SC
    SC->>S: Brak autentykacji
    deactivate SC

    S->>P: 302 Redirect → /login<br/>(+ komunikat: Zaloguj się aby zapisać)
    deactivate S

    %% === WYLOGOWANIE ===
    Note over P,DB: Przepływ Wylogowania (US-010)

    P->>N: POST /logout + Cookie sesyjny
    N->>S: Żądanie wylogowania

    activate S
    S->>SC: Logout
    activate SC
    SC->>SC: Zniszczenie sesji PHP<br/>(session_destroy)
    SC->>S: Sesja zakończona
    deactivate SC

    S->>P: 302 Redirect<br/>+ Usuń cookie → /
    deactivate S

    %% === RESET HASŁA ===
    Note over P,DB: Przepływ Resetowania Hasła (US-002)

    P->>N: GET /reset-password
    N->>S: Żądanie formularza
    S->>P: Formularz: podaj email

    P->>N: POST /reset-password<br/>(email)
    N->>S: Przekazanie emaila

    activate S
    S->>DB: SELECT user WHERE email = ?

    alt Email nie istnieje
        DB->>S: Brak użytkownika
        Note over S: Nie ujawniamy czy email<br/>istnieje (bezpieczeństwo)
        S->>P: Komunikat: Sprawdź email
    else Email istnieje
        DB->>S: user(id, email)
        S->>S: Generowanie tokenu resetującego<br/>(UUID + expiry)
        S->>DB: INSERT INTO reset_tokens<br/>(user_id, token, expires_at)
        S->>P: Komunikat: Sprawdź email

        Note over S: Wysłanie emaila z linkiem:<br/>/reset-password/confirm?token=XXX
    end
    deactivate S

    P->>N: GET /reset-password/confirm<br/>?token=XXX
    N->>S: Weryfikacja tokenu

    activate S
    S->>DB: SELECT token WHERE<br/>token = ? AND expires_at > NOW()

    alt Token nieważny lub wygasły
        DB->>S: Brak tokenu
        S->>P: Błąd: Link wygasł
    else Token ważny
        DB->>S: token(user_id)
        S->>P: Formularz: nowe hasło

        P->>N: POST /reset-password/confirm<br/>(token, new_password)
        N->>S: Nowe hasło

        S->>S: Hash nowego hasła
        S->>DB: UPDATE users<br/>SET password = ? WHERE id = ?
        S->>DB: DELETE FROM reset_tokens<br/>WHERE token = ?

        S->>P: 302 Redirect → /login<br/>(+ komunikat: Hasło zmienione)
    end
    deactivate S
```
</mermaid_diagram>

## Kluczowe punkty bezpieczeństwa

### Hashowanie haseł
- Symfony automatycznie używa bcrypt lub argon2
- Hasła nigdy nie są przechowywane w plain text
- Minimalna długość: 8 znaków (US-001)

### Sesje PHP
- Cookie sesyjny httpOnly (nie dostępny z JS)
- Secure flag w produkcji (tylko HTTPS)
- Timeout po okresie nieaktywności
- Regeneracja ID sesji po logowaniu

### Reset hasła
- Token jednorazowy z expiry (np. 1h)
- Nie ujawniamy czy email istnieje w systemie
- Token automatycznie usuwany po użyciu

### Dostęp gościa
- Generowanie fiszek: dozwolone bez logowania
- Zapisywanie zestawów: wymaga autentykacji
- Przekierowanie do /login z komunikatem

### Walidacja
- Email: format RFC 5322
- Hasło: minimum 8 znaków
- Duplicate email check przed rejestracją
- Form CSRF protection (automatyczne w Symfony)

## Możliwe rozszerzenia (poza MVP)

1. **2FA (Two-Factor Authentication)**
   - TOTP (Google Authenticator)
   - SMS verification

2. **Remember Me**
   - Długoterminowe cookie
   - Automatyczne logowanie

3. **Rate Limiting**
   - Ochrona przed brute-force
   - Symfony RateLimiter component

4. **Email Verification**
   - Weryfikacja emaila po rejestracji
   - Aktywacja konta przez link

5. **OAuth**
   - Google, GitHub login
   - Symfony HWIOAuthBundle
