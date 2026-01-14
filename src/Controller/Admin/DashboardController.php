<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Capsule;
use App\Repository\UserRepository;
use App\Repository\CapsuleRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    // On injecte les repositories pour compter les données
    public function __construct(
        private UserRepository $userRepository,
        private CapsuleRepository $capsuleRepository
    ) {}

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // 1. Récupérer les stats des Capsules
        $capsulesEnvoyees = $this->capsuleRepository->count(['isSent' => true]);
        $capsulesAttente = $this->capsuleRepository->count(['isSent' => false]);

        // 2. Récupérer les stats des Users
        $usersVerifies = $this->userRepository->count(['isVerified' => true]);
        $usersNonVerifies = $this->userRepository->count(['isVerified' => false]);

        // 3. Afficher la vue avec les données
        return $this->render('admin/dashboard.html.twig', [
            'capsules_sent' => $capsulesEnvoyees,
            'capsules_wait' => $capsulesAttente,
            'users_verified' => $usersVerifies,
            'users_unverified' => $usersNonVerifies,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('⏳ Time Capsule Admin');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de Bord', 'fa fa-home');
        yield MenuItem::section('Gestion');
        yield MenuItem::linkToCrud('Utilisateurs', 'fas fa-users', User::class);
        yield MenuItem::linkToCrud('Capsules', 'fas fa-hourglass', Capsule::class);
        yield MenuItem::section('Site Web');
        yield MenuItem::linkToRoute('Retour au site', 'fas fa-arrow-left', 'app_home');
    }

    #[Route('/', name: 'app_home')]
    public function retour(): Response
    {
        // Redirige vers la page de capsules (ou login)
        return $this->redirectToRoute('app_capsule_index');
    }
}
