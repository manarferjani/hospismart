<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TestController extends AbstractController
{
    #[Route('/test', name: 'app_test')]
    public function test(): Response
    {
        return new Response('<h1>Test OK - Symfony fonctionne!</h1><p>Si vous voyez ce message, Symfony répond correctement.</p>');
    }
}
