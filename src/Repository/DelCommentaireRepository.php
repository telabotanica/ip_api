<?php

namespace App\Repository;

use App\Entity\DelCommentaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DelCommentaire>
 */
class DelCommentaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DelCommentaire::class);
    }

    public function findAllPaginated(Array $criteres)
    {
        $queryBuilder = $this->createQueryBuilder('c');

        if ($criteres['pnInscrit'] == 1) {
            $queryBuilder->andWhere('c.ce_utilisateur != 0');
        }

        if (isset($criteres['auteur']) && $criteres['auteur'] != '') {
            $queryBuilder = $this->addAuthorCriteria($criteres, $queryBuilder);
        }

        $queryBuilder
            ->orderBy('c.date', $criteres['order'])
            ->setMaxResults($criteres['limit'])
            ->setFirstResult($criteres['page']*$criteres['limit']);

        return $queryBuilder->getQuery()->getResult();
    }

    private function addAuthorCriteria($criteres, $queryBuilder)
    {
        $queryBuilder
            ->andWhere('c.ce_utilisateur = :auteur_id')
            ->orWhere('c.utilisateur_nom LIKE :nom')
            ->orWhere('c.utilisateur_prenom LIKE :nom')
            ->orWhere('c.utilisateur_courriel LIKE :nom')
            ->setParameter('auteur_id', $criteres['auteur'])
            ->setParameter('nom', '%' . $criteres['auteur'] . '%');

        return $queryBuilder;
    }

    //    /**
    //     * @return DelCommentaire[] Returns an array of DelCommentaire objects
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

    //    public function findOneBySomeField($value): ?DelCommentaire
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
