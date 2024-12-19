<?php

namespace App\Controller;

use App\Repository\DelImageRepository;
use App\Service\Mapping;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ImageController extends AbstractController
{
    private SerializerInterface $serializer;
    private DelImageRepository $imageRepository;
    private Mapping $mapping;

    public function __construct(SerializerInterface $serializer, DelImageRepository $imageRepository, Mapping $mapping)
    {
        $this->serializer = $serializer;
        $this->imageRepository = $imageRepository;
        $this->mapping = $mapping;
    }

    #[Route('/images', name: 'image_all', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        //TODO: add entete ?
        //TODO: add search criterias ?
        $criteres = $this->mapping->getUrlCriterias($request);

        $images = $this->imageRepository->findAllPaginated($criteres);

        if (!$images) {
            return new JsonResponse(['message' => 'Pas d\'images trouvées'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($images, 200, [], ['groups' => ['images']]);
    }

    #[Route('/images/{id_image}', name: 'image_single', methods: ['GET'])]
    public function GetOneImage(int $id_image): JsonResponse
    {
        $image = $this->imageRepository->findOneBy(['id_image' => $id_image]);
        if (!$image) {
            return new JsonResponse(['message' => 'Image: '.$id_image .' introuvable'], Response::HTTP_NOT_FOUND);
        }

        $json = $this->serializer->serialize($image, 'json', ['groups' => 'images']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
