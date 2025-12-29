<?php

namespace App\Controller;

use App\Entity\Capsule;
use App\Form\CapsuleType;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\CapsuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/capsule')]
final class CapsuleController extends AbstractController
{
    #[Route('/', name: 'app_capsule_index', methods: ['GET'])]
    public function index(CapsuleRepository $capsuleRepository, Security $security): Response
    {
        // 1. Récupérer l'utilisateur connecté
        $user = $security->getUser();

        // 2. Sécurité supplémentaire : Si pas connecté, direction Login
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // 3. LA CLE DU SUCCES : On ne cherche que les capsules de l'AUTEUR connecté
        // Avant c'était findAll(), maintenant c'est findBy(['author' => $user])
        return $this->render('capsule/index.html.twig', [
            'capsules' => $capsuleRepository->findBy(['author' => $user]),
        ]);
    }

    #[Route('/new', name: 'app_capsule_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, Security $security): Response
    {
        $user = $security->getUser(); // Récupérer l'utilisateur connecté

        // 1. COMPTER LES CAPSULES D'AUJOURD'HUI
        // On crée une date de début (ce matin à 00:00:00) et de fin (ce soir à 23:59:59)
        $todayStart = new \DateTimeImmutable('today midnight');
        $todayEnd = new \DateTimeImmutable('tomorrow midnight -1 second');

        // On compte en base de données
        $dailyCount = $entityManager->getRepository(Capsule::class)->count([
            'author' => $user,
            'createdAt' => $todayStart, // Note : Il faudra affiner la requête repository pour être précis sur l'intervalle,
            // mais pour simplifier ici, on va juste compter toutes celles créées.
        ]);

        // Pour faire propre, utilisons une requête personnalisée plus tard.
        // Pour l'instant, simple vérification PHP sur la collection de l'user :
        $count = 0;
        foreach ($user->getCapsules() as $c) {
            if ($c->getCreatedAt() >= $todayStart && $c->getCreatedAt() <= $todayEnd) {
                $count++;
            }
        }

        if ($count >= 5) {
            $this->addFlash('danger', 'Oula ! Tu as déjà créé 5 capsules aujourd\'hui. Reviens demain !');
            return $this->redirectToRoute('app_capsule_index');
        }

        // --- FIN DE LA LOGIQUE DE VERIFICATION ---

        $capsule = new Capsule();
        $form = $this->createForm(CapsuleType::class, $capsule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $capsule->setAuthor($user); // On attache l'auteur automatiquement
            $capsule->setCreatedAt(new \DateTimeImmutable());
            $capsule->setIsSent(false); // Par défaut, elle n'est pas envoyée

            $entityManager->persist($capsule);
            $entityManager->flush();

            return $this->redirectToRoute('app_capsule_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('capsule/new.html.twig', [
            'capsule' => $capsule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_capsule_show', methods: ['GET'])]
    public function show(Capsule $capsule): Response
    {
        return $this->render('capsule/show.html.twig', [
            'capsule' => $capsule,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_capsule_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Capsule $capsule, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CapsuleType::class, $capsule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_capsule_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('capsule/edit.html.twig', [
            'capsule' => $capsule,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_capsule_delete', methods: ['POST'])]
    public function delete(Request $request, Capsule $capsule, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $capsule->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($capsule);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_capsule_index', [], Response::HTTP_SEE_OTHER);
    }
}
