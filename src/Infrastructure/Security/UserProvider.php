<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Model\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Value\Email;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Custom User Provider for Symfony Security.
 *
 * Implements Clean Architecture by:
 * - Using Domain's UserRepositoryInterface (not Doctrine directly)
 * - Working with Domain's User entity and Email value object
 * - Decoupling Security component from infrastructure details
 *
 * This provider is registered in security.yaml and used by:
 * - FormLogin authenticator (for login form)
 * - Session-based authentication (for subsequent requests)
 *
 * Security flow:
 * 1. User submits login form with email + password
 * 2. FormLogin calls loadUserByIdentifier(email)
 * 3. This provider queries User via repository
 * 4. FormLogin validates password hash
 * 5. User object stored in session
 * 6. On subsequent requests, refreshUser() reloads from DB
 */
final class UserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {
    }

    /**
     * Load user by their email address (user identifier).
     *
     * Called by:
     * - FormLogin authenticator during login
     * - Custom authenticators that need to load user by email
     *
     * @param string $identifier User's email address
     * @return UserInterface Domain User entity implementing UserInterface
     * @throws UserNotFoundException When user with given email doesn't exist
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        try {
            $email = Email::fromString($identifier);
        } catch (\InvalidArgumentException $e) {
            // Invalid email format - throw UserNotFoundException for security
            // Don't reveal whether email format was invalid (info leak)
            throw new UserNotFoundException(
                sprintf('User with email "%s" not found.', $identifier)
            );
        }

        $user = $this->userRepository->findByEmail($email);

        if ($user === null) {
            // Security best practice: use same message for all failures
            // Don't reveal whether user exists or not (prevents user enumeration)
            throw new UserNotFoundException(
                sprintf('User with email "%s" not found.', $identifier)
            );
        }

        return $user;
    }

    /**
     * Refresh user from persistent storage.
     *
     * Called by:
     * - Symfony Security on each request with authenticated session
     * - Ensures user data is fresh (e.g., roles, password hash)
     *
     * Important for:
     * - Detecting deleted users (throw UserNotFoundException)
     * - Detecting role changes
     * - Detecting password changes (will invalidate session)
     *
     * @param UserInterface $user User instance from session
     * @return UserInterface Fresh user instance from database
     * @throws UserNotFoundException When user no longer exists
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        // Reload user from database by ID
        $freshUser = $this->userRepository->findById($user->getId());

        if ($freshUser === null) {
            // User was deleted - invalidate session
            throw new UserNotFoundException(
                sprintf('User with ID "%s" not found.', $user->getId()->toString())
            );
        }

        return $freshUser;
    }

    /**
     * Check if this provider supports given user class.
     *
     * Symfony Security uses this to determine which provider
     * should handle refreshUser() calls.
     *
     * @param string $class Fully qualified class name
     * @return bool True if class is our Domain User entity
     */
    public function supportsClass(string $class): bool
    {
        return User::class === $class || is_subclass_of($class, User::class);
    }
}
