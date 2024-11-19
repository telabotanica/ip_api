<?php

namespace App\Controller;

use App\Repository\DelUtilisateurInfosRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class DelUtilisateurInfosController extends AbstractController
{
    #[Route('/utilisateur_infos/{id}', name: 'app_del_utilisateur_infos')]
    public function index(int $id, DelUtilisateurInfosRepository $delUserRepository, SerializerInterface $serializer): Response
    {
        $user = $delUserRepository->findOneBy(['id_utilisateur' => $id]);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $json = $serializer->serialize($user, 'json', ['groups' => 'user']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
