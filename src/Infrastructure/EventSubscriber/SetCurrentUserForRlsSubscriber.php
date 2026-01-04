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
        try {
            $connection = $this->entityManager->getConnection();

            // Clear session variable (empty string, not NULL)
            $connection->executeStatement("SET app.current_user_id = ''");

            $this->logger->debug('RLS: Cleared current user for database session (anonymous request)');
        } catch (DBALException $e) {
            // Log error but don't break request
            $this->logger->error('RLS: Failed to clear current user for database session', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
