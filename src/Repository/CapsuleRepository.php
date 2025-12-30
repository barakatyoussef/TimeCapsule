<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Capsule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Capsule>
 */
class CapsuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Capsule::class);
    }

    // ... à l'intérieur de la classe CapsuleRepository

    public function findNextCapsule(User $user): ?Capsule
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.author = :user')
            ->andWhere('c.isSent = :sent')
            // On veut seulement celles dans le futur (ou maintenant)
            ->andWhere('c.sendDate > :now')
            ->setParameter('user', $user)
            ->setParameter('sent', false)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('c.sendDate', 'ASC') // La plus proche d'abord
            ->setMaxResults(1) // Une seule
            ->getQuery()
            ->getOneOrNullResult();
    }
}
