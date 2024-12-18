<?php

namespace App\Service;

use App\Repository\DelCommentaireRepository;
use App\Repository\DelCommentaireVoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Mapping extends AbstractController
{
    private DelCommentaireVoteRepository $voteRepository;
    private DelCommentaireRepository $commentaireRepository;
    private UrlValidator $urlValidator;

    public function __construct(DelCommentaireVoteRepository $voteRepository, DelCommentaireRepository $commentaireRepository, UrlValidator $urlValidator)
    {
        $this->voteRepository = $voteRepository;
        $this->commentaireRepository = $commentaireRepository;
        $this->urlValidator = $urlValidator;
    }


    public function mapObservation(array $obs_array): array
    {
        $commentaires = $this->commentaireRepository->findBy(['ce_observation' => $obs_array['id_observation']]);
        if ($commentaires) {
            $obs_array['commentaires'] = [];
            $obs_array['nb_commentaires'] = count($commentaires);

            foreach ($commentaires as $commentaire) {
                // On map les commentaires
                $commentaires_array = $this->mapCommentaire($commentaire);
                $obs_array['commentaires'][$commentaire->getIdCommentaire()] = $commentaires_array;

                // On ajoute les votes à chaque commentaire
                $votes = $this->voteRepository->findBy(['ce_proposition' => $commentaire->getIdCommentaire()]);
                if ($votes) {
                    $votesParUtilisateur = [];

                    // Regrouper les votes par utilisateur en ne conservant que le dernier
                    foreach ($votes as $vote) {
                        $auteurId = $vote->getAuteurId();
                        if (!isset($votesParUtilisateur[$auteurId]) || $vote->getIdVote() > $votesParUtilisateur[$auteurId]->getIdVote()) {
                            $votesParUtilisateur[$auteurId] = $vote;
                        }
                    }

                    $obs_array['nb_commentaires'] += count($votesParUtilisateur);
                    $obs_array['commentaires'][$commentaire->getIdCommentaire()]['votes'] = [];

                    foreach ($votesParUtilisateur as $vote) {
                        // On map les votes
                        $vote_array = $this->mapVotes($vote);
                        $obs_array['commentaires'][$commentaire->getIdCommentaire()]['votes'][$vote->getIdVote()] = $vote_array;
                    }

                }
            }
        }

        return $obs_array;
    }

    public function mapCommentaire($commentaire): array
    {
        $array = [
            'id_commentaire' => $commentaire->getIdCommentaire(),
            'observation' => $commentaire->getObservation(),
            'proposition' => $commentaire->getCeProposition(),
            'id_parent' => $commentaire->getCeCommentaireParent(),
            'texte' => $commentaire->getTexte(),
            'auteur.id' => $commentaire->getAuteurId(),
            'auteur.nom' => $commentaire->getUtilisateurNom(),
            'auteur.prenom' => $commentaire->getUtilisateurPrenom(),
            'auteur.courriel' => $commentaire->getUtilisateurCourriel(),
            'nom_sel' => $commentaire->getNomSel(),
            'nom_sel_nn' => $commentaire->getNomSelNn(),
            'nom_ret' => $commentaire->getNomRet(),
            'nom_ret_nn' => $commentaire->getNomRetNn(),
            'nt' => $commentaire->getNt(),
            'famille' => $commentaire->getFamille(),
            'nom_referentiel' => $commentaire->getNomReferentiel(),
            'proposition_initiale' => $commentaire->isPropositionInitiale(),
            'proposition_retenue' => $commentaire->isPropositionRetenue(),
            'date' => $commentaire->getDate()->format('Y-m-d H:i:s'),
        ];

        if ($commentaire->isPropositionRetenue()) {
            $array['date_validation'] = $commentaire->getDateValidation()->format('Y-m-d H:i:s');
            $array['validateur'] = $commentaire->getCeValidateur();
        }

        return $array;
    }

    public function mapVotes($vote):array
    {
        return [
            'id_vote' => $vote->getIdVote(),
            'proposition' => $vote->getProposition(),
            'auteur.id' => $vote->getAuteurId(),
            'valeur' => $vote->getValeur(),
            'date' => $vote->getDate()->format('Y-m-d H:i:s'),
        ];
    }

    public function getUrlCriterias(Request $request): array
    {
        $tri = $request->query->get('tri', 'date_transmission');
        $tri = $this->urlValidator->validateTri($tri);

        $order = $request->query->get('ordre', 'desc');
        $order = $this->urlValidator->validateOrder($order);

        $type = $request->query->get('type', 'tous');
        $type = $this->urlValidator->validateType($type);

        return [
            'navigation_depart' => $request->query->get('navigation_depart', 0),
            'navigation_limite' => $request->query->get('navigation_limite', 12),
            'ordre' => $order,
            'tri' => $tri,
            'masque_pninscritsseulement' => $request->query->get('masque_pninscritsseulement', 1),
            'type' => $type
        ];
    }

    public function getObsEntetes(array $criteres): array
    {
        $navigation_depart = $criteres['navigation_depart'];
        $navigation_limite = $criteres['navigation_limite'];
        $new_depart = $navigation_depart - $navigation_limite;

        if (($navigation_depart != 0) && ($new_depart <= 0)){
            $new_depart = 0;
        }

        //$total = $this->obsRepository->countByCriteria($criteres);

        $href_precedent = $this->generateUrl('observation_all', [
            'navigation_depart' => $new_depart,
            'navigation_limite' => $navigation_limite,
            'tri' => $criteres['tri'],
            'ordre' => $criteres['ordre'],
            'type' => $criteres['type']
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        if ($navigation_depart == 0){
            $href_precedent = "";
        }

        $href_suivant = $this->generateUrl('observation_all', [
            'navigation_depart' => $navigation_depart + $navigation_limite,
            'navigation_limite' => $navigation_limite,
            'tri' => $criteres['tri'],
            'ordre' => $criteres['ordre'],
            'type' => $criteres['type']
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $result = [
            'entete' => [
                'masque' => http_build_query($criteres),
//                'total' => $total,
                'depart' => $navigation_depart,
                'limite' => $navigation_limite
            ],
            'resultats' => []
        ];

        if ($href_precedent){
            $result['entete']['href.precedent'] = $href_precedent;
        }

        if ($href_suivant){
            $result['entete']['href.suivant'] = $href_suivant;
        }

        return $result;
    }
}