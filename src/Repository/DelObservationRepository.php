<?php

namespace App\Repository;

use App\Entity\DelCommentaire;
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

    public function findAllPaginated($criteres, $filters)
    {
        $userId = null;
        if (isset($criteres['user'])) {
            $userId = $criteres['user']['id_utilisateur'];
        }

        $queryBuilder = $this->createQueryBuilder('o')
            ->leftJoin('o.commentaires', 'c')
            ->groupBy('o.id_observation');

        $queryBuilder = $this->addTriToQueryBuilder($queryBuilder, $criteres);
        $queryBuilder = $this->addTypeToQueryBuilder($queryBuilder, $criteres['masque.type'], $userId);
        $queryBuilder = $this->addInscritsSeulementToQueryBuilder($queryBuilder, $criteres['masque.pninscritsseulement']);
        $queryBuilder = $this->addFiltersToQueryBuilder($queryBuilder, $filters);

        $queryBuilder
            ->setMaxResults($criteres['navigation.limite'])
//            ->setFirstResult($criteres['page']*$criteres['limit']);
            ->setFirstResult($criteres['navigation.depart']);

        return $queryBuilder->getQuery()->getResult();
    }

    public function findTotalByCriterieas($criteres, $filters)
    {
        $userId = null;
        if (isset($criteres['user'])) {
            $userId = $criteres['user']['id_utilisateur'];
        }

        $queryBuilder = $this->createQueryBuilder('o');

        if ($criteres['masque.type'] == 'monactivite'){
            $queryBuilder = $this->countMonActivite($queryBuilder, $criteres);
        } else {
            $queryBuilder = $this->addInscritsSeulementToQueryBuilder($queryBuilder, $criteres['masque.pninscritsseulement']);
            $queryBuilder = $this->addTypeToQueryBuilder($queryBuilder, $criteres['masque.type'], $userId);
            $queryBuilder = $this->addFiltersToQueryBuilder($queryBuilder, $filters);
        }

        return $queryBuilder->select('count(o.id_observation)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function addTriToQueryBuilder($queryBuilder, $criteres)
    {
        if ($criteres['tri'] === 'nb_commentaires') {
            $queryBuilder->addSelect('COUNT(c.id_commentaire) AS HIDDEN nb_commentaires')
                ->orderBy('nb_commentaires', $criteres['ordre']);
        } else if (in_array($criteres['tri'], ['date_transmission', 'date_observation'], true)) {
            $queryBuilder->orderBy('o.' . $criteres['tri'], $criteres['ordre']);
        }
        return $queryBuilder;
    }

    private function addTypeToQueryBuilder($queryBuilder, $type, $userId = null)
    {
        if ($type == 'adeterminer') {
            $queryBuilder->andWhere('o.certitude = :certitude')
                ->setParameter('certitude', 'à déterminer');
        }

        if ($type == 'aconfirmer') {
            $queryBuilder->andWhere('o.certitude = :certitude')
                ->setParameter('certitude', 'douteux');
        }

        if ($type == 'validees') {
            $queryBuilder->andWhere('o.certitude = :certitude')
                ->setParameter('certitude', 'certain');
        }
/*
        if ($type == 'monactivite') {
            $queryBuilder
                ->andWhere('o.ce_utilisateur = :utilisateur')
                ->orWhere('c.ce_utilisateur = :utilisateur')
                ->setParameter('utilisateur', $userId);
        }
*/

        return $queryBuilder;
    }

    private function addFiltersToQueryBuilder($queryBuilder, $filters)
    {
        foreach ($filters as $filter) {
            if ($filter->getQueryParameter() == 'masque_auteur') {
                $queryBuilder
                    ->andWhere('o.ce_utilisateur = :auteur_id')
                    ->orWhere('o.nom_utilisateur LIKE :nom')
                    ->orWhere('o.prenom_utilisateur LIKE :nom')
                    ->orWhere('o.courriel_utilisateur LIKE :nom')
                    ->setParameter('auteur_id', $filter->getValue())
                    ->setParameter('nom', '%' . $filter->getValue() . '%');
            } else if ($filter->getQueryParameter() == 'masque') {
                $queryBuilder
                    ->andWhere('o.nom_ret LIKE :masque')
                    ->orWhere('o.nom_sel LIKE :masque')
                    ->orWhere('o.famille LIKE :masque')
                    ->orWhere('o.nom_utilisateur LIKE :masque')
                    ->orWhere('o.courriel_utilisateur LIKE :masque')
                    ->setParameter('masque', '%' . $filter->getValue() . '%');
            } else if ($filter->getQueryParameter() == 'masque_departement') {
                $queryBuilder
                    ->andWhere('SUBSTRING(o.ce_zone_geo, 1, 2) = :departement')
                    ->setParameter('departement', substr($filter->getValue(), 0, 2));
            } else if ($filter->getIsExact()) {
                $queryBuilder->andWhere('o.' . $filter->getBddColumn() . ' = :' . $filter->getQueryParameter())
                    ->setParameter($filter->getQueryParameter(), $filter->getValue());
            } else {
                $queryBuilder->andWhere('o.' . $filter->getBddColumn() . ' LIKE :' . $filter->getQueryParameter())
                    ->setParameter($filter->getQueryParameter(), '%' . $filter->getValue() . '%');
            }
        }
        return $queryBuilder;
    }

    private function addInscritsSeulementToQueryBuilder($queryBuilder, $pninscritsseulement)
    {
        if ($pninscritsseulement == 1) {
            $queryBuilder->andWhere('o.ce_utilisateur != 0');
        }

        return $queryBuilder;
    }

    public function findMonActivite($criteres){
        $userId = null;
        if (isset($criteres['user'])) {
            $userId = $criteres['user']['id_utilisateur'];
        }

        $queryBuilder = $this->createQueryBuilder('o')
            ->groupBy('o.id_observation');

        // Récupérer les commentaires de l'utilisateur
        $commentaireObservationIds = $this->getEntityManager()
            ->getRepository(DelCommentaire::class)
            ->createQueryBuilder('c')
            ->select('IDENTITY(c.ce_observation) as ce_observation') // Utilisez IDENTITY pour référencer correctement le champ
            ->where('c.ce_utilisateur = :utilisateur')
            ->setParameter('utilisateur', $userId)
            ->groupBy('c.ce_observation')
            ->getQuery()
            ->getResult();

        // Extraire les IDs des observations des commentaires
        $commentaireObservationIds = array_column($commentaireObservationIds, 'ce_observation');

        // Ajouter les critères de tri
        $queryBuilder = $this->addTriToQueryBuilder($queryBuilder, $criteres);

        // Ajouter les conditions pour les observations de l'utilisateur ou des commentaires
        $queryBuilder
            ->andWhere('o.ce_utilisateur = :utilisateur OR o.id_observation IN (:commentaireObservationIds)')
            ->setParameter('utilisateur', $userId)
            ->setParameter('commentaireObservationIds', $commentaireObservationIds);

        // Appliquer la pagination
        $queryBuilder
            ->setMaxResults($criteres['navigation.limite'])
            ->setFirstResult($criteres['navigation.depart']);

        return $queryBuilder->getQuery()->getResult();
    }

    public function countMonActivite($queryBuilder, $criteres){
        $userId = null;
        if (isset($criteres['user'])) {
            $userId = $criteres['user']['id_utilisateur'];
        }

        // Récupérer les commentaires de l'utilisateur
        $commentaireObservationIds = $this->getEntityManager()
            ->getRepository(DelCommentaire::class)
            ->createQueryBuilder('c')
            ->select('IDENTITY(c.ce_observation) as ce_observation') // Utilisez IDENTITY pour référencer correctement le champ
            ->where('c.ce_utilisateur = :utilisateur')
            ->setParameter('utilisateur', $userId)
            ->groupBy('c.ce_observation')
            ->getQuery()
            ->getResult();

        // Extraire les IDs des observations des commentaires
        $commentaireObservationIds = array_column($commentaireObservationIds, 'ce_observation');

        $queryBuilder
            ->andWhere('o.ce_utilisateur = :utilisateur OR o.id_observation IN (:commentaireObservationIds)')
            ->setParameter('utilisateur', $userId)
            ->setParameter('commentaireObservationIds', $commentaireObservationIds);

        return $queryBuilder;
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
