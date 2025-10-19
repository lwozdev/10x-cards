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