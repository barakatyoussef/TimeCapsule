<?php

namespace App\Command;

use App\Entity\Capsule;
use App\Repository\CapsuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

#[AsCommand(
    name: 'app:send-capsules',
    description: 'Vérifie et envoie les capsules temporelles dues aujourd\'hui',
)]
class SendCapsulesCommand extends Command
{
    public function __construct(
        private CapsuleRepository $capsuleRepository,
        private MailerInterface $mailer,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $now = new \DateTimeImmutable();

        // On cherche les capsules non envoyées dont la date est passée
        $capsules = $this->capsuleRepository->createQueryBuilder('c')
            ->where('c.isSent = :status')
            ->andWhere('c.sendDate <= :now')
            ->setParameter('status', false)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        $count = count($capsules);
        $io->section("🔍 Recherche de capsules... $count trouvée(s).");

        foreach ($capsules as $capsule) {
            $io->text("Traitement de la capsule ID " . $capsule->getId());

            // --- CORRECTION ICI : On utilise getAuthor() au lieu de getUser() ---

            // On vérifie si un auteur existe (même si ta base dit nullable=false, c'est plus sûr)
            if ($capsule->getAuthor()) {
                $senderEmail = $capsule->getAuthor()->getEmail();
            } else {
                $senderEmail = 'youssefbarakat892@gmail.com'; // Fallback pour les vieilles données
            }

            // Ton email Admin (celui du .env)
            $adminEmail = 'youssefbarakat892@gmail.com';

            $email = (new Email())
                // FROM : Ton mail admin + Nom de l'auteur
                ->from(new Address($adminEmail, $senderEmail . ' (via TimeCapsule)'))

                ->to($capsule->getTargetEmail())
                ->subject('⏳ Une capsule temporelle vient de s\'ouvrir !');

            // REPLY-TO : Si on a un auteur, on met son email
            if ($capsule->getAuthor()) {
                $email->replyTo($senderEmail);
            }

            // --- CONTENU HTML ---
            $htmlContent = '
                <div style="font-family: \'Segoe UI\', Tahoma, Geneva, Verdana, sans-serif; color: #333; max-width: 600px; margin: 0 auto; background-color: #f9fafb; padding: 20px; border-radius: 10px; border: 1px solid #e5e7eb;">
                    <div style="text-align: center; margin-bottom: 20px;">
                        <h1 style="color: #2563eb; margin-bottom: 5px;">⏳ TimeCapsule</h1>
                        <p style="color: #6b7280; font-size: 14px;">Un message du passé a refait surface.</p>
                    </div>

                    <div style="background-color: #ffffff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                        <h2 style="color: #111827; margin-top: 0;">' . htmlspecialchars($capsule->getTitle()) . '</h2>
                        <p style="font-size: 16px; line-height: 1.6; color: #374151; white-space: pre-wrap;">' . htmlspecialchars($capsule->getContent()) . '</p>
                    </div>';

            // --- IMAGE ---
            if ($capsule->getImageFilename()) {
                $imagePath = 'public/uploads/' . $capsule->getImageFilename();

                if (file_exists($imagePath)) {
                    $cid = 'image-capsule-' . $capsule->getId();
                    $email->embedFromPath($imagePath, $cid);
                    $htmlContent .= '
                    <div style="margin-top: 20px; text-align: center;">
                        <img src="cid:' . $cid . '" style="max-width: 100%; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);" alt="Souvenir visuel">
                    </div>';
                }
            }

            $htmlContent .= '
                    <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 30px 0;">
                    <p style="text-align: center; color: #9ca3af; font-size: 12px;">
                        Ce message a été écrit par <strong>' . $senderEmail . '</strong> et scellé jusqu\'à aujourd\'hui.
                        <br>Envoyé via <a href="#" style="color: #2563eb; text-decoration: none;">TimeCapsule App</a>
                    </p>
                </div>';

            $email->html($htmlContent);

            // --- ENVOI ---
            try {
                $this->mailer->send($email);

                $capsule->setIsSent(true);
                // On n'a pas besoin de persist() car l'objet est déjà géré par Doctrine,
                // mais le flush() à la fin sauvera tout.

                $io->success("Capsule ID " . $capsule->getId() . " envoyée à " . $capsule->getTargetEmail());
            } catch (\Exception $e) {
                $io->error("Erreur pour la capsule ID " . $capsule->getId() . " : " . $e->getMessage());
            }
        }

        $this->entityManager->flush();
        $io->success('Terminé ! Toutes les capsules ont été traitées.');

        return Command::SUCCESS;
    }
}
