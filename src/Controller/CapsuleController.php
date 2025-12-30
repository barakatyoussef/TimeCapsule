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
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/capsule')]
final class CapsuleController extends AbstractController
{
    #[Route('/', name: 'app_capsule_index', methods: ['GET'])]
    public function index(CapsuleRepository $capsuleRepository): Response
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $capsules = $capsuleRepository->findBy(['author' => $user]);
        $nextCapsule = $capsuleRepository->findNextCapsule($user);

        return $this->render('capsule/index.html.twig', [
            'capsules' => $capsules,
            'nextCapsule' => $nextCapsule,
        ]);
    }

    #[Route('/new', name: 'app_capsule_new', methods: ['GET', 'POST'])]
    // 👇 J'ai ajouté CapsuleRepository ici pour que ça marche !
    public function new(Request $request, EntityManagerInterface $entityManager, Security $security, CapsuleRepository $capsuleRepository): Response
    {
        $user = $security->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // --- VERIFICATION DES 5 CAPSULES / JOUR ---

        // 1. On définit "Aujourd'hui à 00h00:00"
        $todayMidnight = new \DateTime('today midnight');

        // 2. On compte combien de capsules cet utilisateur a fait DEPUIS minuit
        $todaysCapsules = $capsuleRepository->createQueryBuilder('c')
            ->select('count(c.id)')
            ->where('c.author = :user')
            ->andWhere('c.createdAt >= :date') // Seulement celles créées APRES minuit
            ->setParameter('user', $user)
            ->setParameter('date', $todayMidnight)
            ->getQuery()
            ->getSingleScalarResult();

        // 3. Si c'est 5 ou plus, on bloque
        if ($todaysCapsules >= 5) {
            $this->addFlash('error', 'Oula ! Tu as déjà créé 5 capsules aujourd\'hui. Reviens demain !');
            return $this->redirectToRoute('app_capsule_index');
        }

        // --- FIN DE LA VERIFICATION ---

        $capsule = new Capsule();
        $capsule->setTargetEmail($user->getEmail());
        $form = $this->createForm(CapsuleType::class, $capsule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $capsule->setAuthor($user);
            $capsule->setCreatedAt(new \DateTimeImmutable());
            $capsule->setIsSent(false);

            $this->handleImageUpload($form, $capsule);

            $entityManager->persist($capsule);
            $entityManager->flush();

            $this->addFlash('success', 'Capsule créée avec succès !');
            return $this->redirectToRoute('app_capsule_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('capsule/new.html.twig', [
            'capsule' => $capsule,
            'form' => $form,
        ]);
    }

    // 👉 MODIFICATION ICI : On oblige l'ID à être un nombre (\d+)
    #[Route('/{id}', name: 'app_capsule_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Capsule $capsule): Response
    {
        return $this->render('capsule/show.html.twig', [
            'capsule' => $capsule,
        ]);
    }

    // 👉 MODIFICATION ICI AUSSI
    #[Route('/{id}/edit', name: 'app_capsule_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, Capsule $capsule, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CapsuleType::class, $capsule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $capsule);
            $entityManager->flush();

            $this->addFlash('success', 'Capsule modifiée !');
            return $this->redirectToRoute('app_capsule_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('capsule/edit.html.twig', [
            'capsule' => $capsule,
            'form' => $form,
        ]);
    }

    // 👉 ET MODIFICATION ICI ENFIN
    #[Route('/{id}', name: 'app_capsule_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, Capsule $capsule, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $capsule->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($capsule);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_capsule_index', [], Response::HTTP_SEE_OTHER);
    }

    private function handleImageUpload($form, Capsule $capsule): void
    {
        /** @var UploadedFile $imageFile */
        $imageFile = $form->get('imageFile')->getData();

        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $newFilename = 'image_' . uniqid() . '.' . $imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads',
                    $newFilename
                );
                $capsule->setImageFilename($newFilename);
            } catch (FileException $e) {
                // Erreur silencieuse
            }
        }
    }
}
