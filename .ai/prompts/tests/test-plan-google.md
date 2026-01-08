---
description: 
globs: 
alwaysApply: false
---
Jesteś doświadczonym inżynierem QA, którego zadaniem jest stworzenie kompleksowego planu testów dla projektu programistycznego. Przeanalizuj poniższe informacje o projekcie:

<kod_projektu>
(Files content cropped to 300k characters, download full ingest to see more)
================================================
FILE: README.md
================================================
# AI Flashcard Generator

An AI-powered web application designed to help primary and secondary school students create educational flashcards automatically from their notes. The application streamlines the learning process by eliminating the time-consuming task of manual flashcard creation while promoting effective spaced repetition learning.

## Table of Contents

- [Project Description](#project-description)
- [Tech Stack](#tech-stack)
- [Getting Started Locally](#getting-started-locally)
- [Available Scripts](#available-scripts)
- [Project Scope](#project-scope)
- [Project Status](#project-status)
- [License](#license)

## Project Description

AI Flashcard Generator is a web application (MVP) that transforms student notes into ready-to-study flashcards using artificial intelligence. Users can paste their notes (1,000-10,000 characters), and the application automatically generates a set of flashcards optimized for learning.

### Key Features

- **AI-Powered Generation**: Automatically create flashcards from text using advanced language models
- **Manual Creation & Editing**: Create flashcard sets from scratch or edit AI-generated cards
- **User Authentication**: Secure account system with email/password login and password reset
- **Set Management**: Organize flashcards into named sets, view all saved sets, and track progress
- **Learning Module**: Study flashcards with a spaced repetition algorithm for optimal retention
- **Analytics**: Track flashcard quality and adoption metrics

### Target Metrics

- 75% acceptance rate for AI-generated flashcards
- 75% of all flashcards created using the AI generator

## Tech Stack

### Architecture
**Server-side rendered monolithic application** - chosen for rapid MVP development, simpler deployment, and easier maintenance compared to headless/API-first architecture.

### Backend
- **PHP 8.2+** with **Symfony 7.3** - Core application framework
- **Doctrine ORM 3.5** - Database abstraction and entity management
- **Symfony Security** - Authentication and authorization
- **Symfony Form** - Form handling and validation
- **Symfony HTTP Client** - AI service integration

### Frontend
- **Twig** - Server-side template engine
- **Tailwind CSS** - Utility-first CSS framework
- **Symfony UX (Turbo & Stimulus)** - Modern interactivity without SPA complexity

### Database
- **PostgreSQL 16** - Primary data storage

### AI Integration
- **OpenRouter.ai** - Flexible access to various language models for flashcard generation

### Infrastructure
- **Docker & Docker Compose** - Containerization and local development
- **nginx** - Web server
- **GitHub Actions** - CI/CD automation
- **DigitalOcean** - Hosting platform

### Development Tools
- **PHPUnit 12.4** - Testing framework
- **Symfony Maker Bundle** - Code generation
- **Symfony Web Profiler** - Debugging and profiling

## Getting Started Locally

### Prerequisites

- Docker and Docker Compose installed on your system
- Git for cloning the repository

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd cards
   ```

2. **Configure environment variables**

   Create a `.env.local` file in the project root:
   ```bash
   cp .env .env.local
   ```

   Configure the following variables in `.env.local`:
   ```env
   APP_ENV=dev
   APP_SECRET=<generate-a-secure-secret>
   DATABASE_URL=postgresql://user:pass@postgres:5432/flashcards?serverVersion=16&charset=utf8
   OPENROUTER_API_KEY=<your-openrouter-api-key>
   OPENROUTER_API_URL=https://openrouter.ai/api/v1/chat/completions
   ```

3. **Start Docker services**
   ```bash
   docker-compose up -d
   ```

   This will start:
    - PostgreSQL database (port 5432)
    - Symfony backend application
    - nginx web server (port 8000)

4. **Install dependencies**
   ```bash
   docker exec -it flashcards-backend composer install
   docker exec -it flashcards-backend php bin/console importmap:install
   ```

5. **Run database migrations**
   ```bash
   docker exec -it flashcards-backend php bin/console doctrine:migrations:migrate
   ```

6. **Access the application**

   Open your browser and navigate to: `http://localhost:8000`

### Stopping the Application

```bash
docker-compose down
```

## Available Scripts

### Dependency Management
```bash
# Install PHP dependencies
composer install

# Install frontend dependencies (Asset Mapper)
php bin/console importmap:install
```

### Database Operations
```bash
# Create database
php bin/console doctrine:database:create

# Run migrations
php bin/console doctrine:migrations:migrate

# Create new migration
php bin/console make:migration

# Validate database schema
php bin/console doctrine:schema:validate
```

### Development
```bash
# Clear application cache
php bin/console cache:clear

# Start Symfony development server (alternative to Docker)
symfony serve

# Generate controller
php bin/console make:controller

# Generate entity
php bin/console make:entity

# Generate form
php bin/console make:form
```

### Testing
```bash
# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/PathToTest.php

# Run tests with coverage report
vendor/bin/phpunit --coverage-html var/coverage
```

### Docker Commands
```bash
# View logs
docker-compose logs -f backend

# Access backend container shell
docker exec -it flashcards-backend bash

# Restart services
docker-compose restart
```

### Debugging
```bash
# List all routes
php bin/console debug:router

# Inspect services
php bin/console debug:container

# View Symfony Web Profiler
# Available at http://localhost:8000/_profiler in dev mode
```

## Project Scope

### In Scope (MVP Features)

#### User Authentication
- User registration with email and password
- Login functionality
- Password reset mechanism

#### AI Flashcard Generation
- Text input field with 1,000-10,000 character limit
- Dynamic character count validation
- AI-powered flashcard creation from user-provided text
- Error handling and user feedback
- Automatic set naming based on content

#### Manual Flashcard Management
- Create empty flashcard sets
- Add flashcards manually (front/back fields)
- Edit flashcard content (front and back)
- Delete individual flashcards
- Delete entire sets

#### Set Management
- Preview generated flashcards before saving
- Name flashcard sets with AI-suggested names
- List all user's flashcard sets with metadata (name, card count)
- Organize sets by creation date

#### Learning Module
- Simple interface displaying flashcard front
- Reveal answer functionality
- Two-button response system: "Know" and "Don't Know"
- Spaced repetition algorithm integration (using open-source library)
- Session summary with basic statistics

#### Analytics
- Track flashcard deletion during editing
- Monitor flashcard source (AI vs manual)
- Calculate acceptance rate: `1 - (deleted/generated)`
- Measure AI adoption rate

### Out of Scope (Not in MVP)

- **Custom spaced repetition algorithm** - Will use existing open-source solution (e.g., SM-2 or Leitner system)
- **File imports** - No support for PDF, DOCX, CSV, or other file formats
- **Social features** - No sharing flashcard sets between users
- **Mobile applications** - Web-only application
- **Multimedia content** - Text-only flashcards (no images, audio, or video)
- **External integrations** - No integration with Google Classroom, Moodle, or other platforms
- **Advanced features** - No collaborative editing, public flashcard libraries, or gamification

## Project Status

**Current Status**: MVP in Active Development

### Completed
- Project setup with Symfony 7.3
- Docker development environment configuration
- Database schema design (entities: User, FlashcardSet, Flashcard)
- Doctrine ORM integration
- Security component configuration
- Frontend tooling setup (Twig, Symfony UX, Asset Mapper)

### In Progress
- User authentication implementation
- AI integration with OpenRouter.ai
- Flashcard generation flow
- Set management interface
- Learning module

### Planned
- Spaced repetition algorithm integration
- Analytics implementation
- UI/UX refinement with Tailwind CSS
- Testing coverage
- Production deployment pipeline

## License

**Proprietary** - All rights reserved.

This project is proprietary software developed for educational purposes. Unauthorized copying, distribution, or modification of this software is strictly prohibited.

---

For detailed product requirements, see `.ai/prd.md`
For technical stack analysis, see `.ai/tech-stack.md`
For project-specific guidance, see `CLAUDE.md`


================================================
FILE: CLAUDE.md
================================================
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



================================================
FILE: cloude-help.md
================================================
/ide
/terminal-



================================================
FILE: compose.override.yaml
================================================
#
#services:
####> doctrine/doctrine-bundle ###
#  database:
#    ports:
#      - "5432"
####< doctrine/doctrine-bundle ###
#
####> symfony/mailer ###
#  mailer:
##    image: axllent/mailpit
#    ports:
#      - "1025"
#      - "8025"
#    environment:
#      MP_SMTP_AUTH_ACCEPT_ANY: 1
#      MP_SMTP_AUTH_ALLOW_INSECURE: 1
####< symfony/mailer ###



================================================
FILE: composer.json
================================================
{
"type": "project",
"license": "proprietary",
"minimum-stability": "stable",
"prefer-stable": true,
"require": {
"php": ">=8.2",
"ext-ctype": "*",
"ext-iconv": "*",
"doctrine/dbal": "^3",
"doctrine/doctrine-bundle": "^2.18",
"doctrine/doctrine-migrations-bundle": "^3.4",
"doctrine/orm": "^3.5",
"lexik/jwt-authentication-bundle": "^3.1",
"phpdocumentor/reflection-docblock": "^5.6",
"phpstan/phpdoc-parser": "^2.3",
"symfony/asset": "7.3.*",
"symfony/asset-mapper": "7.3.*",
"symfony/console": "7.3.*",
"symfony/doctrine-messenger": "7.3.*",
"symfony/dotenv": "7.3.*",
"symfony/expression-language": "7.3.*",
"symfony/flex": "^2",
"symfony/form": "7.3.*",
"symfony/framework-bundle": "7.3.*",
"symfony/http-client": "7.3.*",
"symfony/intl": "7.3.*",
"symfony/mailer": "7.3.*",
"symfony/mime": "7.3.*",
"symfony/monolog-bundle": "^3.10",
"symfony/notifier": "7.3.*",
"symfony/process": "7.3.*",
"symfony/property-access": "7.3.*",
"symfony/property-info": "7.3.*",
"symfony/runtime": "7.3.*",
"symfony/security-bundle": "7.3.*",
"symfony/serializer": "7.3.*",
"symfony/stimulus-bundle": "^2.31",
"symfony/string": "7.3.*",
"symfony/translation": "7.3.*",
"symfony/twig-bundle": "7.3.*",
"symfony/uid": "7.3.*",
"symfony/ux-turbo": "^2.31",
"symfony/ux-twig-component": "*",
"symfony/validator": "7.3.*",
"symfony/web-link": "7.3.*",
"symfony/yaml": "7.3.*",
"symfonycasts/reset-password-bundle": "^1.23",
"symfonycasts/tailwind-bundle": "^0.11.1",
"symfonycasts/verify-email-bundle": "^1.18",
"twig/extra-bundle": "^2.12|^3.0",
"twig/twig": "^2.12|^3.0"
},
"config": {
"allow-plugins": {
"php-http/discovery": true,
"symfony/flex": true,
"symfony/runtime": true
},
"bump-after-update": true,
"sort-packages": true
},
"autoload": {
"psr-4": {
"App\\": "src/"
}
},
"autoload-dev": {
"psr-4": {
"App\\Tests\\": "tests/"
}
},
"replace": {
"symfony/polyfill-ctype": "*",
"symfony/polyfill-iconv": "*",
"symfony/polyfill-php72": "*",
"symfony/polyfill-php73": "*",
"symfony/polyfill-php74": "*",
"symfony/polyfill-php80": "*",
"symfony/polyfill-php81": "*",
"symfony/polyfill-php82": "*"
},
"scripts": {
"auto-scripts": {
"cache:clear": "symfony-cmd",
"assets:install %PUBLIC_DIR%": "symfony-cmd",
"importmap:install": "symfony-cmd"
},
"post-install-cmd": [
"@auto-scripts"
],
"post-update-cmd": [
"@auto-scripts"
]
},
"conflict": {
"symfony/symfony": "*"
},
"extra": {
"symfony": {
"allow-contrib": false,
"require": "7.3.*"
}
},
"require-dev": {
"phpunit/phpunit": "^12.4",
"symfony/browser-kit": "7.3.*",
"symfony/css-selector": "7.3.*",
"symfony/debug-bundle": "7.3.*",
"symfony/maker-bundle": "^1.0",
"symfony/stopwatch": "7.3.*",
"symfony/web-profiler-bundle": "7.3.*"
}
}



================================================
FILE: docker-compose.yml
================================================
services:
postgres:
image: postgres:16-alpine
container_name: flashcards-postgres
environment:
POSTGRES_DB: ${POSTGRES_DB}
POSTGRES_USER: ${POSTGRES_USER}
POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}
ports:
- "6432:5432"
volumes:
- postgres_data:/var/lib/postgresql/data
networks:
- flashcards_network
healthcheck:
test: ["CMD-SHELL", "pg_isready -U ${POSTGRES_USER} -d ${POSTGRES_DB}"]
interval: 10s
timeout: 5s
retries: 5

backend:
build:
context: .
dockerfile: Dockerfile
container_name: flashcards-backend
working_dir: /var/www/html
volumes:
- .:/var/www/html
- vendor:/var/www/html/vendor
environment:
#DATABASE_URL: ${DATABASE_URL}
APP_ENV: ${APP_ENV}
networks:
- flashcards_network
depends_on:
postgres:
condition: service_healthy

nginx:
image: nginx:1.27-alpine
container_name: flashcards-nginx
ports:
- "8099:80"
volumes:
- .:/var/www/html
- ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
networks:
- flashcards_network
depends_on:
- backend

mailpit:
image: axllent/mailpit:latest
container_name: flashcards-mailpit
ports:
- "8026:8025"  # Web UI (using 8026 to avoid conflicts with other projects)
- "1035:1025"  # SMTP server (using 1026 to avoid conflicts)
networks:
- flashcards_network
environment:
MP_MAX_MESSAGES: 500
MP_SMTP_AUTH_ACCEPT_ANY: 1
MP_SMTP_AUTH_ALLOW_INSECURE: 1

volumes:
postgres_data:
vendor:

networks:
flashcards_network:
driver: bridge



================================================
FILE: Dockerfile
================================================
FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
git \
unzip \
curl \
postgresql-dev \
icu-dev \
libzip-dev \
linux-headers \
$PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-install \
pdo \
pdo_pgsql \
intl \
zip \
opcache

# Install and configure Xdebug
RUN pecl install xdebug-3.4.0 && \
docker-php-ext-enable xdebug

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Copy Xdebug configuration
COPY docker/php/xdebug.ini $PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini

# Create working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html/var

# Expose port 9000 for PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]



================================================
FILE: importmap.php
================================================
<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
    'daisyui' => [
        'version' => '5.5.5',
    ],
    'daisyui/daisyui.min.css' => [
        'version' => '5.5.5',
        'type' => 'css',
    ],
];



================================================
FILE: manual-test-create-set.http
================================================
### Manual Test: POST /api/sets - Create set with AI cards
# This test creates a set with 3 cards (2 AI-generated, 1 manual)
# Expected: 201 Created with set ID and card_count=3

POST http://localhost:8099/api/sets
Content-Type: application/json
Authorization: Basic test@example.com:test123

{
  "name": "Biologia - Test Manualny1",
  "cards": [
    {
      "front": "Co to jest fotosynteza?",
      "back": "Proces wytwarzania glukozy przez rośliny z CO2 i wody przy użyciu energii słonecznej",
      "origin": "ai",
      "edited": false
    },
    {
      "front": "Jakie są produkty fotosyntezy?",
      "back": "Glukoza (C6H12O6) i tlen (O2)",
      "origin": "ai",
      "edited": true
    },
    {
      "front": "Gdzie zachodzi fotosynteza?",
      "back": "W chloroplastach komórek roślinnych",
      "origin": "manual",
      "edited": false
    }
  ]
}

### Expected Response (201 Created):
# {
#   "id": "550e8400-e29b-41d4-a716-446655440000",
#   "name": "Biologia - Test Manualny",
#   "card_count": 3
# }
#
# Headers:
# Location: /api/sets/550e8400-e29b-41d4-a716-446655440000
# Content-Type: application/json

### Expected database state after test:
# Table: sets
# - 1 new record with name="Biologia - Test Manualny", card_count=3
#
# Table: cards
# - 3 new records linked to the set
# - 2 cards with origin='ai'
# - 1 card with origin='manual'
# - 1 card with edited_by_user_at NOT NULL (the edited AI card)
#
# Table: analytics_events
# - 1 new event with event_type='set_created'
# - payload: {"total_cards": 3, "ai_cards": 2, "manual_cards": 1, "edited_ai_cards": 1, "ai_edit_rate": 0.5}

###



================================================
FILE: manual-test-curl.sh
================================================
#!/bin/bash

# Manual Test: POST /api/sets - Create set with AI cards
# This script tests the endpoint using curl

echo "========================================="
echo "Testing POST /api/sets endpoint"
echo "========================================="
echo ""

# Test data
REQUEST_DATA='{
  "name": "Biologia - Test Manualny cURL",
  "cards": [
    {
      "front": "Co to jest fotosynteza?",
      "back": "Proces wytwarzania glukozy przez rośliny z CO2 i wody przy użyciu energii słonecznej",
      "origin": "ai",
      "edited": false
    },
    {
      "front": "Jakie są produkty fotosyntezy?",
      "back": "Glukoza (C6H12O6) i tlen (O2)",
      "origin": "ai",
      "edited": true
    },
    {
      "front": "Gdzie zachodzi fotosynteza?",
      "back": "W chloroplastach komórek roślinnych",
      "origin": "manual",
      "edited": false
    }
  ]
}'

echo "Sending POST request to http://localhost:8099/api/sets"
echo ""

# Make the request and capture response with headers
RESPONSE=$(curl -i -X POST http://localhost:8099/api/sets \
  -u "test@example.com:test123" \
  -H "Content-Type: application/json" \
  -d "$REQUEST_DATA" \
  -w "\n\nHTTP_CODE: %{http_code}\n" \
  2>/dev/null)

echo "$RESPONSE"
echo ""
echo "========================================="
echo "Expected: HTTP 201 Created"
echo "Expected JSON: {\"id\": \"uuid\", \"name\": \"Biologia - Test Manualny cURL\", \"card_count\": 3}"
echo "Expected Header: Location: /api/sets/{uuid}"
echo "========================================="
echo ""
echo "Database verification queries:"
echo "1. Check sets table:"
echo "   SELECT * FROM sets WHERE name = 'Biologia - Test Manualny cURL';"
echo ""
echo "2. Check cards table (should have 3 cards):"
echo "   SELECT id, front, origin, edited_by_user_at FROM cards WHERE set_id = (SELECT id FROM sets WHERE name = 'Biologia - Test Manualny cURL');"
echo ""
echo "3. Check analytics_events table:"
echo "   SELECT event_type, payload FROM analytics_events WHERE event_type = 'set_created' ORDER BY occurred_at DESC LIMIT 1;"
echo ""



================================================
FILE: phpunit.dist.xml
================================================
<?xml version="1.0" encoding="UTF-8"?>

<!-- https://phpunit.readthedocs.io/en/latest/configuration.html -->
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
colors="true"
failOnDeprecation="true"
failOnNotice="true"
failOnWarning="true"
bootstrap="tests/bootstrap.php"
cacheDirectory=".phpunit.cache"
>
    <php>
        <ini name="display_errors" value="1" />
        <ini name="error_reporting" value="-1" />
        <server name="APP_ENV" value="test" force="true" />
        <server name="SHELL_VERBOSITY" value="-1" />
    </php>

    <testsuites>
        <testsuite name="Project Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>

    <source ignoreSuppressionOfDeprecations="true"
            ignoreIndirectDeprecations="true"
            restrictNotices="true"
            restrictWarnings="true"
    >
        <include>
            <directory>src</directory>
        </include>

        <deprecationTrigger>
            <method>Doctrine\Deprecations\Deprecation::trigger</method>
            <method>Doctrine\Deprecations\Deprecation::delegateTriggerToBackend</method>
            <function>trigger_deprecation</function>
        </deprecationTrigger>
    </source>

    <extensions>
    </extensions>
</phpunit>



================================================
FILE: symfony.lock
================================================
{
"doctrine/deprecations": {
"version": "1.1",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "1.0",
"ref": "87424683adc81d7dc305eefec1fced883084aab9"
}
},
"doctrine/doctrine-bundle": {
"version": "2.18",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "2.13",
"ref": "620b57f496f2e599a6015a9fa222c2ee0a32adcb"
},
"files": [
"config/packages/doctrine.yaml",
"src/Entity/.gitignore",
"src/Repository/.gitignore"
]
},
"doctrine/doctrine-migrations-bundle": {
"version": "3.4",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "3.1",
"ref": "1d01ec03c6ecbd67c3375c5478c9a423ae5d6a33"
},
"files": [
"config/packages/doctrine_migrations.yaml",
"migrations/.gitignore"
]
},
"lexik/jwt-authentication-bundle": {
"version": "3.1",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "2.5",
"ref": "e9481b233a11ef7e15fe055a2b21fd3ac1aa2bb7"
},
"files": [
"config/packages/lexik_jwt_authentication.yaml"
]
},
"phpunit/phpunit": {
"version": "12.4",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "11.1",
"ref": "c6658a60fc9d594805370eacdf542c3d6b5c0869"
},
"files": [
".env.test",
"phpunit.dist.xml",
"tests/bootstrap.php",
"bin/phpunit"
]
},
"symfony/asset-mapper": {
"version": "7.3",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "6.4",
"ref": "5ad1308aa756d58f999ffbe1540d1189f5d7d14a"
},
"files": [
"assets/app.js",
"assets/styles/app.css",
"config/packages/asset_mapper.yaml",
"importmap.php"
]
},
"symfony/console": {
"version": "7.3",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "5.3",
"ref": "1781ff40d8a17d87cf53f8d4cf0c8346ed2bb461"
},
"files": [
"bin/console"
]
},
"symfony/debug-bundle": {
"version": "7.3",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "5.3",
"ref": "5aa8aa48234c8eb6dbdd7b3cd5d791485d2cec4b"
},
"files": [
"config/packages/debug.yaml"
]
},
"symfony/flex": {
"version": "2.8",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "2.4",
"ref": "52e9754527a15e2b79d9a610f98185a1fe46622a"
},
"files": [
".env",
".env.dev"
]
},
"symfony/form": {
"version": "7.3",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "7.2",
"ref": "7d86a6723f4a623f59e2bf966b6aad2fc461d36b"
},
"files": [
"config/packages/csrf.yaml"
]
},
"symfony/framework-bundle": {
"version": "7.3",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "7.3",
"ref": "5a1497d539f691b96afd45ae397ce5fe30beb4b9"
},
"files": [
"config/packages/cache.yaml",
"config/packages/framework.yaml",
"config/preload.php",
"config/routes/framework.yaml",
"config/services.yaml",
"public/index.php",
"src/Controller/.gitignore",
"src/Kernel.php",
".editorconfig"
]
},
"symfony/mailer": {
"version": "7.3",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "4.3",
"ref": "09051cfde49476e3c12cd3a0e44289ace1c75a4f"
},
"files": [
"config/packages/mailer.yaml"
]
},
"symfony/maker-bundle": {
"version": "1.64",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "1.0",
"ref": "fadbfe33303a76e25cb63401050439aa9b1a9c7f"
}
},
"symfony/messenger": {
"version": "7.3",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "6.0",
"ref": "ba1ac4e919baba5644d31b57a3284d6ba12d52ee"
},
"files": [
"config/packages/messenger.yaml"
]
},
"symfony/monolog-bundle": {
"version": "3.10",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "3.7",
"ref": "aff23899c4440dd995907613c1dd709b6f59503f"
},
"files": [
"config/packages/monolog.yaml"
]
},
"symfony/notifier": {
"version": "7.3",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "5.0",
"ref": "178877daf79d2dbd62129dd03612cb1a2cb407cc"
},
"files": [
"config/packages/notifier.yaml"
]
},
"symfony/property-info": {
"version": "7.3",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "7.3",
"ref": "dae70df71978ae9226ae915ffd5fad817f5ca1f7"
},
"files": [
"config/packages/property_info.yaml"
]
},
"symfony/routing": {
"version": "7.3",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "7.0",
"ref": "ab1e60e2afd5c6f4a6795908f646e235f2564eb2"
},
"files": [
"config/packages/routing.yaml",
"config/routes.yaml"
]
},
"symfony/security-bundle": {
"version": "7.3",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "6.4",
"ref": "2ae08430db28c8eb4476605894296c82a642028f"
},
"files": [
"config/packages/security.yaml",
"config/routes/security.yaml"
]
},
"symfony/stimulus-bundle": {
"version": "2.31",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "2.24",
"ref": "3357f2fa6627b93658d8e13baa416b2a94a50c5f"
},
"files": [
"assets/controllers.json",
"assets/controllers/csrf_protection_controller.js",
"assets/controllers/hello_controller.js",
"assets/stimulus_bootstrap.js"
]
},
"symfony/translation": {
"version": "7.3",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "6.3",
"ref": "620a1b84865ceb2ba304c8f8bf2a185fbf32a843"
},
"files": [
"config/packages/translation.yaml",
"translations/.gitignore"
]
},
"symfony/twig-bundle": {
"version": "7.3",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "6.4",
"ref": "cab5fd2a13a45c266d45a7d9337e28dee6272877"
},
"files": [
"config/packages/twig.yaml",
"templates/base.html.twig"
]
},
"symfony/uid": {
"version": "7.3",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "7.0",
"ref": "0df5844274d871b37fc3816c57a768ffc60a43a5"
}
},
"symfony/ux-turbo": {
"version": "2.31",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "2.20",
"ref": "287f7c6eb6e9b65e422d34c00795b360a787380b"
},
"files": [
"config/packages/ux_turbo.yaml"
]
},
"symfony/ux-twig-component": {
"version": "2.31",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "2.13",
"ref": "f367ae2a1faf01c503de2171f1ec22567febeead"
},
"files": [
"config/packages/twig_component.yaml"
]
},
"symfony/validator": {
"version": "7.3",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "7.0",
"ref": "8c1c4e28d26a124b0bb273f537ca8ce443472bfd"
},
"files": [
"config/packages/validator.yaml"
]
},
"symfony/web-profiler-bundle": {
"version": "7.3",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "7.3",
"ref": "a363460c1b0b4a4d0242f2ce1a843ca0f6ac9026"
},
"files": [
"config/packages/web_profiler.yaml",
"config/routes/web_profiler.yaml"
]
},
"symfony/webapp-pack": {
"version": "1.3",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "1.0",
"ref": "7d5c5e282f7e2c36a2c3bbb1504f78456c352407"
},
"files": [
"config/packages/messenger.yaml"
]
},
"symfonycasts/reset-password-bundle": {
"version": "1.23",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "1.0",
"ref": "97c1627c0384534997ae1047b93be517ca16de43"
},
"files": [
"config/packages/reset_password.yaml"
]
},
"symfonycasts/tailwind-bundle": {
"version": "0.11",
"recipe": {
"repo": "github.com/symfony/recipes",
"branch": "main",
"version": "0.8",
"ref": "d0bd0276f74de90adfaa4c6cd74cc0caacd77e0a"
},
"files": [
"config/packages/symfonycasts_tailwind.yaml"
]
},
"symfonycasts/verify-email-bundle": {
"version": "v1.18.0"
},
"twig/extra-bundle": {
"version": "v3.21.0"
}
}



================================================
FILE: test-create-set.http
================================================
### Test POST /api/sets - Create empty set
POST http://localhost:8000/api/sets
Content-Type: application/json

{
"name": "Test Empty Set"
}

### Test POST /api/sets - Create set with manual cards
POST http://localhost:8000/api/sets
Content-Type: application/json

{
"name": "Matematyka - Geometria",
"cards": [
{
"front": "Co to jest trójkąt równoboczny?",
"back": "Trójkąt, którego wszystkie boki mają jednakową długość",
"origin": "manual",
"edited": false
},
{
"front": "Wzór na pole trójkąta",
"back": "P = (a × h) / 2",
"origin": "manual",
"edited": false
}
]
}

### Test POST /api/sets - Create set with AI cards (some edited)
POST http://localhost:8000/api/sets
Content-Type: application/json

{
"name": "Biologia - Komórka",
"cards": [
{
"front": "Co to jest mitochondrium?",
"back": "Organellum komórkowe odpowiedzialne za produkcję energii (ATP)",
"origin": "ai",
"edited": false
},
{
"front": "Czym różni się komórka roślinna od zwierzęcej?",
"back": "Komórka roślinna ma ścianę komórkową, wakuolę i chloroplasty",
"origin": "ai",
"edited": true
},
{
"front": "Co to jest jądro komórkowe?",
"back": "Centrum kontrolne komórki zawierające DNA",
"origin": "ai",
"edited": false
}
]
}

### Test POST /api/sets - Create set with job_id (AI job linkage)
# First run POST /api/generate to get a job_id, then use it here
POST http://localhost:8000/api/sets
Content-Type: application/json

{
"name": "Generated Set with Job",
"cards": [
{
"front": "Test card 1",
"back": "Answer 1",
"origin": "ai",
"edited": false
}
],
"job_id": "REPLACE_WITH_ACTUAL_JOB_ID_FROM_GENERATE"
}

### Test POST /api/sets - Duplicate name (should return 409)
POST http://localhost:8000/api/sets
Content-Type: application/json

{
"name": "Matematyka - Geometria"
}

### Test POST /api/sets - Validation error: empty name (should return 422)
POST http://localhost:8000/api/sets
Content-Type: application/json

{
"name": ""
}

### Test POST /api/sets - Validation error: card front too long (should return 422)
POST http://localhost:8000/api/sets
Content-Type: application/json

{
"name": "Test Validation",
"cards": [
{
"front": "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
"back": "Answer",
"origin": "manual"
}
]
}

### Test POST /api/sets - Validation error: invalid origin (should return 422)
POST http://localhost:8000/api/sets
Content-Type: application/json

{
"name": "Test Invalid Origin",
"cards": [
{
"front": "Question",
"back": "Answer",
"origin": "invalid_value"
}
]
}

### Test POST /api/sets - Validation error: invalid job_id UUID (should return 422)
POST http://localhost:8000/api/sets
Content-Type: application/json

{
"name": "Test Invalid Job ID",
"job_id": "not-a-valid-uuid"
}

### Test POST /api/sets - AI job not found (should return 404)
POST http://localhost:8000/api/sets
Content-Type: application/json

{
"name": "Test Job Not Found",
"job_id": "00000000-0000-0000-0000-000000000000"
}



================================================
FILE: .editorconfig
================================================
# editorconfig.org

root = true

[*]
charset = utf-8
end_of_line = lf
indent_size = 4
indent_style = space
insert_final_newline = true
trim_trailing_whitespace = true

[{compose.yaml,compose.*.yaml}]
indent_size = 2

[*.md]
trim_trailing_whitespace = false



================================================
FILE: .env.dev
================================================

###> symfony/framework-bundle ###
APP_SECRET=0be2d02a24f05006c7794ba7a3b632f6
###< symfony/framework-bundle ###



================================================
FILE: .env.test
================================================
# define your env variables for the test env here
KERNEL_CLASS='App\Kernel'
APP_SECRET='$ecretf0rt3st'



================================================
FILE: assets/app.js
================================================
// import './stimulus_bootstrap.js';
// /*
//  * Welcome to your app's main JavaScript file!
//  *
//  * This file will be included onto the page via the importmap() Twig function,
//  * which should already be in your base.html.twig.
//  */
// import './styles/app.css';


import './stimulus_bootstrap.js';
/*
* Welcome to your app's main JavaScript file!
*
* This file will be included onto the page via the importmap() Twig function,
* which should already be in your base.html.twig.
  */
  import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');



================================================
FILE: assets/controllers.json
================================================
{
"controllers": {
"@symfony/ux-turbo": {
"turbo-core": {
"enabled": true,
"fetch": "eager"
},
"mercure-turbo-stream": {
"enabled": false,
"fetch": "eager"
}
}
},
"entrypoints": []
}



================================================
FILE: assets/stimulus_bootstrap.js
================================================
import {startStimulusApp} from '@symfony/stimulus-bundle';
import GenerateController from './controllers/generate_controller.js';
import EditSetController from './controllers/edit_set_controller.js';
import SetListController from './controllers/set_list_controller.js';
import HelloController from './controllers/hello_controller.js';
import FormValidationController from './controllers/form_validation_controller.js';
import ModalController from './controllers/modal_controller.js';
import SnackbarController from './controllers/snackbar_controller.js';
import ThemeController from './controllers/theme_controller.js';

const app = startStimulusApp();

// Register custom controllers
app.register('generate', GenerateController);
app.register('edit-set', EditSetController);
app.register('set-list', SetListController);
app.register('hello', HelloController);
app.register('form-validation', FormValidationController);
app.register('modal', ModalController);
app.register('snackbar', SnackbarController);
app.register('theme', ThemeController);



================================================
FILE: assets/controllers/csrf_protection_controller.js
================================================
const nameCheck = /^[-_a-zA-Z0-9]{4,22}$/;
const tokenCheck = /^[-_/+a-zA-Z0-9]{24,}$/;

// Generate and double-submit a CSRF token in a form field and a cookie, as defined by Symfony's SameOriginCsrfTokenManager
// Use `form.requestSubmit()` to ensure that the submit event is triggered. Using `form.submit()` will not trigger the event
// and thus this event-listener will not be executed.
document.addEventListener('submit', function (event) {
generateCsrfToken(event.target);
}, true);

// When @hotwired/turbo handles form submissions, send the CSRF token in a header in addition to a cookie
// The `framework.csrf_protection.check_header` config option needs to be enabled for the header to be checked
document.addEventListener('turbo:submit-start', function (event) {
const h = generateCsrfHeaders(event.detail.formSubmission.formElement);
Object.keys(h).map(function (k) {
event.detail.formSubmission.fetchRequest.headers[k] = h[k];
});
});

// When @hotwired/turbo handles form submissions, remove the CSRF cookie once a form has been submitted
document.addEventListener('turbo:submit-end', function (event) {
removeCsrfToken(event.detail.formSubmission.formElement);
});

export function generateCsrfToken (formElement) {
const csrfField = formElement.querySelector('input[data-controller="csrf-protection"], input[name="_csrf_token"]');

    if (!csrfField) {
        return;
    }

    let csrfCookie = csrfField.getAttribute('data-csrf-protection-cookie-value');
    let csrfToken = csrfField.value;

    if (!csrfCookie && nameCheck.test(csrfToken)) {
        csrfField.setAttribute('data-csrf-protection-cookie-value', csrfCookie = csrfToken);
        csrfField.defaultValue = csrfToken = btoa(String.fromCharCode.apply(null, (window.crypto || window.msCrypto).getRandomValues(new Uint8Array(18))));
    }
    csrfField.dispatchEvent(new Event('change', { bubbles: true }));

    if (csrfCookie && tokenCheck.test(csrfToken)) {
        const cookie = csrfCookie + '_' + csrfToken + '=' + csrfCookie + '; path=/; samesite=strict';
        document.cookie = window.location.protocol === 'https:' ? '__Host-' + cookie + '; secure' : cookie;
    }
}

export function generateCsrfHeaders (formElement) {
const headers = {};
const csrfField = formElement.querySelector('input[data-controller="csrf-protection"], input[name="_csrf_token"]');

    if (!csrfField) {
        return headers;
    }

    const csrfCookie = csrfField.getAttribute('data-csrf-protection-cookie-value');

    if (tokenCheck.test(csrfField.value) && nameCheck.test(csrfCookie)) {
        headers[csrfCookie] = csrfField.value;
    }

    return headers;
}

export function removeCsrfToken (formElement) {
const csrfField = formElement.querySelector('input[data-controller="csrf-protection"], input[name="_csrf_token"]');

    if (!csrfField) {
        return;
    }

    const csrfCookie = csrfField.getAttribute('data-csrf-protection-cookie-value');

    if (tokenCheck.test(csrfField.value) && nameCheck.test(csrfCookie)) {
        const cookie = csrfCookie + '_' + csrfField.value + '=0; path=/; samesite=strict; max-age=0';

        document.cookie = window.location.protocol === 'https:' ? '__Host-' + cookie + '; secure' : cookie;
    }
}

/* stimulusFetch: 'lazy' */
export default 'csrf-protection-controller';



================================================
FILE: assets/controllers/edit_set_controller.js
================================================
import { Controller } from '@hotwired/stimulus';

/**
* Stimulus controller for editing newly generated flashcard set.
*
* Manages:
* - Set name editing with validation
* - Individual card editing (front/back)
* - Card deletion with confirmation
* - Form validation (name length, empty cards)
* - Save to POST /api/sets with JSON
* - Cancel with confirmation modal
    */
    export default class extends Controller {
    static targets = [
    'setNameInput',
    'setNameHint',
    'generatedCountText',
    'cardsToSaveCount',
    'cardsList',
    'cardItem',
    'frontTextarea',
    'backTextarea',
    'saveButton',
    'saveButtonText',
    'saveButtonCount',
    'cancelModal',
    'loadingOverlay'
    ];

static values = {
jobId: String,
initialCards: Array,
generatedCount: Number
};

// Current state
setName = '';
cards = [];
isDirty = false;

/**
    * Initialize controller on connect
      */
      connect() {
      // Initialize cards from Twig data
      this.cards = this.initialCardsValue || [];
      this.setName = this.setNameInputTarget.value;

      // Initial validation
      this.validateForm();
      }

/**
    * Update set name on input
      */
      updateSetName(event) {
      this.setName = event.target.value.trim();
      this.isDirty = true;
      this.validateForm();
      }

/**
    * Update card field (front or back)
    *
    * @param {Event} event - Input event from textarea
      */
      updateCard(event) {
      const index = parseInt(event.target.dataset.index);
      const field = event.target.dataset.field; // 'front' or 'back'
      const value = event.target.value;

      if (this.cards[index]) {
      this.cards[index][field] = value;
      this.isDirty = true;
      this.validateForm();
      }
      }

/**
    * Delete card with confirmation
    *
    * @param {Event} event - Click event from delete button
      */
      deleteCard(event) {
      const index = parseInt(event.target.dataset.index);

      // Confirm deletion
      if (!confirm('Czy na pewno chcesz usunąć tę fiszkę?')) {
      return;
      }

      // Remove card from array
      this.cards.splice(index, 1);
      this.isDirty = true;

      // Remove card element from DOM
      const cardElement = this.cardItemTargets[index];
      if (cardElement) {
      cardElement.remove();
      }

      // Re-index remaining cards
      this.reindexCards();

      // Update UI
      this.updateCardsCount();
      this.validateForm();
      }

/**
    * Re-index card elements after deletion
      */
      reindexCards() {
      this.cardItemTargets.forEach((item, newIndex) => {
      // Update data-index attribute
      item.dataset.index = newIndex;

           // Update card number display
           const cardNumber = item.querySelector('.text-sm.font-semibold');
           if (cardNumber) {
               cardNumber.textContent = `Fiszka #${newIndex + 1}`;
           }

           // Update textareas data-index
           const textareas = item.querySelectorAll('textarea');
           textareas.forEach(textarea => {
               textarea.dataset.index = newIndex;
           });

           // Update delete button data-index
           const deleteBtn = item.querySelector('button[data-action*="deleteCard"]');
           if (deleteBtn) {
               deleteBtn.dataset.index = newIndex;
           }
      });
      }

/**
    * Update cards count display
      */
      updateCardsCount() {
      const count = this.cards.length;

      if (this.hasCardsToSaveCountTarget) {
      this.cardsToSaveCountTarget.textContent = count;
      }

      if (this.hasSaveButtonCountTarget) {
      this.saveButtonCountTarget.textContent = count;
      }
      }

/**
    * Validate form and update button state
    *
    * @returns {boolean} - Is form valid
      */
      validateForm() {
      let isValid = true;
      const errors = [];

      // 1. Validate set name (3-100 chars)
      if (this.setName.length < 3) {
      isValid = false;
      errors.push('Nazwa zestawu musi mieć minimum 3 znaki');
      } else if (this.setName.length > 100) {
      isValid = false;
      errors.push('Nazwa zestawu może mieć maksimum 100 znaków');
      }

      // 2. Check if there are any cards
      if (this.cards.length === 0) {
      isValid = false;
      errors.push('Musisz mieć przynajmniej jedną fiszkę');
      }

      // 3. Validate each card (front and back not empty)
      this.cards.forEach((card, index) => {
      if (!card.front || card.front.trim().length === 0) {
      isValid = false;
      errors.push(`Fiszka #${index + 1}: przód nie może być pusty`);
      }
      if (!card.back || card.back.trim().length === 0) {
      isValid = false;
      errors.push(`Fiszka #${index + 1}: tył nie może być pusty`);
      }
      });

      // Update save button state
      if (this.hasSaveButtonTarget) {
      this.saveButtonTarget.disabled = !isValid;
      }

      // Update hint text
      if (this.hasSetNameHintTarget) {
      if (this.setName.length < 3 && this.setName.length > 0) {
      this.setNameHintTarget.textContent = `Minimum 3 znaki (brakuje: ${3 - this.setName.length})`;
      this.setNameHintTarget.classList.add('text-red-600');
      this.setNameHintTarget.classList.remove('text-gray-500');
      } else {
      this.setNameHintTarget.textContent = 'Minimum 3 znaki, maksimum 100 znaków';
      this.setNameHintTarget.classList.add('text-gray-500');
      this.setNameHintTarget.classList.remove('text-red-600');
      }
      }

      return isValid;
      }

/**
    * Handle save button click (form submit)
    *
    * @param {Event} event - Submit event
      */
      async handleSave(event) {
      event.preventDefault();

      if (!this.validateForm()) {
      return;
      }

      // Show loading overlay
      this.showLoading();

      try {
      // Prepare cards with origin and edited flags
      const cardsWithMeta = this.cards.map((card, index) => ({
      front: card.front,
      back: card.back,
      origin: 'ai', // All cards from this flow are AI-generated
      edited: card.front !== this.initialCardsValue[index]?.front ||
      card.back !== this.initialCardsValue[index]?.back
      }));

           const response = await fetch('/api/sets', {
               method: 'POST',
               headers: {
                   'Content-Type': 'application/json',
                   'Accept': 'application/json'
               },
               body: JSON.stringify({
                   name: this.setName,
                   cards: cardsWithMeta,
                   job_id: this.jobIdValue
               })
           });

           this.hideLoading();

           if (response.ok) {
               const data = await response.json();

               // Success! Redirect to sets list
               window.location.href = '/sets';
           } else {
               // Handle error
               const errorData = await response.json();
               alert(`Błąd podczas zapisywania: ${errorData.message || 'Nieznany błąd'}`);
           }
      } catch (error) {
      this.hideLoading();
      alert('Wystąpił problem z połączeniem. Sprawdź internet i spróbuj ponownie.');
      }
      }

/**
    * Handle cancel button click
      */
      handleCancel() {
      if (this.isDirty) {
      // Show confirmation modal
      this.cancelModalTarget.showModal();
      } else {
      // No changes, redirect directly
      window.location.href = '/generate';
      }
      }

/**
    * Close cancel modal
      */
      closeCancelModal() {
      this.cancelModalTarget.close();
      }

/**
    * Confirm cancel and redirect
      */
      confirmCancel() {
      this.cancelModalTarget.close();
      window.location.href = '/generate';
      }

/**
    * Show loading overlay
      */
      showLoading() {
      if (this.hasLoadingOverlayTarget) {
      this.loadingOverlayTarget.classList.remove('hidden');
      }
      }

/**
    * Hide loading overlay
      */
      hideLoading() {
      if (this.hasLoadingOverlayTarget) {
      this.loadingOverlayTarget.classList.add('hidden');
      }
      }
      }



================================================
FILE: assets/controllers/form_validation_controller.js
================================================
import { Controller } from '@hotwired/stimulus';

/**
* Form Validation Controller
*
* Provides real-time client-side validation for authentication forms:
* - Email format validation
* - Password strength checking
* - Password confirmation matching
* - Submit button state management
    */
    export default class extends Controller {
    static targets = [
    'email',
    'emailError',
    'password',
    'passwordError',
    'passwordConfirm',
    'passwordConfirmError',
    'submitButton',
    'strengthBar1',
    'strengthBar2',
    'strengthBar3',
    'strengthBar4',
    'strengthText',
    'terms'
    ];

connect() {
console.log('Form validation controller connected');
this.updateSubmitButtonState();
}

/**
    * Validate email format
      */
      validateEmail(event) {
      const email = event ? event.target.value : this.emailTarget.value;
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

      if (!email) {
      this.showError('emailError', 'Email jest wymagany');
      return false;
      }

      if (!emailRegex.test(email)) {
      this.showError('emailError', 'Podaj prawidłowy adres email');
      return false;
      }

      this.hideError('emailError');
      return true;
      }

/**
    * Real-time email validation (less strict, only shows error after blur)
      */
      validateEmailRealtime(event) {
      const email = event.target.value;
      if (email.length > 0) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (emailRegex.test(email)) {
      this.hideError('emailError');
      }
      }
      this.updateSubmitButtonState();
      }

/**
    * Validate password strength
      */
      validatePassword(event) {
      const password = event ? event.target.value : this.passwordTarget.value;

      if (!password) {
      this.showError('passwordError', 'Hasło jest wymagane');
      this.updatePasswordStrength(0);
      return false;
      }

      if (password.length < 8) {
      this.showError('passwordError', 'Hasło musi mieć co najmniej 8 znaków');
      this.updatePasswordStrength(1);
      return false;
      }

      // Calculate strength
      let strength = 1;
      if (password.length >= 8) strength++;
      if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
      if (/\d/.test(password)) strength++;
      if (/[^a-zA-Z0-9]/.test(password)) strength = 4;

      this.updatePasswordStrength(strength);
      this.hideError('passwordError');
      return true;
      }

/**
    * Real-time password validation
      */
      validatePasswordRealtime(event) {
      const password = event.target.value;

      // Update strength indicator
      if (password.length > 0) {
      let strength = 0;
      if (password.length >= 8) strength++;
      if (password.length >= 8 && /[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
      if (password.length >= 8 && /\d/.test(password)) strength++;
      if (password.length >= 8 && /[^a-zA-Z0-9]/.test(password)) strength = 4;

           this.updatePasswordStrength(strength);

           if (password.length >= 8) {
               this.hideError('passwordError');
           }
      } else {
      this.updatePasswordStrength(0);
      }

      // Also check password match if confirmation field exists and has value
      if (this.hasPasswordConfirmTarget && this.passwordConfirmTarget.value) {
      this.validatePasswordMatchRealtime({ target: this.passwordConfirmTarget });
      }

      this.updateSubmitButtonState();
      }

/**
    * Update password strength visual indicator
      */
      updatePasswordStrength(strength) {
      if (!this.hasStrengthBar1Target) return;

      const bars = [
      this.strengthBar1Target,
      this.strengthBar2Target,
      this.strengthBar3Target,
      this.strengthBar4Target
      ];

      const colors = [
      'bg-[var(--color-surface-variant)]',  // default
      'bg-red-500',                          // weak
      'bg-orange-500',                       // fair
      'bg-yellow-500',                       // good
      'bg-green-500'                         // strong
      ];

      const texts = [
      '',
      'Słabe hasło',
      'Przeciętne hasło',
      'Dobre hasło',
      'Mocne hasło'
      ];

      // Reset all bars
      bars.forEach(bar => {
      bar.className = 'h-1 flex-1 bg-[var(--color-surface-variant)] rounded transition-colors';
      });

      // Fill bars based on strength
      for (let i = 0; i < strength; i++) {
      bars[i].className = `h-1 flex-1 ${colors[strength]} rounded transition-colors`;
      }

      // Update text
      if (this.hasStrengthTextTarget) {
      this.strengthTextTarget.textContent = texts[strength];
      this.strengthTextTarget.className = `text-body-small ${
                strength === 0 ? 'text-[var(--color-on-surface-variant)]' :
                strength === 1 ? 'text-red-500' :
                strength === 2 ? 'text-orange-500' :
                strength === 3 ? 'text-yellow-600' :
                'text-green-600'
            } mt-1`;
      }
      }

/**
    * Validate password confirmation match
      */
      validatePasswordMatch(event) {
      if (!this.hasPasswordTarget || !this.hasPasswordConfirmTarget) {
      return true;
      }

      const password = this.passwordTarget.value;
      const passwordConfirm = event ? event.target.value : this.passwordConfirmTarget.value;

      if (!passwordConfirm) {
      this.showError('passwordConfirmError', 'Potwierdź hasło');
      return false;
      }

      if (password !== passwordConfirm) {
      this.showError('passwordConfirmError', 'Hasła nie są identyczne');
      return false;
      }

      this.hideError('passwordConfirmError');
      return true;
      }

/**
    * Real-time password confirmation validation
      */
      validatePasswordMatchRealtime(event) {
      if (!this.hasPasswordTarget || !this.hasPasswordConfirmTarget) {
      return;
      }

      const password = this.passwordTarget.value;
      const passwordConfirm = event.target.value;

      if (passwordConfirm.length > 0) {
      if (password === passwordConfirm) {
      this.hideError('passwordConfirmError');
      } else if (passwordConfirm.length >= password.length) {
      // Only show error if they've typed enough characters
      this.showError('passwordConfirmError', 'Hasła nie są identyczne');
      }
      }

      this.updateSubmitButtonState();
      }

/**
    * Update submit button state based on form validity
      */
      updateSubmitButtonState() {
      if (!this.hasSubmitButtonTarget) return;

      let isValid = true;

      // Check email if present
      if (this.hasEmailTarget) {
      const email = this.emailTarget.value;
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!email || !emailRegex.test(email)) {
      isValid = false;
      }
      }

      // Check password if present
      if (this.hasPasswordTarget) {
      const password = this.passwordTarget.value;
      if (!password || password.length < 8) {
      isValid = false;
      }
      }

      // Check password confirmation if present
      if (this.hasPasswordConfirmTarget) {
      const password = this.passwordTarget.value;
      const passwordConfirm = this.passwordConfirmTarget.value;
      if (!passwordConfirm || password !== passwordConfirm) {
      isValid = false;
      }
      }

      // Check terms checkbox if present
      if (this.hasTermsTarget) {
      if (!this.termsTarget.checked) {
      isValid = false;
      }
      }

      // Update button state
      if (isValid) {
      this.submitButtonTarget.removeAttribute('disabled');
      this.submitButtonTarget.classList.remove('opacity-50', 'cursor-not-allowed');
      } else {
      this.submitButtonTarget.setAttribute('disabled', 'disabled');
      this.submitButtonTarget.classList.add('opacity-50', 'cursor-not-allowed');
      }
      }

/**
    * Show error message
      */
      showError(targetName, message) {
      const errorTarget = this[targetName + 'Target'];
      if (errorTarget) {
      errorTarget.textContent = message;
      errorTarget.classList.remove('hidden');
      }
      }

/**
    * Hide error message
      */
      hideError(targetName) {
      const errorTarget = this[targetName + 'Target'];
      if (errorTarget) {
      errorTarget.textContent = '';
      errorTarget.classList.add('hidden');
      }
      }

/**
    * Handle form submission (can add additional validation here)
      */
      handleSubmit(event) {
      let isValid = true;

      // Validate all fields
      if (this.hasEmailTarget) {
      if (!this.validateEmail({ target: this.emailTarget })) {
      isValid = false;
      }
      }

      if (this.hasPasswordTarget) {
      if (!this.validatePassword({ target: this.passwordTarget })) {
      isValid = false;
      }
      }

      if (this.hasPasswordConfirmTarget) {
      if (!this.validatePasswordMatch({ target: this.passwordConfirmTarget })) {
      isValid = false;
      }
      }

      if (!isValid) {
      event.preventDefault();
      event.stopPropagation();
      }
      }
      }



================================================
FILE: assets/controllers/generate_controller.js
================================================
import { Controller } from '@hotwired/stimulus';

/**
* Stimulus controller for AI flashcard generation view.
*
* Manages:
* - Real-time character counting with debouncing
* - Validation (1000-10000 characters)
* - Progress bar visualization
* - Loading overlay with multi-stage progress
* - Error modal with recovery suggestions
* - Form submission via Turbo
    */
    export default class extends Controller {
    static targets = [
    'textarea',
    'charCount',
    'counterHint',
    'progressBar',
    'submitButton',
    'loadingOverlay',
    'loadingMessage',
    'errorModal',
    'errorMessage',
    'errorSuggestions'
    ];

static values = {
characterCount: { type: Number, default: 0 },
isValid: { type: Boolean, default: false },
isLoading: { type: Boolean, default: false },
loadingStage: { type: String, default: null }
};

// Constants for validation
MIN_CHARS = 1000;
MAX_CHARS = 10000;
DEBOUNCE_DELAY = 300; // ms

/**
    * Initialize controller on connect
      */
      connect() {
      this.debounceTimer = null;
      this.stageTimeout = null;

      // Initialize character count
      this.updateCharacterCount();
      }

/**
    * Clean up on disconnect
      */
      disconnect() {
      if (this.debounceTimer) {
      clearTimeout(this.debounceTimer);
      }
      if (this.stageTimeout) {
      clearTimeout(this.stageTimeout);
      }
      }

/**
    * Debounced character count update (triggered on textarea input)
      */
      updateCharacterCount() {
      // Clear previous timeout
      if (this.debounceTimer) {
      clearTimeout(this.debounceTimer);
      }

      // Set new timeout
      this.debounceTimer = setTimeout(() => {
      if (!this.hasTextareaTarget) {
      return;
      }

           const text = this.textareaTarget.value;
           this.characterCountValue = text.length;

           this.validateInput();
           this.updateUI();
      }, this.DEBOUNCE_DELAY);
      }

/**
    * Validate input length and return validation state
    *
    * @returns {Object} Validation state object
      */
      validateInput() {
      const count = this.characterCountValue;

      const validationState = {
      count,
      min: this.MIN_CHARS,
      max: this.MAX_CHARS,
      isUnder: count < this.MIN_CHARS,
      isValid: count >= this.MIN_CHARS && count <= this.MAX_CHARS,
      isOver: count > this.MAX_CHARS,
      percentage: Math.min((count / this.MAX_CHARS) * 100, 100)
      };

      this.isValidValue = validationState.isValid;
      return validationState;
      }

/**
    * Update UI based on validation state
      */
      updateUI() {
      const state = this.validateInput();

      // Update character count display
      this.charCountTarget.textContent = state.count.toLocaleString('pl-PL');

      // Update hint text and color
      if (state.isUnder) {
      const missing = this.MIN_CHARS - state.count;
      this.counterHintTarget.textContent = `Minimum ${this.MIN_CHARS.toLocaleString('pl-PL')} znaków (brakuje: ${missing.toLocaleString('pl-PL')})`;
      this.counterHintTarget.classList.add('text-red-600');
      this.counterHintTarget.classList.remove('text-green-600');
      } else if (state.isValid) {
      this.counterHintTarget.textContent = 'Zakres poprawny ✓';
      this.counterHintTarget.classList.add('text-green-600');
      this.counterHintTarget.classList.remove('text-red-600');
      } else if (state.isOver) {
      const excess = state.count - this.MAX_CHARS;
      this.counterHintTarget.textContent = `Przekroczono limit (za dużo: ${excess.toLocaleString('pl-PL')})`;
      this.counterHintTarget.classList.add('text-red-600');
      this.counterHintTarget.classList.remove('text-green-600');
      }

      // Update progress bar
      this.updateProgressBar(state);

      // Update submit button
      this.submitButtonTarget.disabled = !state.isValid;
      }

/**
    * Update progress bar width and color
    *
    * @param {Object} state - Validation state
      */
      updateProgressBar(state) {
      const bar = this.progressBarTarget;
      bar.style.width = `${state.percentage}%`;

      if (state.isValid) {
      bar.classList.add('bg-green-500');
      bar.classList.remove('bg-red-500');
      } else {
      bar.classList.add('bg-red-500');
      bar.classList.remove('bg-green-500');
      }
      }

/**
    * Handle form submit (intercept and send JSON)
    *
    * @param {Event} event - Form submit event
      */
      async handleSubmit(event) {
      event.preventDefault();

      if (!this.isValidValue) {
      return;
      }

      this.isLoadingValue = true;
      this.showLoading();

      try {
      const response = await fetch('/api/generate', {
      method: 'POST',
      headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
      },
      body: JSON.stringify({
      source_text: this.textareaTarget.value
      })
      });

           this.hideLoading();

           if (response.ok) {
               const data = await response.json();

               // Success! Data is stored in session by backend
               // Redirect to edit view
               window.location.href = '/sets/new/edit';
           } else {
               // Handle error
               await this.handleError({ response, statusCode: response.status });
           }
      } catch (error) {
      this.hideLoading();
      this.showErrorModal({
      type: 'unknown',
      message: 'Wystąpił problem z połączeniem. Sprawdź internet i spróbuj ponownie.',
      suggestions: ['Sprawdź połączenie internetowe', 'Spróbuj ponownie za chwilę']
      });
      }
      }

/**
    * Show loading overlay with multi-stage progress
      */
      showLoading() {
      this.loadingOverlayTarget.classList.remove('hidden');
      this.loadingStageValue = 'analyzing';
      this.loadingMessageTarget.textContent = 'Analizuję tekst...';

      // Simulated progress: change to second stage after 5 seconds
      this.stageTimeout = setTimeout(() => {
      this.loadingStageValue = 'creating';
      this.loadingMessageTarget.textContent = 'Tworzę fiszki...';
      }, 5000);
      }

/**
    * Hide loading overlay
      */
      hideLoading() {
      this.isLoadingValue = false;
      this.loadingStageValue = null;
      this.loadingOverlayTarget.classList.add('hidden');

      if (this.stageTimeout) {
      clearTimeout(this.stageTimeout);
      this.stageTimeout = null;
      }
      }

/**
    * Handle error response
    *
    * @param {Object} response - Response object with { response, statusCode }
      */
      async handleError({ response, statusCode }) {
      let errorData;

      try {
      errorData = await response.json();
      } catch {
      errorData = {
      error: 'unknown',
      message: 'Wystąpił nieoczekiwany błąd'
      };
      }

      const errorState = this.mapErrorToState(errorData, statusCode);
      this.showErrorModal(errorState);
      }

/**
    * Map error response to ErrorState object
    *
    * @param {Object} errorData - Error data from API
    * @param {number} statusCode - HTTP status code
    * @returns {Object} ErrorState object
      */
      mapErrorToState(errorData, statusCode) {
      switch (statusCode) {
      case 504:
      return {
      type: 'timeout',
      message: errorData.message || 'Generowanie przekroczyło limit czasu (30s)',
      suggestions: [
      'Skróć tekst do 5000-7000 znaków',
      'Usuń znaki specjalne i formatowanie',
      'Uprość język i usuń skomplikowane fragmenty'
      ]
      };

           case 422:
               return {
                   type: 'validation',
                   message: errorData.message || 'Dane wejściowe są nieprawidłowe',
                   suggestions: errorData.violations?.map(v => v.message) || []
               };

           case 500:
               return {
                   type: 'ai_failure',
                   message: errorData.message || 'Wystąpił błąd podczas generowania fiszek',
                   suggestions: [
                       'Odczekaj 1-2 minuty i spróbuj ponownie',
                       'Sprawdź czy tekst nie zawiera niepoprawnych znaków'
                   ]
               };

           default:
               return {
                   type: 'unknown',
                   message: 'Wystąpił nieoczekiwany błąd',
                   suggestions: ['Spróbuj ponownie później']
               };
      }
      }

/**
    * Show error modal with message and suggestions
    *
    * @param {Object} errorState - ErrorState object
      */
      showErrorModal(errorState) {
      this.errorMessageTarget.textContent = errorState.message;

      // Render suggestions list
      this.errorSuggestionsTarget.innerHTML = errorState.suggestions
      .map(s => `<li>${s}</li>`)
      .join('');

      // Show modal (HTML dialog API)
      this.errorModalTarget.showModal();
      }

/**
    * Close error modal
      */
      closeErrorModal() {
      this.errorModalTarget.close();
      }

/**
    * Retry generation after error
      */
      retryGeneration() {
      this.closeErrorModal();

      // Re-submit the form
      this.element.requestSubmit();
      }
      }



================================================
FILE: assets/controllers/hello_controller.js
================================================
import { Controller } from '@hotwired/stimulus';

/*
* This is an example Stimulus controller!
*
* Any element with a data-controller="hello" attribute will cause
* this controller to be executed. The name "hello" comes from the filename:
* hello_controller.js -> "hello"
*
* Delete this file or adapt it for your use!
  */
  export default class extends Controller {
  connect() {
  this.element.textContent = 'Hello Stimulus! Edit me in assets/controllers/hello_controller.js';
  }
  }



================================================
FILE: assets/controllers/modal_controller.js
================================================
import { Controller } from '@hotwired/stimulus';

/*
* Modal Controller - Material 3 Dialog Component
*
* Handles:
* - Focus trap within modal
* - ESC key to close
* - Backdrop click to close (if dismissible)
* - Focus management (return to trigger on close)
* - Body scroll lock when modal is open
    */
    export default class extends Controller {
    static targets = ['dialog'];
    static values = {
    dismissible: { type: Boolean, default: true }
    };

connect() {
this.previousActiveElement = null;
this.focusableElements = null;
this.firstFocusable = null;
this.lastFocusable = null;
}

open() {
// Store the element that triggered the modal
this.previousActiveElement = document.activeElement;

     // Show modal
     this.element.style.display = 'flex';

     // Lock body scroll
     document.body.style.overflow = 'hidden';

     // Setup focus trap
     this.setupFocusTrap();

     // Focus first focusable element
     if (this.firstFocusable) {
         this.firstFocusable.focus();
     }

     // Dispatch custom event
     this.element.dispatchEvent(new CustomEvent('modal:opened', { bubbles: true }));
}

close() {
// Hide modal
this.element.style.display = 'none';

     // Unlock body scroll
     document.body.style.overflow = '';

     // Return focus to trigger element
     if (this.previousActiveElement && this.previousActiveElement.focus) {
         this.previousActiveElement.focus();
     }

     // Dispatch custom event
     this.element.dispatchEvent(new CustomEvent('modal:closed', { bubbles: true }));
}

confirm() {
// Dispatch confirm event before closing
this.element.dispatchEvent(new CustomEvent('modal:confirmed', { bubbles: true }));
this.close();
}

closeOnBackdrop(event) {
if (!this.dismissibleValue) return;
if (event.target === this.element) {
this.close();
}
}

closeOnEscape(event) {
if (!this.dismissibleValue) return;
if (event.key === 'Escape') {
event.preventDefault();
this.close();
}
}

stopPropagation(event) {
event.stopPropagation();
}

setupFocusTrap() {
const focusableSelectors = [
'a[href]',
'button:not([disabled])',
'textarea:not([disabled])',
'input:not([disabled])',
'select:not([disabled])',
'[tabindex]:not([tabindex="-1"])'
];

     this.focusableElements = this.dialogTarget.querySelectorAll(
         focusableSelectors.join(', ')
     );

     if (this.focusableElements.length === 0) return;

     this.firstFocusable = this.focusableElements[0];
     this.lastFocusable = this.focusableElements[this.focusableElements.length - 1];

     // Add focus trap listener
     this.dialogTarget.addEventListener('keydown', this.handleFocusTrap.bind(this));
}

handleFocusTrap(event) {
if (event.key !== 'Tab') return;

     if (event.shiftKey) {
         // Shift + Tab (backward)
         if (document.activeElement === this.firstFocusable) {
             event.preventDefault();
             this.lastFocusable.focus();
         }
     } else {
         // Tab (forward)
         if (document.activeElement === this.lastFocusable) {
             event.preventDefault();
             this.firstFocusable.focus();
         }
     }
}
}



================================================
FILE: assets/controllers/set_list_controller.js
================================================
import { Controller } from '@hotwired/stimulus';

/**
* Stimulus controller for sets list view.
*
* Manages:
* - Delete confirmation modal
* - DELETE request to API
* - Loading state during deletion
* - Error handling
    */
    export default class extends Controller {
    static targets = [
    'setCard',
    'deleteModal',
    'deleteSetName',
    'confirmDeleteButton',
    'loadingOverlay'
    ];

// Currently selected set for deletion
selectedSetId = null;
selectedSetName = null;

/**
    * Show delete confirmation modal
    *
    * @param {Event} event - Click event from delete button
      */
      confirmDelete(event) {
      this.selectedSetId = event.target.dataset.setId;
      this.selectedSetName = event.target.dataset.setName;

      // Update modal text
      this.deleteSetNameTarget.textContent = this.selectedSetName;

      // Show modal
      this.deleteModalTarget.showModal();
      }

/**
    * Close delete modal (cancel)
      */
      closeDeleteModal() {
      this.deleteModalTarget.close();
      this.selectedSetId = null;
      this.selectedSetName = null;
      }

/**
    * Execute delete operation
      */
      async executeDelete() {
      if (!this.selectedSetId) {
      return;
      }

      // Close modal
      this.deleteModalTarget.close();

      // Show loading overlay
      this.showLoading();

      try {
      const response = await fetch(`/api/sets/${this.selectedSetId}`, {
      method: 'DELETE',
      headers: {
      'Accept': 'application/json'
      }
      });

           this.hideLoading();

           if (response.ok) {
               // Success! Remove card from DOM
               this.removeSetCard(this.selectedSetId);

               // Show success message (temporary alert, TODO: toast)
               alert(`Zestaw "${this.selectedSetName}" został usunięty.`);

               // Reset selected set
               this.selectedSetId = null;
               this.selectedSetName = null;

               // Check if list is empty, show empty state
               if (this.setCardTargets.length === 0) {
                   // Reload page to show empty state
                   window.location.reload();
               }
           } else {
               // Handle error
               const errorData = await response.json();
               alert(`Błąd podczas usuwania: ${errorData.message || 'Nieznany błąd'}`);
           }
      } catch (error) {
      this.hideLoading();
      alert('Wystąpił problem z połączeniem. Sprawdź internet i spróbuj ponownie.');
      }
      }

/**
    * Remove set card from DOM
    *
    * @param {string} setId - Set ID to remove
      */
      removeSetCard(setId) {
      const card = this.setCardTargets.find(
      target => target.dataset.setId === setId
      );

      if (card) {
      // Fade out animation
      card.style.transition = 'opacity 0.3s ease-out';
      card.style.opacity = '0';

           // Remove from DOM after animation
           setTimeout(() => {
               card.remove();
           }, 300);
      }
      }

/**
    * Show loading overlay
      */
      showLoading() {
      if (this.hasLoadingOverlayTarget) {
      this.loadingOverlayTarget.classList.remove('hidden');
      }
      }

/**
    * Hide loading overlay
      */
      hideLoading() {
      if (this.hasLoadingOverlayTarget) {
      this.loadingOverlayTarget.classList.add('hidden');
      }
      }
      }



================================================
FILE: assets/controllers/snackbar_controller.js
================================================
import { Controller } from '@hotwired/stimulus';

/*
* Snackbar Controller - Material 3 Snackbar Component
*
* Handles:
* - Auto-hide after duration
* - Show/hide animations
* - Action button clicks
* - Close button
* - Queue management for multiple snackbars
    */
    export default class extends Controller {
    static values = {
    duration: { type: Number, default: 4000 }
    };

connect() {
this.timeout = null;
}

disconnect() {
this.clearTimeout();
}

show() {
// Show with animation
this.element.style.display = 'flex';

     // Trigger reflow for animation
     this.element.offsetHeight;

     this.element.classList.add('animate-in');

     // Auto-hide after duration (if duration > 0)
     if (this.durationValue > 0) {
         this.timeout = setTimeout(() => {
             this.close();
         }, this.durationValue);
     }

     // Dispatch custom event
     this.element.dispatchEvent(new CustomEvent('snackbar:shown', { bubbles: true }));
}

close() {
this.clearTimeout();

     // Hide with animation
     this.element.classList.remove('animate-in');
     this.element.classList.add('animate-out');

     // Remove after animation
     setTimeout(() => {
         this.element.style.display = 'none';
         this.element.classList.remove('animate-out');

         // Dispatch custom event
         this.element.dispatchEvent(new CustomEvent('snackbar:closed', { bubbles: true }));
     }, 300);
}

action(event) {
event.preventDefault();

     // Dispatch custom event with action
     this.element.dispatchEvent(new CustomEvent('snackbar:action', {
         bubbles: true,
         detail: { element: this.element }
     }));

     this.close();
}

clearTimeout() {
if (this.timeout) {
clearTimeout(this.timeout);
this.timeout = null;
}
}
}



================================================
FILE: assets/controllers/theme_controller.js
================================================
import {Controller} from '@hotwired/stimulus';

export default class extends Controller {
static targets = ['icon', 'label'];
static values = {
storageKey: {type: String, default: 'theme'}, // dark | light | system
};

    connect() {
        this.root = document.documentElement;
        this.media = window.matchMedia('(prefers-color-scheme: dark)');

        this.apply(this.mode);

        // jeśli użytkownik ma "system", to reagujemy na zmianę w OS
        this.onSystemChange = () => {
            if (this.mode === 'system') this.apply('system');
        };

        // nowoczesne API
        if (this.media.addEventListener) {
            this.media.addEventListener('change', this.onSystemChange);
        } else {
            // fallback dla starszych Safari
            this.media.addListener(this.onSystemChange);
        }

        this.syncUi();
    }

    disconnect() {
        if (!this.media) return;

        if (this.media.removeEventListener) {
            this.media.removeEventListener('change', this.onSystemChange);
        } else {
            this.media.removeListener(this.onSystemChange);
        }
    }

    // --- Public actions ---

    toggle() {
        // cykl: system -> light -> dark -> system ...
        const next = this.mode === 'system'
            ? 'light'
            : this.mode === 'light'
                ? 'dark'
                : 'system';

        this.mode = next;
        this.apply(next);
        this.syncUi();
    }

    setDark() {
        this.mode = 'dark';
        this.apply('dark');
        this.syncUi();
    }

    setLight() {
        this.mode = 'light';
        this.apply('light');
        this.syncUi();
    }

    setSystem() {
        this.mode = 'system';
        this.apply('system');
        this.syncUi();
    }

    // --- Core logic ---

    apply(mode) {
        if (mode === 'dark') {
            this.root.classList.add('dark');
        } else if (mode === 'light') {
            this.root.classList.remove('dark');
        } else {
            // system
            const prefersDark = this.media.matches;
            this.root.classList.toggle('dark', prefersDark);
        }
    }

    syncUi() {
        // Przykład: zmiana ikonki/tekstu przycisku
        const isDark = this.root.classList.contains('dark');
        const mode = this.mode;

        if (this.hasIconTarget) {
            // możesz użyć np. 🌙 / ☀️ albo SVG
            this.iconTarget.textContent = isDark ? '☀️' : '🌙';
        }

        if (this.hasLabelTarget) {
            this.labelTarget.textContent = mode === 'system'
                ? `System (${isDark ? 'dark' : 'light'})`
                : mode;
        }

        // przydatne do stylowania / testów
        this.element.dataset.themeMode = mode;
        this.element.setAttribute('aria-label', `Theme: ${mode}`);
    }

    // --- mode getter/setter ---

    get mode() {
        return localStorage.getItem(this.storageKeyValue) || 'system';
    }

    set mode(value) {
        localStorage.setItem(this.storageKeyValue, value);
    }
}



================================================
FILE: assets/styles/app.css
================================================
@import "tailwindcss";

/* Material 3 Design Tokens - CSS Variables */
@theme {
/* ===========================
COLOR TOKENS - LIGHT MODE
=========================== */

    /* Primary Colors */
    --color-primary: #6750a4;
    --color-on-primary: #ffffff;
    --color-primary-container: #eaddff;
    --color-on-primary-container: #21005e;

    /* Secondary Colors */
    --color-secondary: #625b71;
    --color-on-secondary: #ffffff;
    --color-secondary-container: #e8def8;
    --color-on-secondary-container: #1e192b;

    /* Tertiary Colors */
    --color-tertiary: #7d5260;
    --color-on-tertiary: #ffffff;
    --color-tertiary-container: #ffd8e4;
    --color-on-tertiary-container: #31101d;

    /* Error Colors */
    --color-error: #b3261e;
    --color-on-error: #ffffff;
    --color-error-container: #f9dedc;
    --color-on-error-container: #410e0b;

    /* Surface Colors */
    --color-surface: #fef7ff;
    --color-on-surface: #1c1b1f;
    --color-surface-variant: #e7e0ec;
    --color-on-surface-variant: #49454f;
    --color-surface-dim: #ded8e1;
    --color-surface-bright: #fef7ff;
    --color-surface-container-lowest: #ffffff;
    --color-surface-container-low: #f7f2fa;
    --color-surface-container: #f3edf7;
    --color-surface-container-high: #ece6f0;
    --color-surface-container-highest: #e6e0e9;

    /* Outline Colors */
    --color-outline: #79747e;
    --color-outline-variant: #cac4d0;

    /* Background Colors */
    --color-background: #fef7ff;
    --color-on-background: #1c1b1f;

    /* Inverse Colors */
    --color-inverse-surface: #313033;
    --color-inverse-on-surface: #f4eff4;
    --color-inverse-primary: #d0bcff;

    /* Scrim & Shadow */
    --color-scrim: #000000;
    --color-shadow: #000000;

    /* State Layer Opacity Tokens */
    --state-hover-opacity: 0.08;
    --state-focus-opacity: 0.12;
    --state-pressed-opacity: 0.12;
    --state-dragged-opacity: 0.16;
    --state-disabled-opacity: 0.38;
    --state-disabled-container-opacity: 0.12;

    /* ===========================
       DARK MODE COLOR TOKENS
       =========================== */


    /* Elevation/Shadow Tokens */
    --shadow-elevation-0: none;
    --shadow-elevation-1: 0 1px 2px 0 rgb(0 0 0 / 0.3), 0 1px 3px 1px rgb(0 0 0 / 0.15);
    --shadow-elevation-2: 0 1px 2px 0 rgb(0 0 0 / 0.3), 0 2px 6px 2px rgb(0 0 0 / 0.15);
    --shadow-elevation-3: 0 4px 8px 3px rgb(0 0 0 / 0.15), 0 1px 3px 0 rgb(0 0 0 / 0.3);
    --shadow-elevation-4: 0 6px 10px 4px rgb(0 0 0 / 0.15), 0 2px 3px 0 rgb(0 0 0 / 0.3);
    --shadow-elevation-5: 0 8px 12px 6px rgb(0 0 0 / 0.15), 0 4px 4px 0 rgb(0 0 0 / 0.3);

    /* Motion & Animation Tokens - Material 3 Durations */
    --duration-short-1: 50ms;
    --duration-short-2: 100ms;
    --duration-short-3: 150ms;
    --duration-short-4: 200ms;
    --duration-medium-1: 250ms;
    --duration-medium-2: 300ms;
    --duration-medium-3: 350ms;
    --duration-medium-4: 400ms;
    --duration-long-1: 450ms;
    --duration-long-2: 500ms;
    --duration-long-3: 550ms;
    --duration-long-4: 600ms;
    --duration-extra-long-1: 700ms;
    --duration-extra-long-2: 800ms;
    --duration-extra-long-3: 900ms;
    --duration-extra-long-4: 1000ms;

    /* Motion & Animation Tokens - Material 3 Easing */
    --easing-emphasized: cubic-bezier(0.2, 0, 0, 1);
    --easing-emphasized-decelerate: cubic-bezier(0.05, 0.7, 0.1, 1);
    --easing-emphasized-accelerate: cubic-bezier(0.3, 0, 0.8, 0.15);
    --easing-standard: cubic-bezier(0.2, 0, 0, 1);
    --easing-standard-decelerate: cubic-bezier(0, 0, 0, 1);
    --easing-standard-accelerate: cubic-bezier(0.3, 0, 1, 1);
    --easing-legacy: cubic-bezier(0.4, 0, 0.2, 1);
    --easing-legacy-decelerate: cubic-bezier(0, 0, 0.2, 1);
    --easing-legacy-accelerate: cubic-bezier(0.4, 0, 1, 1);
    --easing-linear: cubic-bezier(0, 0, 1, 1);

    /* Icon Size Tokens */
    --icon-size-xs: 16px;
    --icon-size-sm: 18px;
    --icon-size-md: 20px;
    --icon-size-lg: 24px;
    --icon-size-xl: 32px;
    --icon-size-2xl: 40px;
    --icon-size-3xl: 48px;

    /* Z-Index Layers */
    --z-index-base: 0;
    --z-index-dropdown: 1000;
    --z-index-sticky: 1020;
    --z-index-fixed: 1030;
    --z-index-modal-backdrop: 1040;
    --z-index-modal: 1050;
    --z-index-popover: 1060;
    --z-index-tooltip: 1070;
    --z-index-notification: 1080;
    --z-index-snackbar: 1090;

    /* Shape Tokens */
    --radius-none: 0px;
    --radius-xs: 4px;
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --radius-xl: 28px;
    --radius-full: 9999px;

    /* Spacing Tokens */
    --spacing-xs: 4px;
    --spacing-sm: 8px;
    --spacing-md: 12px;
    --spacing-lg: 16px;
    --spacing-xl: 24px;
    --spacing-2xl: 32px;
    --spacing-3xl: 48px;

    /* Max-Width Tokens (for Tailwind max-w-* classes) */
    --max-width-xs: 320px;
    --max-width-sm: 384px;
    --max-width-md: 448px;
    --max-width-lg: 512px;
    --max-width-xl: 576px;
    --max-width-2xl: 672px;
    --max-width-3xl: 768px;
    --max-width-4xl: 896px;
    --max-width-5xl: 1024px;
    --max-width-6xl: 1152px;
    --max-width-7xl: 1280px;
    --max-width-full: 100%;
    --max-width-prose: 65ch;

    /* Typography Tokens - Material 3 Type Scale */
    --font-family-base: 'Roboto', system-ui, -apple-system, sans-serif;

    /* Display */
    --font-size-display-large: 57px;
    --font-weight-display-large: 400;
    --line-height-display-large: 64px;

    --font-size-display-medium: 45px;
    --font-weight-display-medium: 400;
    --line-height-display-medium: 52px;

    --font-size-display-small: 36px;
    --font-weight-display-small: 400;
    --line-height-display-small: 44px;

    /* Headline */
    --font-size-headline-large: 32px;
    --font-weight-headline-large: 400;
    --line-height-headline-large: 40px;

    --font-size-headline-medium: 28px;
    --font-weight-headline-medium: 400;
    --line-height-headline-medium: 36px;

    --font-size-headline-small: 24px;
    --font-weight-headline-small: 400;
    --line-height-headline-small: 32px;

    /* Title */
    --font-size-title-large: 22px;
    --font-weight-title-large: 400;
    --line-height-title-large: 28px;

    --font-size-title-medium: 16px;
    --font-weight-title-medium: 500;
    --line-height-title-medium: 24px;
    --letter-spacing-title-medium: 0.15px;

    --font-size-title-small: 14px;
    --font-weight-title-small: 500;
    --line-height-title-small: 20px;
    --letter-spacing-title-small: 0.1px;

    /* Body */
    --font-size-body-large: 16px;
    --font-weight-body-large: 400;
    --line-height-body-large: 24px;
    --letter-spacing-body-large: 0.5px;

    --font-size-body-medium: 14px;
    --font-weight-body-medium: 400;
    --line-height-body-medium: 20px;
    --letter-spacing-body-medium: 0.25px;

    --font-size-body-small: 12px;
    --font-weight-body-small: 400;
    --line-height-body-small: 16px;
    --letter-spacing-body-small: 0.4px;

    /* Label */
    --font-size-label-large: 14px;
    --font-weight-label-large: 500;
    --line-height-label-large: 20px;
    --letter-spacing-label-large: 0.1px;

    --font-size-label-medium: 12px;
    --font-weight-label-medium: 500;
    --line-height-label-medium: 16px;
    --letter-spacing-label-medium: 0.5px;

    --font-size-label-small: 11px;
    --font-weight-label-small: 500;
    --line-height-label-small: 16px;
    --letter-spacing-label-small: 0.5px;

    /* ===========================
       COMPONENT SPECIFIC TOKENS
       =========================== */

    /* Button Tokens */
    --button-height: 40px;
    --button-height-sm: 32px;
    --button-height-lg: 48px;
    --button-icon-size: 18px;
    --button-padding-x: 24px;
    --button-padding-x-sm: 16px;
    --button-padding-x-lg: 32px;
    --button-gap: 8px;
    --button-min-width: 48px;
    --button-border-width: 1px;

    /* Input / Text Field Tokens */
    --input-height: 56px;
    --input-padding-x: 16px;
    --input-padding-y: 8px;
    --input-border-width: 1px;
    --input-border-width-focus: 2px;
    --input-label-spacing: 4px;

    /* Card Tokens */
    --card-padding: 16px;
    --card-gap: 12px;
    --card-border-width: 1px;

    /* List Item Tokens */
    --list-item-height-1: 56px;
    --list-item-height-2: 72px;
    --list-item-height-3: 88px;
    --list-item-padding-x: 16px;
    --list-item-padding-y: 8px;
    --list-item-gap: 16px;
    --list-item-leading-size: 24px;
    --list-item-trailing-size: 24px;

    /* Navigation Tokens */
    --nav-item-height: 56px;
    --nav-item-width: 64px;
    --nav-item-padding: 12px;
    --nav-item-gap: 4px;
    --nav-indicator-height: 32px;
    --nav-indicator-width: 64px;
    --nav-badge-size: 16px;
    --nav-rail-width: 80px;
    --nav-drawer-width: 360px;
    --nav-drawer-width-compact: 280px;

    /* Dialog / Modal Tokens */
    --dialog-width: 560px;
    --dialog-width-sm: 400px;
    --dialog-width-lg: 640px;
    --dialog-padding: 24px;
    --dialog-gap: 16px;
    --dialog-button-gap: 8px;
    --dialog-max-height: 80vh;

    /* Snackbar Tokens */
    --snackbar-width: 344px;
    --snackbar-width-mobile: calc(100vw - 32px);
    --snackbar-height: 48px;
    --snackbar-padding-x: 16px;
    --snackbar-padding-y: 14px;
    --snackbar-gap: 8px;

    /* FAB (Floating Action Button) Tokens */
    --fab-size: 56px;
    --fab-size-sm: 40px;
    --fab-size-lg: 96px;
    --fab-icon-size: 24px;
    --fab-extended-padding-x: 16px;
    --fab-extended-gap: 12px;

    /* Chip Tokens */
    --chip-height: 32px;
    --chip-padding-x: 16px;
    --chip-gap: 8px;
    --chip-icon-size: 18px;

    /* Top App Bar Tokens */
    --app-bar-height: 64px;
    --app-bar-height-sm: 56px;
    --app-bar-padding-x: 16px;
    --app-bar-gap: 16px;

    /* Bottom Sheet Tokens */
    --bottom-sheet-padding: 16px;
    --bottom-sheet-drag-handle-width: 32px;
    --bottom-sheet-drag-handle-height: 4px;

    /* Progress Indicator Tokens */
    --progress-height: 4px;
    --progress-circular-size: 48px;
    --progress-circular-size-sm: 24px;
    --progress-circular-size-lg: 64px;
    --progress-circular-stroke-width: 4px;

    /* Divider Tokens */
    --divider-width: 1px;
    --divider-spacing: 16px;
}


@layer base {
.dark {
--color-primary: #d0bcff;
--color-on-primary: #381e72;
--color-primary-container: #4f378b;
--color-on-primary-container: #eaddff;
--color-secondary: #ccc2dc;
--color-on-secondary: #332d41;
--color-secondary-container: #4a4458;
--color-on-secondary-container: #e8def8;
--color-tertiary: #efb8c8;
--color-on-tertiary: #492532;
--color-tertiary-container: #633b48;
--color-on-tertiary-container: #ffd8e4;
--color-error: #f2b8b5;
--color-on-error: #601410;
--color-error-container: #8c1d18;
--color-on-error-container: #f9dedc;
--color-surface: #141218;
--color-on-surface: #e6e0e9;
--color-surface-variant: #49454f;
--color-on-surface-variant: #cac4d0;
--color-surface-dim: #141218;
--color-surface-bright: #3b383e;
--color-surface-container-lowest: #0f0d13;
--color-surface-container-low: #1d1b20;
--color-surface-container: #211f26;
--color-surface-container-high: #2b2930;
--color-surface-container-highest: #36343b;
--color-outline: #938f99;
--color-outline-variant: #49454f;
--color-background: #141218;
--color-on-background: #e6e0e9;
--color-inverse-surface: #e6e0e9;
--color-inverse-on-surface: #313033;
--color-inverse-primary: #6750a4;
}
}


/* Utility Classes for Typography */
.text-display-large {
font-size: var(--font-size-display-large);
font-weight: var(--font-weight-display-large);
line-height: var(--line-height-display-large);
}

.text-display-medium {
font-size: var(--font-size-display-medium);
font-weight: var(--font-weight-display-medium);
line-height: var(--line-height-display-medium);
}

.text-display-small {
font-size: var(--font-size-display-small);
font-weight: var(--font-weight-display-small);
line-height: var(--line-height-display-small);
}

.text-headline-large {
font-size: var(--font-size-headline-large);
font-weight: var(--font-weight-headline-large);
line-height: var(--line-height-headline-large);
}

.text-headline-medium {
font-size: var(--font-size-headline-medium);
font-weight: var(--font-weight-headline-medium);
line-height: var(--line-height-headline-medium);
}

.text-headline-small {
font-size: var(--font-size-headline-small);
font-weight: var(--font-weight-headline-small);
line-height: var(--line-height-headline-small);
}

.text-title-large {
font-size: var(--font-size-title-large);
font-weight: var(--font-weight-title-large);
line-height: var(--line-height-title-large);
}

.text-title-medium {
font-size: var(--font-size-title-medium);
font-weight: var(--font-weight-title-medium);
line-height: var(--line-height-title-medium);
letter-spacing: var(--letter-spacing-title-medium);
}

.text-title-small {
font-size: var(--font-size-title-small);
font-weight: var(--font-weight-title-small);
line-height: var(--line-height-title-small);
letter-spacing: var(--letter-spacing-title-small);
}

.text-body-large {
font-size: var(--font-size-body-large);
font-weight: var(--font-weight-body-large);
line-height: var(--line-height-body-large);
letter-spacing: var(--letter-spacing-body-large);
}

.text-body-medium {
font-size: var(--font-size-body-medium);
font-weight: var(--font-weight-body-medium);
line-height: var(--line-height-body-medium);
letter-spacing: var(--letter-spacing-body-medium);
}

.text-body-small {
font-size: var(--font-size-body-small);
font-weight: var(--font-weight-body-small);
line-height: var(--line-height-body-small);
letter-spacing: var(--letter-spacing-body-small);
}

.text-label-large {
font-size: var(--font-size-label-large);
font-weight: var(--font-weight-label-large);
line-height: var(--line-height-label-large);
letter-spacing: var(--letter-spacing-label-large);
}

.text-label-medium {
font-size: var(--font-size-label-medium);
font-weight: var(--font-weight-label-medium);
line-height: var(--line-height-label-medium);
letter-spacing: var(--letter-spacing-label-medium);
}

.text-label-small {
font-size: var(--font-size-label-small);
font-weight: var(--font-weight-label-small);
line-height: var(--line-height-label-small);
letter-spacing: var(--letter-spacing-label-small);
}

/* ===========================
UTILITY CLASSES - ELEVATION
=========================== */
.elevation-0 {
box-shadow: var(--shadow-elevation-0);
}

.elevation-1 {
box-shadow: var(--shadow-elevation-1);
}

.elevation-2 {
box-shadow: var(--shadow-elevation-2);
}

.elevation-3 {
box-shadow: var(--shadow-elevation-3);
}

.elevation-4 {
box-shadow: var(--shadow-elevation-4);
}

.elevation-5 {
box-shadow: var(--shadow-elevation-5);
}

/* ===========================
UTILITY CLASSES - SURFACE
=========================== */
.surface {
background-color: var(--color-surface);
color: var(--color-on-surface);
}

.surface-variant {
background-color: var(--color-surface-variant);
color: var(--color-on-surface-variant);
}

.surface-container-lowest {
background-color: var(--color-surface-container-lowest);
}

.surface-container-low {
background-color: var(--color-surface-container-low);
}

.surface-container {
background-color: var(--color-surface-container);
}

.surface-container-high {
background-color: var(--color-surface-container-high);
}

.surface-container-highest {
background-color: var(--color-surface-container-highest);
}

/* ===========================
UTILITY CLASSES - STATE LAYERS
=========================== */
.state-layer-hover::before {
content: '';
position: absolute;
inset: 0;
background-color: currentColor;
opacity: 0;
transition: opacity var(--duration-short-2) var(--easing-standard);
pointer-events: none;
}

.state-layer-hover:hover::before {
opacity: var(--state-hover-opacity);
}

.state-layer-focus:focus-visible::before {
opacity: var(--state-focus-opacity);
}

.state-layer-pressed:active::before {
opacity: var(--state-pressed-opacity);
}

/* ===========================
UTILITY CLASSES - TRANSITIONS
=========================== */
.transition-short {
transition-duration: var(--duration-short-4);
transition-timing-function: var(--easing-standard);
}

.transition-medium {
transition-duration: var(--duration-medium-2);
transition-timing-function: var(--easing-standard);
}

.transition-long {
transition-duration: var(--duration-long-2);
transition-timing-function: var(--easing-standard);
}

.transition-emphasized {
transition-timing-function: var(--easing-emphasized);
}

/* ===========================
UTILITY CLASSES - SHAPE
=========================== */
.shape-none {
border-radius: var(--radius-none);
}

.shape-xs {
border-radius: var(--radius-xs);
}

.shape-sm {
border-radius: var(--radius-sm);
}

.shape-md {
border-radius: var(--radius-md);
}

.shape-lg {
border-radius: var(--radius-lg);
}

.shape-xl {
border-radius: var(--radius-xl);
}

.shape-full {
border-radius: var(--radius-full);
}

/* ===========================
BASE STYLES
=========================== */
body {
font-family: var(--font-family-base);
background-color: var(--color-background);
color: var(--color-on-background);
}

/* Focus visible styles for accessibility */
*:focus-visible {
outline: 2px solid var(--color-primary);
outline-offset: 2px;
}

/* Smooth scrolling */
@media (prefers-reduced-motion: no-preference) {
html {
scroll-behavior: smooth;
}
}

/* ===========================
UTILITY CLASSES - LAYOUT WIDTHS
=========================== */
.container-xs {
max-width: var(--max-width-xs);
margin-left: auto;
margin-right: auto;
}

.container-sm {
max-width: var(--max-width-sm);
margin-left: auto;
margin-right: auto;
}

.container-md {
max-width: var(--max-width-md);
margin-left: auto;
margin-right: auto;
}

.container-lg {
max-width: var(--max-width-lg);
margin-left: auto;
margin-right: auto;
}

.container-xl {
max-width: var(--max-width-xl);
margin-left: auto;
margin-right: auto;
}

.container-2xl {
max-width: var(--max-width-2xl);
margin-left: auto;
margin-right: auto;
}

/* Special container for forms/dialogs */
.container-form {
max-width: var(--dialog-width);
margin-left: auto;
margin-right: auto;
}

/* ===========================
TAILWIND-LIKE UTILITY CLASSES
=========================== */

/* Max-width utilities */
.max-w-xs {
max-width: var(--max-width-xs);
}

.max-w-sm {
max-width: var(--max-width-sm);
}

.max-w-md {
max-width: var(--max-width-md);
}

.max-w-lg {
max-width: var(--max-width-lg);
}

.max-w-xl {
max-width: var(--max-width-xl);
}

.max-w-2xl {
max-width: var(--max-width-2xl);
}

.max-w-3xl {
max-width: var(--max-width-3xl);
}

.max-w-4xl {
max-width: var(--max-width-4xl);
}

.max-w-5xl {
max-width: var(--max-width-5xl);
}

.max-w-6xl {
max-width: var(--max-width-6xl);
}

.max-w-7xl {
max-width: var(--max-width-7xl);
}

.max-w-full {
max-width: var(--max-width-full);
}

.max-w-prose {
max-width: var(--max-width-prose);
}

/* Margin utilities */
.mx-auto {
margin-left: auto;
margin-right: auto;
}



================================================
FILE: config/bundles.php
================================================
<?php

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Symfony\Bundle\DebugBundle\DebugBundle::class => ['dev' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true],
    Twig\Extra\TwigExtraBundle\TwigExtraBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
    Symfony\UX\StimulusBundle\StimulusBundle::class => ['all' => true],
    Symfony\UX\Turbo\TurboBundle::class => ['all' => true],
    Symfony\UX\TwigComponent\TwigComponentBundle::class => ['all' => true],
    Symfonycasts\TailwindBundle\SymfonycastsTailwindBundle::class => ['all' => true],
    SymfonyCasts\Bundle\VerifyEmail\SymfonyCastsVerifyEmailBundle::class => ['all' => true],
];



================================================
FILE: config/preload.php
================================================
<?php

if (file_exists(dirname(__DIR__).'/var/cache/prod/App_KernelProdContainer.preload.php')) {
    require dirname(__DIR__).'/var/cache/prod/App_KernelProdContainer.preload.php';
}



================================================
FILE: config/routes.yaml
================================================
controllers:
    resource:
        path: ../src/Controller/
        namespace: App\Controller
    type: attribute

# UI Layer controllers (Clean Architecture)
ui_controllers:
    resource:
        path: ../src/UI/Http/Controller/
        namespace: App\UI\Http\Controller
    type: attribute



================================================
FILE: config/services.yaml
================================================
# This file is the entry point to configure your own services.
# Files in the packages/ subdirectory configure your dependencies.

# Put parameters here that don't need to change on each machine where the app is deployed
# https://symfony.com/doc/current/best_practices.html#use-parameters-for-application-configuration
parameters:

services:
    # default configuration for services in *this* file
    _defaults:
        autowire: true      # Automatically injects dependencies in your services.
        autoconfigure: true # Automatically registers your services as commands, event subscribers, etc.
        bind:
            # OpenRouter configuration bindings
            $apiKey: '%env(OPENROUTER_API_KEY)%'
            $apiUrl: '%env(OPENROUTER_API_URL)%'
            $defaultModel: '%env(OPENROUTER_DEFAULT_MODEL)%'
            $defaultTimeout: '%env(int:OPENROUTER_DEFAULT_TIMEOUT)%'

    # makes classes in src/ available to be used as services
    # this creates a service per class whose id is the fully-qualified class name
    App\:
        resource: '../src/'

    # OpenRouter Service Configuration
    App\Infrastructure\Integration\OpenRouter\OpenRouterServiceInterface:
        class: App\Infrastructure\Integration\OpenRouter\OpenRouterService
        arguments:
            $httpClient: '@http_client'
            $logger: '@monolog.logger'
            $apiKey: '%env(OPENROUTER_API_KEY)%'
            $apiUrl: '%env(OPENROUTER_API_URL)%'
            $defaultModel: '%env(OPENROUTER_DEFAULT_MODEL)%'
            $defaultTimeout: '%env(int:OPENROUTER_DEFAULT_TIMEOUT)%'

    # AI Card Generator - Domain Interface Implementation
    # Maps domain interface to infrastructure adapter
    App\Domain\Service\AiCardGeneratorInterface:
        class: App\Infrastructure\Integration\Ai\OpenRouterAiCardGenerator
        arguments:
            $openRouterService: '@App\Infrastructure\Integration\OpenRouter\OpenRouterServiceInterface'
            $logger: '@monolog.logger'

    # add more service definitions when explicit configuration is needed
    # please note that last definitions always *replace* previous ones



================================================
FILE: config/packages/asset_mapper.yaml
================================================
framework:
    asset_mapper:
        # The paths to make available to the asset mapper.
        paths:
            - assets/
        missing_import_mode: strict

when@prod:
    framework:
        asset_mapper:
            missing_import_mode: warn



================================================
FILE: config/packages/cache.yaml
================================================
framework:
    cache:
        # Unique name of your app: used to compute stable namespaces for cache keys.
        #prefix_seed: your_vendor_name/app_name

        # The "app" cache stores to the filesystem by default.
        # The data in this cache should persist between deploys.
        # Other options include:

        # Redis
        #app: cache.adapter.redis
        #default_redis_provider: redis://localhost

        # APCu (not recommended with heavy random-write workloads as memory fragmentation can cause perf issues)
        #app: cache.adapter.apcu

        # Namespaced pools use the above "app" backend by default
        #pools:
            #my.dedicated.cache: null



================================================
FILE: config/packages/csrf.yaml
================================================
# Enable stateless CSRF protection for forms and logins/logouts
framework:
    form:
        csrf_protection:
            token_id: submit

    csrf_protection:
        stateless_token_ids:
            - submit
            - authenticate
            - logout



================================================
FILE: config/packages/debug.yaml
================================================
when@dev:
    debug:
        # Forwards VarDumper Data clones to a centralized server allowing to inspect dumps on CLI or in your browser.
        # See the "server:dump" command to start a new server.
        dump_destination: "tcp://%env(VAR_DUMPER_SERVER)%"



================================================
FILE: config/packages/doctrine.yaml
================================================
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'

        # IMPORTANT: You MUST configure your server version,
        # either here or in the DATABASE_URL env var (see .env file)
        #server_version: '16'

        profiling_collect_backtrace: '%kernel.debug%'
        use_savepoints: true

        # Custom Doctrine DBAL Types for PostgreSQL enums
        types:
            card_origin: App\Infrastructure\Doctrine\Type\CardOriginType
            ai_job_status: App\Infrastructure\Doctrine\Type\AiJobStatusType

        # Map database types to Doctrine types
        # This tells Doctrine that PostgreSQL enums should be treated as our custom types
        mapping_types:
            card_origin: card_origin
            ai_job_status: ai_job_status
            # PostgreSQL citext extension (case-insensitive text) maps to string
            citext: string
    orm:
        auto_generate_proxy_classes: true
        enable_lazy_ghost_objects: true
        report_fields_where_declared: true
        validate_xml_mapping: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        identity_generation_preferences:
            Doctrine\DBAL\Platforms\PostgreSQLPlatform: identity
        auto_mapping: true
        mappings:
            App:
                type: attribute
                is_bundle: false
                dir: '%kernel.project_dir%/src/Domain/Model'
                prefix: 'App\Domain\Model'
                alias: App
        controller_resolver:
            auto_mapping: false

when@test:
    doctrine:
        dbal:
            # "TEST_TOKEN" is typically set by ParaTest
            dbname_suffix: '_test%env(default::TEST_TOKEN)%'

when@prod:
    doctrine:
        orm:
            auto_generate_proxy_classes: false
            proxy_dir: '%kernel.build_dir%/doctrine/orm/Proxies'
            query_cache_driver:
                type: pool
                pool: doctrine.system_cache_pool
            result_cache_driver:
                type: pool
                pool: doctrine.result_cache_pool

    framework:
        cache:
            pools:
                doctrine.result_cache_pool:
                    adapter: cache.app
                doctrine.system_cache_pool:
                    adapter: cache.system



================================================
FILE: config/packages/doctrine_migrations.yaml
================================================
doctrine_migrations:
    migrations_paths:
        # namespace is arbitrary but should be different from App\Migrations
        # as migrations classes should NOT be autoloaded
        'DoctrineMigrations': '%kernel.project_dir%/migrations'
    enable_profiler: false



================================================
FILE: config/packages/framework.yaml
================================================
# see https://symfony.com/doc/current/reference/configuration/framework.html
framework:
    secret: '%env(APP_SECRET)%'

    # Note that the session will be started ONLY if you read or write from it.
    session: true

    #esi: true
    #fragments: true

when@test:
    framework:
        test: true
        session:
            storage_factory_id: session.storage.factory.mock_file



================================================
FILE: config/packages/mailer.yaml
================================================
framework:
    mailer:
        dsn: '%env(MAILER_DSN)%'



================================================
FILE: config/packages/messenger.yaml
================================================
framework:
    messenger:
        failure_transport: failed

        transports:
            # https://symfony.com/doc/current/messenger.html#transport-configuration
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                options:
                    use_notify: true
                    check_delayed_interval: 60000
                retry_strategy:
                    max_retries: 3
                    multiplier: 2
            failed: 'doctrine://default?queue_name=failed'
            sync: 'sync://'

        default_bus: messenger.bus.default

        buses:
            messenger.bus.default: []

        routing:
            # Synchronous email sending for MVP (no need for Messenger workers/tables)
            Symfony\Component\Mailer\Messenger\SendEmailMessage: sync
            Symfony\Component\Notifier\Message\ChatMessage: async
            Symfony\Component\Notifier\Message\SmsMessage: async

            # Route your messages to the transports
            # 'App\Message\YourMessage': async



================================================
FILE: config/packages/monolog.yaml
================================================
monolog:
    channels:
        - deprecation # Deprecations are logged in the dedicated "deprecation" channel when it exists

when@dev:
    monolog:
        handlers:
            main:
                type: stream
                path: "%kernel.logs_dir%/%kernel.environment%.log"
                level: debug
                channels: ["!event"]
            # uncomment to get logging in your browser
            # you may have to allow bigger header sizes in your Web server configuration
            #firephp:
            #    type: firephp
            #    level: info
            #chromephp:
            #    type: chromephp
            #    level: info
            console:
                type: console
                process_psr_3_messages: false
                channels: ["!event", "!doctrine", "!console"]

when@test:
    monolog:
        handlers:
            main:
                type: fingers_crossed
                action_level: error
                handler: nested
                excluded_http_codes: [404, 405]
                channels: ["!event"]
            nested:
                type: stream
                path: "%kernel.logs_dir%/%kernel.environment%.log"
                level: debug

when@prod:
    monolog:
        handlers:
            main:
                type: fingers_crossed
                action_level: error
                handler: nested
                excluded_http_codes: [404, 405]
                buffer_size: 50 # How many messages should be saved? Prevent memory leaks
            nested:
                type: stream
                path: php://stderr
                level: debug
                formatter: monolog.formatter.json
            console:
                type: console
                process_psr_3_messages: false
                channels: ["!event", "!doctrine"]
            deprecation:
                type: stream
                channels: [deprecation]
                path: php://stderr
                formatter: monolog.formatter.json



================================================
FILE: config/packages/notifier.yaml
================================================
framework:
    notifier:
        chatter_transports:
        texter_transports:
        channel_policy:
            # use chat/slack, chat/telegram, sms/twilio or sms/nexmo
            urgent: ['email']
            high: ['email']
            medium: ['email']
            low: ['email']
        admin_recipients:
            - { email: admin@example.com }



================================================
FILE: config/packages/property_info.yaml
================================================
framework:
    property_info:
        with_constructor_extractor: true



================================================
FILE: config/packages/routing.yaml
================================================
framework:
    router:
        # Configure how to generate URLs in non-HTTP contexts, such as CLI commands.
        # See https://symfony.com/doc/current/routing.html#generating-urls-in-commands
        default_uri: '%env(DEFAULT_URI)%'

when@prod:
    framework:
        router:
            strict_requirements: null



================================================
FILE: config/packages/security.yaml
================================================
security:
    # https://symfony.com/doc/current/security.html#registering-the-user-hashing-passwords
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'

    # https://symfony.com/doc/current/security.html#loading-the-user-the-user-provider
    providers:
        # Custom User Provider using Domain repository (Clean Architecture)
        # Loads users from PostgreSQL via UserRepositoryInterface
        app_user_provider:
            id: App\Infrastructure\Security\UserProvider

        # In-memory test user for development/testing (kept for API testing)
        # Can be used with http_basic in test environment
        test_users:
            memory:
                users:
                    # Test user: test@example.com / test123
                    test@example.com:
                        password: '$2y$13$6V7zoNYDttt38XdNcqJUyOuFLi1j6r1/edxlV68Kd2yYxVa3/3Lou'  # test123
                        roles: ['ROLE_USER']

    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        main:
            lazy: true
            provider: app_user_provider

            # Form-based login (session-based authentication)
            # Handles POST /login and creates authenticated session
            form_login:
                # Login form page (GET /login)
                login_path: app_login
                # Target to process login submission (POST /login)
                check_path: app_login
                # Redirect to this route after successful login
                default_target_path: generate_view
                # Always use default_target_path (ignore previous URL)
                always_use_default_target_path: false
                # Enable CSRF protection for login form
                enable_csrf: true
                # Username parameter name in login form
                username_parameter: _username
                # Password parameter name in login form
                password_parameter: _password

            # Logout configuration
            logout:
                # Logout route (GET /logout)
                path: app_logout
                # Redirect to home page after logout
                target: generate_view
                # Invalidate session on logout (security best practice)
                invalidate_session: true

            # Remember me functionality (optional, can be enabled later)
            # remember_me:
            #     secret: '%kernel.secret%'
            #     lifetime: 604800 # 1 week in seconds
            #     path: /
            #     always_remember_me: false

    # Easy way to control access for large sections of your site
    # Note: Only the *first* access control that matches will be used
    access_control:
        # Public routes (no authentication required)
        - { path: ^/login, roles: PUBLIC_ACCESS }
        - { path: ^/register, roles: PUBLIC_ACCESS }
        - { path: ^/reset-password, roles: PUBLIC_ACCESS }

        # Public access to AI generation (PRD US-003, US-010)
        # Users can generate flashcards WITHOUT logging in
        - { path: ^/generate, roles: PUBLIC_ACCESS }
        - { path: ^/api/generate, roles: PUBLIC_ACCESS }

        # Protected routes (require ROLE_USER)
        - { path: ^/sets, roles: ROLE_USER }
        - { path: ^/api/sets, roles: ROLE_USER }
        - { path: ^/api/learning, roles: ROLE_USER }

        # Everything else is public by default
        # - { path: ^/, roles: PUBLIC_ACCESS }

when@test:
    security:
        password_hashers:
            # By default, password hashers are resource intensive and take time. This is
            # important to generate secure password hashes. In tests however, secure hashes
            # are not important, waste resources and increase test times. The following
            # reduces the work factor to the lowest possible values.
            Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface:
                algorithm: auto
                cost: 4 # Lowest possible value for bcrypt
                time_cost: 3 # Lowest possible value for argon
                memory_cost: 10 # Lowest possible value for argon



================================================
FILE: config/packages/symfonycasts_tailwind.yaml
================================================
symfonycasts_tailwind:
    # Specify the EXACT version of Tailwind CSS you want to use
    binary_version: 'v4.1.11'

    # Alternatively, you can specify the path to the binary that you manage yourself
    #binary: 'node_modules/.bin/tailwindcss'



================================================
FILE: config/packages/symfonycasts_verify_email.yaml
================================================
symfonycasts_verify_email:
    # Lifetime of the verification link (1 hour = 3600 seconds)
    # After this time, the link will expire and user must request a new one
    # Auth-spec.md section 3.5.3 specifies 1 hour expiration
    lifetime: 3600



================================================
FILE: config/packages/translation.yaml
================================================
framework:
    default_locale: en
    translator:
        default_path: '%kernel.project_dir%/translations'
        providers:



================================================
FILE: config/packages/twig.yaml
================================================
twig:
    file_name_pattern: '*.twig'

when@test:
    twig:
        strict_variables: true



================================================
FILE: config/packages/twig_component.yaml
================================================
twig_component:
    anonymous_template_directory: 'components/'
    defaults:
        # Namespace & directory for components
        App\Twig\Components\: 'components/'



================================================
FILE: config/packages/ux_turbo.yaml
================================================
# Enable stateless CSRF protection for forms and logins/logouts
framework:
    csrf_protection:
        check_header: true



================================================
FILE: config/packages/validator.yaml
================================================
framework:
    validation:
        # Enables validator auto-mapping support.
        # For instance, basic validation constraints will be inferred from Doctrine's metadata.
        #auto_mapping:
        #    App\Entity\: []

when@test:
    framework:
        validation:
            not_compromised_password: false



================================================
FILE: config/packages/web_profiler.yaml
================================================
when@dev:
    web_profiler:
        toolbar: true

    framework:
        profiler:
            collect_serializer_data: true

when@test:
    framework:
        profiler:
            collect: false
            collect_serializer_data: true



================================================
FILE: config/routes/framework.yaml
================================================
when@dev:
    _errors:
        resource: '@FrameworkBundle/Resources/config/routing/errors.php'
        prefix: /_error



================================================
FILE: config/routes/security.yaml
================================================
_security_logout:
    resource: security.route_loader.logout
    type: service



================================================
FILE: config/routes/web_profiler.yaml
================================================
when@dev:
    web_profiler_wdt:
        resource: '@WebProfilerBundle/Resources/config/routing/wdt.php'
        prefix: /_wdt

    web_profiler_profiler:
        resource: '@WebProfilerBundle/Resources/config/routing/profiler.php'
        prefix: /_profiler



================================================
FILE: docker/nginx/default.conf
================================================
server {
    listen 80;
    server_name localhost;
    root /var/www/html/public;

    index index.php;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # AssetMapper - force through Symfony for dynamic asset generation
    #location /assets/ {
    #    try_files $uri /index.php$is_args$args;
    #}

    # Symfony rewrite rules
    location / {
        try_files $uri /index.php$is_args$args;
    }

    # PHP-FPM configuration
    location ~ ^/index\.php(/|$) {
        fastcgi_pass backend:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;

        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        fastcgi_param PATH_INFO $fastcgi_path_info;

        # Prevents URIs that include the front controller from being cached
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;

        internal;
    }

    # Return 404 for other PHP files
    location ~ \.php$ {
        return 404;
    }

    # Deny access to hidden files
    location ~ /\. {
        deny all;
    }

    # Assets, media
    #location ~* \.(jpg|jpeg|gif|png|css|js|ico|xml|svg|woff|woff2|ttf|eot)$ {
    #    expires 30d;
    #    access_log off;
    #    log_not_found off;
    #}

    error_log /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;
}



================================================
FILE: migrations/Version20251024000000.php
================================================
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Initial database schema migration for AI Flashcard Generator MVP
 *
 * Purpose: Creates complete database structure for flashcard application
 * Tables affected: users, sets, cards, review_states, review_events, ai_jobs, analytics_events
 * Special notes:
 *   - Enables Row Level Security (RLS) on all tables
 *   - Uses CITEXT for case-insensitive email/name fields
 *   - Implements soft delete pattern for sets and cards
 *   - Includes denormalization trigger for card_count in sets table
 */
final class Version20251024000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial database schema with users, flashcard sets, cards, review system, AI jobs tracking, and analytics';
    }

    public function up(Schema $schema): void
    {
        // ========================================
        // 1. ENABLE REQUIRED POSTGRESQL EXTENSIONS
        // ========================================

        // pgcrypto: provides gen_random_uuid() for UUID generation
        $this->addSql('create extension if not exists "pgcrypto"');

        // citext: case-insensitive text type for emails and names
        // enables unique constraints that ignore case without lower() functions
        $this->addSql('create extension if not exists "citext"');

        // pg_trgm: trigram-based text search (optional, for future search features)
        // enables fast LIKE queries and fuzzy matching on flashcard set names
        $this->addSql('create extension if not exists "pg_trgm"');

        // ========================================
        // 2. CREATE CUSTOM ENUM TYPES
        // ========================================

        // card_origin: tracks whether flashcard was created by AI or manually
        // used for analytics to measure AI adoption rate (target: 75%)
        $this->addSql("create type card_origin as enum ('ai', 'manual')");

        // ai_job_status: tracks lifecycle of AI generation requests
        // queued -> running -> succeeded/failed
        $this->addSql("create type ai_job_status as enum ('queued', 'running', 'succeeded', 'failed')");

        // ========================================
        // 3. CREATE HELPER FUNCTIONS FOR RLS
        // ========================================

        // current_app_user(): returns UUID of currently authenticated user
        // reads from PostgreSQL session variable set by application (Symfony)
        // usage: SET app.current_user_id = '<uuid>' at start of each request
        // this function is used in all RLS policies to enforce user ownership
        $this->addSql('
            create or replace function current_app_user() returns uuid language sql stable as $$
                select current_setting(\'app.current_user_id\', true)::uuid;
            $$
        ');

        // ========================================
        // 4. CREATE TABLES
        // ========================================

        // ----------------------------------------
        // 4.1. users table
        // ----------------------------------------
        // stores user authentication data
        // email is case-insensitive (citext) for user-friendly login
        // password_hash must be at least 60 chars (bcrypt/argon2 requirement)
        $this->addSql('
            create table users (
                id uuid primary key default gen_random_uuid(),
                email citext unique not null,
                password_hash text not null,
                created_at timestamptz not null default now(),
                last_login_at timestamptz null,

                -- ensure password hash is long enough for bcrypt/argon2
                constraint users_password_hash_length check (char_length(password_hash) >= 60)
            )
        ');

        // ----------------------------------------
        // 4.2. sets table (flashcard sets)
        // ----------------------------------------
        // groups flashcards into named collections owned by users
        // implements soft delete pattern (deleted_at column)
        // stores AI generation metadata for analytics
        // card_count is denormalized for fast listing (maintained by trigger)
        $this->addSql('
            create table sets (
                id uuid primary key default gen_random_uuid(),
                owner_id uuid not null,
                name citext not null,

                -- denormalized count for performance (updated by trigger)
                -- only counts active (non-deleted) cards
                card_count int not null default 0,

                -- ai generation metadata (null if created manually)
                generated_at timestamptz null,
                generated_model text null,
                generated_tokens_in int null,
                generated_tokens_out int null,

                -- soft delete and audit timestamps
                deleted_at timestamptz null,
                created_at timestamptz not null default now(),
                updated_at timestamptz not null default now(),

                -- foreign key to user who owns this set
                -- cascade delete: when user is deleted, all their sets are deleted
                constraint sets_owner_fk foreign key (owner_id)
                    references users(id) on delete cascade,

                -- set name must be unique per owner (case-insensitive via citext)
                -- allows different users to have sets with same name
                constraint sets_owner_name_unique unique (owner_id, name),

                -- set name cannot be empty string
                constraint sets_name_not_empty check (name <> \'\')
            )
        ');

        // ----------------------------------------
        // 4.3. cards table (flashcards)
        // ----------------------------------------
        // individual flashcards belonging to sets
        // front = question, back = answer (both max 1000 chars)
        // tracks origin (ai vs manual) for analytics
        // edited_by_user_at tracks if AI-generated card was modified (for quality metrics)
        // implements soft delete pattern
        $this->addSql('
            create table cards (
                id uuid primary key default gen_random_uuid(),
                set_id uuid not null,
                origin card_origin not null,
                front text not null,
                back text not null,

                -- timestamp when user manually edited this card (null if never edited)
                -- used to track if AI-generated cards needed corrections
                edited_by_user_at timestamptz null,

                -- soft delete and audit timestamps
                deleted_at timestamptz null,
                created_at timestamptz not null default now(),
                updated_at timestamptz not null default now(),

                -- foreign key to parent set
                -- cascade delete: when set is deleted, all its cards are deleted
                constraint cards_set_fk foreign key (set_id)
                    references sets(id) on delete cascade,

                -- card text length limits (student-friendly, concise flashcards)
                constraint cards_front_length check (char_length(front) <= 1000),
                constraint cards_back_length check (char_length(back) <= 1000)
            )
        ');

        // ----------------------------------------
        // 4.4. review_states table (spaced repetition state)
        // ----------------------------------------
        // stores spaced repetition algorithm state per user per card
        // composite primary key (user_id, card_id)
        // due_at: when card should be shown next
        // ease, interval_days, reps: spaced repetition algorithm parameters
        // last_grade: 0 = "Don't Know", 1 = "Know"
        $this->addSql('
            create table review_states (
                user_id uuid not null,
                card_id uuid not null,

                -- when this card is next due for review
                due_at timestamptz not null,

                -- spaced repetition algorithm parameters
                -- ease: difficulty factor (higher = easier, intervals grow faster)
                ease numeric(4,2) not null default 2.50,

                -- current interval in days between reviews
                interval_days int not null default 0,

                -- number of times this card has been reviewed
                reps int not null default 0,

                -- last user response: 0 = "Nie wiem" (don\'t know), 1 = "Wiem" (know)
                last_grade smallint null,

                -- last update timestamp for this review state
                updated_at timestamptz not null default now(),

                -- composite primary key: one review state per user per card
                primary key (user_id, card_id),

                -- foreign key to user
                -- cascade delete: when user deleted, their review progress is deleted
                constraint review_states_user_fk foreign key (user_id)
                    references users(id) on delete cascade,

                -- foreign key to card
                -- restrict delete: cannot delete card if it has review state
                -- protects against accidental data loss of learning progress
                constraint review_states_card_fk foreign key (card_id)
                    references cards(id) on delete restrict
            )
        ');

        // ----------------------------------------
        // 4.5. review_events table (review history log)
        // ----------------------------------------
        // immutable log of all review attempts for analytics
        // records every time user reviews a card with their response
        // duration_ms optional: how long user took to answer
        $this->addSql('
            create table review_events (
                id bigserial primary key,
                user_id uuid not null,

                -- nullable: card might be deleted later but we keep event history
                card_id uuid null,

                answered_at timestamptz not null default now(),

                -- user response: 0 = "Nie wiem", 1 = "Wiem"
                grade smallint not null,

                -- optional: response time in milliseconds (for future analytics)
                duration_ms int null,

                -- foreign key to user
                -- cascade delete: when user deleted, their review history is deleted
                constraint review_events_user_fk foreign key (user_id)
                    references users(id) on delete cascade,

                -- foreign key to card (nullable)
                -- set null on delete: keep event history even if card is deleted
                constraint review_events_card_fk foreign key (card_id)
                    references cards(id) on delete set null,

                -- grade must be 0 or 1 (binary know/don\'t know)
                constraint review_events_grade_valid check (grade in (0, 1))
            )
        ');

        // ----------------------------------------
        // 4.6. ai_jobs table (AI generation job tracking)
        // ----------------------------------------
        // tracks AI flashcard generation requests and responses
        // stores request/response for debugging and analytics
        // status lifecycle: queued -> running -> succeeded/failed
        $this->addSql('
            create table ai_jobs (
                id uuid primary key default gen_random_uuid(),
                user_id uuid not null,

                -- nullable: set_id might be null if job failed before set creation
                set_id uuid null,

                status ai_job_status not null,

                -- error message if status = failed
                error_message text null,

                -- user\'s input text (1000-10000 chars per PRD requirement)
                request_prompt text null,

                -- raw JSON response from AI API (OpenRouter.ai)
                response_raw jsonb null,

                -- AI model used (e.g. "gpt-4", "claude-3")
                model_name text null,

                -- token usage for cost tracking
                tokens_in int null,
                tokens_out int null,

                created_at timestamptz not null default now(),
                updated_at timestamptz not null default now(),
                completed_at timestamptz null,

                -- foreign key to user who requested generation
                -- cascade delete: when user deleted, their job history is deleted
                constraint ai_jobs_user_fk foreign key (user_id)
                    references users(id) on delete cascade,

                -- foreign key to resulting set (nullable)
                -- set null on delete: keep job history even if set is deleted
                constraint ai_jobs_set_fk foreign key (set_id)
                    references sets(id) on delete set null,

                -- validate request prompt length matches PRD requirements
                constraint ai_jobs_prompt_length check (
                    request_prompt is null or
                    char_length(request_prompt) between 1000 and 10000
                )
            )
        ');

        // ----------------------------------------
        // 4.7. analytics_events table (event tracking)
        // ----------------------------------------
        // generic event log for business metrics and user behavior analytics
        // event_type examples: "fiszka_usunięta_w_edycji", "set_created", etc.
        // payload: flexible JSONB for event-specific data
        $this->addSql('
            create table analytics_events (
                id bigserial primary key,
                event_type text not null,
                user_id uuid not null,

                -- optional context: which set/card the event relates to
                set_id uuid null,
                card_id uuid null,

                -- flexible event data as JSON object
                payload jsonb not null default \'{}\'::jsonb,

                occurred_at timestamptz not null default now(),

                -- foreign key to user
                -- cascade delete: when user deleted, their analytics are deleted
                constraint analytics_events_user_fk foreign key (user_id)
                    references users(id) on delete cascade,

                -- foreign key to set (nullable)
                -- set null on delete: keep event history even if set deleted
                constraint analytics_events_set_fk foreign key (set_id)
                    references sets(id) on delete set null,

                -- foreign key to card (nullable)
                -- set null on delete: keep event history even if card deleted
                constraint analytics_events_card_fk foreign key (card_id)
                    references cards(id) on delete set null,

                -- ensure payload is always a JSON object, not array or primitive
                constraint analytics_events_payload_is_object check (
                    jsonb_typeof(payload) = \'object\'
                )
            )
        ');

        // ========================================
        // 5. CREATE INDEXES FOR PERFORMANCE
        // ========================================

        // ----------------------------------------
        // 5.1. users indexes
        // ----------------------------------------
        // unique index on email for login lookups (already created via unique constraint)
        $this->addSql('create unique index users_email_unique on users (email)');

        // ----------------------------------------
        // 5.2. sets indexes
        // ----------------------------------------
        // composite index for "My Sets" listing with soft delete filter
        // supports query: where owner_id = ? and deleted_at is null order by updated_at desc
        $this->addSql('create index sets_owner_listing on sets (owner_id, deleted_at)');

        // partial index for ordering by most recently updated (excludes deleted)
        // optimizes "My Sets" page with recency sorting
        $this->addSql('
            create index sets_owner_updated_at
            on sets (owner_id, updated_at desc)
            where deleted_at is null
        ');

        // trigram gin index for fast fuzzy search by set name (optional, requires pg_trgm)
        // enables fast LIKE queries: where name like \'%search%\'
        $this->addSql('
            create index sets_name_trgm
            on sets using gin (name gin_trgm_ops)
            where deleted_at is null
        ');

        // ----------------------------------------
        // 5.3. cards indexes
        // ----------------------------------------
        // index for listing active cards in a set (excludes soft-deleted)
        // supports query: where set_id = ? and deleted_at is null
        $this->addSql('
            create index cards_set_active
            on cards (set_id)
            where deleted_at is null
        ');

        // index for ordering cards by most recently updated within set
        // supports card editing interface with recency sorting
        $this->addSql('
            create index cards_set_updated
            on cards (set_id, updated_at desc)
            where deleted_at is null
        ');

        // ----------------------------------------
        // 5.4. review_states indexes
        // ----------------------------------------
        // composite index for finding next card due for review
        // critical for learning session query: where user_id = ? and due_at <= now() order by due_at
        $this->addSql('create index review_states_due on review_states (user_id, due_at)');

        // ----------------------------------------
        // 5.5. review_events indexes
        // ----------------------------------------
        // index for user review history ordered by time (for analytics dashboard)
        // supports query: where user_id = ? order by answered_at desc
        $this->addSql('create index review_events_user_time on review_events (user_id, answered_at desc)');

        // index for per-card review history (for card difficulty analytics)
        // supports query: where card_id = ? order by answered_at
        $this->addSql('create index review_events_card_time on review_events (card_id, answered_at)');

        // ----------------------------------------
        // 5.6. ai_jobs indexes
        // ----------------------------------------
        // index for user\'s AI job history ordered by time
        // supports "My AI Generations" page
        $this->addSql('create index ai_jobs_user_time on ai_jobs (user_id, created_at desc)');

        // index for background job processing queue
        // supports query: where status = \'queued\' order by created_at
        $this->addSql('create index ai_jobs_status_time on ai_jobs (status, created_at)');

        // index for finding jobs related to a set
        $this->addSql('create index ai_jobs_set on ai_jobs (set_id)');

        // gin index for searching within JSON responses (optional, for debugging)
        // enables queries like: where response_raw @> \'{"key": "value"}\'
        $this->addSql('create index ai_jobs_response_gin on ai_jobs using gin (response_raw)');

        // ----------------------------------------
        // 5.7. analytics_events indexes
        // ----------------------------------------
        // index for user analytics history ordered by time
        // supports analytics dashboard queries
        $this->addSql('create index analytics_user_time on analytics_events (user_id, occurred_at desc)');

        // ========================================
        // 6. ENABLE ROW LEVEL SECURITY (RLS)
        // ========================================

        // enable RLS on all tables to enforce multi-tenant data isolation
        // users can only access their own data through RLS policies
        // application must set app.current_user_id session variable

        $this->addSql('alter table users enable row level security');
        $this->addSql('alter table sets enable row level security');
        $this->addSql('alter table cards enable row level security');
        $this->addSql('alter table review_states enable row level security');
        $this->addSql('alter table review_events enable row level security');
        $this->addSql('alter table ai_jobs enable row level security');
        $this->addSql('alter table analytics_events enable row level security');

        // ========================================
        // 7. CREATE RLS POLICIES
        // ========================================
        // policies are granular: separate policy for each operation (select/insert/update/delete)
        // this allows fine-grained control and easier auditing

        // ----------------------------------------
        // 7.1. users table policies
        // ----------------------------------------

        // users_select: users can only select their own user record
        // used for profile page, account settings
        $this->addSql('
            create policy users_select on users
                for select
                using (id = current_app_user())
        ');

        // users_update: users can only update their own user record
        // used for profile updates, password changes
        $this->addSql('
            create policy users_update on users
                for update
                using (id = current_app_user())
                with check (id = current_app_user())
        ');

        // ----------------------------------------
        // 7.2. sets table policies
        // ----------------------------------------

        // sets_select: users can only select their own sets that are not soft-deleted
        // enforces ownership and filters soft-deleted sets
        $this->addSql('
            create policy sets_select on sets
                for select
                using (owner_id = current_app_user() and deleted_at is null)
        ');

        // sets_insert: users can only insert sets where they are the owner
        // prevents users from creating sets for other users
        $this->addSql('
            create policy sets_insert on sets
                for insert
                with check (owner_id = current_app_user())
        ');

        // sets_update: users can only update their own non-deleted sets
        // cannot change ownership to another user
        $this->addSql('
            create policy sets_update on sets
                for update
                using (owner_id = current_app_user() and deleted_at is null)
                with check (owner_id = current_app_user())
        ');

        // sets_delete: users can only delete their own sets
        // note: this is hard delete; soft delete uses update
        $this->addSql('
            create policy sets_delete on sets
                for delete
                using (owner_id = current_app_user())
        ');

        // ----------------------------------------
        // 7.3. cards table policies
        // ----------------------------------------

        // cards_select: users can select cards belonging to their own non-deleted sets
        // checks both card and parent set are not soft-deleted
        // joins to sets table to verify ownership
        $this->addSql('
            create policy cards_select on cards
                for select
                using (
                    deleted_at is null and
                    exists (
                        select 1 from sets s
                        where s.id = cards.set_id
                          and s.owner_id = current_app_user()
                          and s.deleted_at is null
                    )
                )
        ');

        // cards_insert: users can only insert cards into their own non-deleted sets
        // prevents users from adding cards to other users\' sets
        $this->addSql('
            create policy cards_insert on cards
                for insert
                with check (
                    exists (
                        select 1 from sets s
                        where s.id = cards.set_id
                          and s.owner_id = current_app_user()
                          and s.deleted_at is null
                    )
                )
        ');

        // cards_update: users can only update cards in their own non-deleted sets
        // cannot move cards between sets (set_id is checked)
        $this->addSql('
            create policy cards_update on cards
                for update
                using (
                    deleted_at is null and
                    exists (
                        select 1 from sets s
                        where s.id = cards.set_id
                          and s.owner_id = current_app_user()
                          and s.deleted_at is null
                    )
                )
                with check (
                    exists (
                        select 1 from sets s
                        where s.id = cards.set_id
                          and s.owner_id = current_app_user()
                          and s.deleted_at is null
                    )
                )
        ');

        // cards_delete: users can only delete cards from their own sets
        // note: this is hard delete; soft delete uses update
        $this->addSql('
            create policy cards_delete on cards
                for delete
                using (
                    exists (
                        select 1 from sets s
                        where s.id = cards.set_id
                          and s.owner_id = current_app_user()
                    )
                )
        ');

        // ----------------------------------------
        // 7.4. review_states table policies
        // ----------------------------------------

        // review_states_select: users can only select their own review states
        // used in learning session to fetch next card due for review
        $this->addSql('
            create policy review_states_select on review_states
                for select
                using (user_id = current_app_user())
        ');

        // review_states_insert: users can only create review states for themselves
        // prevents users from manipulating other users\' learning progress
        $this->addSql('
            create policy review_states_insert on review_states
                for insert
                with check (user_id = current_app_user())
        ');

        // review_states_update: users can only update their own review states
        // ensures users cannot modify others\' learning progress
        $this->addSql('
            create policy review_states_update on review_states
                for update
                using (user_id = current_app_user())
                with check (user_id = current_app_user())
        ');

        // review_states_delete: users can only delete their own review states
        // allows users to reset their learning progress for specific cards
        $this->addSql('
            create policy review_states_delete on review_states
                for delete
                using (user_id = current_app_user())
        ');

        // ----------------------------------------
        // 7.5. review_events table policies
        // ----------------------------------------

        // review_events_select: users can only select their own review events
        // used for personal learning analytics and progress tracking
        $this->addSql('
            create policy review_events_select on review_events
                for select
                using (user_id = current_app_user())
        ');

        // review_events_insert: users can only create review events for themselves
        // logs each review attempt with user_id from session
        $this->addSql('
            create policy review_events_insert on review_events
                for insert
                with check (user_id = current_app_user())
        ');

        // ----------------------------------------
        // 7.6. ai_jobs table policies
        // ----------------------------------------

        // ai_jobs_select: users can only select their own AI generation jobs
        // used for "My Generations" history page
        $this->addSql('
            create policy ai_jobs_select on ai_jobs
                for select
                using (user_id = current_app_user())
        ');

        // ai_jobs_insert: users can only create AI jobs for themselves
        // prevents users from triggering jobs for other users (cost/quota abuse)
        $this->addSql('
            create policy ai_jobs_insert on ai_jobs
                for insert
                with check (user_id = current_app_user())
        ');

        // ai_jobs_update: users can only update their own AI jobs
        // background worker updates job status using app user context
        $this->addSql('
            create policy ai_jobs_update on ai_jobs
                for update
                using (user_id = current_app_user())
                with check (user_id = current_app_user())
        ');

        // ----------------------------------------
        // 7.7. analytics_events table policies
        // ----------------------------------------

        // analytics_events_select: users can only select their own analytics events
        // used for personal analytics dashboard
        $this->addSql('
            create policy analytics_events_select on analytics_events
                for select
                using (user_id = current_app_user())
        ');

        // analytics_events_insert: users can only create analytics events for themselves
        // application tracks events with user_id from session
        $this->addSql('
            create policy analytics_events_insert on analytics_events
                for insert
                with check (user_id = current_app_user())
        ');

        // ========================================
        // 8. CREATE TRIGGERS FOR DENORMALIZATION
        // ========================================

        // trigger function to maintain card_count in sets table
        // increments on card insert, decrements on soft delete (deleted_at set)
        // only counts active (non-deleted) cards

        $this->addSql('
            create or replace function maintain_set_card_count() returns trigger as $$
            begin
                -- handle INSERT: increment card_count if card is not deleted
                if (tg_op = \'INSERT\') then
                    if new.deleted_at is null then
                        update sets
                        set card_count = card_count + 1,
                            updated_at = now()
                        where id = new.set_id;
                    end if;
                    return new;
                end if;

                -- handle UPDATE: adjust card_count if deleted_at changes
                if (tg_op = \'UPDATE\') then
                    -- card was soft-deleted (active -> deleted)
                    if old.deleted_at is null and new.deleted_at is not null then
                        update sets
                        set card_count = card_count - 1,
                            updated_at = now()
                        where id = new.set_id;
                    end if;

                    -- card was restored (deleted -> active)
                    if old.deleted_at is not null and new.deleted_at is null then
                        update sets
                        set card_count = card_count + 1,
                            updated_at = now()
                        where id = new.set_id;
                    end if;

                    return new;
                end if;

                -- handle DELETE: decrement card_count if card was active
                -- WARNING: this is for hard delete, soft delete uses UPDATE above
                if (tg_op = \'DELETE\') then
                    if old.deleted_at is null then
                        update sets
                        set card_count = card_count - 1,
                            updated_at = now()
                        where id = old.set_id;
                    end if;
                    return old;
                end if;

                return null;
            end;
            $$ language plpgsql
        ');

        // attach trigger to cards table for all relevant operations
        $this->addSql('
            create trigger maintain_set_card_count_trigger
            after insert or update or delete on cards
            for each row
            execute function maintain_set_card_count()
        ');
    }

    public function down(Schema $schema): void
    {
        // ========================================
        // ROLLBACK: DROP ALL OBJECTS IN REVERSE ORDER
        // ========================================
        // WARNING: this will destroy all data in the database
        // only use in development or if absolutely necessary

        // drop triggers first
        $this->addSql('drop trigger if exists maintain_set_card_count_trigger on cards');
        $this->addSql('drop function if exists maintain_set_card_count()');

        // drop tables (foreign keys will cascade)
        $this->addSql('drop table if exists analytics_events cascade');
        $this->addSql('drop table if exists ai_jobs cascade');
        $this->addSql('drop table if exists review_events cascade');
        $this->addSql('drop table if exists review_states cascade');
        $this->addSql('drop table if exists cards cascade');
        $this->addSql('drop table if exists sets cascade');
        $this->addSql('drop table if exists users cascade');

        // drop helper function
        $this->addSql('drop function if exists current_app_user()');

        // drop custom types
        $this->addSql('drop type if exists ai_job_status');
        $this->addSql('drop type if exists card_origin');

        // drop extensions (only if no other database objects use them)
        // commented out to avoid breaking other schemas that might use these
        // $this->addSql('drop extension if exists pg_trgm');
        // $this->addSql('drop extension if exists citext');
        // $this->addSql('drop extension if exists pgcrypto');
    }
}


================================================
FILE: migrations/Version20251028193900.php
================================================
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add new fields to ai_jobs table for preview functionality
 *
 * Purpose: Extends ai_jobs to store preview cards and statistics
 * Tables affected: ai_jobs
 * Changes:
 *   - Add cards JSONB column (stores preview cards before save)
 *   - Add generated_count INT (number of cards AI produced)
 *   - Add edited_count INT (number of cards user edited in preview)
 *   - Add deleted_count INT (number of cards user deleted in preview)
 *   - Add suggested_name TEXT (AI-suggested set name)
 */
final class Version20251028193900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add preview cards storage and statistics fields to ai_jobs table';
    }

    public function up(Schema $schema): void
    {
        // Add cards JSONB column for storing preview cards
        // Structure: [{ tmp_id: uuid, front: string, back: string, edited: bool, deleted: bool }]
        $this->addSql("
            ALTER TABLE ai_jobs
            ADD COLUMN cards JSONB NOT NULL DEFAULT '[]'::jsonb
        ");

        // Add generated_count: tracks how many cards AI produced (right after completion)
        // Includes CHECK constraint as per db-plan.md
        $this->addSql("
            ALTER TABLE ai_jobs
            ADD COLUMN generated_count INT NOT NULL DEFAULT 0
                CHECK (generated_count >= 0)
        ");

        // Add edited_count: tracks how many cards user edited in preview
        $this->addSql("
            ALTER TABLE ai_jobs
            ADD COLUMN edited_count INT NOT NULL DEFAULT 0
        ");

        // Add deleted_count: tracks how many cards user deleted in preview
        $this->addSql("
            ALTER TABLE ai_jobs
            ADD COLUMN deleted_count INT NOT NULL DEFAULT 0
        ");

        // Add suggested_name: AI-suggested name for the set
        $this->addSql("
            ALTER TABLE ai_jobs
            ADD COLUMN suggested_name TEXT NULL
        ");
    }

    public function down(Schema $schema): void
    {
        // Remove added columns in reverse order
        $this->addSql('ALTER TABLE ai_jobs DROP COLUMN IF EXISTS suggested_name');
        $this->addSql('ALTER TABLE ai_jobs DROP COLUMN IF EXISTS deleted_count');
        $this->addSql('ALTER TABLE ai_jobs DROP COLUMN IF EXISTS edited_count');
        $this->addSql('ALTER TABLE ai_jobs DROP COLUMN IF EXISTS generated_count');
        $this->addSql('ALTER TABLE ai_jobs DROP COLUMN IF EXISTS cards');
    }
}



================================================
FILE: migrations/Version20251102160547.php
================================================
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Refactor ai_jobs for synchronous generation (no async queue)
 *
 * Purpose: Remove server-side preview storage, simplify to KPI tracking only
 * Tables affected: ai_jobs
 * Changes:
 *   - Remove cards JSONB column (preview managed client-side)
 *   - Remove deleted_count (calculated as generated_count - accepted_count)
 *   - Rename edited_count to accepted_count (tracks cards saved by user)
 *   - Add accepted_count INT (number of cards user saved)
 *   - Update ai_job_status ENUM to only 'succeeded' and 'failed' (no queuing)
 *
 * Migration strategy for ENUM:
 *   - Delete all rows with status 'queued' or 'running' (stale async jobs)
 *   - Create new ENUM type with only 'succeeded' and 'failed'
 *   - Alter column to use new type
 *   - Drop old ENUM type
 */
final class Version20251102160547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refactor ai_jobs for synchronous generation - remove preview storage, update status ENUM';
    }

    public function up(Schema $schema): void
    {
        // 1. Clean up any queued/running jobs (from old async system)
        //    In production, verify there are no important running jobs first!
        $this->addSql("DELETE FROM ai_jobs WHERE status IN ('queued', 'running')");

        // 2. Create new ENUM type with only synchronous statuses
        $this->addSql("CREATE TYPE ai_job_status_new AS ENUM ('succeeded', 'failed')");

        // 3. Alter status column to use new type
        //    Cast existing values (succeeded/failed already exist in new type)
        $this->addSql("
            ALTER TABLE ai_jobs
            ALTER COLUMN status TYPE ai_job_status_new
            USING status::text::ai_job_status_new
        ");

        // 4. Drop old ENUM type and rename new one
        $this->addSql("DROP TYPE ai_job_status");
        $this->addSql("ALTER TYPE ai_job_status_new RENAME TO ai_job_status");

        // 5. Remove preview-related columns (client-side preview now)
        $this->addSql('ALTER TABLE ai_jobs DROP COLUMN IF EXISTS cards');

        // 6. Remove deleted_count (calculated as generated_count - accepted_count)
        $this->addSql('ALTER TABLE ai_jobs DROP COLUMN IF EXISTS deleted_count');

        // 7. Add accepted_count (replaces edited_count with different semantics)
        //    accepted_count = how many cards user saved (regardless of edits)
        $this->addSql("
            ALTER TABLE ai_jobs
            ADD COLUMN accepted_count INT NOT NULL DEFAULT 0
                CHECK (accepted_count >= 0)
        ");

        // 8. Keep edited_count but update its meaning
        //    edited_count = how many of the SAVED cards were edited before saving
        //    (filled when user calls POST /api/sets with edited flag)
        // Note: No migration of old data - old edited_count tracked preview edits,
        //       new edited_count tracks final saved edits (different concept)
    }

    public function down(Schema $schema): void
    {
        // Reverse order of operations

        // 1. Remove accepted_count
        $this->addSql('ALTER TABLE ai_jobs DROP COLUMN IF EXISTS accepted_count');

        // 2. Re-add deleted_count
        $this->addSql('ALTER TABLE ai_jobs ADD COLUMN deleted_count INT NOT NULL DEFAULT 0');

        // 3. Re-add cards JSONB column
        $this->addSql("ALTER TABLE ai_jobs ADD COLUMN cards JSONB NOT NULL DEFAULT '[]'::jsonb");

        // 4. Recreate old ENUM type with queue statuses
        $this->addSql("CREATE TYPE ai_job_status_new AS ENUM ('queued', 'running', 'succeeded', 'failed')");

        // 5. Alter status column back to old type
        $this->addSql("
            ALTER TABLE ai_jobs
            ALTER COLUMN status TYPE ai_job_status_new
            USING status::text::ai_job_status_new
        ");

        // 6. Drop current type and rename
        $this->addSql("DROP TYPE ai_job_status");
        $this->addSql("ALTER TYPE ai_job_status_new RENAME TO ai_job_status");
    }
}



================================================
FILE: migrations/Version20260104130000.php
================================================
<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Add email verification support to User entity
 *
 * Changes:
 * - Add is_verified column to users table (default: false)
 *
 * Part of user registration backend implementation (auth-spec.md)
 */
final class Version20260104130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add email verification support (is_verified column) to users table';
    }

    public function up(Schema $schema): void
    {
        // Add is_verified column (required for email verification)
        // Default: false - users must verify email before login
        $this->addSql('ALTER TABLE users ADD COLUMN IF NOT EXISTS is_verified BOOLEAN NOT NULL DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        // Remove is_verified column
        $this->addSql('ALTER TABLE users DROP COLUMN IF EXISTS is_verified');
    }
}



================================================
FILE: public/index.php
================================================
<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};



================================================
FILE: src/Kernel.php
================================================
<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}



================================================
FILE: src/Application/Command/CreateSetCardDto.php
================================================
<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Model\CardOrigin;
use App\Domain\Value\CardBack;
use App\Domain\Value\CardFront;

/**
 * Data Transfer Object representing a card to be created within a set.
 *
 * Used by CreateSetCommand to encapsulate card data with proper value objects.
 */
final readonly class CreateSetCardDto
{
    /**
     * @param CardFront $front Front side of the card (max 1000 chars)
     * @param CardBack $back Back side of the card (max 1000 chars)
     * @param CardOrigin $origin Source of the card (AI or MANUAL)
     * @param bool $wasEdited Whether the user edited this card before saving
     */
    public function __construct(
        public CardFront $front,
        public CardBack $back,
        public CardOrigin $origin,
        public bool $wasEdited,
    ) {
    }
}



================================================
FILE: src/Application/Command/CreateSetCommand.php
================================================
<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Value\AiJobId;
use App\Domain\Value\SetName;
use App\Domain\Value\UserId;

/**
 * Command to create a new flashcard set with optional cards.
 *
 * This command supports two use cases:
 * 1. Creating an empty set for manual card addition later
 * 2. Creating a set with AI-generated cards (or manually created cards from frontend)
 *
 * When job_id is provided, it links the created set to an AI generation job for KPI tracking.
 */
final readonly class CreateSetCommand
{
    /**
     * @param UserId $userId Owner of the set
     * @param SetName $name Name of the set (unique per user, case-insensitive)
     * @param CreateSetCardDto[] $cards Array of cards to create with the set
     * @param AiJobId|null $jobId Optional AI job ID for KPI linkage
     */
    public function __construct(
        public UserId $userId,
        public SetName $name,
        public array $cards,
        public ?AiJobId $jobId = null,
    ) {
    }
}



================================================
FILE: src/Application/Command/GenerateCardsCommand.php
================================================
<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Value\SourceText;
use App\Domain\Value\UserId;

/**
 * Command to generate flashcards from source text using AI.
 *
 * This is a synchronous command - the handler will:
 * 1. Call AI service to generate cards (blocking, max 30s)
 * 2. Create AiJob record with status SUCCEEDED or FAILED
 * 3. Return generated cards to the user immediately
 *
 * Flow:
 * - User provides source text (1000-10000 chars)
 * - AI generates flashcards synchronously
 * - Frontend receives cards and manages them locally
 * - User can edit/delete cards before saving via POST /api/sets
 */
final readonly class GenerateCardsCommand
{
    public function __construct(
        public SourceText $sourceText,
        public UserId $userId,
    ) {
    }
}



================================================
FILE: src/Application/Command/GenerateFlashcardsCommand.php
================================================
<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Value\UserId;

/**
 * Command to initiate AI-powered flashcard generation from source text.
 *
 * This command represents the user's intent to generate flashcards
 * using AI. It creates an AiJob record with status "queued" which
 * will be processed asynchronously.
 */
final readonly class GenerateFlashcardsCommand
{
    public function __construct(
        public UserId $userId,
        public string $sourceText,
    ) {}
}



================================================
FILE: src/Application/EventListener/FlashcardGenerationExceptionListener.php
================================================
<?php

declare(strict_types=1);

namespace App\Application\EventListener;

use App\Domain\Model\AnalyticsEvent;
use App\Domain\Model\User;
use App\Domain\Repository\AnalyticsEventRepositoryInterface;
use App\Domain\Value\UserId;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Tracks failed flashcard generation attempts in analytics.
 *
 * Listens to exceptions thrown during POST /generate endpoint execution
 * and records analytics events for monitoring and improving AI generation quality.
 *
 * This separates analytics concerns from the controller (thin controller principle).
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: -10)]
final readonly class FlashcardGenerationExceptionListener
{
    public function __construct(
        private AnalyticsEventRepositoryInterface $analyticsRepository,
        private Security $security,
        private LoggerInterface $logger,
    ) {}

    /**
     * Handle exception and track analytics if from /generate endpoint.
     */
    public function __invoke(ExceptionEvent $event): void
    {
        // Only track exceptions from main request
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $exception = $event->getThrowable();

        // Only track exceptions from POST /generate endpoint
        if ($request->getMethod() !== 'POST' || $request->getPathInfo() !== '/generate') {
            return;
        }

        // Only track for authenticated users
        $user = $this->security->getUser();
        if ($user === null) {
            return;
        }

        // Get user ID (support both Doctrine User and in-memory test user)
        $userId = $this->getUserId($user);

        // Don't track validation errors (422) - those are expected user errors
        // Only track unexpected errors (500-level)
        if ($exception instanceof \InvalidArgumentException) {
            return;
        }

        try {
            // Record analytics event for failed generation
            $analyticsEvent = AnalyticsEvent::create(
                eventType: 'ai_generate_failed',
                userId: $userId,
                payload: [
                    'error_code' => $exception->getCode(),
                    'error_type' => $exception::class,
                    'error_message' => $exception->getMessage(),
                    'request_uri' => $request->getRequestUri(),
                ],
                occurredAt: new DateTimeImmutable()
            );

            $this->analyticsRepository->save($analyticsEvent);

            $this->logger->info('Tracked ai_generate_failed analytics event', [
                'user_id' => $userId->toString(),
                'error_type' => $exception::class,
            ]);

        } catch (\Exception $analyticsError) {
            // Don't fail the request if analytics tracking fails
            // Just log the error
            $this->logger->error('Failed to track ai_generate_failed analytics event', [
                'error' => $analyticsError->getMessage(),
                'original_exception' => $exception::class,
            ]);
        }
    }

    /**
     * Get UserId from authenticated user.
     *
     * Supports both:
     * - Doctrine User entity (production) - has getId() method
     * - In-memory test user (development) - uses fixed UUID
     *
     * @param mixed $user
     * @return UserId
     */
    private function getUserId($user): UserId
    {
        // Production: Doctrine User entity
        if ($user instanceof User) {
            return $user->getId();
        }

        // Development: In-memory test user
        // Use fixed UUID for test user (test@example.com)
        // TODO: Replace with proper authentication when implemented
        return UserId::fromString('308a32a9-f215-4140-b89b-440e2cb42542');
    }
}



================================================
FILE: src/Application/Handler/CreateSetHandler.php
================================================
<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\CreateSetCommand;
use App\Domain\Event\SetCreatedEvent;
use App\Domain\Exception\AiJobNotFoundException;
use App\Domain\Exception\DuplicateSetNameException;
use App\Domain\Model\Card;
use App\Domain\Model\CardOrigin;
use App\Domain\Model\Set;
use App\Domain\Repository\AiJobRepositoryInterface;
use App\Domain\Repository\CardRepositoryInterface;
use App\Domain\Repository\SetRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Handler for creating a new flashcard set with optional cards.
 *
 * Flow:
 * 1. Validate set name uniqueness (per user, case-insensitive)
 * 2. Verify AI job exists and belongs to user (if job_id provided)
 * 3. Create Set entity
 * 4. Create Card entities with proper origin and edited tracking
 * 5. Calculate KPI metrics (accepted count, edited count)
 * 6. Link AI job to set (if job_id provided)
 * 7. Persist all changes in a single transaction
 * 8. Dispatch analytics event
 * 9. Return result
 */
final readonly class CreateSetHandler
{
    public function __construct(
        private SetRepositoryInterface $setRepository,
        private CardRepositoryInterface $cardRepository,
        private AiJobRepositoryInterface $aiJobRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @throws DuplicateSetNameException When set name already exists for this user
     * @throws AiJobNotFoundException When job_id is provided but job not found
     */
    public function __invoke(CreateSetCommand $command): CreateSetResult
    {
        // Step 1: Check for duplicate set name (case-insensitive, per user)
        $setNameString = $command->name->toString();
        if ($this->setRepository->existsByOwnerAndName($command->userId, $setNameString)) {
            throw DuplicateSetNameException::forName($setNameString);
        }

        // Step 2: Verify AI job exists and belongs to user (if provided)
        $aiJob = null;
        if ($command->jobId !== null) {
            $aiJob = $this->aiJobRepository->findById($command->jobId->toString());

            if ($aiJob === null) {
                throw AiJobNotFoundException::forId($command->jobId->toString());
            }

            // RLS automatically ensures job belongs to current user
            // If user doesn't own it, findById returns null due to RLS policy
        }

        $now = new \DateTimeImmutable();
        $setId = Uuid::v4()->toString();

        // Step 3: Create Set entity
        $set = Set::create(
            $setId,
            $command->userId,
            $command->name,
            $now
        );

        // Step 4: Create Card entities and calculate KPI metrics
        $cards = [];
        $aiAcceptedCount = 0;
        $aiEditedCount = 0;

        foreach ($command->cards as $cardDto) {
            $card = Card::create(
                Uuid::v4()->toString(),
                $setId,
                $cardDto->origin,
                $cardDto->front,
                $cardDto->back,
                $now,
                $cardDto->wasEdited
            );

            $cards[] = $card;

            // Track KPI metrics for AI-generated cards
            if ($cardDto->origin === CardOrigin::AI) {
                $aiAcceptedCount++;

                if ($cardDto->wasEdited) {
                    $aiEditedCount++;
                }
            }
        }

        // Step 5: Persist Set
        $this->setRepository->save($set);

        // Step 6: Persist all Cards in batch (single flush after all persists)
        if (count($cards) > 0) {
            $this->cardRepository->saveAll($cards);
        }

        // Step 7: Link AI job to set (if provided)
        if ($aiJob !== null) {
            $aiJob->linkToSet(
                Uuid::fromString($setId),
                $aiAcceptedCount,
                $aiEditedCount
            );

            $this->aiJobRepository->save($aiJob);
        }

        // Step 8: Dispatch analytics event
        $this->eventDispatcher->dispatch(
            new SetCreatedEvent(
                setId: $setId,
                userId: $command->userId->toString(),
                totalCardCount: count($cards),
                aiCardCount: $aiAcceptedCount,
                editedAiCardCount: $aiEditedCount,
                jobId: $command->jobId?->toString()
            )
        );

        // Step 9: Return result
        return new CreateSetResult(
            setId: $setId,
            name: $setNameString,
            cardCount: count($cards)
        );
    }
}



================================================
FILE: src/Application/Handler/CreateSetResult.php
================================================
<?php

declare(strict_types=1);

namespace App\Application\Handler;

/**
 * Result returned after successfully creating a flashcard set.
 *
 * Contains the essential information needed to respond to the client
 * and confirm the set creation.
 */
final readonly class CreateSetResult
{
    /**
     * @param string $setId UUID of the newly created set
     * @param string $name Name of the created set
     * @param int $cardCount Number of cards created with the set
     */
    public function __construct(
        public string $setId,
        public string $name,
        public int $cardCount,
    ) {
    }
}



================================================
FILE: src/Application/Handler/GenerateCardsHandler.php
================================================
<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\GenerateCardsCommand;
use App\Domain\Model\AiJob;
use App\Domain\Repository\AiJobRepositoryInterface;
use App\Domain\Service\AiCardGeneratorInterface;
use App\Domain\Value\AiJobId;
use App\Infrastructure\Integration\Ai\Exception\AiGenerationException;
use App\Infrastructure\Integration\Ai\Exception\AiTimeoutException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * Handler for generating flashcards using AI.
 *
 * Orchestrates the entire generation process:
 * 1. Call AI service to generate cards (synchronous, max 30s)
 * 2. Create AiJob record for KPI tracking
 * 3. Handle success and failure cases
 * 4. Return result with generated cards
 *
 * Transaction handling:
 * - AiJob is persisted regardless of success/failure
 * - On timeout/error: AiJob.status = FAILED, exception is re-thrown
 * - On success: AiJob.status = SUCCEEDED, cards returned
 */
final class GenerateCardsHandler
{
    public function __construct(
        private readonly AiCardGeneratorInterface $aiCardGenerator,
        private readonly AiJobRepositoryInterface $aiJobRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @throws AiTimeoutException When AI takes longer than 30s (re-thrown for HTTP 504)
     * @throws AiGenerationException When AI service fails (re-thrown for HTTP 500)
     */
    public function handle(GenerateCardsCommand $command): GenerateCardsHandlerResult
    {
        $startTime = microtime(true);
        $userId = Uuid::fromString($command->userId->toString());
        $sourceText = $command->sourceText->toString();

        $this->logger->info('Starting AI flashcard generation', [
            'user_id' => $command->userId->toString(),
            'text_length' => $command->sourceText->length(),
        ]);

        try {
            // Call AI service (blocking, max 30s)
            $result = $this->aiCardGenerator->generate($command->sourceText);

            // Create successful AiJob for KPI tracking
            $aiJob = AiJob::createSucceeded(
                userId: $userId,
                requestPrompt: $sourceText,
                generatedCount: $result->generatedCount(),
                suggestedName: $result->suggestedName->toString(),
                modelName: $result->modelName,
                tokensIn: $result->tokensIn,
                tokensOut: $result->tokensOut
            );

            $this->aiJobRepository->save($aiJob);

            $duration = (microtime(true) - $startTime) * 1000; // ms

            $this->logger->info('AI flashcard generation succeeded', [
                'user_id' => $command->userId->toString(),
                'job_id' => $aiJob->getId()->toString(),
                'generated_count' => $result->generatedCount(),
                'duration_ms' => round($duration, 2),
            ]);

            return new GenerateCardsHandlerResult(
                jobId: AiJobId::fromString($aiJob->getId()->toString()),
                suggestedName: $result->suggestedName,
                cards: $result->cards,
                generatedCount: $result->generatedCount()
            );
        } catch (AiTimeoutException $e) {
            // Timeout - create failed AiJob and re-throw for HTTP 504
            $this->handleFailure($userId, $sourceText, $e, 'timeout');
            throw $e;
        } catch (AiGenerationException $e) {
            // AI service error - create failed AiJob and re-throw for HTTP 500
            $this->handleFailure($userId, $sourceText, $e, 'generation_error');
            throw $e;
        } catch (\Throwable $e) {
            // Unexpected error - log and re-throw as AiGenerationException
            $this->handleFailure($userId, $sourceText, $e, 'unexpected_error');
            throw AiGenerationException::invalidResponse($e->getMessage());
        }
    }

    /**
     * Handle failure by creating failed AiJob and logging error
     */
    private function handleFailure(
        Uuid $userId,
        string $sourceText,
        \Throwable $exception,
        string $errorType
    ): void {
        $aiJob = AiJob::createFailed(
            userId: $userId,
            requestPrompt: $sourceText,
            errorMessage: $this->truncateErrorMessage($exception->getMessage())
        );

        $this->aiJobRepository->save($aiJob);

        $this->logger->error('AI flashcard generation failed', [
            'user_id' => $userId->toString(),
            'job_id' => $aiJob->getId()->toString(),
            'error_type' => $errorType,
            'error_message' => $exception->getMessage(),
            'exception_class' => get_class($exception),
        ]);
    }

    /**
     * Truncate error message to fit DB constraint (max 255 chars in error_message)
     */
    private function truncateErrorMessage(string $message): string
    {
        if (mb_strlen($message, 'UTF-8') <= 255) {
            return $message;
        }

        return mb_substr($message, 0, 252, 'UTF-8') . '...';
    }
}



================================================
FILE: src/Application/Handler/GenerateCardsHandlerResult.php
================================================
<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Domain\Value\AiJobId;
use App\Domain\Value\CardPreview;
use App\Domain\Value\SuggestedSetName;

/**
 * Result returned by GenerateCardsHandler.
 *
 * Contains all data needed for the API response:
 * - job_id: For linking KPI when user saves the set
 * - suggestedName: AI-suggested name for the flashcard set
 * - cards: Generated flashcard previews (not persisted yet)
 * - generatedCount: Number of cards generated
 */
final readonly class GenerateCardsHandlerResult
{
    /**
     * @param CardPreview[] $cards
     */
    public function __construct(
        public AiJobId $jobId,
        public SuggestedSetName $suggestedName,
        public array $cards,
        public int $generatedCount,
    ) {
    }
}



================================================
FILE: src/Application/Handler/GenerateFlashcardsHandler.php
================================================
<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Command\GenerateFlashcardsCommand;
use App\Domain\Model\AiJob;
use App\Domain\Model\AnalyticsEvent;
use App\Domain\Repository\AiJobRepositoryInterface;
use App\Domain\Repository\AnalyticsEventRepositoryInterface;
use DateTimeImmutable;
use Symfony\Component\Uid\Uuid;

/**
 * Handler for GenerateFlashcardsCommand.
 *
 * Creates an AiJob record with status "queued" and publishes
 * analytics event for tracking. The actual AI generation
 * happens asynchronously in a separate worker process.
 */
final readonly class GenerateFlashcardsHandler
{
    public function __construct(
        private AiJobRepositoryInterface $aiJobRepository,
        private AnalyticsEventRepositoryInterface $analyticsRepository,
    ) {}

    /**
     * Handle the command and return job ID.
     *
     * @return string UUID of the created AiJob
     * @throws \InvalidArgumentException if source text validation fails
     */
    public function handle(GenerateFlashcardsCommand $command): string
    {
        $now = new DateTimeImmutable();
        $jobId = Uuid::v4()->toString();

        // Create AiJob entity (validation happens in constructor)
        $aiJob = AiJob::create(
            id: $jobId,
            userId: $command->userId,
            requestPrompt: $command->sourceText,
            createdAt: $now
        );

        // Persist the job
        $this->aiJobRepository->save($aiJob);

        // Publish analytics event
        $analyticsEvent = AnalyticsEvent::create(
            eventType: 'ai_generate_started',
            userId: $command->userId,
            payload: [
                'job_id' => $jobId,
                'text_length' => mb_strlen($command->sourceText),
            ],
            occurredAt: $now
        );

        $this->analyticsRepository->save($analyticsEvent);

        return $jobId;
    }
}



================================================
FILE: src/Controller/AuthUITestController.php
================================================
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Temporary controller for testing authentication UI
 * DELETE THIS FILE after backend implementation
 */
#[Route('/test-auth')]
class AuthUITestController extends AbstractController
{
    #[Route('/login', name: 'test_auth_login')]
    public function login(): Response
    {
        return $this->render('security/login.html.twig', [
            'last_username' => 'test@example.com', // Optional: pre-fill email
        ]);
    }

    #[Route('/register', name: 'test_auth_register')]
    public function register(): Response
    {
        return $this->render('registration/register.html.twig');
    }

    #[Route('/reset-request', name: 'test_auth_reset_request')]
    public function resetRequest(): Response
    {
        return $this->render('reset_password/request.html.twig');
    }

    #[Route('/reset-form', name: 'test_auth_reset_form')]
    public function resetForm(): Response
    {
        return $this->render('reset_password/reset.html.twig');
    }
}



================================================
FILE: src/Controller/KitchenSinkController.php
================================================
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class KitchenSinkController extends AbstractController
{
    #[Route('/kitchen-sink', name: 'kitchen_sink')]
    public function index(): Response
    {
        // Sample data for components demonstration
        $navDestinations = [
            ['icon' => '#icon-home', 'label' => 'Strona główna', 'path' => '/', 'badge' => 0],
            ['icon' => '#icon-cards', 'label' => 'Fiszki', 'path' => '/sets', 'badge' => 12],
            ['icon' => '#icon-learn', 'label' => 'Nauka', 'path' => '/learn', 'badge' => 3],
            ['icon' => '#icon-profile', 'label' => 'Profil', 'path' => '/profile', 'badge' => 0],
        ];

        $sampleCards = [
            ['id' => 1, 'name' => 'Matematyka - Geometria', 'count' => 25, 'created' => '2 dni temu'],
            ['id' => 2, 'name' => 'Angielski A2', 'count' => 45, 'created' => '1 tydzień temu'],
            ['id' => 3, 'name' => 'Historia Polski', 'count' => 30, 'created' => '3 dni temu'],
        ];

        return $this->render('kitchen_sink/index.html.twig', [
            'navDestinations' => $navDestinations,
            'sampleCards' => $sampleCards,
            'currentPath' => '/kitchen-sink',
        ]);
    }
}



================================================
FILE: src/Controller/LuckyController.php
================================================
<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LuckyController  extends AbstractController
{
    #[Route('/lucky/number')]
    public function number(): Response
    {
        $number = random_int(0, 100);

        $number = random_int(0, 100);

        return $this->render('lucky/number.html.twig', [
            'number' => $number,
        ]);
    }
}



================================================
FILE: src/Controller/ScaffoldDemoController.php
================================================
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ScaffoldDemoController extends AbstractController
{
    #[Route('/scaffold-demo', name: 'scaffold_demo')]
    public function index(): Response
    {
        $navDestinations = [
            ['icon' => '#icon-home', 'label' => 'Strona główna', 'path' => '/', 'badge' => 0],
            ['icon' => '#icon-cards', 'label' => 'Fiszki', 'path' => '/sets', 'badge' => 12],
            ['icon' => '#icon-learn', 'label' => 'Nauka', 'path' => '/learn', 'badge' => 3],
            ['icon' => '#icon-profile', 'label' => 'Profil', 'path' => '/profile', 'badge' => 0],
        ];

        $flashcards = [];
        for ($i = 1; $i <= 12; $i++) {
            $flashcards[] = [
                'id' => $i,
                'name' => 'Zestaw fiszek #' . $i,
                'count' => rand(10, 50),
                'created' => rand(1, 30) . ' dni temu',
            ];
        }

        return $this->render('scaffold_demo/index.html.twig', [
            'navDestinations' => $navDestinations,
            'flashcards' => $flashcards,
            'currentPath' => '/scaffold-demo',
        ]);
    }
}



================================================
FILE: src/Domain/Event/SetCreatedEvent.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Event;

/**
 * Event dispatched when a new flashcard set is successfully created.
 *
 * Used for:
 * - Analytics tracking (set_created event)
 * - KPI metrics (AI card acceptance rates, edit rates)
 * - Audit logging
 */
final readonly class SetCreatedEvent
{
    /**
     * @param string $setId UUID of the newly created set
     * @param string $userId UUID of the user who created the set
     * @param int $totalCardCount Total number of cards created with the set
     * @param int $aiCardCount Number of AI-generated cards in the set
     * @param int $editedAiCardCount Number of AI cards that were edited before saving
     * @param string|null $jobId Optional AI job ID that generated the cards
     */
    public function __construct(
        public string $setId,
        public string $userId,
        public int $totalCardCount,
        public int $aiCardCount,
        public int $editedAiCardCount,
        public ?string $jobId = null,
    ) {
    }

    /**
     * Calculate the number of manual cards in the set
     */
    public function getManualCardCount(): int
    {
        return $this->totalCardCount - $this->aiCardCount;
    }

    /**
     * Calculate the percentage of AI cards that were edited
     * Returns 0.0 if no AI cards
     */
    public function getAiEditRate(): float
    {
        if ($this->aiCardCount === 0) {
            return 0.0;
        }

        return $this->editedAiCardCount / $this->aiCardCount;
    }
}



================================================
FILE: src/Domain/Exception/AiJobNotFoundException.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use DomainException;

/**
 * Thrown when an AI job ID is provided but the job doesn't exist
 * or doesn't belong to the current user (RLS prevents access).
 */
final class AiJobNotFoundException extends DomainException
{
    public static function forId(string $jobId): self
    {
        return new self(
            sprintf('AI job with ID "%s" not found or you do not have access to it.', $jobId)
        );
    }
}



================================================
FILE: src/Domain/Exception/DuplicateSetNameException.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use DomainException;

/**
 * Thrown when attempting to create a set with a name that already exists
 * for the same user (case-insensitive).
 */
final class DuplicateSetNameException extends DomainException
{
    public static function forName(string $name): self
    {
        return new self(
            sprintf('A set with the name "%s" already exists. Please choose a different name.', $name)
        );
    }
}



================================================
FILE: src/Domain/Model/AiJob.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Model;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

/**
 * AI Job - tracks AI flashcard generation for KPI metrics
 *
 * Purpose: Optional KPI tracking only. No server-side preview.
 * Flow:
 *   1. POST /api/generate creates AiJob with status=SUCCEEDED/FAILED
 *   2. Frontend manages card editing/deletion locally
 *   3. POST /api/sets updates AiJob with set_id, accepted_count, edited_count
 *
 * KPI Metrics:
 *   - Acceptance rate = accepted_count / generated_count (target: 75%)
 *   - Deleted count = generated_count - accepted_count
 *   - Edit rate = edited_count / accepted_count
 */
#[ORM\Entity]
#[ORM\Table(name: 'ai_jobs')]
#[ORM\Index(name: 'ai_jobs_user_time', columns: ['user_id', 'created_at'])]
#[ORM\Index(name: 'ai_jobs_status_time', columns: ['status', 'created_at'])]
#[ORM\Index(name: 'ai_jobs_set', columns: ['set_id'])]
class AiJob
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $userId;

    /**
     * Set ID - filled when user saves the set (POST /api/sets)
     * NULL until then
     */
    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $setId = null;

    #[ORM\Column(type: 'ai_job_status')]
    private AiJobStatus $status;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $requestPrompt = null;

    /**
     * How many cards AI generated
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $generatedCount = 0;

    /**
     * How many cards user saved (filled when POST /api/sets)
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $acceptedCount = 0;

    /**
     * How many saved cards were edited before saving (filled when POST /api/sets)
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $editedCount = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $suggestedName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $modelName = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $tokensIn = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $tokensOut = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    private function __construct()
    {
        $this->id = Uuid::v4();
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Create new successful AI generation job
     */
    public static function createSucceeded(
        Uuid $userId,
        string $requestPrompt,
        int $generatedCount,
        ?string $suggestedName,
        string $modelName,
        int $tokensIn,
        int $tokensOut
    ): self {
        $job = new self();
        $job->userId = $userId;
        $job->requestPrompt = $requestPrompt;
        $job->status = AiJobStatus::SUCCEEDED;
        $job->generatedCount = $generatedCount;
        $job->suggestedName = $suggestedName;
        $job->modelName = $modelName;
        $job->tokensIn = $tokensIn;
        $job->tokensOut = $tokensOut;
        $job->completedAt = new \DateTimeImmutable();

        return $job;
    }

    /**
     * Create new failed AI generation job
     */
    public static function createFailed(
        Uuid $userId,
        string $requestPrompt,
        string $errorMessage
    ): self {
        $job = new self();
        $job->userId = $userId;
        $job->requestPrompt = $requestPrompt;
        $job->status = AiJobStatus::FAILED;
        $job->errorMessage = $errorMessage;
        $job->completedAt = new \DateTimeImmutable();

        return $job;
    }

    // ===== Getters =====

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUserId(): Uuid
    {
        return $this->userId;
    }

    public function getSetId(): ?Uuid
    {
        return $this->setId;
    }

    public function getStatus(): AiJobStatus
    {
        return $this->status;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function getRequestPrompt(): ?string
    {
        return $this->requestPrompt;
    }

    public function getGeneratedCount(): int
    {
        return $this->generatedCount;
    }

    public function getAcceptedCount(): int
    {
        return $this->acceptedCount;
    }

    public function getEditedCount(): int
    {
        return $this->editedCount;
    }

    public function getDeletedCount(): int
    {
        return $this->generatedCount - $this->acceptedCount;
    }

    public function getSuggestedName(): ?string
    {
        return $this->suggestedName;
    }

    public function getModelName(): ?string
    {
        return $this->modelName;
    }

    public function getTokensIn(): ?int
    {
        return $this->tokensIn;
    }

    public function getTokensOut(): ?int
    {
        return $this->tokensOut;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function isSuccessful(): bool
    {
        return $this->status->isSuccessful();
    }

    public function isFailed(): bool
    {
        return $this->status->isFailed();
    }

    /**
     * Calculate acceptance rate (target: 75%)
     */
    public function getAcceptanceRate(): float
    {
        if ($this->generatedCount === 0) {
            return 0.0;
        }

        return $this->acceptedCount / $this->generatedCount;
    }

    // ===== Intentional Methods (Business Operations) =====

    /**
     * Link this job to a saved Set and record KPI metrics
     * Called when user saves cards via POST /api/sets
     *
     * @param Uuid $setId
     * @param int $acceptedCount Number of cards user saved
     * @param int $editedCount Number of saved cards that were edited
     */
    public function linkToSet(Uuid $setId, int $acceptedCount, int $editedCount): void
    {
        if ($this->setId !== null) {
            throw new \DomainException('Job already linked to a set');
        }

        if (!$this->isSuccessful()) {
            throw new \DomainException('Can only link successful jobs to sets');
        }

        if ($acceptedCount > $this->generatedCount) {
            throw new \DomainException('Cannot accept more cards than generated');
        }

        if ($editedCount > $acceptedCount) {
            throw new \DomainException('Cannot have more edited cards than accepted');
        }

        $this->setId = $setId;
        $this->acceptedCount = $acceptedCount;
        $this->editedCount = $editedCount;
        $this->updatedAt = new \DateTimeImmutable();
    }
}



================================================
FILE: src/Domain/Model/AiJobStatus.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Model;

/**
 * AI Job Status - synchronous generation (no queuing)
 *
 * Jobs complete immediately:
 * - SUCCEEDED: AI successfully generated cards
 * - FAILED: AI generation failed with error
 */
enum AiJobStatus: string
{
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';

    public function isSuccessful(): bool
    {
        return $this === self::SUCCEEDED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }
}



================================================
FILE: src/Domain/Model/AnalyticsEvent.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Value\UserId;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'analytics_events')]
#[ORM\Index(name: 'analytics_user_time', columns: ['user_id', 'occurred_at'])]
class AnalyticsEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'event_type', type: 'text')]
    private string $eventType;

    #[ORM\Column(name: 'user_id', type: 'guid')]
    private string $userId;

    #[ORM\Column(name: 'set_id', type: 'guid', nullable: true)]
    private ?string $setId = null;

    #[ORM\Column(name: 'card_id', type: 'guid', nullable: true)]
    private ?string $cardId = null;

    #[ORM\Column(type: 'json')]
    private array $payload;

    #[ORM\Column(name: 'occurred_at', type: 'datetime_immutable')]
    private DateTimeImmutable $occurredAt;

    private function __construct(
        string $eventType,
        UserId $userId,
        array $payload,
        DateTimeImmutable $occurredAt,
        ?string $setId = null,
        ?string $cardId = null
    ) {
        if (empty($eventType)) {
            throw new \InvalidArgumentException('Event type cannot be empty');
        }

        $this->eventType = $eventType;
        $this->userId = $userId->toString();
        $this->payload = $payload;
        $this->occurredAt = $occurredAt;
        $this->setId = $setId;
        $this->cardId = $cardId;
    }

    public static function create(
        string $eventType,
        UserId $userId,
        array $payload,
        DateTimeImmutable $occurredAt,
        ?string $setId = null,
        ?string $cardId = null
    ): self {
        return new self($eventType, $userId, $payload, $occurredAt, $setId, $cardId);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }

    public function getSetId(): ?string
    {
        return $this->setId;
    }

    public function getCardId(): ?string
    {
        return $this->cardId;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getOccurredAt(): DateTimeImmutable
    {
        return $this->occurredAt;
    }
}



================================================
FILE: src/Domain/Model/Card.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Value\CardBack;
use App\Domain\Value\CardFront;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'cards')]
#[ORM\Index(name: 'cards_set_active', columns: ['set_id', 'deleted_at'])]
#[ORM\Index(name: 'cards_set_updated', columns: ['set_id', 'updated_at'])]
class Card
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'set_id', type: 'guid')]
    private string $setId;

    #[ORM\Column(type: 'card_origin')]
    private CardOrigin $origin;

    #[ORM\Column(type: 'text')]
    private string $front;

    #[ORM\Column(type: 'text')]
    private string $back;

    #[ORM\Column(name: 'edited_by_user_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $editedByUserAt = null;

    #[ORM\Column(name: 'deleted_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        string $id,
        string $setId,
        CardOrigin $origin,
        CardFront $front,
        CardBack $back,
        DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->setId = $setId;
        $this->origin = $origin;
        $this->front = $front->toString();
        $this->back = $back->toString();
        $this->createdAt = $createdAt;
        $this->updatedAt = $createdAt;
    }

    public static function create(
        string $id,
        string $setId,
        CardOrigin $origin,
        CardFront $front,
        CardBack $back,
        DateTimeImmutable $createdAt,
        bool $wasEditedByUser = false
    ): self {
        $card = new self($id, $setId, $origin, $front, $back, $createdAt);

        if ($wasEditedByUser) {
            $card->editedByUserAt = $createdAt;
        }

        return $card;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getSetId(): string
    {
        return $this->setId;
    }

    public function getOrigin(): CardOrigin
    {
        return $this->origin;
    }

    public function getFront(): CardFront
    {
        return CardFront::fromString($this->front);
    }

    public function getBack(): CardBack
    {
        return CardBack::fromString($this->back);
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getEditedByUserAt(): ?DateTimeImmutable
    {
        return $this->editedByUserAt;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function wasEditedByUser(): bool
    {
        return $this->editedByUserAt !== null;
    }

    public function editFrontBack(
        CardFront $front,
        CardBack $back,
        DateTimeImmutable $editedAt
    ): void {
        $this->front = $front->toString();
        $this->back = $back->toString();
        $this->editedByUserAt = $editedAt;
        $this->updatedAt = $editedAt;
    }

    public function softDelete(DateTimeImmutable $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
        $this->updatedAt = $deletedAt;
    }
}



================================================
FILE: src/Domain/Model/CardOrigin.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Model;

enum CardOrigin: string
{
    case AI = 'ai';
    case MANUAL = 'manual';
}



================================================
FILE: src/Domain/Model/ReviewEvent.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Value\UserId;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'review_events')]
#[ORM\Index(name: 'review_events_user_time', columns: ['user_id', 'answered_at'])]
#[ORM\Index(name: 'review_events_card_time', columns: ['card_id', 'answered_at'])]
class ReviewEvent
{
    #[ORM\Id]
    #[ORM\Column(type: 'bigint')]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(name: 'user_id', type: 'guid')]
    private string $userId;

    #[ORM\Column(name: 'card_id', type: 'guid', nullable: true)]
    private ?string $cardId;

    #[ORM\Column(name: 'answered_at', type: 'datetime_immutable')]
    private DateTimeImmutable $answeredAt;

    #[ORM\Column(type: 'smallint')]
    private int $grade;

    #[ORM\Column(name: 'duration_ms', type: 'integer', nullable: true)]
    private ?int $durationMs = null;

    private function __construct(
        UserId $userId,
        ?string $cardId,
        DateTimeImmutable $answeredAt,
        int $grade,
        ?int $durationMs = null
    ) {
        if ($grade < 0 || $grade > 1) {
            throw new \InvalidArgumentException('Grade must be 0 (Don\'t know) or 1 (Know)');
        }

        $this->userId = $userId->toString();
        $this->cardId = $cardId;
        $this->answeredAt = $answeredAt;
        $this->grade = $grade;
        $this->durationMs = $durationMs;
    }

    public static function record(
        UserId $userId,
        string $cardId,
        DateTimeImmutable $answeredAt,
        int $grade,
        ?int $durationMs = null
    ): self {
        return new self($userId, $cardId, $answeredAt, $grade, $durationMs);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }

    public function getCardId(): ?string
    {
        return $this->cardId;
    }

    public function getAnsweredAt(): DateTimeImmutable
    {
        return $this->answeredAt;
    }

    public function getGrade(): int
    {
        return $this->grade;
    }

    public function getDurationMs(): ?int
    {
        return $this->durationMs;
    }

    public function wasCorrect(): bool
    {
        return $this->grade === 1;
    }
}



================================================
FILE: src/Domain/Model/ReviewState.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Value\UserId;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'review_states')]
#[ORM\Index(name: 'review_states_due', columns: ['user_id', 'due_at'])]
class ReviewState
{
    #[ORM\Id]
    #[ORM\Column(name: 'user_id', type: 'guid')]
    private string $userId;

    #[ORM\Id]
    #[ORM\Column(name: 'card_id', type: 'guid')]
    private string $cardId;

    #[ORM\Column(name: 'due_at', type: 'datetime_immutable')]
    private DateTimeImmutable $dueAt;

    #[ORM\Column(type: 'decimal', precision: 4, scale: 2)]
    private string $ease;

    #[ORM\Column(name: 'interval_days', type: 'integer')]
    private int $intervalDays;

    #[ORM\Column(type: 'integer')]
    private int $reps;

    #[ORM\Column(name: 'last_grade', type: 'smallint', nullable: true)]
    private ?int $lastGrade = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        UserId $userId,
        string $cardId,
        DateTimeImmutable $dueAt,
        float $ease = 2.50,
        int $intervalDays = 0,
        int $reps = 0
    ) {
        $this->userId = $userId->toString();
        $this->cardId = $cardId;
        $this->dueAt = $dueAt;
        $this->ease = number_format($ease, 2, '.', '');
        $this->intervalDays = $intervalDays;
        $this->reps = $reps;
        $this->updatedAt = $dueAt;
    }

    public static function initialize(
        UserId $userId,
        string $cardId,
        DateTimeImmutable $dueAt
    ): self {
        return new self($userId, $cardId, $dueAt);
    }

    public function getUserId(): UserId
    {
        return UserId::fromString($this->userId);
    }

    public function getCardId(): string
    {
        return $this->cardId;
    }

    public function getDueAt(): DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function getEase(): float
    {
        return (float) $this->ease;
    }

    public function getIntervalDays(): int
    {
        return $this->intervalDays;
    }

    public function getReps(): int
    {
        return $this->reps;
    }

    public function getLastGrade(): ?int
    {
        return $this->lastGrade;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function isDue(DateTimeImmutable $now): bool
    {
        return $this->dueAt <= $now;
    }

    /**
     * Update review state after answering
     *
     * @param int $grade 0 = "Don't know", 1 = "Know"
     */
    public function updateAfterReview(
        int $grade,
        DateTimeImmutable $nextDueAt,
        float $newEase,
        int $newIntervalDays,
        DateTimeImmutable $updatedAt
    ): void {
        if ($grade < 0 || $grade > 1) {
            throw new \InvalidArgumentException('Grade must be 0 or 1');
        }

        $this->lastGrade = $grade;
        $this->dueAt = $nextDueAt;
        $this->ease = number_format($newEase, 2, '.', '');
        $this->intervalDays = $newIntervalDays;
        $this->reps++;
        $this->updatedAt = $updatedAt;
    }
}



================================================
FILE: src/Domain/Model/Set.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Value\SetName;
use App\Domain\Value\UserId;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'sets')]
#[ORM\Index(name: 'sets_owner_listing', columns: ['owner_id', 'deleted_at'])]
#[ORM\Index(name: 'sets_owner_updated_at', columns: ['owner_id', 'updated_at'])]
#[ORM\UniqueConstraint(name: 'sets_owner_name_unique', columns: ['owner_id', 'name'])]
class Set
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(name: 'owner_id', type: 'guid')]
    private string $ownerId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'card_count', type: 'integer')]
    private int $cardCount = 0;

    #[ORM\Column(name: 'generated_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $generatedAt = null;

    #[ORM\Column(name: 'generated_model', type: 'text', nullable: true)]
    private ?string $generatedModel = null;

    #[ORM\Column(name: 'generated_tokens_in', type: 'integer', nullable: true)]
    private ?int $generatedTokensIn = null;

    #[ORM\Column(name: 'generated_tokens_out', type: 'integer', nullable: true)]
    private ?int $generatedTokensOut = null;

    #[ORM\Column(name: 'deleted_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $deletedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        string $id,
        UserId $ownerId,
        SetName $name,
        DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->ownerId = $ownerId->toString();
        $this->name = $name->toString();
        $this->createdAt = $createdAt;
        $this->updatedAt = $createdAt;
    }

    public static function create(
        string $id,
        UserId $ownerId,
        SetName $name,
        DateTimeImmutable $createdAt
    ): self {
        return new self($id, $ownerId, $name, $createdAt);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getOwnerId(): UserId
    {
        return UserId::fromString($this->ownerId);
    }

    public function getName(): SetName
    {
        return SetName::fromString($this->name);
    }

    public function getCardCount(): int
    {
        return $this->cardCount;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    public function renameTo(SetName $newName, DateTimeImmutable $updatedAt): void
    {
        $this->name = $newName->toString();
        $this->updatedAt = $updatedAt;
    }

    public function markAsGenerated(
        DateTimeImmutable $generatedAt,
        string $modelName,
        int $tokensIn,
        int $tokensOut
    ): void {
        $this->generatedAt = $generatedAt;
        $this->generatedModel = $modelName;
        $this->generatedTokensIn = $tokensIn;
        $this->generatedTokensOut = $tokensOut;
    }

    public function softDelete(DateTimeImmutable $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function incrementCardCount(): void
    {
        $this->cardCount++;
    }

    public function decrementCardCount(): void
    {
        if ($this->cardCount > 0) {
            $this->cardCount--;
        }
    }

    public function touch(DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}



================================================
FILE: src/Domain/Model/User.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Value\Email;
use App\Domain\Value\UserId;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\Index(name: 'users_email_unique', columns: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'guid')]
    private string $id;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    #[ORM\Column(name: 'password_hash', type: 'text')]
    private string $passwordHash;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'last_login_at', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $lastLoginAt = null;

    #[ORM\Column(name: 'is_verified', type: 'boolean')]
    private bool $isVerified = false;

    private function __construct(
        UserId $id,
        Email $email,
        string $passwordHash,
        DateTimeImmutable $createdAt,
        bool $isVerified = false
    ) {
        $this->id = $id->toString();
        $this->email = $email->toString();
        $this->passwordHash = $passwordHash;
        $this->createdAt = $createdAt;
        $this->isVerified = $isVerified;
    }

    public static function create(
        UserId $id,
        Email $email,
        string $passwordHash,
        DateTimeImmutable $createdAt,
        bool $isVerified = false
    ): self {
        if (strlen($passwordHash) < 60) {
            throw new \InvalidArgumentException('Password hash must be at least 60 characters');
        }

        return new self($id, $email, $passwordHash, $createdAt, $isVerified);
    }

    public function getId(): UserId
    {
        return UserId::fromString($this->id);
    }

    public function getEmail(): Email
    {
        return Email::fromString($this->email);
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastLoginAt(): ?DateTimeImmutable
    {
        return $this->lastLoginAt;
    }

    public function updateLastLogin(DateTimeImmutable $lastLoginAt): void
    {
        $this->lastLoginAt = $lastLoginAt;
    }

    // Symfony Security UserInterface implementation
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
        // Nothing to erase in this implementation
    }

    // PasswordAuthenticatedUserInterface implementation
    public function getPassword(): string
    {
        return $this->passwordHash;
    }

    // Email verification methods
    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function markAsVerified(): void
    {
        $this->isVerified = true;
    }
}



================================================
FILE: src/Domain/Repository/AiJobRepositoryInterface.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\AiJob;
use App\Domain\Model\AiJobStatus;
use App\Domain\Value\UserId;

interface AiJobRepositoryInterface
{
    public function findById(string $id): ?AiJob;

    /**
     * @return AiJob[]
     */
    public function findByUser(UserId $userId, int $limit = 50): array;

    /**
     * @return AiJob[]
     */
    public function findByStatus(AiJobStatus $status, int $limit = 100): array;

    public function save(AiJob $job): void;

    public function countFailedByUser(UserId $userId): int;
}



================================================
FILE: src/Domain/Repository/AnalyticsEventRepositoryInterface.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\AnalyticsEvent;
use App\Domain\Value\UserId;

interface AnalyticsEventRepositoryInterface
{
    public function save(AnalyticsEvent $event): void;

    /**
     * @return AnalyticsEvent[]
     */
    public function findByUser(UserId $userId, int $limit = 100): array;

    /**
     * @return AnalyticsEvent[]
     */
    public function findByEventType(string $eventType, int $limit = 100): array;
}



================================================
FILE: src/Domain/Repository/CardRepositoryInterface.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\Card;

interface CardRepositoryInterface
{
    public function findById(string $id): ?Card;

    /**
     * @return Card[]
     */
    public function findActiveBySetId(string $setId): array;

    public function save(Card $card): void;

    /**
     * Save multiple cards in a single transaction (batch persist + flush)
     *
     * @param Card[] $cards
     */
    public function saveAll(array $cards): void;

    public function softDelete(Card $card): void;

    public function countActiveBySetId(string $setId): int;
}



================================================
FILE: src/Domain/Repository/ReviewEventRepositoryInterface.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\ReviewEvent;
use App\Domain\Value\UserId;

interface ReviewEventRepositoryInterface
{
    public function save(ReviewEvent $event): void;

    /**
     * @return ReviewEvent[]
     */
    public function findRecentByUser(UserId $userId, int $limit = 100): array;

    public function countByUserAndCard(UserId $userId, string $cardId): int;
}



================================================
FILE: src/Domain/Repository/ReviewStateRepositoryInterface.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\ReviewState;
use App\Domain\Value\UserId;
use DateTimeImmutable;

interface ReviewStateRepositoryInterface
{
    public function findByUserAndCard(UserId $userId, string $cardId): ?ReviewState;

    /**
     * Find cards due for review (due_at <= now)
     *
     * @return ReviewState[]
     */
    public function findDueForUser(UserId $userId, DateTimeImmutable $now, int $limit = 20): array;

    public function save(ReviewState $state): void;

    public function countDueForUser(UserId $userId, DateTimeImmutable $now): int;
}



================================================
FILE: src/Domain/Repository/SetRepositoryInterface.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\Set;
use App\Domain\Value\UserId;

interface SetRepositoryInterface
{
    public function findById(string $id): ?Set;

    /**
     * @return Set[]
     */
    public function findOwnedBy(UserId $ownerId): array;

    /**
     * Find active (not soft-deleted) sets owned by user, ordered by updated_at DESC
     *
     * @return Set[]
     */
    public function findActiveOwnedBy(UserId $ownerId, int $limit = 100, int $offset = 0): array;

    public function save(Set $set): void;

    public function softDelete(Set $set): void;

    public function existsByOwnerAndName(UserId $ownerId, string $name): bool;
}



================================================
FILE: src/Domain/Repository/UserRepositoryInterface.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\Model\User;
use App\Domain\Value\Email;
use App\Domain\Value\UserId;

interface UserRepositoryInterface
{
    public function findById(UserId $id): ?User;

    public function findByEmail(Email $email): ?User;

    public function save(User $user): void;

    public function exists(Email $email): bool;
}



================================================
FILE: src/Domain/Service/AiCardGeneratorInterface.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Value\SourceText;

/**
 * Domain interface for AI-powered flashcard generation.
 *
 * Implementation should:
 * - Call external AI service (e.g., OpenRouter.ai)
 * - Parse AI response into domain objects
 * - Handle timeouts and errors from AI service
 * - Return structured result with cards and metadata
 *
 * This is a Domain interface - implementation lives in Infrastructure layer.
 */
interface AiCardGeneratorInterface
{
    /**
     * Generate flashcards from source text using AI.
     *
     * @param SourceText $sourceText Text to generate flashcards from (1000-10000 chars)
     * @return GenerateCardsResult Contains generated cards, suggested name, and metadata
     *
     * @throws \App\Infrastructure\Integration\Ai\Exception\AiTimeoutException
     *         When AI service takes longer than 30 seconds to respond
     * @throws \App\Infrastructure\Integration\Ai\Exception\AiGenerationException
     *         When AI service returns an error or invalid response
     */
    public function generate(SourceText $sourceText): GenerateCardsResult;
}



================================================
FILE: src/Domain/Service/GenerateCardsResult.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Value\CardPreview;
use App\Domain\Value\SuggestedSetName;

/**
 * Result DTO returned by AI card generator.
 *
 * Contains all data returned from the AI service:
 * - Generated flashcards (previews, not persisted yet)
 * - Suggested name for the flashcard set
 * - Metadata about AI model and token usage
 */
final readonly class GenerateCardsResult
{
    /**
     * @param CardPreview[] $cards
     */
    public function __construct(
        public array $cards,
        public SuggestedSetName $suggestedName,
        public string $modelName,
        public int $tokensIn,
        public int $tokensOut
    ) {
    }

    public function generatedCount(): int
    {
        return count($this->cards);
    }
}



================================================
FILE: src/Domain/Value/AiJobId.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Value;

use InvalidArgumentException;

/**
 * Represents a unique identifier for an AI job.
 *
 * Used for tracking KPI metrics for AI flashcard generation.
 */
final readonly class AiJobId
{
    private function __construct(
        public string $value
    ) {
        if (!$this->isValidUuid($value)) {
            throw new InvalidArgumentException('Invalid UUID format for AiJobId');
        }
    }

    public static function fromString(string $uuid): self
    {
        return new self($uuid);
    }

    public static function generate(): self
    {
        // Generate UUID v4
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant

        return new self(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private function isValidUuid(string $uuid): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }
}



================================================
FILE: src/Domain/Value/CardBack.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Value;

use InvalidArgumentException;

final readonly class CardBack
{
    private function __construct(
        public string $value
    ) {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Card back cannot be empty');
        }

        if (mb_strlen($value) > 1000) {
            throw new InvalidArgumentException('Card back cannot exceed 1000 characters');
        }
    }

    public static function fromString(string $back): self
    {
        return new self($back);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}



================================================
FILE: src/Domain/Value/CardFront.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Value;

use InvalidArgumentException;

final readonly class CardFront
{
    private function __construct(
        public string $value
    ) {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Card front cannot be empty');
        }

        if (mb_strlen($value) > 1000) {
            throw new InvalidArgumentException('Card front cannot exceed 1000 characters');
        }
    }

    public static function fromString(string $front): self
    {
        return new self($front);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}



================================================
FILE: src/Domain/Value/CardPreview.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Value;

use InvalidArgumentException;

/**
 * Represents a preview of a flashcard generated by AI.
 *
 * This is NOT persisted to database - it's only used for API responses
 * before the user saves the flashcard set.
 *
 * Enforces business rules:
 * - Front and back cannot be empty
 * - Maximum 1000 characters each (mirrors DB constraint)
 */
final readonly class CardPreview
{
    private const MAX_LENGTH = 1000;

    private function __construct(
        public string $front,
        public string $back
    ) {
        $this->validateSide('front', $this->front);
        $this->validateSide('back', $this->back);
    }

    public static function create(string $front, string $back): self
    {
        return new self($front, $back);
    }

    public function toArray(): array
    {
        return [
            'front' => $this->front,
            'back' => $this->back,
        ];
    }

    private function validateSide(string $sideName, string $value): void
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException(
                sprintf('Card %s cannot be empty', $sideName)
            );
        }

        $length = mb_strlen($trimmed, 'UTF-8');

        if ($length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'Card %s must not exceed %d characters, got %d',
                    $sideName,
                    self::MAX_LENGTH,
                    $length
                )
            );
        }
    }
}



================================================
FILE: src/Domain/Value/Email.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Value;

use InvalidArgumentException;

final readonly class Email
{
    private function __construct(
        public string $value
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(sprintf('Invalid email format: %s', $value));
        }

        if (strlen($value) > 255) {
            throw new InvalidArgumentException('Email cannot exceed 255 characters');
        }
    }

    public static function fromString(string $email): self
    {
        return new self(strtolower(trim($email)));
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}



================================================
FILE: src/Domain/Value/SetName.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Value;

use InvalidArgumentException;

final readonly class SetName
{
    private function __construct(
        public string $value
    ) {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Set name cannot be empty');
        }

        if (mb_strlen($trimmed) > 255) {
            throw new InvalidArgumentException('Set name cannot exceed 255 characters');
        }
    }

    public static function fromString(string $name): self
    {
        return new self($name);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        // Case-insensitive comparison (similar to CITEXT)
        return mb_strtolower($this->value) === mb_strtolower($other->value);
    }
}



================================================
FILE: src/Domain/Value/SourceText.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Value;

use InvalidArgumentException;

/**
 * Represents source text for AI flashcard generation.
 *
 * Enforces business rules:
 * - Minimum 1000 characters
 * - Maximum 10000 characters
 * - Cannot be empty after trimming whitespace
 */
final readonly class SourceText
{
    private const MIN_LENGTH = 1000;
    private const MAX_LENGTH = 10000;

    private function __construct(
        public string $value
    ) {
        $this->validate($value);
    }

    public static function fromString(string $text): self
    {
        return new self($text);
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function length(): int
    {
        return mb_strlen($this->value, 'UTF-8');
    }

    private function validate(string $text): void
    {
        $trimmed = trim($text);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Source text cannot be empty');
        }

        $length = mb_strlen($trimmed, 'UTF-8');

        if ($length < self::MIN_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'Source text must be at least %d characters long, got %d',
                    self::MIN_LENGTH,
                    $length
                )
            );
        }

        if ($length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'Source text must not exceed %d characters, got %d',
                    self::MAX_LENGTH,
                    $length
                )
            );
        }
    }
}



================================================
FILE: src/Domain/Value/SuggestedSetName.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Value;

use InvalidArgumentException;

/**
 * Represents a suggested name for a flashcard set generated by AI.
 *
 * This is a lighter version of SetName - it's only a suggestion
 * that the user can accept or modify before saving.
 *
 * Enforces business rules:
 * - Cannot be empty
 * - Maximum 255 characters (mirrors DB constraint)
 */
final readonly class SuggestedSetName
{
    private const MAX_LENGTH = 255;

    private function __construct(
        public string $value
    ) {
        $this->validate($value);
    }

    public static function fromString(string $name): self
    {
        return new self($name);
    }

    public function toString(): string
    {
        return $this->value;
    }

    private function validate(string $name): void
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            throw new InvalidArgumentException('Suggested set name cannot be empty');
        }

        $length = mb_strlen($trimmed, 'UTF-8');

        if ($length > self::MAX_LENGTH) {
            throw new InvalidArgumentException(
                sprintf(
                    'Suggested set name must not exceed %d characters, got %d',
                    self::MAX_LENGTH,
                    $length
                )
            );
        }
    }
}



================================================
FILE: src/Domain/Value/UserId.php
================================================
<?php

declare(strict_types=1);

namespace App\Domain\Value;

use InvalidArgumentException;

final readonly class UserId
{
    private function __construct(
        public string $value
    ) {
        if (!$this->isValidUuid($value)) {
            throw new InvalidArgumentException('Invalid UUID format');
        }
    }

    public static function fromString(string $uuid): self
    {
        return new self($uuid);
    }

    public static function generate(): self
    {
        // Generate UUID v4
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // Version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // Variant

        return new self(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));
    }

    public function toString(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    private function isValidUuid(string $uuid): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }
}



================================================
FILE: src/Form/RegistrationFormType.php
================================================
<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;

/**
 * Registration form type for user registration.
 *
 * Validates:
 * - Email format and required
 * - Password minimum length (8 chars) and strength
 * - Password confirmation matching
 * - Terms acceptance checkbox
 *
 * Based on auth-spec.md section 1.3.1 and 2.3.2
 */
class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'attr' => [
                    'autocomplete' => 'email',
                    'placeholder' => 'twój@email.pl',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Email jest wymagany',
                    ]),
                    new Email([
                        'message' => 'Podaj prawidłowy adres email',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => 'Hasło',
                'mapped' => false, // Not persisted to entity
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Minimum 8 znaków',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Hasło jest wymagane',
                    ]),
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Hasło musi zawierać co najmniej {{ limit }} znaków',
                        'max' => 4096, // Security: max length to prevent DoS
                    ]),
                    new PasswordStrength([
                        'minScore' => PasswordStrength::STRENGTH_MEDIUM,
                        'message' => 'Hasło jest zbyt słabe. Użyj kombinacji liter, cyfr i znaków specjalnych',
                    ]),
                ],
            ])
            ->add('passwordConfirm', PasswordType::class, [
                'label' => 'Potwierdź hasło',
                'mapped' => false,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => 'Wpisz hasło ponownie',
                ],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Potwierdź swoje hasło',
                    ]),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => false, // Label is rendered in template
                'mapped' => false,
                'constraints' => [
                    new IsTrue([
                        'message' => 'Musisz zaakceptować regulamin',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // No data_class - we handle data manually in controller
            // This allows us to use Domain model without direct form mapping
            'csrf_protection' => true,
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'registration',
        ]);
    }
}



================================================
FILE: src/Infrastructure/Console/Command/CreateUserCommand.php
================================================
<?php

declare(strict_types=1);

namespace App\Infrastructure\Console\Command;

use App\Domain\Model\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Value\Email;
use App\Domain\Value\UserId;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Console command to create users for testing/development.
 *
 * Usage:
 *   php bin/console app:create-user test@example.com test123
 *   php bin/console app:create-user admin@example.com secure_password
 *
 * Features:
 * - Validates email format (via Email value object)
 * - Hashes password using Symfony password hasher (bcrypt/argon2)
 * - Checks for duplicate emails
 * - Uses Domain layer (User entity, UserRepositoryInterface)
 *
 * Security notes:
 * - Passwords are immediately hashed (never stored in plain text)
 * - Only use for development/testing (not production user management)
 * - For production: implement proper registration flow with email verification
 */
#[AsCommand(
    name: 'app:create-user',
    description: 'Create a new user (for testing/development)',
)]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'User email address')
            ->addArgument('password', InputArgument::REQUIRED, 'User password (plain text, will be hashed)')
            ->setHelp(
                <<<'HELP'
                The <info>app:create-user</info> command creates a new user in the database:

                  <info>php bin/console app:create-user test@example.com test123</info>

                This command is intended for development and testing purposes only.
                For production, use the web registration flow.

                The password will be automatically hashed using the configured password hasher
                (bcrypt or argon2, depending on PHP extensions available).
                HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $emailString = $input->getArgument('email');
        $plainPassword = $input->getArgument('password');

        // Validate inputs
        if (!is_string($emailString) || !is_string($plainPassword)) {
            $io->error('Email and password must be strings.');
            return Command::FAILURE;
        }

        // Validate password length (minimum 8 characters per auth-spec.md)
        if (strlen($plainPassword) < 8) {
            $io->error('Password must be at least 8 characters long.');
            return Command::FAILURE;
        }

        try {
            // Create Email value object (validates format)
            $email = Email::fromString($emailString);

            // Check if user already exists
            if ($this->userRepository->exists($email)) {
                $io->error(sprintf('User with email "%s" already exists.', $email->toString()));
                return Command::FAILURE;
            }

            // Generate UUID and create timestamp first
            $userId = UserId::generate();
            $createdAt = new \DateTimeImmutable();

            // Create placeholder user for password hashing
            // Workaround: UserPasswordHasherInterface requires UserInterface instance
            // We use a temporary hash that meets length requirement (60+ chars)
            $tempHash = str_repeat('x', 60); // Temporary hash for UserInterface requirement
            $tempUser = User::create(
                id: $userId,
                email: $email,
                passwordHash: $tempHash,
                createdAt: $createdAt
            );

            // Hash password using Symfony password hasher
            $passwordHash = $this->passwordHasher->hashPassword($tempUser, $plainPassword);

            // Create final user with real hashed password
            // Use same ID and timestamp for consistency
            $user = User::create(
                id: $userId,
                email: $email,
                passwordHash: $passwordHash,
                createdAt: $createdAt
            );

            // Save to database
            $this->userRepository->save($user);

            $io->success(sprintf(
                'User created successfully!' . PHP_EOL .
                'Email: %s' . PHP_EOL .
                'ID: %s' . PHP_EOL .
                'You can now log in at /login',
                $user->getEmail()->toString(),
                $user->getId()->toString()
            ));

            return Command::SUCCESS;
        } catch (\InvalidArgumentException $e) {
            // Email validation failed or other domain constraint violated
            $io->error($e->getMessage());
            return Command::FAILURE;
        } catch (\Throwable $e) {
            // Unexpected error (DB connection, etc.)
            $io->error(sprintf('Failed to create user: %s', $e->getMessage()));
            return Command::FAILURE;
        }
    }
}



================================================
FILE: src/Infrastructure/Doctrine/EventSubscriber/PostgresRLSSubscriber.php
================================================
<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\EventSubscriber;

use App\Domain\Model\User;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * PostgreSQL Row-Level Security (RLS) Subscriber.
 *
 * Sets the current user ID in PostgreSQL session variable for RLS policies.
 * This ensures that database-level security policies enforce data isolation
 * between users automatically.
 *
 * RLS Policies reference this via: current_app_user() function which reads
 * from current_setting('app.current_user_id')
 */
final readonly class PostgresRLSSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private LoggerInterface $logger,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    /**
     * Set PostgreSQL session variable for RLS on each request.
     *
     * @throws DBALException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // Only process main requests (not sub-requests)
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();

        // Only set RLS for authenticated users
        if (!$user instanceof User) {
            return;
        }

        try {
            $userId = $user->getId()->toString();

            // Set PostgreSQL session variable for RLS policies
            // This will be used by: current_setting('app.current_user_id', true)::uuid
            $this->entityManager->getConnection()->executeStatement(
                'SET LOCAL app.current_user_id = :user_id',
                ['user_id' => $userId]
            );

            $this->logger->debug('RLS session variable set', [
                'user_id' => $userId,
                'request_uri' => $event->getRequest()->getRequestUri(),
            ]);

        } catch (DBALException $e) {
            // Log error but don't break the request
            // RLS will fail-safe by denying access if variable is not set
            $this->logger->error('Failed to set RLS session variable', [
                'error' => $e->getMessage(),
                'user_id' => $user->getId()->toString(),
            ]);

            // Re-throw to ensure request fails if RLS setup fails
            // This is critical for security - better to fail than bypass RLS
            throw $e;
        }
    }
}



================================================
FILE: src/Infrastructure/Doctrine/Repository/DoctrineAiJobRepository.php
================================================
<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Model\AiJob;
use App\Domain\Model\AiJobStatus;
use App\Domain\Repository\AiJobRepositoryInterface;
use App\Domain\Value\UserId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AiJob>
 */
class DoctrineAiJobRepository extends ServiceEntityRepository implements AiJobRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiJob::class);
    }

    public function findById(string $id): ?AiJob
    {
        return $this->find($id);
    }

    public function findByUser(UserId $userId, int $limit = 50): array
    {
        return $this->createQueryBuilder('aj')
            ->where('aj.userId = :userId')
            ->setParameter('userId', $userId->toString())
            ->orderBy('aj.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByStatus(AiJobStatus $status, int $limit = 100): array
    {
        return $this->createQueryBuilder('aj')
            ->where('aj.status = :status')
            ->setParameter('status', $status)
            ->orderBy('aj.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function save(AiJob $job): void
    {
        $this->getEntityManager()->persist($job);
        $this->getEntityManager()->flush();
    }

    public function countFailedByUser(UserId $userId): int
    {
        return $this->count([
            'userId' => $userId->toString(),
            'status' => AiJobStatus::FAILED
        ]);
    }
}



================================================
FILE: src/Infrastructure/Doctrine/Repository/DoctrineAnalyticsEventRepository.php
================================================
<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Model\AnalyticsEvent;
use App\Domain\Repository\AnalyticsEventRepositoryInterface;
use App\Domain\Value\UserId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AnalyticsEvent>
 */
class DoctrineAnalyticsEventRepository extends ServiceEntityRepository implements AnalyticsEventRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnalyticsEvent::class);
    }

    public function save(AnalyticsEvent $event): void
    {
        $this->getEntityManager()->persist($event);
        $this->getEntityManager()->flush();
    }

    public function findByUser(UserId $userId, int $limit = 100): array
    {
        return $this->createQueryBuilder('ae')
            ->where('ae.userId = :userId')
            ->setParameter('userId', $userId->toString())
            ->orderBy('ae.occurredAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByEventType(string $eventType, int $limit = 100): array
    {
        return $this->createQueryBuilder('ae')
            ->where('ae.eventType = :eventType')
            ->setParameter('eventType', $eventType)
            ->orderBy('ae.occurredAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}



================================================
FILE: src/Infrastructure/Doctrine/Repository/DoctrineCardRepository.php
================================================
<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Model\Card;
use App\Domain\Repository\CardRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Card>
 */
class DoctrineCardRepository extends ServiceEntityRepository implements CardRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }

    public function findById(string $id): ?Card
    {
        return $this->find($id);
    }

    public function findActiveBySetId(string $setId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.setId = :setId')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('setId', $setId)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Card $card): void
    {
        $this->getEntityManager()->persist($card);
        $this->getEntityManager()->flush();
    }

    public function saveAll(array $cards): void
    {
        $em = $this->getEntityManager();

        foreach ($cards as $card) {
            $em->persist($card);
        }

        $em->flush();
    }

    public function softDelete(Card $card): void
    {
        $card->softDelete(new \DateTimeImmutable());
        $this->getEntityManager()->flush();
    }

    public function countActiveBySetId(string $setId): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.setId = :setId')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('setId', $setId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}



================================================
FILE: src/Infrastructure/Doctrine/Repository/DoctrineReviewEventRepository.php
================================================
<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Model\ReviewEvent;
use App\Domain\Repository\ReviewEventRepositoryInterface;
use App\Domain\Value\UserId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReviewEvent>
 */
class DoctrineReviewEventRepository extends ServiceEntityRepository implements ReviewEventRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReviewEvent::class);
    }

    public function save(ReviewEvent $event): void
    {
        $this->getEntityManager()->persist($event);
        $this->getEntityManager()->flush();
    }

    public function findRecentByUser(UserId $userId, int $limit = 100): array
    {
        return $this->createQueryBuilder('re')
            ->where('re.userId = :userId')
            ->setParameter('userId', $userId->toString())
            ->orderBy('re.answeredAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByUserAndCard(UserId $userId, string $cardId): int
    {
        return (int) $this->createQueryBuilder('re')
            ->select('COUNT(re.id)')
            ->where('re.userId = :userId')
            ->andWhere('re.cardId = :cardId')
            ->setParameter('userId', $userId->toString())
            ->setParameter('cardId', $cardId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}



================================================
FILE: src/Infrastructure/Doctrine/Repository/DoctrineReviewStateRepository.php
================================================
<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Model\ReviewState;
use App\Domain\Repository\ReviewStateRepositoryInterface;
use App\Domain\Value\UserId;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReviewState>
 */
class DoctrineReviewStateRepository extends ServiceEntityRepository implements ReviewStateRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReviewState::class);
    }

    public function findByUserAndCard(UserId $userId, string $cardId): ?ReviewState
    {
        return $this->findOneBy([
            'userId' => $userId->toString(),
            'cardId' => $cardId
        ]);
    }

    public function findDueForUser(UserId $userId, DateTimeImmutable $now, int $limit = 20): array
    {
        return $this->createQueryBuilder('rs')
            ->where('rs.userId = :userId')
            ->andWhere('rs.dueAt <= :now')
            ->setParameter('userId', $userId->toString())
            ->setParameter('now', $now)
            ->orderBy('rs.dueAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function save(ReviewState $state): void
    {
        $this->getEntityManager()->persist($state);
        $this->getEntityManager()->flush();
    }

    public function countDueForUser(UserId $userId, DateTimeImmutable $now): int
    {
        return (int) $this->createQueryBuilder('rs')
            ->select('COUNT(rs.userId)')
            ->where('rs.userId = :userId')
            ->andWhere('rs.dueAt <= :now')
            ->setParameter('userId', $userId->toString())
            ->setParameter('now', $now)
            ->getQuery()
            ->getSingleScalarResult();
    }
}



================================================
FILE: src/Infrastructure/Doctrine/Repository/DoctrineSetRepository.php
================================================
<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Model\Set;
use App\Domain\Repository\SetRepositoryInterface;
use App\Domain\Value\UserId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Set>
 */
class DoctrineSetRepository extends ServiceEntityRepository implements SetRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Set::class);
    }

    public function findById(string $id): ?Set
    {
        return $this->find($id);
    }

    public function findOwnedBy(UserId $ownerId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.ownerId = :ownerId')
            ->setParameter('ownerId', $ownerId->toString())
            ->orderBy('s.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findActiveOwnedBy(UserId $ownerId, int $limit = 100, int $offset = 0): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.ownerId = :ownerId')
            ->andWhere('s.deletedAt IS NULL')
            ->setParameter('ownerId', $ownerId->toString())
            ->orderBy('s.updatedAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    public function save(Set $set): void
    {
        $this->getEntityManager()->persist($set);
        $this->getEntityManager()->flush();
    }

    public function softDelete(Set $set): void
    {
        $set->softDelete(new \DateTimeImmutable());
        $this->getEntityManager()->flush();
    }

    public function existsByOwnerAndName(UserId $ownerId, string $name): bool
    {
        return $this->count([
            'ownerId' => $ownerId->toString(),
            'name' => $name,
            'deletedAt' => null
        ]) > 0;
    }
}



================================================
FILE: src/Infrastructure/Doctrine/Repository/DoctrineUserRepository.php
================================================
<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Repository;

use App\Domain\Model\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Value\Email;
use App\Domain\Value\UserId;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class DoctrineUserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findById(UserId $id): ?User
    {
        return $this->find($id->toString());
    }

    public function findByEmail(Email $email): ?User
    {
        return $this->findOneBy(['email' => $email->toString()]);
    }

    public function save(User $user): void
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function exists(Email $email): bool
    {
        return $this->count(['email' => $email->toString()]) > 0;
    }
}



================================================
FILE: src/Infrastructure/Doctrine/Type/AiJobStatusType.php
================================================
<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Type;

use App\Domain\Model\AiJobStatus;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Doctrine DBAL Type for AiJobStatus enum.
 *
 * Maps PostgreSQL enum type 'ai_job_status' to PHP enum AiJobStatus.
 *
 * PostgreSQL enum definition:
 * CREATE TYPE ai_job_status AS ENUM ('queued', 'running', 'succeeded', 'failed');
 */
class AiJobStatusType extends Type
{
    public const NAME = 'ai_job_status';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'ai_job_status';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?AiJobStatus
    {
        if ($value === null) {
            return null;
        }

        return AiJobStatus::from($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof AiJobStatus) {
            return $value->value;
        }

        throw new \InvalidArgumentException(sprintf(
            'Expected %s, got %s',
            AiJobStatus::class,
            get_debug_type($value)
        ));
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}



================================================
FILE: src/Infrastructure/Doctrine/Type/CardOriginType.php
================================================
<?php

declare(strict_types=1);

namespace App\Infrastructure\Doctrine\Type;

use App\Domain\Model\CardOrigin;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Doctrine DBAL Type for CardOrigin enum.
 *
 * Maps PostgreSQL enum type 'card_origin' to PHP enum CardOrigin.
 *
 * PostgreSQL enum definition:
 * CREATE TYPE card_origin AS ENUM ('ai', 'manual');
 *
 * This type must be registered in doctrine.yaml:
 * doctrine:
 *   dbal:
 *     types:
 *       card_origin: App\Infrastructure\Doctrine\Type\CardOriginType
 */
class CardOriginType extends Type
{
    public const NAME = 'card_origin';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'card_origin';
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?CardOrigin
    {
        if ($value === null) {
            return null;
        }

        return CardOrigin::from($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof CardOrigin) {
            return $value->value;
        }

        throw new \InvalidArgumentException(sprintf(
            'Expected %s, got %s',
            CardOrigin::class,
            get_debug_type($value)
        ));
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }
}



================================================
FILE: src/Infrastructure/EventSubscriber/SetCreatedEventSubscriber.php
================================================
<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSubscriber;

use App\Domain\Event\SetCreatedEvent;
use App\Domain\Model\AnalyticsEvent;
use App\Domain\Repository\AnalyticsEventRepositoryInterface;
use App\Domain\Value\UserId;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to SetCreatedEvent and persists analytics data.
 *
 * Tracks KPI metrics for flashcard set creation:
 * - Total cards created
 * - AI vs manual card distribution
 * - AI card edit rate
 * - Linkage to AI generation jobs
 */
final readonly class SetCreatedEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AnalyticsEventRepositoryInterface $analyticsRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SetCreatedEvent::class => 'onSetCreated',
        ];
    }

    public function onSetCreated(SetCreatedEvent $event): void
    {
        $analyticsEvent = AnalyticsEvent::create(
            eventType: 'set_created',
            userId: UserId::fromString($event->userId),
            payload: [
                'total_cards' => $event->totalCardCount,
                'ai_cards' => $event->aiCardCount,
                'manual_cards' => $event->getManualCardCount(),
                'edited_ai_cards' => $event->editedAiCardCount,
                'ai_edit_rate' => $event->getAiEditRate(),
                'job_id' => $event->jobId,
            ],
            occurredAt: new \DateTimeImmutable(),
            setId: $event->setId
        );

        $this->analyticsRepository->save($analyticsEvent);
    }
}



================================================
FILE: src/Infrastructure/EventSubscriber/SetCurrentUserForRlsSubscriber.php
================================================
<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSubscriber;

use App\Domain\Model\User;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Sets PostgreSQL session variable for Row Level Security (RLS).
 *
 * PostgreSQL RLS Overview:
 * - All tables have RLS policies filtering by current_app_user()
 * - current_app_user() reads from session var: app.current_user_id
 * - This subscriber sets that variable at start of each request
 *
 * Execution flow:
 * 1. Symfony dispatches KernelEvents::REQUEST (early in request lifecycle)
 * 2. This subscriber checks if user is authenticated
 * 3. If authenticated: execute "SET app.current_user_id = '<user_uuid>'"
 * 4. If not authenticated: execute "SET app.current_user_id = ''" (clear)
 * 5. All subsequent DB queries automatically enforce RLS policies
 *
 * Security benefits:
 * - Defense in depth: even if application logic fails, DB prevents unauthorized access
 * - Prevents IDOR vulnerabilities at database level
 * - Protects against SQL injection accessing other users' data
 * - Audit trail: PostgreSQL logs which user accessed what
 *
 * Performance notes:
 * - SET command is cheap (~0.1ms overhead per request)
 * - Connection pooling: each request gets fresh connection, so SET is required
 * - Alternative: use connection pooling with persistent app.current_user_id
 *   (not implemented in MVP due to complexity)
 *
 * Priority: 10 (early, before any controllers/services run)
 */
final class SetCurrentUserForRlsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Subscribe to early request event (before controller execution).
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // Priority 10: early in request lifecycle, before controllers
            // Must run before any DB queries are executed
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    /**
     * Set PostgreSQL session variable for authenticated user.
     *
     * Executed on every request (master request only, not sub-requests).
     *
     * @param RequestEvent $event
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // Only process master request (not ESI sub-requests, etc.)
        if (!$event->isMainRequest()) {
            return;
        }

        $user = $this->security->getUser();

        if ($user instanceof User) {
            // User is authenticated - set their UUID as current_app_user
            $this->setCurrentUserForRls($user->getId()->toString());
        } else {
            // User is not authenticated - clear session variable
            // This ensures anonymous requests don't accidentally use previous user's ID
            // (important for connection pooling scenarios)
            $this->clearCurrentUserForRls();
        }
    }

    /**
     * Execute PostgreSQL SET command to store user ID in session.
     *
     * SQL: SET app.current_user_id = '<uuid>'
     *
     * This value is then read by RLS policies via current_app_user() function:
     *   CREATE FUNCTION current_app_user() RETURNS uuid AS $$
     *     SELECT current_setting('app.current_user_id', true)::uuid;
     *   $$ LANGUAGE sql STABLE;
     *
     * @param string $userId User's UUID as string
     * @return void
     */
    private function setCurrentUserForRls(string $userId): void
    {
        try {
            $connection = $this->entityManager->getConnection();

            // Use quoted value to prevent SQL injection (even though userId is from trusted source)
            // SET LOCAL would be safer (auto-clears at transaction end) but requires transaction
            // Using SET for simplicity in MVP - value is cleared on connection return to pool
            $connection->executeStatement(
                'SET app.current_user_id = :user_id',
                ['user_id' => $userId]
            );

            // Debug logging (only in dev environment)
            $this->logger->debug('RLS: Set current user for database session', [
                'user_id' => $userId,
            ]);
        } catch (DBALException $e) {
            // Log error but don't break request
            // Rationale: RLS failure should not prevent page load
            // However, subsequent queries will fail RLS checks (intended behavior)
            $this->logger->error('RLS: Failed to set current user for database session', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear PostgreSQL session variable (for anonymous users).
     *
     * SQL: SET app.current_user_id = ''
     *
     * Important for security:
     * - Prevents connection reuse attacks in pooling scenarios
     * - Ensures anonymous users get RLS policies that expect NULL user
     *
     * Note: Empty string is used instead of NULL because:
     * - current_setting('app.current_user_id', true) returns NULL if not set
     * - Setting to empty string makes intent explicit
     * - RLS function current_app_user() will fail to cast '' to UUID (intended)
     *
     * @return void
     */
    private function clearCurrentUserForRls(): void
    {
        try
</kod_projektu>

<struktura_projektu>
Directory structure:
└── lwozdev-10x-cards/
    ├── README.md
    ├── CLAUDE.md
    ├── cloude-help.md
    ├── compose.override.yaml
    ├── composer.json
    ├── docker-compose.yml
    ├── Dockerfile
    ├── importmap.php
    ├── manual-test-create-set.http
    ├── manual-test-curl.sh
    ├── phpunit.dist.xml
    ├── symfony.lock
    ├── test-create-set.http
    ├── .editorconfig
    ├── .env.dev
    ├── .env.test
    ├── assets/
    │   ├── app.js
    │   ├── controllers.json
    │   ├── stimulus_bootstrap.js
    │   ├── controllers/
    │   │   ├── csrf_protection_controller.js
    │   │   ├── edit_set_controller.js
    │   │   ├── form_validation_controller.js
    │   │   ├── generate_controller.js
    │   │   ├── hello_controller.js
    │   │   ├── modal_controller.js
    │   │   ├── set_list_controller.js
    │   │   ├── snackbar_controller.js
    │   │   └── theme_controller.js
    │   └── styles/
    │       └── app.css
    ├── config/
    │   ├── bundles.php
    │   ├── preload.php
    │   ├── routes.yaml
    │   ├── services.yaml
    │   ├── packages/
    │   │   ├── asset_mapper.yaml
    │   │   ├── cache.yaml
    │   │   ├── csrf.yaml
    │   │   ├── debug.yaml
    │   │   ├── doctrine.yaml
    │   │   ├── doctrine_migrations.yaml
    │   │   ├── framework.yaml
    │   │   ├── mailer.yaml
    │   │   ├── messenger.yaml
    │   │   ├── monolog.yaml
    │   │   ├── notifier.yaml
    │   │   ├── property_info.yaml
    │   │   ├── routing.yaml
    │   │   ├── security.yaml
    │   │   ├── symfonycasts_tailwind.yaml
    │   │   ├── symfonycasts_verify_email.yaml
    │   │   ├── translation.yaml
    │   │   ├── twig.yaml
    │   │   ├── twig_component.yaml
    │   │   ├── ux_turbo.yaml
    │   │   ├── validator.yaml
    │   │   └── web_profiler.yaml
    │   └── routes/
    │       ├── framework.yaml
    │       ├── security.yaml
    │       └── web_profiler.yaml
    ├── docker/
    │   └── nginx/
    │       └── default.conf
    ├── migrations/
    │   ├── Version20251024000000.php
    │   ├── Version20251028193900.php
    │   ├── Version20251102160547.php
    │   └── Version20260104130000.php
    ├── public/
    │   └── index.php
    ├── src/
    │   ├── Kernel.php
    │   ├── Application/
    │   │   ├── Command/
    │   │   │   ├── CreateSetCardDto.php
    │   │   │   ├── CreateSetCommand.php
    │   │   │   ├── GenerateCardsCommand.php
    │   │   │   └── GenerateFlashcardsCommand.php
    │   │   ├── EventListener/
    │   │   │   └── FlashcardGenerationExceptionListener.php
    │   │   └── Handler/
    │   │       ├── CreateSetHandler.php
    │   │       ├── CreateSetResult.php
    │   │       ├── GenerateCardsHandler.php
    │   │       ├── GenerateCardsHandlerResult.php
    │   │       └── GenerateFlashcardsHandler.php
    │   ├── Controller/
    │   │   ├── AuthUITestController.php
    │   │   ├── KitchenSinkController.php
    │   │   ├── LuckyController.php
    │   │   └── ScaffoldDemoController.php
    │   ├── Domain/
    │   │   ├── Event/
    │   │   │   └── SetCreatedEvent.php
    │   │   ├── Exception/
    │   │   │   ├── AiJobNotFoundException.php
    │   │   │   └── DuplicateSetNameException.php
    │   │   ├── Model/
    │   │   │   ├── AiJob.php
    │   │   │   ├── AiJobStatus.php
    │   │   │   ├── AnalyticsEvent.php
    │   │   │   ├── Card.php
    │   │   │   ├── CardOrigin.php
    │   │   │   ├── ReviewEvent.php
    │   │   │   ├── ReviewState.php
    │   │   │   ├── Set.php
    │   │   │   └── User.php
    │   │   ├── Repository/
    │   │   │   ├── AiJobRepositoryInterface.php
    │   │   │   ├── AnalyticsEventRepositoryInterface.php
    │   │   │   ├── CardRepositoryInterface.php
    │   │   │   ├── ReviewEventRepositoryInterface.php
    │   │   │   ├── ReviewStateRepositoryInterface.php
    │   │   │   ├── SetRepositoryInterface.php
    │   │   │   └── UserRepositoryInterface.php
    │   │   ├── Service/
    │   │   │   ├── AiCardGeneratorInterface.php
    │   │   │   └── GenerateCardsResult.php
    │   │   └── Value/
    │   │       ├── AiJobId.php
    │   │       ├── CardBack.php
    │   │       ├── CardFront.php
    │   │       ├── CardPreview.php
    │   │       ├── Email.php
    │   │       ├── SetName.php
    │   │       ├── SourceText.php
    │   │       ├── SuggestedSetName.php
    │   │       └── UserId.php
    │   ├── Form/
    │   │   └── RegistrationFormType.php
    │   ├── Infrastructure/
    │   │   ├── Console/
    │   │   │   └── Command/
    │   │   │       └── CreateUserCommand.php
    │   │   ├── Doctrine/
    │   │   │   ├── EventSubscriber/
    │   │   │   │   └── PostgresRLSSubscriber.php
    │   │   │   ├── Repository/
    │   │   │   │   ├── DoctrineAiJobRepository.php
    │   │   │   │   ├── DoctrineAnalyticsEventRepository.php
    │   │   │   │   ├── DoctrineCardRepository.php
    │   │   │   │   ├── DoctrineReviewEventRepository.php
    │   │   │   │   ├── DoctrineReviewStateRepository.php
    │   │   │   │   ├── DoctrineSetRepository.php
    │   │   │   │   └── DoctrineUserRepository.php
    │   │   │   └── Type/
    │   │   │       ├── AiJobStatusType.php
    │   │   │       └── CardOriginType.php
    │   │   ├── EventSubscriber/
    │   │   │   ├── SetCreatedEventSubscriber.php
    │   │   │   ├── SetCurrentUserForRlsSubscriber.php
    │   │   │   └── UpdateLastLoginSubscriber.php
    │   │   ├── Integration/
    │   │   │   ├── Ai/
    │   │   │   │   ├── MockAiCardGenerator.php
    │   │   │   │   ├── OpenRouterAiCardGenerator.php
    │   │   │   │   └── Exception/
    │   │   │   │       ├── AiException.php
    │   │   │   │       ├── AiGenerationException.php
    │   │   │   │       └── AiTimeoutException.php
    │   │   │   └── OpenRouter/
    │   │   │       ├── OpenRouterService.php
    │   │   │       ├── OpenRouterServiceInterface.php
    │   │   │       ├── DTO/
    │   │   │       │   ├── Flashcard.php
    │   │   │       │   ├── FlashcardGenerationResult.php
    │   │   │       │   └── OpenRouterResponse.php
    │   │   │       └── Exception/
    │   │   │           ├── OpenRouterApiException.php
    │   │   │           ├── OpenRouterAuthenticationException.php
    │   │   │           ├── OpenRouterException.php
    │   │   │           ├── OpenRouterInvalidRequestException.php
    │   │   │           ├── OpenRouterNetworkException.php
    │   │   │           ├── OpenRouterParseException.php
    │   │   │           ├── OpenRouterRateLimitException.php
    │   │   │           ├── OpenRouterServerException.php
    │   │   │           └── OpenRouterTimeoutException.php
    │   │   └── Security/
    │   │       └── UserProvider.php
    │   ├── Twig/
    │   │   └── Components/
    │   │       ├── AppScaffold.php
    │   │       ├── BottomNav.php
    │   │       ├── Button.php
    │   │       ├── Card.php
    │   │       ├── ListItem.php
    │   │       ├── Modal.php
    │   │       ├── NavDrawer.php
    │   │       ├── NavRail.php
    │   │       ├── Snackbar.php
    │   │       └── TextField.php
    │   └── UI/
    │       └── Http/
    │           ├── Controller/
    │           │   ├── CreateSetController.php
    │           │   ├── EditNewSetController.php
    │           │   ├── FlashcardGeneratorController.php
    │           │   ├── GenerateCardsController.php
    │           │   ├── GenerateViewController.php
    │           │   ├── RegistrationController.php
    │           │   ├── SecurityController.php
    │           │   └── SetListController.php
    │           ├── Request/
    │           │   ├── CreateSetCardRequestDto.php
    │           │   ├── CreateSetRequest.php
    │           │   ├── GenerateCardsRequest.php
    │           │   └── GenerateFlashcardsRequest.php
    │           └── Response/
    │               ├── AiJobResponse.php
    │               ├── CardPreviewDto.php
    │               ├── CreateSetResponse.php
    │               └── GenerateCardsResponse.php
    ├── templates/
    │   ├── base.html.twig
    │   ├── components/
    │   │   ├── AppScaffold.html.twig
    │   │   ├── BottomNav.html.twig
    │   │   ├── Button.html.twig
    │   │   ├── Card.html.twig
    │   │   ├── ListItem.html.twig
    │   │   ├── Modal.html.twig
    │   │   ├── NavDrawer.html.twig
    │   │   ├── NavRail.html.twig
    │   │   ├── Snackbar.html.twig
    │   │   └── TextField.html.twig
    │   ├── generate/
    │   │   └── index.html.twig
    │   ├── kitchen_sink/
    │   │   └── index.html.twig
    │   ├── lucky/
    │   │   └── number.html.twig
    │   ├── registration/
    │   │   ├── check_email.html.twig
    │   │   ├── register.html.twig
    │   │   └── verification_email.html.twig
    │   ├── reset_password/
    │   │   ├── request.html.twig
    │   │   └── reset.html.twig
    │   ├── scaffold_demo/
    │   │   └── index.html.twig
    │   ├── security/
    │   │   └── login.html.twig
    │   └── sets/
    │       ├── edit_new.html.twig
    │       └── list.html.twig
    ├── tests/
    │   └── bootstrap.php
    ├── .ai/
    │   ├── api-endpoint-generate-examples.md
    │   ├── api-plan.md
    │   ├── auth-spec.md
    │   ├── auth-ui-implementation-summary.md
    │   ├── component-demo-example.twig
    │   ├── db-plan.md
    │   ├── edit-set-view-implementation-summary.md
    │   ├── generate-endpoint-implementation-plan-claude.md
    │   ├── generate-implementation-plan.md
    │   ├── generate-view-implementation-plan.md
    │   ├── generate-view-implementation-summary.md
    │   ├── kitchen-sink-readme.md
    │   ├── komponenty-struktura.md
    │   ├── material-3-components-usage.md
    │   ├── material-3-components.md
    │   ├── material-3-implementation-summary.md
    │   ├── openrouter-service-implementation-plan-min.md
    │   ├── prd.md
    │   ├── symfony.md
    │   ├── tech-stack-prompt.md
    │   ├── tech-stack.md
    │   ├── ui-plan.md
    │   ├── view-implementation-plan-endpoint-generatre.md
    │   ├── view-implementation-plan-endpoint-post-sets.md
    │   ├── diagrams/
    │   │   ├── auth.md
    │   │   └── journey.md
    │   └── prompts/
    │       ├── readme.md
    │       ├── ui-plan.md
    │       ├── api/
    │       │   ├── api-plan.md
    │       │   ├── api.md
    │       │   ├── entity.md
    │       │   ├── implementacja-endpointu-3x3.md
    │       │   ├── plan-implementacji-endpointa-rest-api.md
    │       │   └── endpoints/
    │       │       ├── generate-endpoint-implementation-generate-3x3.md
    │       │       ├── generate-endpoint-implementation-plan-gpt5.md
    │       │       ├── plan-implementacji-endpointa-post-flashcards.md
    │       │       ├── plan-implementacji-endponit-generate-cards.md
    │       │       └── plan-implementacji.md
    │       ├── auth/
    │       │   ├── arch.md
    │       │   ├── implementacja-backendu-rejestracji.md
    │       │   ├── mermaid-diagram-auth.md
    │       │   ├── planowanie-integracji-backendu-logowania.md
    │       │   ├── ui.md
    │       │   ├── validate.md
    │       │   └── diagrams/
    │       │       ├── mermaid-diagram-auth.md
    │       │       ├── mermaid-diagram-journey.mdc
    │       │       └── mermaid-diagram-ui.mdc
    │       ├── db/
    │       │   ├── 1-asystent-planowania.md
    │       │   ├── 2-podsumowanie.md
    │       │   ├── 3-schemat.md
    │       │   ├── 4-tworzenie-migracji.md
    │       │   ├── db-plan-summary.md
    │       │   ├── db-plan.md
    │       │   └── planowienie-bd.md
    │       ├── open-router/
    │       │   ├── implementacja.md
    │       │   └── plan-implementacj.md
    │       └── ui/
    │           ├── asystent-planowania.md
    │           ├── generate-components.md
    │           ├── implmentacja-widoku-3x-2.md
    │           ├── implmentacja-widoku-3x.md
    │           ├── plan-implementacji-navigation.md
    │           ├── plan-implementacji.md
    │           ├── podsumowanie-planowania.md
    │           ├── podsumowanie-planu-dodatkowe.md
    │           └── wysokopoziomowy-plan-ui.md
    └── .claude/
        └── commands/
            └── git-commit-message.md

</struktura_projektu>

Twoim zadaniem jest wygenerowanie szczegółowego planu testów, który będzie dostosowany do specyfiki projektu, uwzględniając wykorzystywane technologie, strukturę kodu oraz kluczowe elementy repozytorium. Plan testów powinien być napisany w języku polskim.

Przed stworzeniem planu testów, przeprowadź dogłębną analizę projektu wewnątrz bloku <analiza_projektu> w swoim bloku myślowym. W analizie uwzględnij:

1. Kluczowe komponenty projektu wynikające z analizy kodu:
    - Wymień i opisz główne komponenty projektu
2. Specyfikę stosu technologicznego i jego wpływ na strategię testowania:
    - Przeanalizuj każdy element stosu technologicznego i jego implikacje dla testowania
3. Priorytety testowe bazujące na strukturze repozytorium:
    - Zidentyfikuj i uszereguj obszary testowe według ważności
4. Potencjalne obszary ryzyka wymagające szczególnej uwagi w testach:
    - Wymień potencjalne ryzyka i uzasadnij, dlaczego wymagają specjalnej uwagi

Po zakończeniu analizy, stwórz plan testów wewnątrz bloku <plan_testów>. Plan powinien zawierać:

1. Wprowadzenie i cele testowania
2. Zakres testów
3. Typy testów do przeprowadzenia (np. testy jednostkowe, integracyjne, wydajnościowe)
4. Scenariusze testowe dla kluczowych funkcjonalności
5. Środowisko testowe
6. Narzędzia do testowania
7. Harmonogram testów
8. Kryteria akceptacji testów
9. Role i odpowiedzialności w procesie testowania
10. Procedury raportowania błędów

Pamiętaj, aby plan testów był:
- Dokładnie dostosowany do kontekstu projektu
- Uwzględniał specyfikę wykorzystywanych technologii
- Priorytetyzował kluczowe elementy repozytorium
- Był napisany w języku polskim
- Prezentował wysoką jakość i profesjonalizm

Rozpocznij od analizy, a następnie przejdź do tworzenia planu testów. Twój końcowy wynik powinien składać się tylko z planu testów i nie powinien powielać ani streszczać żadnej pracy wykonanej w bloku analizy projektu.

Przedstaw ten plan w formacie Markdown.
