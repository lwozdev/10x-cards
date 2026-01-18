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
    ) {
    }

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
