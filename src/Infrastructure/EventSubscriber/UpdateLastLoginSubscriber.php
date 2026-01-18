<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSubscriber;

use App\Domain\Model\User;
use App\Domain\Repository\UserRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Updates user's last_login_at timestamp after successful authentication.
 *
 * Execution flow:
 * 1. User successfully authenticates (FormLogin, JWT, etc.)
 * 2. Symfony Security dispatches LoginSuccessEvent
 * 3. This subscriber updates User.last_login_at to current timestamp
 * 4. Useful for:
 *    - User activity tracking
 *    - Analytics (last active users)
 *    - Security auditing (detect compromised accounts)
 *    - "Last seen" features in admin panel
 *
 * Note: Updates happen AFTER authentication is complete
 * - User is already logged in when this runs
 * - Failure to update last_login_at doesn't break login flow
 * - Transaction: uses repository which handles flush
 *
 * Clean Architecture:
 * - Uses UserRepositoryInterface (domain abstraction)
 * - Works with Domain User entity
 * - Decoupled from Doctrine/infrastructure
 */
final class UpdateLastLoginSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Subscribe to login success event.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    /**
     * Update user's last_login_at timestamp after successful login.
     */
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        // Only process our Domain User entity (not other UserInterface implementations)
        if (!$user instanceof User) {
            return;
        }

        try {
            // Update last login timestamp
            $user->updateLastLogin(new \DateTimeImmutable());

            // Persist to database
            $this->userRepository->save($user);

            $this->logger->info('Updated last login timestamp for user', [
                'user_id' => $user->getId()->toString(),
                'email' => $user->getEmail()->toString(),
            ]);
        } catch (\Throwable $e) {
            // Log error but don't break login flow
            // Rationale: updating last_login_at is not critical for authentication
            // User should still be able to use the application
            $this->logger->error('Failed to update last login timestamp', [
                'user_id' => $user->getId()->toString(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
