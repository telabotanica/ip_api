<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExternalRequests
{
    private string $urlServiceBaseEflore;
    private string $urlServiceCelObs;

    public function __construct(
        private HttpClientInterface $client, string $urlServiceBaseEflore, string $urlServiceCelObs
    ) {
        $this->urlServiceBaseEflore = $urlServiceBaseEflore;
        $this->urlServiceCelObs = $urlServiceCelObs;
    }

    public function getPays(): array
    {
        $url = $this->urlServiceBaseEflore."iso-3166-1/zone-geo?masque.statut=officiellement%20attribu%C3%A9&navigation.limite=1000";

        try {
            $response = $this->client->request('GET', $url);
        } catch (\Throwable $e) {
            $this->logger->error('Error while fetching countries', ['exception' => $e]);
            throw $e;
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200) {
            throw new \Exception('Error in ExternalRequests service while retrieving countries data');
        }

        $liste_pays = json_decode($response->getContent(), true);

        if (!isset($liste_pays['resultat']) || !is_array($liste_pays['resultat'])) {
            throw new \Exception('Invalid data structure in response from external service');
        }

        $pays_fmt = array();
        foreach($liste_pays['resultat'] as $pays) {
            // Les pays renvoyé par le web service sont tous en majuscule
            $nom = mb_convert_case($pays['nom'], MB_CASE_TITLE, 'UTF-8');
            $pays_fmt[] = array('code_iso_3166_1' => $pays['code'], 'nom_fr' => $nom);
        }

        // Tri par nom plutot que par code
        usort($pays_fmt, fn($a, $b) => strcmp($a['nom_fr'], $b['nom_fr']));

        return $pays_fmt;
    }

    protected function trierPays($a, $b) {
        return strcmp($a['nom_fr'], $b['nom_fr']);
    }

    public function modifierObservation($obs_id, $parametres, $token, $type): Response
    {
        $url = $this->urlServiceCelObs.$obs_id;
        $json = json_encode($parametres);

        $response = $this->client->request($type, $url, [
            'headers' => [
                'Authorization' => $token,
                'Content-Type' => 'application/json'
            ],
            'body' => $json
        ]);

        if ($response->getStatusCode() !== 200) {
            return new JsonResponse([
                'message' => 'Erreur lors de la modification de l\'observation',
                'error' => $response->getContent()
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse($response->getContent(), Response::HTTP_OK);
    }
}