<?php

namespace App\Controller;

use App\Repository\DelObservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class DelObservationController extends AbstractController
{
    private EntityManagerInterface $em;
    private DelObservationRepository $obsRepository;
    private SerializerInterface $serializer;

    public function __construct(EntityManagerInterface $em, DelObservationRepository $obsRepository, SerializerInterface $serializer)
    {
        $this->em = $em;
        $this->obsRepository = $obsRepository;
        $this->serializer = $serializer;
    }

    #[Route('/observation/{id}', name: 'app_del_observation', methods: ['GET'])]
    public function GetOneObs(int $id): Response
    {
        $obs = $this->obsRepository->find($id);
        if (!$obs) {
            throw $this->createNotFoundException(
                'Pas \'observation avec l\'id '.$id
            );
        }

        $json = $this->serializer->serialize($obs, 'json', ['groups' => 'observations']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
