<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExternalRequests
{
    private string $urlServiceBaseEflore;

    public function __construct(
        private HttpClientInterface $client, string $urlServiceBaseEflore
    ) {
        $this->urlServiceBaseEflore = $urlServiceBaseEflore;
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
}