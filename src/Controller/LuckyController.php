<?php

// src/Controller/LuckyController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LuckyController extends AbstractController
{
    #[Route('/lucky/number')]
    public function number(): Response
    {
        $number = random_int(0, 100);

        $number = random_int(0, 100);

        return $this->render('lucky/number.html.twig', [
            'number' => $number,
        ]);
    }

    #[Route('/xdebug-info')]
    public function xdebugInfo(): Response
    {
        $info = [
            'xdebug_loaded' => extension_loaded('xdebug'),
            'xdebug_mode' => ini_get('xdebug.mode'),
            'xdebug_client_host' => ini_get('xdebug.client_host'),
            'xdebug_client_port' => ini_get('xdebug.client_port'),
            'xdebug_start_with_request' => ini_get('xdebug.start_with_request'),
        ];

        return new Response('<pre>'.print_r($info, true).'</pre>');
    }
}
