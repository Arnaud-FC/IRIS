<?php

namespace App\Repository;

use App\Entity\LanguagesAvailable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LanguagesAvailable>
 */
class LanguagesAvailableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LanguagesAvailable::class);
    }

    public function findByNameLike(string $name)
    {
        return $this->createQueryBuilder('l')
            ->where('LOWER(l.name) LIKE LOWER(:name)')
            ->setParameter('name', '%' . $name . '%')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return LanguagesAvailable[] Returns an array of LanguagesAvailable objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('l.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?LanguagesAvailable
    //    {
    //        return $this->createQueryBuilder('l')
    //            ->andWhere('l.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
