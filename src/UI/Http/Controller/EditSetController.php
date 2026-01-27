<?php

declare(strict_types=1);

namespace App\UI\Http\Controller;

use App\Domain\Repository\CardRepositoryInterface;
use App\Domain\Repository\SetRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for GET /sets/{id} route.
 *
 * Renders the edit view for an existing flashcard set.
 * Allows editing set name, modifying flashcard content, and deleting flashcards.
 */
#[Route('/sets/{id}', name: 'set_edit', methods: ['GET'])]
#[IsGranted('ROLE_USER')]
final class EditSetController extends AbstractController
{
    public function __construct(
        private readonly SetRepositoryInterface $setRepository,
        private readonly CardRepositoryInterface $cardRepository,
    ) {
    }

    public function __invoke(string $id): Response
    {
        // 1. Get current user
        /** @var \App\Domain\Model\User|null $user */
        $user = $this->getUser();
        if (null === $user) {
            return $this->redirectToRoute('app_login');
        }

        // 2. Find set by ID
        $set = $this->setRepository->findById($id);

        // 3. Verify set exists and belongs to current user
        if (null === $set || $set->isDeleted()) {
            $this->addFlash('error', 'Zestaw nie został znaleziony.');

            return $this->redirectToRoute('set_list');
        }

        if (!$set->getOwnerId()->equals($user->getId())) {
            $this->addFlash('error', 'Nie masz uprawnień do edycji tego zestawu.');

            return $this->redirectToRoute('set_list');
        }

        // 4. Get all active cards for this set
        $cards = $this->cardRepository->findActiveBySetId($id);

        // 5. Transform cards to array for template
        $cardsData = array_map(
            fn ($card) => [
                'id' => $card->getId(),
                'front' => $card->getFront()->toString(),
                'back' => $card->getBack()->toString(),
                'origin' => $card->getOrigin()->value,
            ],
            $cards
        );

        // 6. Render template with data
        return $this->render('sets/edit.html.twig', [
            'setId' => $set->getId(),
            'setName' => $set->getName()->toString(),
            'cards' => $cardsData,
            'cardCount' => count($cardsData),
            'isAiGenerated' => $set->isAiGenerated(),
        ]);
    }
}
