<?php

namespace App\Dto;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Controller\OntologieController;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/ontologie/pays',
            controller: OntologieController::class,
            paginationEnabled: false,
            name: "pays"
        )
    ],
    formats: ["json"],

)]
class Pays
{
    public string $code_iso_3166_1;
    public string $nom_fr;

    public function __construct(string $code_iso_3166_1, string $nom_fr)
    {
        $this->code_iso_3166_1 = $code_iso_3166_1;
        $this->nom_fr = $nom_fr;
    }
}
