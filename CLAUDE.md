# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is an AI-powered flashcard generator web application (MVP) built with Symfony 7.3. The application helps students (primary and secondary school) create educational flashcards automatically from their notes using AI, with manual creation options and a spaced repetition learning system.

**Key Features:**
- AI-powered flashcard generation from user-provided text (1000-10000 characters)
- Manual flashcard creation and editing
- User authentication and flashcard set management
- Learning module with spaced repetition algorithm
- Analytics for tracking flashcard generation quality and adoption

**Target Metrics:**
- 75% acceptance rate for AI-generated flashcards
- 75% of all flashcards created using AI generator

**Tech Stack:**
- Backend: Symfony 7.3 (PHP 8.2+) with Doctrine ORM
- Frontend: Twig templates with Symfony UX (Turbo & Stimulus) for interactivity
- Styling: Tailwind CSS (via Asset Mapper)
- Database: PostgreSQL 16
- AI: OpenRouter.ai integration for flashcard generation
- Infrastructure: Docker, Docker Compose, nginx

## Project Structure

```
src/
├── Controller/     # HTTP controllers (currently empty - needs implementation)
├── Entity/        # Doctrine entities (User, Flashcard, FlashcardSet)
├── Repository/    # Doctrine repositories for data access
└── Kernel.php     # Application kernel

config/
├── packages/      # Bundle configurations (doctrine, security, twig, etc.)
└── routes/        # Routing configuration

templates/         # Twig templates
assets/           # Frontend assets (JS, CSS)
├── controllers/  # Stimulus controllers
└── styles/       # CSS files

migrations/       # Doctrine database migrations
tests/           # PHPUnit tests
.ai/             # Product documentation (PRD, tech stack analysis)
```

## Common Commands

### Development Setup
```bash
# Install PHP dependencies
composer install

# Install frontend dependencies (Asset Mapper)
php bin/console importmap:install

# Create database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate

# Clear cache
php bin/console cache:clear
```

### Docker Environment
```bash
# Start all services (postgres, backend, nginx)
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker-compose logs -f backend

# Access backend container
docker exec -it flashcards-backend bash
```

**Services:**
- PostgreSQL: exposed on port 5432
- Nginx: exposed on port 8000
- Backend container: `flashcards-backend`

### Database Operations
```bash
# Create a new migration
php bin/console make:migration

# Execute migrations
php bin/console doctrine:migrations:migrate

# Validate schema
php bin/console doctrine:schema:validate

# Generate entity
php bin/console make:entity
```

### Testing
```bash
# Run all tests
php vendor/bin/phpunit

# Run specific test file
php vendor/bin/phpunit tests/PathToTest.php

# Run tests with coverage
php vendor/bin/phpunit --coverage-html var/coverage
```

Test environment is configured with:
- Separate test database (suffix: `_test`)
- Strict error handling (fails on deprecations, notices, warnings)
- Configuration in `phpunit.dist.xml`

### Code Generation
```bash
# Generate controller
php bin/console make:controller

# Generate form
php bin/console make:form

# Generate repository
php bin/console make:repository

# Generate subscriber/listener
php bin/console make:subscriber
```

### Development Server
```bash
# Start Symfony development server
symfony serve

# Or use PHP built-in server
php -S localhost:8000 -t public/
```

## Architecture Guidelines

### Monolithic Server-Side Rendered Application
This is NOT a headless/API-first application. The architecture deliberately uses:
- **Server-side rendering** with Twig templates
- **Standard Symfony forms** for data handling and validation
- **Symfony UX (Turbo/Stimulus)** for modern interactivity without full SPA complexity
- **No API Platform** - direct controller-to-view rendering

**Rationale:** This architecture was chosen over headless (separate frontend + API) to maximize development speed for MVP. Building one integrated application is faster than building two separate systems.

### Entity Design Principles
When creating entities for this application:
- **User entity**: handles authentication, owns multiple FlashcardSets
- **FlashcardSet entity**: contains metadata (name, created date), belongs to User, has many Flashcards, tracks source (AI vs manual)
- **Flashcard entity**: has `front` and `back` fields, belongs to FlashcardSet, includes spaced repetition data (next_review_date, ease_factor, interval)
- Use Doctrine attributes for mapping (no XML/YAML)
- Repository pattern for data access

### Controller Structure
Controllers should:
- Handle HTTP requests/responses
- Use Symfony forms for input validation
- Render Twig templates directly (no JSON APIs)
- Keep business logic in services
- Use type declarations for better IDE support

### Frontend Approach
- **Twig templates** for all HTML rendering
- **Tailwind CSS** for styling (via Asset Mapper, not Webpack)
- **Stimulus controllers** for interactive components (flashcard flip, character counter, form validation)
- **Turbo** for SPA-like navigation without page reloads
- Minimal vanilla JavaScript when needed

Key interactive features to implement with Stimulus:
- Dynamic character counter (1000-10000 limit) with visual feedback
- Enable/disable "Generate" button based on character count
- Loading animations during AI generation
- Inline editing of flashcard front/back
- "Show Answer" button in learning interface

### Security Configuration
- Password hashing: auto-configured for `PasswordAuthenticatedUserInterface`
- Implement proper user provider (currently using `users_in_memory` placeholder)
- Configure firewalls and access control in `config/packages/security.yaml`
- Use Symfony Security component for authentication/authorization
- Password reset functionality required per PRD

### Database Configuration
- PostgreSQL 16 as primary database
- Connection via `DATABASE_URL` environment variable
- Migrations stored in `migrations/` directory
- Use identity generation for PostgreSQL
- Schema validation enabled in dev/test environments

## Integration Guidelines

### AI Integration (OpenRouter.ai)
When implementing AI flashcard generation:
- Store API credentials in `.env.local` (not committed)
- Use Symfony HTTP Client for API requests
- Implement retry logic for API failures
- Parse AI responses and validate flashcard format
- Provide clear error messages to users on failure
- Track generation metrics for analytics
- Instruct AI to generate flashcards in simple, concise language appropriate for students

### Analytics Implementation
Required event tracking:
- Flashcard deletion during editing (`fiszka_usunięta_w_edycji`)
- Source of flashcard creation (AI vs manual)
- User acceptance rate calculation: `1 - (deleted/generated)`
- Total flashcard counts by source

### Spaced Repetition Algorithm
- Use external open-source algorithm (e.g., SM-2 or Leitner system)
- Store repetition metadata in Flashcard entity:
  - `next_review_date`: when to show card next
  - `ease_factor`: difficulty modifier
  - `interval`: days between reviews
- Update on each review based on "Know"/"Don't Know" response
- Simple two-button interface: "Wiem" (Know) and "Nie wiem" (Don't Know)

### Auto-Generated Set Name
When saving a new flashcard set:
- Analyze the source text to suggest a name
- Can use simple text extraction (first sentence) or AI-based summarization
- User should be able to override the suggestion

## Environment Variables

Key variables to configure in `.env.local`:
```
APP_ENV=dev
APP_SECRET=<generate-secure-secret>
DATABASE_URL=postgresql://user:pass@postgres:5432/dbname?serverVersion=16&charset=utf8
OPENROUTER_API_KEY=<your-api-key>
OPENROUTER_API_URL=https://openrouter.ai/api/v1/chat/completions
```

## Product Requirements Reference

See `.ai/prd.md` for complete product requirements document including:
- User stories (US-001 through US-012)
- Functional requirements
- Success metrics (75% acceptance rate, 75% AI adoption)
- MVP boundaries and out-of-scope features

**Key User Flows:**
1. **Registration/Login** (US-001, US-002): Email/password authentication
2. **AI Generation** (US-003, US-005, US-006, US-007): Paste text -> Generate -> Edit/Review -> Save
3. **Manual Creation** (US-008): Create empty set -> Add flashcards manually
4. **Set Management** (US-009): List all sets with name, card count, Learn/Delete actions
5. **Learning** (US-010, US-011, US-012): Start session -> Show front -> Show back -> Rate (Know/Don't Know) -> Summary

**MVP Boundaries (Out of Scope):**
- No custom spaced repetition algorithm (use existing library)
- No file imports (PDF, DOCX, CSV)
- No social features or sharing between users
- No mobile apps (web-only)
- No images/audio in flashcards (text-only)
- No external platform integrations (Google Classroom, Moodle)

## Naming Conventions

- Controllers: `{Name}Controller` (e.g., `FlashcardController`)
- Entities: Singular nouns (e.g., `Flashcard`, `FlashcardSet`, `User`)
- Repositories: `{Entity}Repository` (e.g., `FlashcardRepository`)
- Templates: snake_case (e.g., `flashcard/create.html.twig`)
- Routes: snake_case with meaningful names (e.g., `flashcard_generate`, `set_list`)
- Services: interfaces with `Interface` suffix, implementations without (e.g., `FlashcardGeneratorInterface`)

## Debugging

- Web Profiler available at `/_profiler` in dev mode
- Monolog logs in `var/log/`
- Use `dump()` or `dd()` functions in controllers/templates
- Enable query logging in `config/packages/doctrine.yaml` for DB debugging
- Use `bin/console debug:router` to list all routes
- Use `bin/console debug:container` to inspect services

## Technology Rationale

This architecture was chosen based on a detailed analysis (see `.ai/tech-stack-prompt.md`):

**Why Monolith over Headless?**
- Faster MVP development (one application instead of separate frontend + API)
- Simpler deployment and maintenance
- Less complexity for small team
- Twig + Symfony UX provides modern interactivity without SPA overhead

**Why No API Platform?**
- Not needed when rendering views server-side
- Adds unnecessary abstraction layer
- Standard Symfony forms are more natural for this use case
- Faster development by focusing on business logic

**Why This Stack is Cost-Effective:**
- All major technologies are open-source (no licensing costs)
- DigitalOcean offers competitive pricing
- OpenRouter.ai provides flexibility to switch AI models for best price/quality
- PHP/Symfony developers are widely available

**Security Benefits:**
- Symfony Security component protects against SQL injection, XSS, CSRF
- Doctrine ORM automatically parametrizes queries
- Twig auto-escapes output by default
- Built-in secure password hashing and session management
