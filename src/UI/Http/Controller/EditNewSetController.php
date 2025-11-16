<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for GET /sets/new/edit route.
 *
 * Renders the edit view for newly generated flashcard set.
 * Expects 'pending_set' data in session (from GenerateCardsController).
 */
#[Route('/sets/new/edit', name: 'edit_new_set', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class EditNewSetController extends AbstractController
{
    public function __invoke(Request $request): Response
    {
        // 1. Read pending_set from session
        $pendingSet = $request->getSession()->get('pending_set');

        // 2. If no data in session, redirect to /generate with flash message
        if ($pendingSet === null) {
            $this->addFlash('error', 'Brak danych do edycji. Wygeneruj najpierw fiszki.');
            return $this->redirectToRoute('generate_view');
        }

        // 3. Render template with data
        return $this->render('sets/edit_new.html.twig', [
            'jobId' => $pendingSet['job_id'] ?? null,
            'suggestedName' => $pendingSet['suggested_name'] ?? '',
            'cards' => $pendingSet['cards'] ?? [],
            'sourceText' => $pendingSet['source_text'] ?? '',
            'generatedCount' => $pendingSet['generated_count'] ?? 0,
        ]);
    }
}
