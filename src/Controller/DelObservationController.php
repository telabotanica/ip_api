<?php

namespace App\Controller;

use App\Repository\DelCommentaireRepository;
use App\Repository\DelCommentaireVoteRepository;
use App\Repository\DelObservationRepository;
use App\Service\ExternalRequests;
use App\Service\Mapping;
use App\Service\UrlValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class DelObservationController extends AbstractController
{
    private EntityManagerInterface $em;
    private DelObservationRepository $obsRepository;
    private DelCommentaireVoteRepository $voteRepository;
    private DelCommentaireRepository $commentaireRepository;
    private SerializerInterface $serializer;
    private Mapping $mapping;
    private ExternalRequests $externalRequests;

    public function __construct(EntityManagerInterface $em, DelObservationRepository $obsRepository, DelCommentaireVoteRepository $voteRepository, DelCommentaireRepository $commentaireRepository, SerializerInterface $serializer, Mapping $mapping, ExternalRequests $externalRequests)
    {
        $this->em = $em;
        $this->obsRepository = $obsRepository;
        $this->voteRepository = $voteRepository;
        $this->commentaireRepository = $commentaireRepository;
        $this->serializer = $serializer;
        $this->mapping = $mapping;
        $this->externalRequests = $externalRequests;
    }

    #[Route('/observations', name: 'observation_all',methods:['GET'])]
    public function index(Request $request, SerializerInterface $serializer, UrlValidator $urlValidator): JsonResponse
    {
        $criteres = $this->mapping->getUrlCriterias($request);

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
        $observations = $this->obsRepository->findAllPaginated($criteres);

        if (!$observations) {
            return new JsonResponse(['message' => 'Pas d\'observations trouvées avec les critères spécifiés'], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($observations, 'json', ['groups' => 'observations']);
        $observations_array = json_decode($json, true);

        //TODO: faire un service pour les liens d'entete
        //TODO calculer le total et désactiver le href suivant si dernière page

        // On map les obs de manière à ajouter l'entête
        $result = $this->mapping->getObsEntetes($criteres);

        foreach ($observations_array as $obs_array){
            $obs_array['nb_commentaires'] = 0;
            $obs_array = $this->mapping->mapObservation($obs_array);
            $result['resultats'][] = $obs_array;
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }

    #[Route('/observations/{id_observation}', name: 'observation_single', methods: ['GET'])]
    public function GetOneObs(int $id_observation): Response
    {
        $obs = $this->obsRepository->findOneBy(['id_observation' => $id_observation]);
        if (!$obs) {
            return new JsonResponse(['message' => 'Observation: '.$id_observation .' introuvable'], Response::HTTP_NOT_FOUND);
        }

        //TODO ajouter images

        $json = $this->serializer->serialize($obs, 'json', ['groups' => 'observations']);
        $obs_array = json_decode($json, true);
        $obs_array['nb_commentaires'] = 0;

        $obs_array = $this->mapping->mapObservation($obs_array);

        return new JsonResponse($obs_array, Response::HTTP_OK);
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
    #[Route('/observations/{id_observation}/{id_commentaire}/vote', name: 'proposition_vote', methods: ['GET'])]
    public function GetPropositionVotes(int $id_observation, int $id_commentaire): Response
    {
        $votes = $this->voteRepository->findBy(['ce_proposition' => $id_commentaire]);

        return $this->json($votes, 200, [], ['groups' => ['votes']]);
    }
}
