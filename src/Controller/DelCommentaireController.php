<?php

namespace App\Controller;

use App\Repository\DelCommentaireRepository;
use App\Repository\DelCommentaireVoteRepository;
use App\Repository\DelObservationRepository;
use App\Service\Mapping;
use App\Service\UrlValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class DelCommentaireController extends AbstractController
{
    private EntityManagerInterface $em;
    private DelCommentaireRepository $commentaireRepository;
    private DelCommentaireVoteRepository $voteRepository;
    private SerializerInterface $serializer;
    private Mapping $mapping;

    public function __construct(EntityManagerInterface $em, DelCommentaireRepository $commentaireRepository, DelCommentaireVoteRepository $voteRepository, SerializerInterface $serializer, Mapping $mapping)
    {
        $this->em = $em;
        $this->commentaireRepository = $commentaireRepository;
        $this->voteRepository = $voteRepository;
        $this->serializer = $serializer;
        $this->mapping = $mapping;
    }

    #[Route('/commentaires', name: 'commentaire_all', methods: ['GET'])]
    public function index(Request $request, UrlValidator $urlValidator): JsonResponse
    {
        $order = $request->query->get('ordre', 'desc');
        $order = $urlValidator->validateOrder($order);

        $criteres = [
            'page' => $request->query->get('navigation_depart', 0),
            'limit' => $request->query->get('navigation_limite', 10),
            'order' => $order,
            'pnInscrit' => $request->query->get('masque_pninscritsseulement', 1),
        ];

        //TODO: ajouter filtre masque.auteur
        //TODO: ajouter les commentaires sur plusieurs niveaux

        $commentaires = $this->commentaireRepository->findAllPaginated($criteres);

        if (!$commentaires) {
            return new JsonResponse(['message' => 'Pas de commentaires trouvés avec les critères spécifiés'], Response::HTTP_NOT_FOUND);
        }

        foreach ($commentaires as $key => $commentaire) {
            $commentaires[$key] = $this->mapping->findVotesByCommentaire($commentaire);
        }

        return new JsonResponse($commentaires, Response::HTTP_OK);
    }

    #[Route('/commentaires/{id_commentaire}', name: 'commentaire_single', methods: ['GET'])]
    public function GetOneComment(int $id_commentaire): Response
    {
        $commentaire = $this->commentaireRepository->findOneBy(['id_commentaire' => $id_commentaire]);
        if (!$commentaire) {
            return new JsonResponse(['message' => 'Commentaire: '.$id_commentaire .' introuvable'], Response::HTTP_NOT_FOUND);
        }

        $commentaire = $this->mapping->findVotesByCommentaire($commentaire);

        //TODO: ajouter les commentaires sur plusieurs niveaux

        return new JsonResponse($commentaire, Response::HTTP_OK);
    }

    // toutes les infos sur les votes d'un commentaire
    #[Route('/commentaires/{id_commentaire}/vote', name: 'commentaire_vote', methods: ['GET'])]
    public function GetCommentaireVotes(int $id_commentaire): Response
    {
        $votes = $this->voteRepository->findBy(['ce_proposition' => $id_commentaire]);

        if (!$votes) {
            return new JsonResponse(['message' => 'Pas de votes trouvés avec les critères spécifiés'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($votes, 200, [], ['groups' => ['votes']]);
    }
}
