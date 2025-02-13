<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Controller\ImageController;
use App\Repository\DelImageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: DelImageRepository::class, readOnly: true)]
#[ORM\Table(name: 'del_image')]
#[ApiResource(
    shortName: 'Images',
    operations: [
        new GetCollection(
            uriTemplate: '/images',
            openapiContext: [
                'summary' => 'Get paginated images',
                'description' => 'Get paginated images',
                'parameters' => [
                    [
                        'name' => 'navigation.depart',
                        'in' => 'query',
                        'description' => 'Starting index',
                        'required' => false,
                        'schema' => ['type' => 'integer'],
                        'default' => 0,
                    ],
                    [
                        'name' => 'navigation.limite',
                        'in' => 'query',
                        'description' => 'Number of results',
                        'required' => false,
                        'schema' => ['type' => 'integer'],
                        'default' => 12,
                    ],
                    [
                        'name' => 'ordre',
                        'in' => 'query',
                        'description' => 'select data by newer or older first (newer data: desc by default)',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'enum' => ['desc', 'asc'],
                        ],
                    ],
                    [
                        'name' => 'tri',
                        'in' => 'query',
                        'description' => 'Select by wich field the result will be sorted (date_transmission by default)',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'enum' => ['date_transmission', 'date_observation'],
                        ],
                    ],
//                    [
//                        'name' => 'type',
//                        'in' => 'query',
//                        'description' => 'Select the type of observations (tous by default)',
//                        'required' => false,
//                        'schema' => [
//                            'type' => 'string',
//                            'enum' => ['adeterminer', 'aconfirmer', 'validees', 'monactivite', 'tous'],
//                        ],
//                    ],
                    [
                        'name' => 'masque.pninscritsseulement',
                        'in' => 'query',
                        'description' => 'Only get result with registered user (true by default)',
                        'required' => false,
                        'schema' => ['type' => 'boolean']
                    ],
//                    [
//                        'name' => 'masque',
//                        'in' => 'query',
//                        'description' => 'Free search of taxons',
//                        'required' => false,
//                        'schema' => ['type' => 'string']
//                    ],
//                    [
//                        'name' => 'masque.referentiel',
//                        'in' => 'query',
//                        'description' => 'search by referentiel',
//                        'required' => false,
//                        'schema' => [
//                            'type' => 'string',
//                            'enum' => ['bdtfx', 'bdtxa', 'bdtre', 'aublet', 'florical', "isfan", "apd", "lbf", "taxreflich", "taxref"],
//                        ],
//                    ],
//                    [
//                        'name' => 'masque.famille',
//                        'in' => 'query',
//                        'description' => 'search by taxon family',
//                        'required' => false,
//                        'schema' => ['type' => 'string']
//                    ],
//                    [
//                        'name' => 'masque.genre',
//                        'in' => 'query',
//                        'description' => 'search by taxon genre',
//                        'required' => false,
//                        'schema' => ['type' => 'string']
//                    ],
//                    [
//                        'name' => 'masque.ns',
//                        'in' => 'query',
//                        'description' => 'Search by taxon scientific name or id',
//                        'required' => false,
//                        'schema' => ['type' => 'string']
//                    ],
                    [
                        'name' => 'masque.date',
                        'in' => 'query',
                        'description' => 'Search by creation date (dd/mm/yyyy or yyyy)',
                        'required' => false,
                        'schema' => ['type' => 'string']
                    ],
//                    [
//                        'name' => 'masque.pays',
//                        'in' => 'query',
//                        'description' => 'Search by observation country',
//                        'required' => false,
//                        'schema' => ['type' => 'string']
//                    ],
//                    [
//                        'name' => 'masque.departement',
//                        'in' => 'query',
//                        'description' => 'Search by observation region (France only)',
//                        'required' => false,
//                        'schema' => ['type' => 'string']
//                    ],
//                    [
//                        'name' => 'masque.commune',
//                        'in' => 'query',
//                        'description' => 'Search by observation city',
//                        'required' => false,
//                        'schema' => ['type' => 'string']
//                    ],
                    [
                        'name' => 'masque.auteur',
                        'in' => 'query',
                        'description' => 'Search by user',
                        'required' => false,
                        'schema' => ['type' => 'string']
                    ],
                    [
                        'name' => 'masque.tag',
                        'in' => 'query',
                        'description' => 'Search by imagetags',
                        'required' => false,
                        'schema' => ['type' => 'string']
                    ],

                ],
            ],
            paginationEnabled: false,
            denormalizationContext: ['groups' => ['images']],
            name: 'image_all',
        ),
        new Get(uriTemplate: '/images/{id_image}', denormalizationContext: ['groups' => ['images']], name: 'image_single'),
    ],
    formats: ["json"],
    controller: ImageController::class,
)]
class DelImage
{
    #[Groups(['images'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_image', type: 'bigint')]
    private ?int $id_image = null;

//    #[Groups(['images'])]
    #[SerializedName('auteur.id')]
    #[ORM\Column(nullable: true)]
    private ?int $ce_utilisateur = null;

    #[Groups(['images'])]
    #[SerializedName('observation')]
    #[ORM\JoinColumn(name: 'ce_observation', referencedColumnName: 'id_observation', nullable: false)]
    #[ORM\ManyToOne(targetEntity: DelObservation::class)]
    private ?DelObservation $ce_observation = null;

//    #[Groups(['images'])]
//    #[ORM\Column(length: 255)]
//    private ?string $prenom_utilisateur = null;

//    #[Groups(['images'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom_utilisateur = null;

//    #[Groups(['images'])]
    #[ORM\Column(length: 155, nullable: true)]
    private ?string $courriel_utilisateur = null;

    #[Groups(['images'])]
    #[ORM\Column(length: 4, nullable: true)]
    private ?string $hauteur = null;

    #[Groups(['images'])]
    #[ORM\Column(length: 4, nullable: true)]
    private ?string $largeur = null;

    #[Groups(['images'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_prise_de_vue = null;

    #[Groups(['images'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $mots_cles_texte = null;

    #[Groups(['images'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $commentaire = null;

    #[Groups(['images'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom_original = null;

    #[Groups(['images'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_creation = null;

    #[Groups(['images'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_modification = null;

    #[Groups(['images'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_liaison = null;

    #[Groups(['images'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_transmission = null;

    public function getIdImage(): ?int
    {
        return $this->id_image;
    }

    public function getCeUtilisateur(): ?int
    {
        return $this->ce_utilisateur;
    }

    public function setCeUtilisateur(?int $ce_utilisateur): static
    {
        $this->ce_utilisateur = $ce_utilisateur;

        return $this;
    }

    #[Groups(['images'])]
    #[SerializedName('observation')]
    public function getCeObservation(): ?DelObservation
    {
        return $this->ce_observation;
    }

//    #[Groups(['images'])]
//    #[SerializedName('observation')]
//    public function getObservation(): ?int
//    {
//        return $this->ce_observation ? $this->ce_observation->getIdObservation() : null;
//    }

    public function setCeObservation(?DelObservation $ce_observation): static
    {
        $this->ce_observation = $ce_observation;

        return $this;
    }

//    public function getPrenomUtilisateur(): ?string
//    {
//        return $this->prenom_utilisateur;
//    }
//
//    public function setPrenomUtilisateur(string $prenom_utilisateur): static
//    {
//        $this->prenom_utilisateur = $prenom_utilisateur;
//
//        return $this;
//    }

    public function getNomUtilisateur(): ?string
    {
        return $this->nom_utilisateur;
    }

    public function setNomUtilisateur(?string $nom_utilisateur): static
    {
        $this->nom_utilisateur = $nom_utilisateur;

        return $this;
    }

    public function getCourrielUtilisateur(): ?string
    {
        return $this->courriel_utilisateur;
    }

    public function setCourrielUtilisateur(?string $courriel_utilisateur): static
    {
        $this->courriel_utilisateur = $courriel_utilisateur;

        return $this;
    }

    public function getHauteur(): ?string
    {
        return $this->hauteur;
    }

    public function setHauteur(?string $hauteur): static
    {
        $this->hauteur = $hauteur;

        return $this;
    }

    public function getLargeur(): ?string
    {
        return $this->largeur;
    }

    public function setLargeur(?string $largeur): static
    {
        $this->largeur = $largeur;

        return $this;
    }

    public function getDatePriseDeVue(): ?\DateTimeInterface
    {
        return $this->date_prise_de_vue;
    }

    public function setDatePriseDeVue(?\DateTimeInterface $date_prise_de_vue): static
    {
        $this->date_prise_de_vue = $date_prise_de_vue;

        return $this;
    }

    public function getMotsClesTexte(): ?string
    {
        return $this->mots_cles_texte;
    }

    public function setMotsClesTexte(?string $mots_cles_texte): static
    {
        $this->mots_cles_texte = $mots_cles_texte;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getNomOriginal(): ?string
    {
        return $this->nom_original;
    }

    public function setNomOriginal(?string $nom_original): static
    {
        $this->nom_original = $nom_original;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDateCreation(?\DateTimeInterface $date_creation): static
    {
        $this->date_creation = $date_creation;

        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->date_modification;
    }

    public function setDateModification(?\DateTimeInterface $date_modification): static
    {
        $this->date_modification = $date_modification;

        return $this;
    }

    public function getDateLiaison(): ?\DateTimeInterface
    {
        return $this->date_liaison;
    }

    public function setDateLiaison(?\DateTimeInterface $date_liaison): static
    {
        $this->date_liaison = $date_liaison;

        return $this;
    }

    public function getDateTransmission(): ?\DateTimeInterface
    {
        return $this->date_transmission;
    }

    public function setDateTransmission(?\DateTimeInterface $date_transmission): static
    {
        $this->date_transmission = $date_transmission;

        return $this;
    }
}
