<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ScaffoldDemoController extends AbstractController
{
    #[Route('/scaffold-demo', name: 'scaffold_demo')]
    public function index(): Response
    {
        $navDestinations = [
            ['icon' => '#icon-home', 'label' => 'Strona główna', 'path' => '/', 'badge' => 0],
            ['icon' => '#icon-cards', 'label' => 'Fiszki', 'path' => '/sets', 'badge' => 12],
            ['icon' => '#icon-learn', 'label' => 'Nauka', 'path' => '/learn', 'badge' => 3],
            ['icon' => '#icon-profile', 'label' => 'Profil', 'path' => '/profile', 'badge' => 0],
        ];

        $flashcards = [];
        for ($i = 1; $i <= 12; $i++) {
            $flashcards[] = [
                'id' => $i,
                'name' => 'Zestaw fiszek #' . $i,
                'count' => rand(10, 50),
                'created' => rand(1, 30) . ' dni temu',
            ];
        }

        return $this->render('scaffold_demo/index.html.twig', [
            'navDestinations' => $navDestinations,
            'flashcards' => $flashcards,
            'currentPath' => '/scaffold-demo',
        ]);
    }
}
