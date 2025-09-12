<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_login');
    }

    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    public function adminPanel(): Response
    {
        return $this->redirectToRoute('admin_users_index');
    }
}
