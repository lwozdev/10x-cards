<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class KitchenSinkController extends AbstractController
{
    #[Route('/kitchen-sink', name: 'kitchen_sink')]
    public function index(): Response
    {
        // Sample data for components demonstration
        $navDestinations = [
            ['icon' => '#icon-home', 'label' => 'Strona główna', 'path' => '/', 'badge' => 0],
            ['icon' => '#icon-cards', 'label' => 'Fiszki', 'path' => '/sets', 'badge' => 12],
            ['icon' => '#icon-learn', 'label' => 'Nauka', 'path' => '/learn', 'badge' => 3],
            ['icon' => '#icon-profile', 'label' => 'Profil', 'path' => '/profile', 'badge' => 0],
        ];

        $sampleCards = [
            ['id' => 1, 'name' => 'Matematyka - Geometria', 'count' => 25, 'created' => '2 dni temu'],
            ['id' => 2, 'name' => 'Angielski A2', 'count' => 45, 'created' => '1 tydzień temu'],
            ['id' => 3, 'name' => 'Historia Polski', 'count' => 30, 'created' => '3 dni temu'],
        ];

        return $this->render('kitchen_sink/index.html.twig', [
            'navDestinations' => $navDestinations,
            'sampleCards' => $sampleCards,
            'currentPath' => '/kitchen-sink',
        ]);
    }
}
