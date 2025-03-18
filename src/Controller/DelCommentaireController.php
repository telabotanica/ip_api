<?php

namespace App\Controller;

use App\Entity\DelCommentaire;
use App\Entity\DelUtilisateurInfos;
use App\Repository\DelCommentaireRepository;
use App\Repository\DelCommentaireVoteRepository;
use App\Repository\DelObservationRepository;
use App\Repository\DelUtilisateurInfosRepository;
use App\Service\AnnuaireService;
use App\Service\CommentaireService;
use App\Service\Mapping;
use App\Service\UrlValidator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class DelCommentaireController extends AbstractController
{
    private EntityManagerInterface $em;
    private DelCommentaireRepository $commentaireRepository;
    private DelObservationRepository $observationRepository;
    private DelCommentaireVoteRepository $voteRepository;
    private DelUtilisateurInfosRepository $delUserRepository;
    private SerializerInterface $serializer;
    private Mapping $mapping;
    private AnnuaireService $annuaire;
    private CommentaireService $commentaireService;
    private array $user = [];

    public function __construct(EntityManagerInterface $em, DelCommentaireRepository $commentaireRepository, DelObservationRepository $observationRepository, DelCommentaireVoteRepository $voteRepository, DelUtilisateurInfosRepository $delUserRepository,SerializerInterface $serializer, Mapping $mapping,  AnnuaireService $annuaire, CommentaireService $commentaireService, array $user = [])
    {
        $this->em = $em;
        $this->commentaireRepository = $commentaireRepository;
        $this->observationRepository = $observationRepository;
        $this->voteRepository = $voteRepository;
        $this->delUserRepository = $delUserRepository;
        $this->serializer = $serializer;
        $this->mapping = $mapping;
        $this->annuaire = $annuaire;
        $this->commentaireService = $commentaireService;
        $this->user = $this->annuaire->getUtilisateurAnonyme();
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

        if ($request->query->get('masque_auteur')) {
            $criteres['auteur'] = $request->query->get('masque_auteur');
        }

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
    public function GetOneComment(int|string $id_commentaire): Response
    {
        $commentaire = $this->commentaireRepository->findOneBy(['id_commentaire' => (int)$id_commentaire]);
        if (!$commentaire) {
            return new JsonResponse(['message' => 'Commentaire: '.$id_commentaire .' introuvable'], Response::HTTP_NOT_FOUND);
        }

        $commentaire = $this->mapping->findVotesByCommentaire($commentaire);

        //TODO: ajouter les commentaires sur plusieurs niveaux

        return new JsonResponse($commentaire, Response::HTTP_OK);
    }

    // toutes les infos sur les votes d'un commentaire
    #[Route('/commentaires/{id_commentaire}/vote', name: 'commentaire_vote', methods: ['GET'])]
    public function GetCommentaireVotes(int|string $id_commentaire): Response
    {
        $votes = $this->voteRepository->findBy(['ce_proposition' => $id_commentaire]);

        if (!$votes) {
            return new JsonResponse(['message' => 'Pas de votes trouvés avec les critères spécifiés'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($votes, 200, [], ['groups' => ['votes']]);
    }

    #[Route('/commentaires/', name: 'put_commentaire', methods: ['PUT'])]
    public function PostCommentaireVote(Request $request): Response
    {
        $erreurs= [];
        $content = json_decode($request->getContent(), true);

        $erreurs = $this->commentaireService->verifierParametres($content, $erreurs);
        if (!empty($erreurs)) {
            $msg = "Erreur de configuration : ".implode(" --- ", $erreurs);
            return new JsonResponse(['error' => $msg], Response::HTTP_BAD_REQUEST);
        }

        $data = $this->mapping->mapNewCommentaire($content);

        $observation = $this->observationRepository->findOneBy(['id_observation' => $data['observation']]);
        if (!$observation) {
            return new JsonResponse(['message' => 'Observation: '.$data['ce_observation'] .' introuvable'], Response::HTTP_NOT_FOUND);
        }

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

        // Transformation de l'observation en array
        $json = $this->serializer->serialize($observation, 'json', ['groups' => 'observations']);
        $obs_array = json_decode($json, true);
        $obs_array['nb_commentaires'] = 0;
        $obs_array = $this->mapping->mapObservation($obs_array);

        //Si c'est le 1er commentaire/vote sur l'obs on crée un 1er commentaire avec les données de l'obs
        if ($obs_array['nb_commentaires'] == 0) {
            $this->commentaireService->creerPremierCommentaire($observation, $obs_array);
        }

//        dd($obs_array, $commentaire);
        $this->em->persist($commentaire);
        $this->em->flush();

        $json = json_encode(["id_commentaire" => $commentaire->getIdCommentaire()], true);
//        $json = $this->serializer->serialize($commentaire, 'json', [
//            'groups' => 'commentaires',
//            'iri' => false
//        ]);

        //TODO: $this->completerParametresUtilisateur(); ????

        return new JsonResponse($json, Response::HTTP_CREATED, [], true);
    }

    #[Route('/commentaires/{id_commentaire}', name: 'delete_commentaire', methods: ['DELETE'])]
    public function DeleteOneComment(Request $request, int|string $id_commentaire): Response
    {
        $commentaire = $this->commentaireRepository->findOneBy(['id_commentaire' => (int)$id_commentaire]);
        if (!$commentaire) {
            return new JsonResponse(['message' => 'Commentaire: '.$id_commentaire .' introuvable'], Response::HTTP_NOT_FOUND);
        }

        $auth = $this->annuaire->getUtilisateurAuthentifie($request);
        if ($auth->getStatusCode() != 200) {
            return new JsonResponse(['message' => 'Vous devez vous connecter pour supprimer ce commentaire '], Response::HTTP_UNAUTHORIZED);
        }

        $user = $this->delUserRepository->findOneBy(['id_utilisateur' => $auth->getContent()]);
        if ( $user->getIdUtilisateur() != $commentaire->getAuteurId() && $user->getAdmin() < 2){
            return new JsonResponse(['message' => 'Vous n\'êtes pas autorisé à supprimer ce commentaire '], Response::HTTP_UNAUTHORIZED);
        }

        $childs = $this->commentaireRepository->findBy(['ce_commentaire_parent' => $id_commentaire]);
        if ($childs){
            return new JsonResponse(['message' => 'Le commentaire: '.$id_commentaire .' ne peut pas être supprimé car des commentaires enfants existent'], Response::HTTP_UNAUTHORIZED);
        }

        $this->em->remove($commentaire);
        $this->em->flush();

        $json = json_encode(["ok" => 'Commentaire '. $id_commentaire .' supprimé'], true);

        return new JsonResponse($json, Response::HTTP_ACCEPTED);
    }

    protected function completerInfosUtilisateur() {
        $this->user['session_id'] = session_id();
        $this->user['connecte'] = true;
    }

}
