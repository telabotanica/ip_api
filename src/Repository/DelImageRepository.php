<?php

namespace App\Repository;

use App\Entity\DelImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DelImage>
 */
class DelImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DelImage::class);
    }

    public function findAllPaginated($criteres)
    {
        if (in_array($criteres['tri'], ['date_observation'], true)) {
            $criteres['tri'] = 'date_creation';
        }
        $queryBuilder = $this->createQueryBuilder('o');

        //TODO: ajouter les autres critères de recherche

        if ($criteres['masque_pninscritsseulement'] == 1) {
            $queryBuilder->andWhere('o.ce_utilisateur IS NOT NULL');
            $queryBuilder->andWhere('o.ce_utilisateur != 0');
        }

        $queryBuilder
            ->orderBy('o.' . $criteres['tri'], $criteres['ordre'])
            ->setMaxResults($criteres['navigation_limite'])
//            ->setFirstResult($criteres['page']*$criteres['limit']);
            ->setFirstResult($criteres['navigation_depart']);



        return $queryBuilder->getQuery()->getResult();
    }

    //    /**
    //     * @return DelImage[] Returns an array of DelImage objects
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

    //    public function findOneBySomeField($value): ?DelImage
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
