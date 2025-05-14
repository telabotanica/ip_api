<?php

namespace App\Controller;

use App\Repository\DelUtilisateurInfosRepository;
use App\Service\AnnuaireService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class DelUtilisateurInfosController extends AbstractController
{
    #[Route('/utilisateur_infos/{id}', name: 'app_del_utilisateur_infos', methods: ['GET'])]
    public function index(int $id, DelUtilisateurInfosRepository $delUserRepository, SerializerInterface $serializer): Response
    {
        $user = $delUserRepository->findOneBy(['id_utilisateur' => $id]);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $json = $serializer->serialize($user, 'json', ['groups' => 'user']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/utilisateurs/', name: 'del_utilisateurs', methods: ['GET'])]
    public function getUserInfo(DelUtilisateurInfosRepository $delUserRepository, SerializerInterface $serializer, Request $request, AnnuaireService $annuaire, EntityManagerInterface $em): Response
    {
        $auth = $annuaire->getUtilisateurAuthentifie($request);
        if ($auth->getStatusCode() != 200) {
            return new JsonResponse(['message' => 'Utilisateur non enregristré ou token manquant'], Response::HTTP_UNAUTHORIZED);
        }
        $user = $delUserRepository->findOneBy(['id_utilisateur' => $auth->getContent()]);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $user->setDateDerniereConsultationEvenements(new \DateTime('now'));
        $em->persist($user);
        $em->flush();

        $json = $serializer->serialize($user, 'json', ['groups' => 'user']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
