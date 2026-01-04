<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Security Controller - handles login/logout views.
 *
 * Responsibilities (Thin Controller pattern):
 * - Render login form template
 * - Display authentication errors (via AuthenticationUtils)
 * - Pre-fill email field with last attempted username
 * - Logout route definition (actual logout handled by Symfony Security)
 *
 * What this controller DOES NOT do:
 * - Validate credentials (handled by FormLogin authenticator)
 * - Hash passwords (handled by UserPasswordHasher)
 * - Create sessions (handled by Symfony Security)
 * - Load user from DB (handled by UserProvider)
 *
 * Authentication flow (FormLogin):
 * 1. GET /login - this controller renders form
 * 2. POST /login - Symfony Security intercepts (check_path in security.yaml)
 * 3. FormLogin authenticator validates credentials
 * 4. Success: redirect to /sets (or target_path)
 * 5. Failure: redirect back to GET /login with error (this controller)
 *
 * Error handling:
 * - Generic message: "Nieprawidłowy email lub hasło" (auth-spec.md requirement)
 * - No user enumeration (same error for invalid email vs wrong password)
 * - Last username pre-filled for UX (user can fix typo)
 *
 * Template: templates/security/login.html.twig
 * - Material 3 design components
 * - Stimulus form validation controller
 * - CSRF protection via Symfony forms
 */
final class SecurityController extends AbstractController
{
    /**
     * Display login form.
     *
     * GET /login
     *
     * Template receives:
     * - last_username: email from failed login attempt (for pre-fill)
     * - error: authentication error message (if login failed)
     * - csrf_token_intention: 'authenticate' (for CSRF protection)
     *
     * @param AuthenticationUtils $authenticationUtils Symfony service for login errors
     * @return Response Rendered login form template
     */
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Redirect authenticated users away from login page
        // Use case: user navigates to /login while already logged in
        if ($this->getUser()) {
            return $this->redirectToRoute('generate_view');
        }

        // Get authentication error (if any) from last login attempt
        // Error is stored in session by Symfony Security on failed login
        $error = $authenticationUtils->getLastAuthenticationError();

        // Get last username (email) entered by user
        // Useful for pre-filling form after failed attempt
        // Prevents user frustration (no need to re-type email)
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    /**
     * Logout route.
     *
     * GET /logout
     *
     * Important: This method will NEVER be executed.
     * Symfony Security intercepts this route (defined in security.yaml)
     * and handles logout automatically:
     * - Invalidates session
     * - Clears security token
     * - Redirects to target (/) as configured
     *
     * This method exists only to:
     * - Generate route name for templates: path('app_logout')
     * - Provide IDE autocomplete and type hints
     *
     * @return void
     */
    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): void
    {
        // This code will never be executed
        // Symfony Security intercepts and handles logout
        // See: security.yaml -> firewalls.main.logout
        throw new \LogicException(
            'This method should never be reached. ' .
            'Logout is handled by Symfony Security. ' .
            'Check security.yaml configuration.'
        );
    }
}
