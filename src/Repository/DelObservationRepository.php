<?php

namespace App\Repository;

use App\Entity\DelCommentaire;
use App\Entity\DelImage;
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

        // Affiche la requête sql pour debug
//        $sql = $queryBuilder->getQuery()->getSQL();
//        dd($sql);

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
            if ($filter->getQueryParameter() == 'masque_tag') {
                $queryBuilder
                    ->leftJoin('App\Entity\DelImage', 'i', 'WITH', 'i.ce_observation = o.id_observation')
                    ->andWhere(
                        $queryBuilder->expr()->orX(
                            $queryBuilder->expr()->like('i.mots_cles_texte', ':masque_tag'),
                            $queryBuilder->expr()->like('o.mots_cles_texte', ':masque_tag')
                        )
                    )
                    ->setParameter('masque_tag', '%' . $filter->getValue() . '%');
            } else if ($filter->getQueryParameter() == 'masque_auteur') {
                $queryBuilder
                    ->andWhere(
                        $queryBuilder->expr()->orX(
                            'o.ce_utilisateur = :auteur_id',
                            'o.nom_utilisateur LIKE :nom',
                            'o.prenom_utilisateur LIKE :nom',
                            'o.courriel_utilisateur LIKE :nom'
                        )
                    )
                    ->setParameter('auteur_id', $filter->getValue())
                    ->setParameter('nom', '%' . $filter->getValue() . '%');
            } else if ($filter->getQueryParameter() == 'masque') {
                $queryBuilder
                    ->andWhere(
                        $queryBuilder->expr()->orX(
                            'o.nom_ret LIKE :masque',
                            'o.nom_sel LIKE :masque',
                            'o.famille LIKE :masque'
                        )
                    )
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
/* Essais requêtes sql natives
    private function getBaseSqlQuery(): NativeQuery
    {
        $rsm = new ResultSetMappingBuilder($this->getEntityManager());
        $rsm->addRootEntityFromClassMetadata(DelObservation::class, 'o');

        $sql = "
        SELECT o.*
        FROM del_observation o
        LEFT JOIN del_commentaire c ON c.ce_observation = o.id_observation
        ";

        return $this->getEntityManager()->createNativeQuery($sql, $rsm);
    }

    public function findAllPaginatedNative(array $criteres, array $filters)
    {
        $query = $this->getBaseSqlQuery();
        $sql = $query->getSQL();

        foreach ($filters as $filter) {
            if ($filter->getQueryParameter() == 'masque_tag') {
                $sql .= " LEFT JOIN del_image i ON i.ce_observation = o.id_observation";
                break;
            }
        }

        $sql .= " WHERE 1=1";
        $sql .= $this->getTypeWhereClause($criteres['masque.type'] ?? null);

        [$filtersSql, $params] = $this->getFiltersWhereClause($filters);
        $sql .= $filtersSql;
//        $sql .= $this->getFiltersWhereClause($filters);
        $sql .= $this->getInscritsOnlyClause($criteres['masque.pninscritsseulement'] ?? false);
        $sql .= $this->getTriClause($criteres['tri'] ?? null, $criteres['ordre'] ?? 'DESC');

        // Pagination
        $sql .= " LIMIT " . intval($criteres['navigation.limite']) . " OFFSET " . intval($criteres['navigation.depart']);

        $query->setSQL($sql);
        foreach ($params as $key => $value) {
            $query->setParameter($key, $value);
        }

//        dd($sql, $params);
        return $query->getResult();
    }

    private function getTypeWhereClause(?string $type): string
    {
        return match ($type) {
            'adeterminer' => " AND o.certitude = 'à déterminer' ",
            'aconfirmer'  => " AND o.certitude = 'douteux' ",
            'validees'    => " AND o.certitude = 'certain' ",
            default       => '',
        };
    }

    private function getTriClause(?string $tri, ?string $ordre): string
    {
        $ordre = strtoupper($ordre ?? 'DESC');

        return match ($tri) {
            'nb_commentaires' => " GROUP BY o.id_observation ORDER BY COUNT(c.id_commentaire) $ordre",
            'date_transmission',
            'date_observation' => " GROUP BY o.id_observation ORDER BY o.$tri $ordre",
            default => " GROUP BY o.id_observation ORDER BY o.date_transmission $ordre",
        };
    }

    private function getFiltersWhereClause(array $filters): array
    {
        $clauses = [];
        $params = [];
        $i = 0;

        foreach ($filters as $filter) {
            $value = $filter->getValue();
            $paramName = 'param_' . $i;

            switch ($filter->getQueryParameter()) {
                case 'masque_tag':
                    $clauses[] = "(o.mots_cles_texte LIKE :$paramName OR i.mots_cles_texte LIKE :$paramName)";
                    $params[$paramName] = '%' . $value . '%';
                    break;

                case 'masque_auteur':
                    $clauses[] = "(o.ce_utilisateur = :$paramName OR o.nom_utilisateur LIKE :{$paramName}_nom OR o.prenom_utilisateur LIKE :{$paramName}_prenom OR o.courriel_utilisateur LIKE :{$paramName}_mail)";
                    $params[$paramName] = $value;
                    $params["{$paramName}_nom"] = '%' . $value . '%';
                    $params["{$paramName}_prenom"] = '%' . $value . '%';
                    $params["{$paramName}_mail"] = '%' . $value . '%';
                    break;

                case 'masque':
                    $clauses[] = "(" .
                        "o.nom_ret LIKE :{$paramName}_ret OR " .
                        "o.nom_sel LIKE :{$paramName}_sel OR " .
                        "o.famille LIKE :{$paramName}_fam OR " .
                        "o.nom_utilisateur LIKE :{$paramName}_nom OR " .
                        "o.courriel_utilisateur LIKE :{$paramName}_mail OR " .
                        "o.mots_cles_texte LIKE :{$paramName}_mots OR " .
                        "i.mots_cles_texte LIKE :{$paramName}_imots" .
                        ")";
                    $params["{$paramName}_ret"] = '%' . $value . '%';
                    $params["{$paramName}_sel"] = '%' . $value . '%';
                    $params["{$paramName}_fam"] = '%' . $value . '%';
                    $params["{$paramName}_nom"] = '%' . $value . '%';
                    $params["{$paramName}_mail"] = '%' . $value . '%';
                    $params["{$paramName}_mots"] = '%' . $value . '%';
                    $params["{$paramName}_imots"] = '%' . $value . '%';
                    break;

                case 'masque_departement':
                    $clauses[] = "SUBSTRING(o.ce_zone_geo, 1, 2) = :$paramName";
                    $params[$paramName] = $value;
                    break;

                    ///TODO: rechercher dans les commentaires pour proposition
//                case 'masque.nom_ret_nn':
//                    $clauses[] = "(" .
//                        "o.nom_ret_nn = :{$paramName}_ret_nn OR " .
//                        "c.nom_ret_nn = :{$paramName}_cret_nn OR " .
//                        "o.nom_sel_nn = :{$paramName}_sel_nn OR " .
//                        "c.nom_sel_nn = :{$paramName}_csel_nn OR " .
//                        ")";
//                    $params["{$paramName}_ret_nn"] =  $value;
//                    $params["{$paramName}_rcet_nn"] =  $value;
//                    $params["{$paramName}_sel_nn"] = $value;
//                    $params["{$paramName}_csel_nn"] = $value;
//                    break;

                default:
                    if ($filter->getIsExact()) {
                        $clauses[] = "o." . $filter->getBddColumn() . " = :$paramName";
                        $params[$paramName] = $value;
                    } else {
                        $clauses[] = "o." . $filter->getBddColumn() . " LIKE :$paramName";
                        $params[$paramName] = '%' . $value . '%';
                    }
                    break;
            }

            $i++;
        }

        $sqlClause = $clauses ? ' AND ' . implode(' AND ', $clauses) : '';
        return [$sqlClause, $params];
    }

    private function getInscritsOnlyClause(bool $inscritsSeulement): string
    {
        return $inscritsSeulement ? " AND o.ce_utilisateur IS NOT NULL " : '';
    }

    public function findTotalByCriteriasNative(array $criteres, array $filters): int
    {
        $sql = "
        SELECT COUNT(DISTINCT o.id_observation)
        FROM del_observation o
        LEFT JOIN del_commentaire c ON c.ce_observation = o.id_observation
    ";

        // Cas spécifique du tag qui nécessite la jointure sur image
        foreach ($filters as $filter) {
            if ($filter->getQueryParameter() === 'masque_tag') {
                $sql .= " LEFT JOIN del_image i ON i.ce_observation = o.id_observation";
                break;
            }
        }

        $sql .= " WHERE 1=1";
        $sql .= $this->getTypeWhereClause($criteres['masque.type'] ?? null);

        [$filtersSql, $params] = $this->getFiltersWhereClause($filters);
        $sql .= $filtersSql;
        $sql .= $this->getInscritsOnlyClause($criteres['masque.pninscritsseulement'] ?? false);

        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery($params);

        return (int) $result->fetchOne();
    }
*/

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
