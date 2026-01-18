<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Domain\Model\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Domain\Value\Email;
use App\Domain\Value\UserId;
use App\Form\RegistrationFormType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

/**
 * Registration Controller - handles user registration with email verification.
 *
 * Responsibilities (Thin Controller pattern):
 * - Render registration form
 * - Validate registration data (via RegistrationFormType)
 * - Create new User entity
 * - Hash password securely
 * - Send verification email with signed URL
 * - Handle email verification link clicks
 * - Mark user as verified in database
 *
 * Registration flow (auth-spec.md section 3.2):
 * 1. GET /register - render registration form
 * 2. POST /register - validate and create user (NOT auto-logged in)
 * 3. Send verification email with token
 * 4. Redirect to "check your email" page
 * 5. User clicks link in email
 * 6. GET /verify/email?token={token} - verify and mark user as verified
 * 7. Redirect to login with success message
 *
 * Security features:
 * - Password hashing (bcrypt/argon2 via UserPasswordHasherInterface)
 * - Email uniqueness validation
 * - Password strength validation (minimum 8 chars, medium strength)
 * - CSRF protection on form
 * - Signed URLs for verification (prevents tampering)
 * - No auto-login until email verified (prevents spam registrations)
 *
 * Templates:
 * - templates/registration/register.html.twig (form)
 * - templates/registration/check_email.html.twig (confirmation message)
 * - templates/registration/verification_email.html.twig (email template)
 */
final class RegistrationController extends AbstractController
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly VerifyEmailHelperInterface $verifyEmailHelper,
        private readonly MailerInterface $mailer,
    ) {
    }

    /**
     * Display registration form and handle registration submission.
     *
     * GET/POST /register
     *
     * Validation:
     * - Email format and uniqueness (auth-spec.md section 1.3.1)
     * - Password min 8 chars, medium strength
     * - Password confirmation matches
     * - Terms acceptance checkbox
     *
     * On success:
     * - Create User entity with isVerified=false
     * - Hash password
     * - Save to database
     * - Send verification email
     * - Redirect to "check your email" page
     *
     * On failure:
     * - Re-render form with validation errors
     * - Pre-fill email field
     *
     * @param Request                     $request        HTTP request
     * @param UserPasswordHasherInterface $passwordHasher Symfony service for password hashing
     *
     * @return Response Rendered form or redirect
     */
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        // Redirect authenticated users away from registration
        if ($this->getUser()) {
            return $this->redirectToRoute('generate_view');
        }

        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Validate password confirmation matches (client-side already checked)
            $plainPassword = $form->get('plainPassword')->getData();
            $passwordConfirm = $form->get('passwordConfirm')->getData();

            if ($plainPassword !== $passwordConfirm) {
                $this->addFlash('error', 'Hasła nie są identyczne');

                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ], new Response('', 422));
            }

            try {
                $email = Email::fromString($data['email']);

                // Check if email already exists (auth-spec.md section 1.3.1)
                if ($this->userRepository->exists($email)) {
                    $this->addFlash('error', 'Ten adres email jest już zarejestrowany');

                    return $this->render('registration/register.html.twig', [
                        'registrationForm' => $form->createView(),
                    ], new Response('', 422));
                }

                // Create temporary user object for password hashing
                // (User entity requires hashed password, but hasher needs User object)
                $tempUser = User::create(
                    id: UserId::fromString(Uuid::v4()->toString()),
                    email: $email,
                    passwordHash: str_repeat('x', 60), // Placeholder hash (minimum 60 chars)
                    createdAt: new \DateTimeImmutable(),
                    isVerified: false
                );

                // Hash the password using the temporary user object
                $hashedPassword = $passwordHasher->hashPassword($tempUser, $plainPassword);

                // Create the real user with the hashed password
                $user = User::create(
                    id: UserId::fromString(Uuid::v4()->toString()),
                    email: $email,
                    passwordHash: $hashedPassword,
                    createdAt: new \DateTimeImmutable(),
                    isVerified: false // User must verify email first
                );

                // Persist user to database
                $this->userRepository->save($user);

                // Send verification email
                $this->sendVerificationEmail($user);

                // Redirect to "check your email" page
                return $this->redirectToRoute('app_verify_email_sent');
            } catch (\InvalidArgumentException $e) {
                $this->addFlash('error', $e->getMessage());

                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ], new Response('', 422));
            }
        }

        // If form was submitted but has validation errors, return 422 for Turbo
        // Otherwise return 200 for initial GET request
        $response = $form->isSubmitted() && !$form->isValid()
            ? new Response('', 422)
            : new Response();

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ], $response);
    }

    /**
     * Display "check your email" confirmation page.
     *
     * GET /register/check-email
     *
     * Shows message:
     * "Sprawdź swoją skrzynkę email. Wysłaliśmy link aktywacyjny."
     *
     * @return Response Rendered confirmation page
     */
    #[Route('/register/check-email', name: 'app_verify_email_sent')]
    public function checkEmail(): Response
    {
        return $this->render('registration/check_email.html.twig');
    }

    /**
     * Handle email verification from link in email.
     *
     * GET /verify/email?token={signed_token}
     *
     * Flow:
     * 1. Extract and validate signed token from URL
     * 2. Load user by ID from token
     * 3. Verify signature (prevents tampering)
     * 4. Check expiration (1 hour TTL)
     * 5. Mark user as verified
     * 6. Save to database
     * 7. Show success message
     * 8. Redirect to login
     *
     * Security:
     * - Signed URLs (HMAC signature)
     * - Time-based expiration (1 hour)
     * - One-time use (checked via isVerified flag)
     *
     * Error handling:
     * - Invalid signature: "Link weryfikacyjny jest nieprawidłowy"
     * - Expired link: "Link weryfikacyjny wygasł. Poproś o nowy"
     * - Already verified: redirect to login silently
     * - User not found: error message
     *
     * @param Request $request HTTP request with token in query string
     *
     * @return Response Redirect to login or error page
     */
    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request): Response
    {
        // Extract user ID from signed URL
        // VerifyEmailHelper validates signature and expiration
        try {
            $userId = $request->query->get('id');

            if (!$userId) {
                throw new \RuntimeException('Brak ID użytkownika w linku');
            }

            $user = $this->userRepository->findById(UserId::fromString($userId));

            if (!$user) {
                $this->addFlash('error', 'Użytkownik nie został znaleziony');

                return $this->redirectToRoute('app_register');
            }

            // Validate signed URL signature and expiration
            $this->verifyEmailHelper->validateEmailConfirmation(
                $request->getUri(),
                $user->getId()->toString(),
                $user->getEmail()->toString()
            );

            // Check if already verified (prevents duplicate verification)
            if ($user->isVerified()) {
                $this->addFlash('info', 'Email został już zweryfikowany. Możesz się zalogować.');

                return $this->redirectToRoute('app_login');
            }

            // Mark user as verified
            $user->markAsVerified();
            $this->userRepository->save($user);

            // Success message
            $this->addFlash('success', 'Email został zweryfikowany! Możesz się teraz zalogować.');

            return $this->redirectToRoute('app_login');
        } catch (VerifyEmailExceptionInterface $e) {
            // Handle verification errors (invalid signature, expired, etc.)
            $this->addFlash('error', 'Link weryfikacyjny jest nieprawidłowy lub wygasł. Zarejestruj się ponownie.');

            return $this->redirectToRoute('app_register');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wystąpił błąd podczas weryfikacji: '.$e->getMessage());

            return $this->redirectToRoute('app_register');
        }
    }

    /**
     * Send verification email with signed URL.
     *
     * Email contains:
     * - Welcome message
     * - Verification link (signed, 1 hour expiration)
     * - Instructions ("Click link to activate account")
     *
     * Template: templates/registration/verification_email.html.twig
     *
     * Signed URL structure:
     * /verify/email?token={signature}&id={user_id}&expires={timestamp}
     *
     * @param User $user User entity to send verification to
     *
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    private function sendVerificationEmail(User $user): void
    {
        // Generate signed verification URL
        // Signature prevents tampering, expires in 1 hour
        $signatureComponents = $this->verifyEmailHelper->generateSignature(
            routeName: 'app_verify_email',
            userId: $user->getId()->toString(),
            userEmail: $user->getEmail()->toString(),
            extraParams: ['id' => $user->getId()->toString()]
        );

        // Create email from Twig template
        $email = (new TemplatedEmail())
            ->from(new Address('noreply@fiszki-ai.pl', 'Fiszki AI'))
            ->to($user->getEmail()->toString())
            ->subject('Potwierdź swój adres email - Fiszki AI')
            ->htmlTemplate('registration/verification_email.html.twig')
            ->context([
                'signedUrl' => $signatureComponents->getSignedUrl(),
                'expiresAtMessageKey' => $signatureComponents->getExpirationMessageKey(),
                'expiresAtMessageData' => $signatureComponents->getExpirationMessageData(),
            ]);

        // Send email
        $this->mailer->send($email);
    }
}
