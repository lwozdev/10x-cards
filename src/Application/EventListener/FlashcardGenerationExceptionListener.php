<?php

declare(strict_types=1);

namespace App\Application\EventListener;

use App\Domain\Model\AnalyticsEvent;
use App\Domain\Model\User;
use App\Domain\Repository\AnalyticsEventRepositoryInterface;
use App\Domain\Value\UserId;
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
    ) {
    }

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
        if ('POST' !== $request->getMethod() || '/generate' !== $request->getPathInfo()) {
            return;
        }

        // Only track for authenticated users
        $user = $this->security->getUser();
        if (null === $user) {
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
                occurredAt: new \DateTimeImmutable()
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
