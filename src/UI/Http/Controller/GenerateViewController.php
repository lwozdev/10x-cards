<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for GET /generate route.
 *
 * Renders the AI flashcard generation view.
 */
#[Route('/generate', name: 'generate_view', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class GenerateViewController extends AbstractController
{
    public function __invoke(): Response
    {
        return $this->render('generate/index.html.twig');
    }
}
