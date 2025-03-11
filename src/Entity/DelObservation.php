<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Controller\DelObservationController;
use App\Repository\DelObservationRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: DelObservationRepository::class)]
#[ApiResource(
    shortName: 'Observations',
    operations: [
        new GetCollection(
            uriTemplate: '/observations',
            openapiContext: [
            'summary' => 'Get paginated observations',
            'description' => 'Charge les observations pour les outils de DEL et principalement pour Identiplante',
                'parameters' => [
                    [
                        'name' => 'navigation.depart',
                        'in' => 'query',
                        'description' => 'Starting index ',
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
                            'enum' => ['date_transmission', 'date_observation', 'nb_commentaires'],
                        ],
                    ],
                    [
                        'name' => 'masque.type',
                        'in' => 'query',
                        'description' => 'Select the type of observations (tous by default)',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'enum' => ['adeterminer', 'aconfirmer', 'validees', 'monactivite', 'tous'],
                        ],
                    ],
                    [
                        'name' => 'masque.pninscritsseulement',
                        'in' => 'query',
                        'description' => 'Only get result with registered user (true by default)',
                        'required' => false,
                        'schema' => ['type' => 'boolean']
                    ],
                    [
                        'name' => 'masque',
                        'in' => 'query',
                        'description' => 'Free search by taxon or author',
                        'required' => false,
                        'schema' => ['type' => 'string']
                    ],
                    [
                        'name' => 'masque.referentiel',
                        'in' => 'query',
                        'description' => 'search by referentiel',
                        'required' => false,
                        'schema' => [
                            'type' => 'string',
                            'enum' => ['bdtfx', 'bdtxa', 'bdtre', 'aublet', 'florical', "isfan", "apd", "lbf", "taxreflich", "taxref"],
                        ],
                    ],
                    [
                        'name' => 'masque.famille',
                        'in' => 'query',
                        'description' => 'search by taxon family',
                        'required' => false,
                        'schema' => ['type' => 'string']
                    ],
                    [
                        'name' => 'masque.genre',
                        'in' => 'query',
                        'description' => 'search by taxon genre (alias for nom_sel)',
                        'required' => false,
                        'schema' => ['type' => 'string']
                    ],
                    [
                        'name' => 'masque.nom_ret',
                        'in' => 'query',
                        'description' => 'Search by taxon scientific name',
                        'required' => false,
                        'schema' => ['type' => 'string']
                    ],
                    [
                        'name' => 'masque.nom_ret_nn',
                        'in' => 'query',
                        'description' => 'Search by taxon scientific id',
                        'required' => false,
                        'schema' => ['type' => 'integer']
                    ],
                    [
                        'name' => 'masque.ns',
                        'in' => 'query',
                        'description' => 'Search by taxon scientific id',
                        'required' => false,
                        'schema' => ['type' => 'integer']
                    ],
                    [
                        'name' => 'masque.nom_sel',
                        'in' => 'query',
                        'description' => 'Search by selected taxon scientific name',
                        'required' => false,
                        'schema' => ['type' => 'string']
                    ],
                    [
                        'name' => 'masque.nom_sel_nn',
                        'in' => 'query',
                        'description' => 'Search by selected taxon scientific id',
                        'required' => false,
                        'schema' => ['type' => 'integer']
                    ],
                    [
                        'name' => 'masque.date',
                        'in' => 'query',
                        'description' => 'Search by observation date (dd/mm/yyyy or yyyy)',
                        'required' => false,
                        'schema' => ['type' => 'string']
                    ],
                    [
                        'name' => 'masque.pays',
                        'in' => 'query',
                        'description' => 'Search by observation country',
                        'required' => false,
                        'schema' => ['type' => 'string']
                    ],
                    [
                        'name' => 'masque.cp',
                        'in' => 'query',
                        'description' => 'Search by observation post code',
                        'required' => false,
                        'schema' => ['type' => 'string']
                    ],
                    [
                        'name' => 'masque.departement',
                        'in' => 'query',
                        'description' => 'Search by observation region (France only)',
                        'required' => false,
                        'schema' => ['type' => 'string']
                    ],
                    [
                        'name' => 'masque.commune',
                        'in' => 'query',
                        'description' => 'Search by observation city',
                        'required' => false,
                        'schema' => ['type' => 'string']
                    ],
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
                        'description' => 'Search by observation tags',
                        'required' => false,
                        'schema' => ['type' => 'string']
                    ],

                ],
            ],
            paginationEnabled: false,
            denormalizationContext: ['groups' => ['observations']],
            name: 'observation_all',),
        new Get(uriTemplate: '/observations/{id_observation}', denormalizationContext: ['groups' => ['observations']], name: 'observation_single'),
        new Get(
            uriTemplate: '/observations/{id_observation}/vote',
            openapiContext: [
                'summary' => 'Get observation votes',
                'description' => 'Get observation votes',
            ],
            denormalizationContext: ['groups' => ['votes']],
            name: 'observation_vote'),
        new Get(
            uriTemplate: '/observations/{id_observation}/{id_commentaire}/vote',
            openapiContext: [
                'summary' => 'Get all votes from a proposition',
                'description' => 'Get all votes from a proposition',
            ],
            denormalizationContext: ['groups' => ['votes']],
            name: 'proposition_vote'),
    ],
    formats: ["json"],
    controller: DelObservationController::class
)]
class DelObservation
{
    #[Groups(['observations', 'commentaires', 'images'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_observation', type: 'bigint')]
    private ?int $id_observation = null;

    #[Groups(['observations', 'images'])]
    #[SerializedName('auteur.id')]
    #[ORM\Column(nullable: true)]
    private ?int $ce_utilisateur = null;

    #[Groups(['observations', 'images'])]
    #[SerializedName('auteur.nom')]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom_utilisateur = null;

    #[Groups(['observations', 'images'])]
    #[SerializedName('auteur.prenom')]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prenom_utilisateur = null;

    #[Groups(['observations', 'images'])]
    #[SerializedName('auteur.courriel')]
    #[ORM\Column(length: 155, nullable: true)]
    private ?string $courriel_utilisateur = null;

    #[Groups(['observations', 'images'])]
    #[SerializedName('determination.ns')]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom_sel = null;

    #[Groups(['observations', 'images'])]
    #[SerializedName('determination.nn')]
    #[ORM\Column(nullable: true)]
    private ?int $nom_sel_nn = null;

    #[Groups(['observations', 'images'])]
    #[SerializedName('determination.nom_ret')]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom_ret = null;

    #[Groups(['observations', 'images'])]
    #[SerializedName('determination.nom_ret_nn')]
    #[ORM\Column(nullable: true)]
    private ?int $nom_ret_nn = null;

    #[Groups(['observations'])]
    #[SerializedName('determination.nt')]
    #[ORM\Column(nullable: true)]
    private ?int $nt = null;

    #[Groups(['observations', 'images'])]
    #[SerializedName('determination.referentiel')]
    #[ORM\Column(length: 25, nullable: true)]
    private ?string $nom_referentiel = null;

    #[Groups(['observations', 'images'])]
    #[SerializedName('determination.famille')]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $famille = null;

    #[Groups(['observations', 'images'])]
    #[SerializedName('id_zone_geo')]
    #[ORM\Column(length: 5, nullable: true)]
    private ?string $ce_zone_geo = null;

    #[Groups(['observations', 'images'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zone_geo = null;

    #[Groups(['observations', 'images'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieudit = null;

    #[Groups(['observations', 'images'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $station = null;

    #[Groups(['observations', 'images'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $milieu = null;

    #[Groups(['observations', 'images'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_observation = null;

    #[Groups(['observations', 'images'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $mots_cles_texte = null;

    #[Groups(['observations'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $commentaire = null;

    #[Groups(['observations'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_creation = null;

    #[Groups(['observations'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_modification = null;

    #[Groups(['observations', 'images'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_transmission = null;

    #[Groups(['observations'])]
    #[ORM\Column(length: 25, nullable: true)]
    private ?string $certitude = null;

    #[Groups(['observations', 'images'])]
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $pays = null;

    #[Groups(['observations'])]
    #[ORM\Column(length: 15, nullable: true)]
    private ?string $input_source = null;

    #[Groups(['observations'])]
    #[ORM\Column(nullable: true)]
    private ?bool $donnees_standard = null;

    #[ORM\OneToMany(targetEntity: DelCommentaire::class, mappedBy: "ce_observation")]
    private ?Collection $commentaires;

    public function getIdObservation(): ?int
    {
        return $this->id_observation;
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

    public function getNomUtilisateur(): ?string
    {
        return $this->nom_utilisateur;
    }

    public function setNomUtilisateur(?string $nom_utilisateur): static
    {
        $this->nom_utilisateur = $nom_utilisateur;

        return $this;
    }

    public function getPrenomUtilisateur(): ?string
    {
        return $this->prenom_utilisateur;
    }

    public function setPrenomUtilisateur(?string $prenom_utilisateur): static
    {
        $this->prenom_utilisateur = $prenom_utilisateur;

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

    public function getNomSel(): ?string
    {
        return $this->nom_sel;
    }

    public function setNomSel(?string $nom_sel): static
    {
        $this->nom_sel = $nom_sel;

        return $this;
    }

    public function getNomRet(): ?string
    {
        return $this->nom_ret;
    }

    public function setNomRet(?string $nom_ret): static
    {
        $this->nom_ret = $nom_ret;

        return $this;
    }

    public function getNomSelNn(): ?int
    {
        return $this->nom_sel_nn;
    }

    public function setNomSelNn(?int $nom_sel_nn): static
    {
        $this->nom_sel_nn = $nom_sel_nn;

        return $this;
    }

    public function getNomRetNn(): ?int
    {
        return $this->nom_ret_nn;
    }

    public function setNomRetNn(?int $nom_ret_nn): static
    {
        $this->nom_ret_nn = $nom_ret_nn;

        return $this;
    }

    public function getNt(): ?int
    {
        return $this->nt;
    }

    public function setNt(?int $nt): static
    {
        $this->nt = $nt;

        return $this;
    }

    public function getFamille(): ?string
    {
        return $this->famille;
    }

    public function setFamille(?string $famille): static
    {
        $this->famille = $famille;

        return $this;
    }

    public function getCeZoneGeo(): ?string
    {
        return $this->ce_zone_geo;
    }

    public function setCeZoneGeo(?string $ce_zone_geo): static
    {
        $this->ce_zone_geo = $ce_zone_geo;

        return $this;
    }

    public function getZoneGeo(): ?string
    {
        return $this->zone_geo;
    }

    public function setZoneGeo(?string $zone_geo): static
    {
        $this->zone_geo = $zone_geo;

        return $this;
    }

    public function getLieudit(): ?string
    {
        return $this->lieudit;
    }

    public function setLieudit(?string $lieudit): static
    {
        $this->lieudit = $lieudit;

        return $this;
    }

    public function getStation(): ?string
    {
        return $this->station;
    }

    public function setStation(?string $station): static
    {
        $this->station = $station;

        return $this;
    }

    public function getMilieu(): ?string
    {
        return $this->milieu;
    }

    public function setMilieu(?string $milieu): static
    {
        $this->milieu = $milieu;

        return $this;
    }

    public function getNomReferentiel(): ?string
    {
        return $this->nom_referentiel;
    }

    public function setNomReferentiel(?string $nom_referentiel): static
    {
        $this->nom_referentiel = $nom_referentiel;

        return $this;
    }

    public function getDateObservation(): ?\DateTimeInterface
    {
        return $this->date_observation;
    }

    public function setDateObservation(?\DateTimeInterface $date_observation): static
    {
        $this->date_observation = $date_observation;

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

    public function getDateTransmission(): ?\DateTimeInterface
    {
        return $this->date_transmission;
    }

    public function setDateTransmission(?\DateTimeInterface $date_transmission): static
    {
        $this->date_transmission = $date_transmission;

        return $this;
    }

    public function getCertitude(): ?string
    {
        return $this->certitude;
    }

    public function setCertitude(?string $certitude): static
    {
        $this->certitude = $certitude;

        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(?string $pays): static
    {
        $this->pays = $pays;

        return $this;
    }

    public function getInputSource(): ?string
    {
        return $this->input_source;
    }

    public function setInputSource(?string $input_source): static
    {
        $this->input_source = $input_source;

        return $this;
    }

    public function isDonneesStandard(): ?bool
    {
        return $this->donnees_standard;
    }

    public function setDonneesStandard(?bool $donnees_standard): static
    {
        $this->donnees_standard = $donnees_standard;

        return $this;
    }

    public function getImages(): ?array
    {
        return $this->images;
    }
}
