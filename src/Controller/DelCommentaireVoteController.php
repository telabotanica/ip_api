<?php

namespace App\Controller;

use App\Repository\DelCommentaireRepository;
use App\Repository\DelCommentaireVoteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DelCommentaireVoteController extends AbstractController
{
    /*
    private DelCommentaireVoteRepository $voteRepository;
    private DelCommentaireRepository $commentaireRepository;


    // toutes les infos sur les votes d'une observation

    public function __construct(DelCommentaireVoteRepository $voteRepository, DelCommentaireRepository $commentaireRepository)
    {
        $this->voteRepository = $voteRepository;
        $this->commentaireRepository = $commentaireRepository;
    }

    #[Route('/observations/{id_observation}/vote', name: 'observation_vote', methods: ['GET'])]
    public function GetObsVotes(int $id_observation): Response
    {
        $commentaires = $this->commentaireRepository->findBy(['ce_observation' => $id_observation]);
        $votes = [];

        foreach ($commentaires as $commentaire) {
            $votesBycomment = $this->voteRepository->findBy(['ce_proposition' => $commentaire->getIdCommentaire()]);
            if ($votesBycomment) {
                $votes += $votesBycomment;
            }
        }

        return $this->json($votes, 200, [], ['groups' => ['votes']]);
    }

    // toutes les infos sur les votes d'une proposition
    #[Route('/observations/{id_observation}/{id_commentaire}/vote', name: 'proposition_vote', methods: ['GET'])]
    public function GetPropositionVotes(int $id_observation, int $id_commentaire): Response
    {
        $votes = $this->voteRepository->findBy(['ce_proposition' => $id_commentaire]);

        return $this->json($votes, 200, [], ['groups' => ['votes']]);
    }
    */
}
