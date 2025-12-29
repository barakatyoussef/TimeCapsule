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

        // 1. Trouver les capsules à envoyer (Date dépassée ET pas encore envoyée)
        // On cherche tout ce qui est <= à "Maintenant"
        $now = new \DateTimeImmutable();

        // Note: Idéalement, on crée une méthode findDueCapsules dans le Repository,
        // mais pour faire simple, on filtre ici ou on utilise une requête simple.
        // Faisons une requête custom rapide :
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
            $io->text("Envoi de la capsule ID " . $capsule->getId() . " vers " . $capsule->getTargetEmail());

            // 2. Créer l'email
            $email = (new Email())
                ->from('admin@timecapsule.com') // L'adresse d'envoi (sera remplacée par ton Gmail automatiquement)
                ->to($capsule->getTargetEmail())
                ->subject('⏳ Une capsule temporelle vient de s\'ouvrir : ' . $capsule->getTitle())
                ->text($capsule->getContent()) // Version texte simple
                ->html('
                    <h1>⏳ Time Capsule Arrivée !</h1>
                    <p>Bonjour,</p>
                    <p>Quelqu\'un a voulu vous envoyer un message depuis le passé.</p>
                    <hr>
                    <h3>' . $capsule->getTitle() . '</h3>
                    <p>' . nl2br($capsule->getContent()) . '</p>
                    <hr>
                    <p><small>Envoyé via TimeCapsule App</small></p>
                ');

            // 3. Envoyer
            try {
                $this->mailer->send($email);

                // 4. Marquer comme envoyée
                $capsule->setIsSent(true);
                $this->entityManager->persist($capsule); // Sauvegarder le changement d'état

                $io->success("Capsule envoyée !");
            } catch (\Exception $e) {
                $io->error("Erreur lors de l'envoi : " . $e->getMessage());
            }
        }

        // Sauvegarder tout en base de données
        $this->entityManager->flush();

        $io->success('Terminé ! Toutes les capsules prêtes ont été envoyées.');

        return Command::SUCCESS;
    }
}
