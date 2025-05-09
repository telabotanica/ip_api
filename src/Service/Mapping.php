<?php

namespace App\Service;

use App\Repository\DelCommentaireRepository;
use App\Repository\DelCommentaireVoteRepository;
use App\Repository\DelImageRepository;
use App\Repository\DelObservationRepository;
use App\Repository\DelUtilisateurInfosRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Mapping extends AbstractController
{
    private DelCommentaireVoteRepository $voteRepository;
    private DelCommentaireRepository $commentaireRepository;
    private DelObservationRepository $obsRepository;
    private DelImageRepository $imageRepository;
    private DelUtilisateurInfosRepository $userRepository;
    private UrlValidator $urlValidator;
    private string $cel_img_url_tpl;

    public function __construct(DelCommentaireVoteRepository $voteRepository, DelCommentaireRepository $commentaireRepository, DelObservationRepository $obsRepository, DelImageRepository $imageRepository, DelUtilisateurInfosRepository $userRepository, UrlValidator $urlValidator, string $cel_img_url_tpl)
    {
        $this->voteRepository = $voteRepository;
        $this->commentaireRepository = $commentaireRepository;
        $this->obsRepository = $obsRepository;
        $this->imageRepository = $imageRepository;
        $this->userRepository = $userRepository;
        $this->urlValidator = $urlValidator;
        $this->cel_img_url_tpl = $cel_img_url_tpl;
    }


    public function mapObservation(array $obs_array): array
    {
        $commentaires = $this->commentaireRepository->findBy(['ce_observation' => $obs_array['id_observation']]);
        if ($commentaires) {
            $obs_array['commentaires'] = [];
            $obs_array['nb_commentaires'] = count($commentaires);

            foreach ($commentaires as $commentaire) {
                $obs_array = $this->addCommentsToObs($commentaire, $obs_array);
            }
        }

        $images = $this->imageRepository->findBy(['ce_observation' => $obs_array['id_observation']]);
        if ($images) {
            $obs_array['images'] = [];
            foreach ($images as $image) {
                $obs_array['images'][$image->getIdImage()] = $this->mapImage($image);
            }
        }

        return $obs_array;
    }

    public function addCommentsToObs($commentaire, array $obs_array): array
    {
        $commentaires_array = $this->findVotesByCommentaire($commentaire);
        $obs_array['commentaires'][$commentaire->getIdCommentaire()] = $commentaires_array;

        // On rajoute le nbre de votes au nbr total de commentaire
        if (isset($obs_array['commentaires'][$commentaire->getIdCommentaire()]['votes'])) {
            $obs_array['nb_commentaires'] += count($obs_array['commentaires'][$commentaire->getIdCommentaire()]['votes']);
        }

        return $obs_array;
    }

    public function findVotesByCommentaire($commentaire): array
    {
        $commentaire_array = $this->mapCommentaire($commentaire);
        $votes = $this->voteRepository->findBy(['ce_proposition' => $commentaire->getIdCommentaire()]);

        if (!$votes) {
            return $commentaire_array;
        }

        $votesParUtilisateur = [];
        $votesParUtilisateur = $this->regroupVotesByUser($votes, $votesParUtilisateur);
        $commentaire_array['votes'] = [];
        foreach ($votesParUtilisateur as $vote) {
            $vote_array = $this->mapVotes($vote);
            $commentaire_array['votes'][$vote->getIdVote()] = $vote_array;
        }
        return $commentaire_array;
    }
/*
    public function addVotesToComment($votesParUtilisateur, $commentaire, $obs_array): array
    {
        foreach ($votesParUtilisateur as $vote) {
            $vote_array = $this->mapVotes($vote);
            $obs_array['commentaires'][$commentaire->getIdCommentaire()]['votes'][$vote->getIdVote()] = $vote_array;
        }

        return $obs_array;
    }
*/

    // Regrouper les votes par utilisateur en ne conservant que le dernier
    public function regroupVotesByUser($votes, $votesParUtilisateur): array
    {
        foreach ($votes as $vote) {
            $auteurId = $vote->getAuteurId();
            if (!isset($votesParUtilisateur[$auteurId]) || $vote->getIdVote() > $votesParUtilisateur[$auteurId]->getIdVote()) {
                $votesParUtilisateur[$auteurId] = $vote;
            }
        }
        return $votesParUtilisateur;
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

    public function mapNewCommentaire(array $commentaire): array
    {
        $array = [
            'observation' => $commentaire['observation'],
            'utilisateur_nom' => $commentaire['auteur.nom'],
            'utilisateur_prenom' => $commentaire['auteur.prenom'],
            'utilisateur_courriel' => $commentaire['auteur.courriel']
        ];

        if ( isset($commentaire['proposition'])){
            $array['ce_proposition'] = $commentaire['proposition'];
        } else {
            $array['ce_proposition'] = 0;
        }

        if ( isset($commentaire['texte'])){
            $array['texte'] = $commentaire['texte'];
        }

        if ( isset($commentaire['auteur.id'])){
            $array['ce_utillisateur'] = $commentaire['auteur.id'];
        }

        if (isset($commentaire['id_parent'])){
            $array['ce_commentaire_parent'] = $commentaire['id_parent'];
        } else {
            $array['ce_commentaire_parent'] = "0";
        }

        if (isset($commentaire['nom_sel'])){
            $array['nom_sel'] = $commentaire['nom_sel'];
        }

        if (isset($commentaire['nom_sel_nn'])){
            $array['nom_sel_nn'] = $commentaire['nom_sel_nn'];
        }

        if (isset($commentaire['nom_ret'])){
            $array['nom_ret'] = $commentaire['nom_ret'];
        }

        if (isset($commentaire['nom_ret_nn'])){
            $array['nom_ret_nn'] = $commentaire['nom_ret_nn'];
        }

        if (isset($commentaire['nt'])){
            $array['nt'] = $commentaire['nt'];
        }

        if (isset($commentaire['famille'])){
            $array['famille'] = $commentaire['famille'];
        }

        if (isset($commentaire['nom_referentiel'])){
            $array['nom_referentiel'] = $commentaire['nom_referentiel'];
        }

//        if (isset($commentaire['date_validation'])){
//            $array['date_validation'] = $commentaire['date_validation'];
//            $array['validateur'] = $commentaire['validateur'];
//        }

        return $array;
    }

    public function mapVotes($vote):array
    {
        $user = null;
        if ( $vote->getAuteurId() != '0' && $vote->getAuteurId() != null &&  strlen($vote->getAuteurId()) < 6){
            $user=$this->userRepository->findOneBy(['id_utilisateur' => $vote->getAuteurId()]);
        }

        return [
            'vote.id' => $vote->getIdVote(),
            'proposition.id' => $vote->getProposition(),
            'auteur.id' => $vote->getAuteurId(),
            'vote' => $vote->getValeur(),
            'date' => $vote->getDate()->format('Y-m-d H:i:s'),
            'auteur.nom' => $user?->getNom(),
            'auteur.prenom' => $user?->getPrenom(),
            'auteur.courriel' => $user?->getCourriel(),
            'auteur.intitule' => $user?->getIntitule(),
        ];
    }

    public function getUrlCriterias(Request $request): array
    {
        $tri = $request->query->get('tri', 'date_transmission');
        $tri = $this->urlValidator->validateTri($tri);

        $order = $request->query->get('ordre', 'desc');
        $order = $this->urlValidator->validateOrder($order);

        $type = $request->query->get('masque_type', 'tous');
        $type = $this->urlValidator->validateType($type);

        $criterias = [
            'navigation.depart' => $request->query->get('navigation_depart', 0),
            'navigation.limite' => $request->query->get('navigation_limite', 12),
            'ordre' => $order,
            'tri' => $tri,
            'masque.pninscritsseulement' => $request->query->get('masque_pninscritsseulement', 1),
            'masque.type' => $type,
            'masque.standard' => $request->query->get('masque_standard', '1')
        ];

        return $criterias;
    }

    public function getObsEntetes(array $criteres, array $filters): array
    {
        $navigation_depart = $criteres['navigation.depart'];
        $navigation_limite = $criteres['navigation.limite'];
        $new_depart = $navigation_depart - $navigation_limite;

        if (($navigation_depart != 0) && ($new_depart <= 0)){
            $new_depart = 0;
        }

        $total = $this->obsRepository->findTotalByCriterieas($criteres, $filters);
        $href_precedent = "";
        $href_suivant = "";

        if ($navigation_depart != 0){
            $href_precedent = $this->generateUrl('observation_all', [
                'navigation.depart' => $new_depart,
                'navigation.limite' => $navigation_limite,
                'tri' => $criteres['tri'],
                'ordre' => $criteres['ordre'],
                'masque.type' => $criteres['masque.type']
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        if ($navigation_depart + $navigation_limite < $total){
            $href_suivant = $this->generateUrl('observation_all', [
                'navigation.depart' => $navigation_depart + $navigation_limite,
                'navigation.limite' => $navigation_limite,
                'tri' => $criteres['tri'],
                'ordre' => $criteres['ordre'],
                'masque.type' => $criteres['masque.type']
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        }

        $result = [
            'entete' => [
//                'masque' => http_build_query($criteres),
                'masque' => $criteres,
                'total' => $total,
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

    private function mapImage($image)
    {
//        $datePriseDeVue = $image->getDatePriseDeVue() ? $image->getDatePriseDeVue()->format('Y-m-d H:i:s') : null;
        $dateCreation = $image->getDateCreation() ? $image->getDateCreation()->format('Y-m-d H:i:s') : null;
//        $dateModification = $image->getDateModification() ? $image->getDateModification()->format('Y-m-d H:i:s') : null;
//        $dateLiaison = $image->getDateLiaison() ? $image->getDateLiaison()->format('Y-m-d H:i:s') : null;
//        $dateTransmission = $image->getDateTransmission() ? $image->getDateTransmission()->format('Y-m-d H:i:s') : null;

        return [
            'id_image' => $image->getIdImage(),
//            'id_utilisateur' => $image->getCeUtilisateur(),
//            'nom_utilisateur' => $image->getNomUtilisateur(),
//            'courriel_utilisateur' => $image->getCourrielUtilisateur(),
            'binaire.href' => sprintf($this->cel_img_url_tpl, $image->getIdImage(), 'O'),
            'nom_original' => $image->getNomOriginal(),
            'hauteur' => $image->getHauteur(),
            'largeur' => $image->getLargeur(),
//            'date_prise_de_vue' => $datePriseDeVue,
            'date_creation' => $dateCreation,
//            'date_modification' => $dateModification,
//            'date_liaison' => $dateLiaison,
//            'date_transmission' => $dateTransmission,
            'mots_cles_img' => $image->getMotsClesTexte(),
            'commentaires' => $image->getCommentaire(),
        ];
    }
}