<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Home controller - redirects to admin panel
 */
class HomeController extends AbstractController
{
    /**
     * Homepage - redirect to admin users panel
     */
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        // Redirect to admin users panel
        return $this->redirectToRoute('admin_users_index');
    }
    
    /**
     * Admin dashboard - redirect to users management
     */
    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        // Redirect to admin users panel
        return $this->redirectToRoute('admin_users_index');
    }
}