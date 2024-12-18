<?php

namespace App\Controller;

use ApiPlatform\Metadata\ApiResource;
use App\Dto\Pays;
use App\Service\ExternalRequests;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class OntologieController extends AbstractController
{
    private ExternalRequests $externalRequests;
    private SerializerInterface $serializer;

    public function __construct(ExternalRequests $externalRequests, SerializerInterface $serializer)
    {
        $this->externalRequests = $externalRequests;
        $this->serializer = $serializer;
    }

    #[Route('/ontologie/pays', name: 'pays', methods: ['GET'])]
    public function GetPays(): Response
    {
        $data = $this->externalRequests->getPays();

        // Transforme les données en objets DTO `Pays`
        $paysCollection = [];
        foreach ($data as $item) {
            $paysCollection[] = new Pays($item['code_iso_3166_1'], $item['nom_fr']);
        }

        $json = $this->serializer->serialize($paysCollection, 'json');

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
