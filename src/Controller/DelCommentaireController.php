<?php

namespace App\Controller;

use App\Repository\DelCommentaireRepository;
use App\Repository\DelObservationRepository;
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
    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $em, DelCommentaireRepository $commentaireRepository, SerializerInterface $serializer)
    {
        $this->em = $em;
        $this->commentaireRepository = $commentaireRepository;
        $this->serializer = $serializer;
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

        $commentaires = $this->commentaireRepository->findAllPaginated($criteres);

        if (!$commentaires) {
            return new JsonResponse(['message' => 'Pas de commentaires trouvés avec les critères spécifiés'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($commentaires, 200, [], ['groups' => ['commentaires']]);

    }

    #[Route('/commentaires/{id_commentaire}', name: 'commentaire_single', methods: ['GET'])]
    public function GetOneComment(int $id_commentaire): Response
    {
        $obs = $this->commentaireRepository->findOneBy(['id_commentaire' => $id_commentaire]);
        if (!$obs) {
            return new JsonResponse(['message' => 'Commentaire: '.$id_commentaire .' introuvable'], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($obs, 'json', ['groups' => 'commentaires']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
