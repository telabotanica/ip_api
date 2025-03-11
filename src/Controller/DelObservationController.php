<?php

namespace App\Controller;

use App\Repository\DelCommentaireRepository;
use App\Repository\DelCommentaireVoteRepository;
use App\Repository\DelObservationRepository;
use App\Repository\DelUtilisateurInfosRepository;
use App\Service\AnnuaireService;
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
    private AnnuaireService $annuaire;
    private array $user = [];

    public function __construct(EntityManagerInterface $em, DelObservationRepository $obsRepository, DelCommentaireVoteRepository $voteRepository, DelCommentaireRepository $commentaireRepository, DelUtilisateurInfosRepository $delUserRepository, SerializerInterface $serializer, Mapping $mapping, ExternalRequests $externalRequests, UrlValidator $urlValidator, AnnuaireService $annuaire, array $user = [])
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
        $this->annuaire = $annuaire;
        $this->user = $this->annuaire->getUtilisateurAnonyme();
    }

    #[Route('/observations', name: 'observation_all',methods:['GET'])]
    public function index(Request $request): JsonResponse
    {
        $criteres = $this->mapping->getUrlCriterias($request);
        $filters = $this->urlValidator->mapUrlParameters($request);

        if ($criteres['masque.type'] == 'monactivite') {
            $auth = $this->getUtilisateurAuthentifie($request);

            if ($auth->getStatusCode() != 200) {
                return new JsonResponse(['error' => 'Vous devez être connecté pour accéder à cette page'], Response::HTTP_UNAUTHORIZED);
            }

//            $cookie = $request->cookies->get($this->annuaire->getCookieName()) ?? null;
//            if (!$cookie) {
//                return new JsonResponse(['message' => 'Vous devez vous connecter pour accéder à votrea activité'], Response::HTTP_UNAUTHORIZED);
//            }
            $this->user = json_decode($auth->getContent(), true);
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
    public function GetOneObs(int $id_observation): Response
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

    private function getUtilisateurAuthentifie($request): Response
    {
        $authHeader = $request->headers->get('Authorization') ?? null;

        $jetonValide = $this->annuaire->verifierJeton($authHeader);
        if (!$jetonValide) {
            return new JsonResponse(['error' => 'Le token est invalide ou a expiré'], Response::HTTP_UNAUTHORIZED);
        }

        $jetonDecode = $this->annuaire->decodeToken($authHeader);
        if ($jetonDecode == null || !isset($jetonDecode['sub']) || $jetonDecode['sub'] == '') {
            return new JsonResponse(['error' => 'Erreur lors du décodage du jeton'], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->delUserRepository->findOneBy(['id_utilisateur' => $jetonDecode['id']]);
        if ($user == null) {
            //1ere connexion au del
            if (!$user->getPreferences()) {
                $user->setPreferences('{"mail_notification_mes_obs":"1","mail_notification_toutes_obs":"0"}');
            }
            $user->setDatePremiereUtilisation(new \DateTime());
            $user->setDateDerniereConsultationEvenements(new \DateTime());

            $this->em->persist($user);
            $this->em->flush();
            $user = $this->delUserRepository->findOneBy(['id_utilisateur' => $jetonDecode['id']]);
        } else {
            // Vérifier si le profil a changé
            $profilUpdated = $this->annuaire->profilAChange($user, $jetonDecode);
            if ($profilUpdated) {
                $this->updateLocalProfil($user, $jetonDecode);
                $this->updateCommentsUserInfos($jetonDecode);
                $user = $this->delUserRepository->findOneBy(['id_utilisateur' => $jetonDecode['id']]);
            }
        }

        $json = $this->serializer->serialize($user, 'json', ['groups' => 'user']);

        return new Response($json, Response::HTTP_OK);
    }

    private function updateLocalProfil($user, $jetonDecode): bool
    {
        $user->setNom($jetonDecode['nom']);
        $user->setPrenom($jetonDecode['prenom']);
        $user->setIntitule($jetonDecode['intitule']);
        $user->setCourriel($jetonDecode['sub']);

        $this->em->persist($user);
        $this->em->flush();

        return true;
    }

    private function updateCommentsUserInfos($jetonDecode): bool
    {
        if ($jetonDecode != null && $jetonDecode['id'] != '' && ($jetonDecode['nom'] != '' || $jetonDecode['prenom'] != '')){
            $userComments = $this->commentaireRepository->findBy(['ce_utilisateur' => $jetonDecode['id']]);

            if ($userComments){
                foreach ($userComments as $commentaire) {
                    $commentaire->setNom($jetonDecode['nom']);
                    $commentaire->setPrenom($jetonDecode['prenom']);
                    $this->em->persist($commentaire);
                }
                $this->em->flush();
            };
            $this->em->flush();
        }
        return true;
    }

    protected function completerInfosUtilisateur() {
        $this->user['session_id'] = session_id();
        $this->user['connecte'] = true;
    }
}
