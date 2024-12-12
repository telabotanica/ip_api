<?php

namespace App\Repository;

use App\Entity\DelObservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DelObservation>
 */
class DelObservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DelObservation::class);
    }

    public function findAllPaginated($criteres)
    {
        $queryBuilder = $this->createQueryBuilder('d');

        if (in_array($criteres['tri'], ['date_transmission', 'date_observation'], true)) {
            $queryBuilder->orderBy('d.' . $criteres['tri'], $criteres['order']);
        }

        //TODO pour nb_commentaires

        $queryBuilder
            ->setMaxResults($criteres['limit'])
            ->setFirstResult($criteres['page']*$criteres['limit']);

        if ($criteres['pnInscrit'] == 1) {
            $queryBuilder->andWhere('d.ce_utilisateur != 0');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    //    /**
    //     * @return DelObservation[] Returns an array of DelObservation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?DelObservation
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
