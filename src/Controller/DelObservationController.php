<?php

namespace App\Controller;

use App\Repository\DelCommentaireRepository;
use App\Repository\DelCommentaireVoteRepository;
use App\Repository\DelObservationRepository;
use App\Service\UrlValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class DelObservationController extends AbstractController
{
    private EntityManagerInterface $em;
    private DelObservationRepository $obsRepository;
    private DelCommentaireVoteRepository $voteRepository;
    private DelCommentaireRepository $commentaireRepository;
    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $em, DelObservationRepository $obsRepository, DelCommentaireVoteRepository $voteRepository, DelCommentaireRepository $commentaireRepository, SerializerInterface $serializer)
    {
        $this->em = $em;
        $this->obsRepository = $obsRepository;
        $this->voteRepository = $voteRepository;
        $this->commentaireRepository = $commentaireRepository;
        $this->serializer = $serializer;
    }

    #[Route('/observations', name: 'observation_all',methods:['GET'])]
    public function index(Request $request, SerializerInterface $serializer, UrlValidator $urlValidator): JsonResponse
    {
        $tri = $request->query->get('tri', 'date_transmission');
        $tri = $urlValidator->validateTri($tri);

        $order = $request->query->get('ordre', 'desc');
        $order = $urlValidator->validateOrder($order);

        $type = $request->query->get('type', 'tous');
        $type = $urlValidator->validateType($type);

        $criteres = [
            'page' => $request->query->get('navigation_depart', 0),
            'limit' => $request->query->get('navigation_limite', 12),
            'order' => $order,
            'tri' => $tri,
            'pnInscrit' => $request->query->get('masque_pninscritsseulement', 1),
            'type' => $type
        ];

        //TODO prendre en compte le type
        //TODO gérer les critères de recherche
        //TODO ajouter entetes ({"masque":"navigation.depart=0&navigation.limite=12&masque.type=adeterminer&masque=betula&masque.pninscritsseulement=0",
        //"total":66,
        //"depart":0,
        //"limite":12,
        //"href.suivant":"http:\/\/api-test2.tela-botanica.org\/service:del:0.1\/observations?navigation.depart=12&navigation.limite=12&masque.type=adeterminer&masque=betula&masque.pninscritsseulement=0"})
        //TODO ajouter les images et mots_cles_texte_img
        //TODO ajouter 1ere image:
        //            "id_image": "1235453",
        //            "date": "2020-06-26 15:36:08",
        //            "hauteur": "2400",
        //            "largeur": "3200",
        //            "nom_original": "D61_3011_908.JPG",
        //TODO ajouter les commentaires (et les votes dans les commentaires)
        $observations = $this->obsRepository->findAllPaginated($criteres);

        if (!$observations) {
            return new JsonResponse(['message' => 'Pas d\'observations trouvées avec les critères spécifiés'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($observations, 200, [], ['groups' => ['observations']]);
    }

    #[Route('/observations/{id_observation}', name: 'observation_single', methods: ['GET'])]
    public function GetOneObs(int $id_observation): Response
    {
        $obs = $this->obsRepository->findOneBy(['id_observation' => $id_observation]);
        if (!$obs) {
            return new JsonResponse(['message' => 'Observation: '.$id_observation .' introuvable'], Response::HTTP_NOT_FOUND);
        }

        //TODO ajouter les votes, commentaires et images

        $json = $this->serializer->serialize($obs, 'json', ['groups' => 'observations']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    // toutes les infos sur les votes d'une observation
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
    #[Route('/observations/{id_observation}/{id_commentaire}/vote', name: 'observation_vote', methods: ['GET'])]
    public function GetPropositionVotes(int $id_observation, int $id_commentaire): Response
    {
        $votes = $this->voteRepository->findBy(['ce_proposition' => $id_commentaire]);

        return $this->json($votes, 200, [], ['groups' => ['votes']]);
    }
}
