<?php

namespace App\Service;

use App\Entity\DelCommentaire;
use App\Entity\DelObservation;
use App\Repository\DelCommentaireRepository;
use App\Repository\DelCommentaireVoteRepository;
use App\Repository\DelUtilisateurInfosRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class CommentaireService
{
    private EntityManagerInterface $em;
    private DelCommentaireVoteRepository $voteRepository;
    private DelCommentaireRepository $commentaireRepository;
    private DelUtilisateurInfosRepository $delUserRepository;
    private SerializerInterface $serializer;
    private AnnuaireService $annuaire;

    public function __construct(EntityManagerInterface $em, DelCommentaireVoteRepository $voteRepository, DelCommentaireRepository $commentaireRepository,DelUtilisateurInfosRepository $delUserRepository, SerializerInterface $serializer, AnnuaireService $annuaire)
    {
        $this->em = $em;
        $this->voteRepository = $voteRepository;
        $this->commentaireRepository = $commentaireRepository;
        $this->delUserRepository = $delUserRepository;
        $this->serializer = $serializer;
        $this->annuaire = $annuaire;
    }

    /**
     * Vérifie notamment que l'auteur du vote est désigné soit par un ID, soit
     * par un triplet (nom, prénom, adresse courriel)
     */
    public function verifierParametres($parametres, $erreurs) {
        if (!isset($parametres['observation'])) {
            $erreurs[] = "Impossible d'ajouter un commentaire sans identifiant d'observation (paramètre 'observation').";
        }

        if (!isset($parametres['auteur_id'])) {
            $erreurs[] = $this->verifierParamsAuteurAnonyme($parametres, $erreurs);
        }

        if (!isset($parametres['nom_sel_nn']) && (!isset($parametres['texte']) || trim($parametres['texte']) == '')) {
            $erreurs[] = "Le paramètre «texte» est obligatoire et ne doit pas être vide pour ajouter un commentaire.";
        }

        $erreurs = $this->verifierParamsTaxonNonVide($parametres, $erreurs);

        if (isset($parametres['nom_sel_nn']) && (!isset($parametres['nom_referentiel']) || !isset($parametres['nom_sel']) )) {
            $erreurs[] = "Si le paramètre «nom_sel_nn» est présent, les paramètre «nom_referentiel» et «nom_sel» doivent l'être aussi.";
        }

        return $erreurs;
    }

    private function verifierParamsTaxonNonVide($parametres, $erreurs) {
        $paramsNonVide = array('nom_sel', 'nom_referentiel', 'nom_sel_nn');
        foreach ($paramsNonVide as $param) {
            if (isset($parametres[$param]) && trim($parametres[$param]) == '' ) {
                $erreurs[] = "S'il est présent le paramètre «".$param."» ne peut pas être vide.";
            }
        }
        return $erreurs;
    }

    private function verifierParamsAuteurAnonyme($parametres, $erreurs) {
        $paramsAuteur = array('auteur.nom', 'auteur.prenom', 'auteur.courriel');
        $paramsAuteurManquant = array();
        foreach ($paramsAuteur as $param) {
            if (!isset($parametres[$param])) {
                $paramsAuteurManquant[] = $param;
            }
        }

        if (!empty($paramsAuteurManquant)) {
            $msgAuteurTpl = "Si le parametre 'auteur_id' n'est pas utilisé, il est nécessaire d'indiquer les ".
                "nom (paramètre 'auteur.nom'), prénom (paramètre 'auteur.prenom') et courriel ".
                "(paramètre 'auteur.courriel') de l'auteur.\nLes paramètres suivant sont abscents : %s\n";
            $erreurs[] = sprintf($msgAuteurTpl, implode(', ', $paramsAuteurManquant));
        }

        return $erreurs;
    }

    public function creerPremierCommentaire(DelObservation $observation): DelCommentaire
    {
        $obsAuteur = $this->delUserRepository->findOneBy(['id_utilisateur' => $observation->getCeUtilisateur()]);

        $firstComment = new DelCommentaire();
        $firstComment->setCeObservation($observation);
        $firstComment->setCeProposition(0);
        $firstComment->setCeUtilisateur($obsAuteur);
        $firstComment->setUtilisateurNom($observation->getNomUtilisateur());
        $firstComment->setUtilisateurPrenom($observation->getPrenomUtilisateur());
        $firstComment->setUtilisateurCourriel($observation->getCourrielUtilisateur());
        $firstComment->setTexte($observation->getCommentaire());
        $firstComment->setNomSel($observation->getNomSel());
        $firstComment->setNomSelNn($observation->getNomSelNn());
        $firstComment->setNomRet($observation->getNomRet());
        $firstComment->setNomRetNn($observation->getNomRetNn());
        $firstComment->setNomReferentiel($observation->getNomReferentiel());
        $firstComment->setFamille($observation->getFamille());
        $firstComment->setNt($observation->getNt());
        $firstComment->setCeCommentaireParent(0);
        $firstComment->setPropositionInitiale(true);
        $firstComment->setPropositionRetenue(false);
        $firstComment->setCeValidateur(0);
        $firstComment->setDateValidation(null);
        $firstComment->setDate($observation->getDateObservation());

        $this->em->persist($firstComment);
        $this->em->flush();

        return $firstComment;
    }

    public function verifierCommentairesExistantSurObs(DelObservation $observation): bool {
        $commentaires = $this->commentaireRepository->findBy(['ce_observation' => $observation->getIdObservation()]);
        if (count($commentaires) > 0) {
            return false;
        }

        $votes = $this->voteRepository->findBy(['ce_proposition' => $observation->getIdObservation()]);
        if (count($votes) > 0) {
            return false;
        }
        return true;
    }

    public function creerNouveauCommentaire(DelObservation $observation, array $data, Request $request): DelCommentaire
    {
        $commentaire = new DelCommentaire();
        $commentaire = $this->serializer->deserialize(json_encode($data), DelCommentaire::class, 'json');
        $commentaire->setCeObservation($observation);
        $commentaire->setPropositionInitiale(false);
        $commentaire->setPropositionRetenue(false);
        $commentaire->setDate(new \DateTime('now'));
        $commentaire->setCeValidateur(0);
        $commentaire->setDateValidation(null);

        // Ajout utilisateur anonyme
        $user = $this->delUserRepository->findOneBy(['id_utilisateur' => 0]);
        $commentaire->setCeUtilisateur($user);

        // Modif des infos user si utilisateur connecté
        $auth = $this->annuaire->getUtilisateurAuthentifie($request);
        if ($auth->getStatusCode() == 200) {
            $user = $this->delUserRepository->findOneBy(['id_utilisateur' => $auth->getContent()]);
            $commentaire->setUtilisateurNom($user->getNom());
            $commentaire->setUtilisateurPrenom($user->getPrenom());
            $commentaire->setUtilisateurCourriel($user->getCourriel());
            $commentaire->setCeUtilisateur($user);
//            $this->completerInfosUtilisateur();
        }

        return $commentaire;
    }

    public function verifierPropositionValidable(DelCommentaire $commentaire, array $erreurs)
    {
        if (!$commentaire->getNomSelNn()) {
            $erreurs[] = 'Le numéro taxonomique n\'est pas renseigné';
        }

        if (!$commentaire->getNomSel()) {
            $erreurs[] = 'Le nom scientifique n\'est pas renseigné';
        }

        if (!$commentaire->getNomReferentiel()) {
            $erreurs[] = 'Le nom du référentiel n\'est pas renseigné';
        }

        if ($commentaire->getCeCommentaireParent()){
            $erreurs[] = 'Une proposition ne peut pas être une réponse à un commentaire';
        }

        if ($commentaire->getCeProposition()){
            $erreurs[] = 'Une proposition ne peut pas être une réponse à une autre proposition';
        }

        return $erreurs;
    }

    /**
     * @param DelCommentaire[] $commentaires
     */
    public function devaliderAutresPropositions(array $commentaires, int $id_proposition)
    {
        $modifications = false;

        foreach ($commentaires as $commentairePresents) {
            if ($commentairePresents->getIdCommentaire() != $id_proposition && $commentairePresents->isPropositionRetenue() == 1) {
                $commentairePresents->setPropositionRetenue(0);
//               $commentairePresents->setCeValidateur(0);
//               $commentairePresents->setDateValidation(null);
                $this->em->persist($commentairePresents);
                $modifications = true;
            }
        }

        if ($modifications) {
            $this->em->flush();
        }
    }
    /*
    protected function completerInfosUtilisateur() {
        $this->user['session_id'] = session_id();
        $this->user['connecte'] = true;
    }
    */
}