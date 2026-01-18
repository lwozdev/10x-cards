<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Temporary controller for testing authentication UI
 * DELETE THIS FILE after backend implementation.
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
