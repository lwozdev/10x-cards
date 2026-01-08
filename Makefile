.PHONY: help test test-php test-js test-e2e test-all coverage setup-test-db phpstan

# Colors for output
GREEN  := \033[0;32m
YELLOW := \033[0;33m
NC     := \033[0m # No Color

help: ## Show this help message
	@echo '${GREEN}Available commands:${NC}'
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  ${YELLOW}%-20s${NC} %s\n", $$1, $$2}'

##
## Setup
##

install: ## Install all dependencies (PHP + Node.js in Docker)
	@echo "${GREEN}Installing PHP dependencies...${NC}"
	docker compose exec backend composer install
	@echo "${GREEN}Building and starting Node.js container...${NC}"
	docker compose up -d node
	@echo "${GREEN}Dependencies installed successfully!${NC}"

setup-test-db: ## Create and migrate test database
	@echo "${GREEN}Creating test database...${NC}"
	php bin/console doctrine:database:create --env=test --if-not-exists
	@echo "${GREEN}Running migrations...${NC}"
	php bin/console doctrine:migrations:migrate --env=test --no-interaction

##
## Testing
##

test: test-php test-js ## Run all tests (PHP + JS, excluding E2E)

test-all: test-php test-js test-e2e ## Run ALL tests including E2E

test-php: ## Run all PHP tests (Unit + Integration + Functional)
	@echo "${GREEN}Running PHP tests...${NC}"
	vendor/bin/phpunit

test-unit: ## Run PHP unit tests only
	@echo "${GREEN}Running PHP unit tests...${NC}"
	vendor/bin/phpunit --testsuite=Unit

test-integration: ## Run PHP integration tests only
	@echo "${GREEN}Running PHP integration tests...${NC}"
	vendor/bin/phpunit --testsuite=Integration

test-functional: ## Run PHP functional tests only
	@echo "${GREEN}Running PHP functional tests...${NC}"
	vendor/bin/phpunit --testsuite=Functional

test-js: ## Run frontend tests (Vitest in Docker)
	@echo "${GREEN}Running frontend tests...${NC}"
	docker compose exec node npm run test:unit

test-js-watch: ## Run frontend tests in watch mode (Docker)
	@echo "${GREEN}Running frontend tests in watch mode...${NC}"
	docker compose exec node npm run test:watch

test-js-ui: ## Run frontend tests with UI (Docker)
	@echo "${GREEN}Opening Vitest UI...${NC}"
	docker compose exec node npm run test:ui

test-e2e: ## Run E2E tests (Playwright in Docker)
	@echo "${GREEN}Running E2E tests...${NC}"
	docker compose exec -e BASE_URL=http://nginx node npm run test:e2e

test-e2e-ui: ## Run E2E tests with Playwright UI (Docker)
	@echo "${GREEN}Opening Playwright UI...${NC}"
	docker compose exec -e BASE_URL=http://nginx node npm run test:e2e:ui

test-e2e-headed: ## Run E2E tests in headed mode (Docker)
	@echo "${GREEN}Running E2E tests in headed mode...${NC}"
	docker compose exec -e BASE_URL=http://nginx node npm run test:e2e:headed

test-e2e-debug: ## Debug E2E tests step-by-step (Docker)
	@echo "${GREEN}Starting Playwright debugger...${NC}"
	docker compose exec -e BASE_URL=http://nginx node npm run test:e2e:debug

##
## Coverage
##

coverage: coverage-php coverage-js ## Generate coverage reports for PHP and JS

coverage-php: ## Generate PHP coverage report
	@echo "${GREEN}Generating PHP coverage report...${NC}"
	vendor/bin/phpunit --coverage-html var/coverage/html
	@echo "${GREEN}Coverage report: var/coverage/html/index.html${NC}"

coverage-js: ## Generate frontend coverage report (Docker)
	@echo "${GREEN}Generating frontend coverage report...${NC}"
	docker compose exec node npm run test:coverage
	@echo "${GREEN}Coverage report: var/coverage/frontend/index.html${NC}"

##
## Code Quality
##

phpstan: ## Run PHPStan static analysis (level 8)
	@echo "${GREEN}Running PHPStan analysis...${NC}"
	vendor/bin/phpstan analyse src tests --level=8

cs-fix: ## Fix coding standards (when PHP-CS-Fixer is installed)
	@echo "${YELLOW}PHP-CS-Fixer not yet configured${NC}"

##
## Development
##

serve: ## Start Symfony development server
	@echo "${GREEN}Starting Symfony server on http://localhost:8000${NC}"
	symfony serve

serve-bg: ## Start Symfony server in background
	@echo "${GREEN}Starting Symfony server in background...${NC}"
	symfony serve -d

docker-up: ## Start all Docker containers (PostgreSQL, Backend, Nginx, Node)
	@echo "${GREEN}Starting Docker containers...${NC}"
	docker compose up -d

docker-down: ## Stop all Docker containers
	@echo "${GREEN}Stopping Docker containers...${NC}"
	docker compose down

docker-logs: ## Show Docker logs for all containers
	docker compose logs -f

docker-logs-node: ## Show Node.js container logs
	docker compose logs -f node

##
## Cleanup
##

clean: ## Clean cache and temporary files
	@echo "${GREEN}Cleaning cache...${NC}"
	rm -rf var/cache/* var/log/* .phpunit.cache
	@echo "${GREEN}Clearing Symfony cache...${NC}"
	php bin/console cache:clear

clean-test: ## Clean test artifacts
	@echo "${GREEN}Cleaning test artifacts...${NC}"
	rm -rf var/coverage/ test-results/ var/playwright-report/ var/.auth/

##
## CI/CD
##

ci: setup-test-db test-all phpstan ## Run full CI pipeline (install, test, analyze)
	@echo "${GREEN}CI pipeline completed successfully!${NC}"
