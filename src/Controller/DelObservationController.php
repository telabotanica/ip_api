<?php

namespace App\Controller;

use App\Entity\DelCommentaireVote;
use App\Repository\DelCommentaireRepository;
use App\Repository\DelCommentaireVoteRepository;
use App\Repository\DelObservationRepository;
use App\Repository\DelUtilisateurInfosRepository;
use App\Service\AnnuaireService;
use App\Service\CommentaireService;
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
    private DelUtilisateurInfosRepository $delUserRepository;
    private SerializerInterface $serializer;
    private Mapping $mapping;
    private ExternalRequests $externalRequests;
    private UrlValidator $urlValidator;
    private CommentaireService $commentaireService;
    private AnnuaireService $annuaire;
    private array $user = [];

    public function __construct(
        EntityManagerInterface $em,
        DelObservationRepository $obsRepository,
        DelCommentaireVoteRepository $voteRepository,
        DelCommentaireRepository $commentaireRepository,
        DelUtilisateurInfosRepository $delUserRepository,
        SerializerInterface $serializer,
        Mapping $mapping,
        ExternalRequests $externalRequests,
        UrlValidator $urlValidator,
        CommentaireService $commentaireService,
        AnnuaireService $annuaire,
        array $user = []
    )
    {
        $this->em = $em;
        $this->obsRepository = $obsRepository;
        $this->voteRepository = $voteRepository;
        $this->commentaireRepository = $commentaireRepository;
        $this->delUserRepository = $delUserRepository;
        $this->serializer = $serializer;
        $this->mapping = $mapping;
        $this->externalRequests = $externalRequests;
        $this->urlValidator = $urlValidator;
        $this->commentaireService = $commentaireService;
        $this->annuaire = $annuaire;
        $this->user = $this->annuaire->getUtilisateurAnonyme();
    }

    #[Route('/observations', name: 'observation_all',methods:['GET'])]
    public function index(Request $request): JsonResponse
    {
        $criteres = $this->mapping->getUrlCriterias($request);
        $filters = $this->urlValidator->mapUrlParameters($request);

        if ($criteres['masque.type'] == 'monactivite') {
            $auth = $this->annuaire->getUtilisateurAuthentifie($request);

            if ($auth->getStatusCode() != 200) {
                return new JsonResponse(['error' => 'Vous devez être connecté pour accéder à cette page'], Response::HTTP_UNAUTHORIZED);
            }

//            $cookie = $request->cookies->get($this->annuaire->getCookieName()) ?? null;
//            if (!$cookie) {
//                return new JsonResponse(['message' => 'Vous devez vous connecter pour accéder à votrea activité'], Response::HTTP_UNAUTHORIZED);
//            }
            $user = $this->delUserRepository->findOneBy(['id_utilisateur' => $auth->getContent()]);
            $json = $this->serializer->serialize($user, 'json', ['groups' => 'user']);

            $this->user = json_decode($json, true);
            $this->completerInfosUtilisateur();

            $criteres['user'] = $this->user;
            $observations = $this->obsRepository->findMonActivite($criteres);
        } else {
            $observations = $this->obsRepository->findAllPaginated($criteres, $filters);
        }

        if (!$observations) {
            return new JsonResponse(['error' => 'Pas d\'observations trouvées avec les critères spécifiés'], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($observations, 'json', ['groups' => 'observations']);
        $observations_array = json_decode($json, true);

        // On map les obs de manière à ajouter l'entête
        $result = $this->mapping->getObsEntetes($criteres, $filters);

        foreach ($observations_array as $obs_array){
            $obs_array['nb_commentaires'] = 0;
            $obs_array = $this->mapping->mapObservation($obs_array);
            $result['resultats'][] = $obs_array;
        }

        return new JsonResponse($result, Response::HTTP_OK);
    }

    #[Route('/observations/{id_observation}', name: 'observation_single', methods: ['GET'])]
    public function getOneObs(int $id_observation): Response
    {
        $obs = $this->obsRepository->findOneBy(['id_observation' => $id_observation]);
        if (!$obs) {
            return new JsonResponse(['message' => 'Observation: '.$id_observation .' introuvable'], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($obs, 'json', ['groups' => 'observations']);
        $obs_array = json_decode($json, true);
        $obs_array['nb_commentaires'] = 0;

        $obs_array = $this->mapping->mapObservation($obs_array);

        return new JsonResponse($obs_array, Response::HTTP_OK);
    }

    // toutes les infos sur les votes d'une observation
    #[Route('/observations/vote/{id_observation}', name: 'observation_vote', methods: ['GET'])]
    public function getObsVotes(int $id_observation): Response
    {
        $commentaires = $this->commentaireRepository->findBy(['ce_observation' => $id_observation]);
        $votes = [];

        foreach ($commentaires as $commentaire) {
            $votesBycomment = $this->voteRepository->findBy(['ce_proposition' => $commentaire->getIdCommentaire()]);

            if ($votesBycomment) {
                foreach ($votesBycomment as $value) {
                    $vote = $this->mapping->mapVotes($value);
                    $votes[] = $vote;
                }
            }

        }

        return $this->json($votes, 200, [], ['groups' => ['votes']]);
    }

    // toutes les infos sur les votes d'une proposition
    #[Route('/observations/vote/proposition/{id_observation}/{id_commentaire}', name: 'proposition_vote', methods: ['GET'])]
    public function getPropositionVotes(int $id_observation, int $id_commentaire): Response
    {
        $mappedVotes = [];
        $votes = $this->voteRepository->findBy(['ce_proposition' => $id_commentaire]);
        if ($votes) {
            foreach ($votes as $value) {
                $vote = $this->mapping->mapVotes($value);
                $mappedVotes[] = $vote;
            }
        }

        return $this->json($mappedVotes, 200, [], ['groups' => ['votes']]);
    }

    protected function completerInfosUtilisateur() {
        $this->user['session_id'] = session_id();
        $this->user['connecte'] = true;
    }

    #[Route('/observations/{id_observation}/{id_proposition}/vote', name: 'voter', methods: ['PUT'])]
    public function voterPourProposition(int $id_observation, int $id_proposition, Request $request): Response
    {
        $userId = "";
        $content = json_decode($request->getContent(), true);

        if (!isset($content['valeur']) || (trim($content['valeur']) != '0' && trim($content['valeur']) != '1') ) {
            return new JsonResponse(['message' => 'Erreur de configuration, le paramètre valeur est obligatoire et doit avoir une valeur de "0" ou "1".'], Response::HTTP_BAD_REQUEST);
        }

        $observation = $this->obsRepository->findOneBy(['id_observation' => $id_observation]);
        if (!$observation) {
            return new JsonResponse(['message' => 'Observation: '.$id_observation .' introuvable'], Response::HTTP_NOT_FOUND);
        }

        // On vérifie l'id de la proposition et crée un nouveau si nécessaire
        if ($id_proposition == 0) {
            $isFirstComment = $this->commentaireService->verifierCommentairesExistantSurObs($observation);
            if ($isFirstComment) {
                // Si pas de commentaire existant, on en crée un avec les données de l'obs pour pouvoir récupérer un id_proposition
                $firstComment = $this->commentaireService->creerPremierCommentaire($observation);
                $id_proposition = $firstComment;
            } else {
                return new JsonResponse(['message' => 'L\'observation '.$id_observation.' est déjà commentée, veuillez indiquer un id_proposition'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            $commentaire = $this->commentaireRepository->findOneBy(['id_commentaire' => (int)$id_proposition]);
            if (!$commentaire) {
                return new JsonResponse(['message' => 'Proposition: '.$id_proposition .' introuvable, impossible de voter'], Response::HTTP_NOT_FOUND);
            }
            $id_proposition = $commentaire;
        }

        // On vérifie les données utilisateur (anonyme ou authentifié)
        if (!isset($content['utilisateur']) || $content['utilisateur'] == '' || $content['utilisateur'] == 0) {
           $user = $this->annuaire->getUtilisateurAnonyme();
           $userId = $user['id_utilisateur'];
        } else {
            $auth = $this->annuaire->getUtilisateurAuthentifie($request);
            $response = $auth->getContent();

            if ($auth->getStatusCode() != 200) {
                $error = json_decode($response, true);
                return new JsonResponse(['message' => 'Utilisateur introuvable, veuillez vous reconnecter', 'error' => $error['error']], Response::HTTP_UNAUTHORIZED);
            }

            $user = $this->delUserRepository->findOneBy(['id_utilisateur' => $response]);
            $userId = $user->getIdUtilisateur();
        }

        $vote = new DelCommentaireVote();
        $vote->setValeur($content['valeur']);
        $vote->setCeProposition($id_proposition);
        $vote->setCeUtilisateur($userId);
        $vote->setDate(new \DateTime('now'));

        $this->em->persist($vote);
        $this->em->flush();

        $json = json_encode(["id_commentaire" => $vote->getIdVote()], true);
        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    #[Route('/observations/{id_observation}', name: 'depublier_obs', methods: ['POST'])]
    public function depublierObs(int $id_observation, Request $request): Response
    {
        $token = $request->headers->get('Authorization');
        $content = json_decode($request->getContent(), true);

        $obs = $this->obsRepository->findOneBy(['id_observation' => $id_observation]);
        if (!$obs) {
            return new JsonResponse(['message' => 'Observation: '.$id_observation .' introuvable'], Response::HTTP_NOT_FOUND);
        }

        if (!isset($content['transmission']) || $content['transmission'] != "0" ){
            return new JsonResponse(['message' => 'Le paramètre transmission est obligatoire (0).'], Response::HTTP_BAD_REQUEST);
        }

        $auth = $this->annuaire->getUtilisateurAuthentifie($request);
        if ($auth->getStatusCode() != 200) {
            return new JsonResponse(['message' => 'Vous devez vous connecter pour supprimer ce commentaire '], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->delUserRepository->findOneBy(['id_utilisateur' => $auth->getContent()]);
        if ( !$this->annuaire->isAuthorOrAdmin($user, $obs->getCeUtilisateur()) ){
            return new JsonResponse(['message' => 'Vous n\'êtes pas autorisé à supprimer ce commentaire '], Response::HTTP_UNAUTHORIZED);
        }

        $parametres = [
            "isPublic" => (bool)$content["transmission"]
        ];
        $celUpdate = $this->externalRequests->modifierObservation($id_observation, $parametres, $token, 'PATCH');
        if ($celUpdate->getStatusCode() !== 200) {
            return new JsonResponse([
                'message' => 'Erreur lors de la modification de l\'observation',
                'error' => json_decode($celUpdate->getContent(), true)
            ], Response::HTTP_BAD_REQUEST);
        }

        return new Response("OK", Response::HTTP_CREATED);
    }
}
