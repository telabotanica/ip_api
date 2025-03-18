<?php

namespace App\Service;

use App\Entity\DelCommentaire;
use App\Entity\DelObservation;
use App\Repository\DelUtilisateurInfosRepository;
use Doctrine\ORM\EntityManagerInterface;

class CommentaireService
{
    private EntityManagerInterface $em;
    private DelUtilisateurInfosRepository $delUserRepository;

    public function __construct(EntityManagerInterface $em, DelUtilisateurInfosRepository $delUserRepository)
    {
        $this->em = $em;
        $this->delUserRepository = $delUserRepository;
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

    public function creerPremierCommentaire(DelObservation $observation, array $obs_array) {
        $obsAuteur = $this->delUserRepository->findOneBy(['id_utilisateur' => $obs_array['auteur.id']]);

        $firstComment = new DelCommentaire();
        $firstComment->setCeObservation($observation);
        $firstComment->setCeProposition(0);
        $firstComment->setCeUtilisateur($obsAuteur);
        $firstComment->setUtilisateurNom($obs_array['auteur.nom']);
        $firstComment->setUtilisateurPrenom($obs_array['auteur.prenom']);
        $firstComment->setUtilisateurCourriel($obs_array['auteur.courriel']);
        $firstComment->setTexte($obs_array['commentaire']);
        $firstComment->setNomSel($obs_array['determination.ns']);
        $firstComment->setNomSelNn($obs_array['determination.nn']);
        $firstComment->setNomRet($obs_array['determination.nom_ret']);
        $firstComment->setNomRetNn($obs_array['determination.nom_ret_nn']);
        $firstComment->setNomReferentiel($obs_array['determination.referentiel']);
        $firstComment->setFamille($obs_array['determination.famille']);
        $firstComment->setNt($obs_array['determination.nt']);
        $firstComment->setCeCommentaireParent(0);
        $firstComment->setPropositionInitiale(true);
        $firstComment->setPropositionRetenue(false);
        $firstComment->setCeValidateur(0);
        $firstComment->setDateValidation(null);
        $firstComment->setDate($observation->getDateObservation());

        $this->em->persist($firstComment);
        $this->em->flush();
    }

}