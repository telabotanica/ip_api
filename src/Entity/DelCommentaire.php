<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Controller\DelCommentaireController;
use App\Repository\DelCommentaireRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: DelCommentaireRepository::class)]
#[ApiResource(
    shortName: 'Commentaires',
    operations: [
        new GetCollection(
            uriTemplate: '/commentaires',
            openapiContext: [
                'summary' => 'Get paginated comments',
                'description' => 'Get paginated comments',
                'parameters' => [
                    [
                        'name' => 'navigation.depart',
                        'in' => 'query',
                        'description' => 'Starting index (page number -1, for example 0 for page 1)',
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
                        'name' => 'masque.pninscritsseulement',
                        'in' => 'query',
                        'description' => 'Only get result with registered user (true by default)',
                        'required' => false,
                        'schema' => ['type' => 'boolean']
                    ],
                    [
                        'name' => 'masque.auteur',
                        'in' => 'query',
                        'description' => 'Search by user',
                        'required' => false,
                        'schema' => ['type' => 'string']
                    ],
                ],
            ],
            paginationEnabled: false,
            denormalizationContext: ['groups' => ['commentaires']],
            name: 'commentaire_all',),
        new Get(uriTemplate: '/commentaires/{id_commentaire}', denormalizationContext: ['groups' => ['commentaires']], name: 'commentaire_single'),
        new Put(uriTemplate: '/commentaires/', denormalizationContext: ['groups' => ['commentaires_post']], name: 'put_commentaire'),
        new Delete(uriTemplate: '/commentaires/{id_commentaire}', denormalizationContext: ['groups' => ['commentaires']], name: 'delete_commentaire'),
        new Post(
            uriTemplate: '/determinations/valider-determination/{id_proposition}',
            openapiContext: [
                'summary' => 'Validate a proposition',
                'description' => 'Validate a proposition',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'auteur.id' => ['type' => 'integer'],
                                    'validateur.id' => ['type' => 'integer'],
                                ],
                                'required' => ['auteur.id', 'validateur.id'],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '201' => [
                        'description' => 'ok',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'string',
                                    'example' => 'ok'
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            denormalizationContext: ['groups' => ['proposition_valider']],
            name: 'valider'
        )
    ],
    formats: ["json"],
    controller: DelCommentaireController::class
)]
class DelCommentaire
{
    #[Groups(['commentaires'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_commentaire', type: 'bigint')]
    #[ApiProperty(identifier: true)]
    private int|string|null $id_commentaire = null;

    #[Groups(['commentaires', 'commentaires_post'])]
    #[SerializedName('observation')]
    #[ORM\JoinColumn(name: 'ce_observation', referencedColumnName: 'id_observation', nullable: false)]
    #[ORM\ManyToOne(targetEntity: DelObservation::class)]
    private ?DelObservation $ce_observation = null;

    #[Groups(['commentaires', 'commentaires_post'])]
    #[SerializedName('proposition')]
    #[ORM\Column]
    private ?int $ce_proposition = null;

    #[Groups(['commentaires', 'commentaires_post'])]
    #[SerializedName('id_parent')]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $ce_commentaire_parent = null;
//    #[Groups(['commentaires'])]
//    #[SerializedName('id_parent')]
//    #[ORM\ManyToOne(targetEntity: self::class)]
//    #[ORM\JoinColumn(name: 'ce_commentaire_parent', referencedColumnName: 'id_commentaire', nullable: true, onDelete: 'SET NULL')]
//    private ?self $ce_commentaire_parent = null;


    #[Groups(['commentaires', 'commentaires_post'])]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $texte = null;

    #[Groups(['commentaires', 'commentaires_post'])]
    #[SerializedName('auteur.id')]
//    #[ORM\Column(nullable: false)]
    #[ORM\ManyToOne(targetEntity: DelUtilisateurInfos::class)]
//    #[ORM\ManyToOne(targetEntity: DelUtilisateurInfos::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'ce_utilisateur', referencedColumnName: 'id_utilisateur',nullable: false)]
    private ?DelUtilisateurInfos $ce_utilisateur = null;
//    private ?int $ce_utilisateur = null;

    #[Groups(['commentaires', 'commentaires_post'])]
    #[SerializedName('auteur.prenom')]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $utilisateur_prenom = null;

    #[Groups(['commentaires', 'commentaires_post'])]
    #[SerializedName('auteur.nom')]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $utilisateur_nom = null;

    #[Groups(['commentaires', 'commentaires_post'])]
    #[SerializedName('auteur.courriel')]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $utilisateur_courriel = null;

    #[Groups(['commentaires', 'commentaires_post'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom_sel = null;

    #[Groups(['commentaires', 'commentaires_post'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom_sel_nn = null;

    #[Groups(['commentaires'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom_ret = null;

    #[Groups(['commentaires'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom_ret_nn = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $famille = null;

    #[Groups(['commentaires', 'commentaires_post'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom_referentiel = null;

    #[Groups(['commentaires'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[Groups(['commentaires'])]
    #[ORM\Column(length: 1)]
    private ?int $proposition_initiale = null;

    #[Groups(['commentaires'])]
    #[ORM\Column(length: 1)]
    private ?int $proposition_retenue = null;

    #[Groups(['commentaires'])]
    #[SerializedName('validateur.id')]
    #[ORM\Column(nullable: true)]
    private ?int $ce_validateur = null;

    #[Groups(['commentaires'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_validation = null;

//    #[Groups(['commentaires'])]
//    private ?array $votes = null;

    public function getId(): int|string|null
    {
        return $this->id_commentaire;
    }


    public function getIdCommentaire(): int|string|null
    {
        return $this->id_commentaire;
    }

//    public function getCeObservation(): ?DelObservation
//    {
//        return $this->ce_observation;
//    }

    #[Groups(['commentaires'])]
    #[SerializedName('observation')]
    public function getObservation(): ?int
    {
        return $this->ce_observation ? $this->ce_observation->getIdObservation() : null;
    }

    public function setCeObservation(?DelObservation $ce_observation): static
    {
        $this->ce_observation = $ce_observation;

        return $this;
    }

    public function getCeCommentaireParent(): ?string
    {
        return $this->ce_commentaire_parent;
    }
//    public function getCeCommentaireParent(): ?self
//    {
//        return $this->ce_commentaire_parent;
//    }

    public function setCeCommentaireParent(string $ce_commentaire_parent): static
    {
        $this->ce_commentaire_parent = $ce_commentaire_parent;

        return $this;
    }
//    public function setCeCommentaireParent(?self $ce_commentaire_parent): static
//    {
//        $this->ce_commentaire_parent = $ce_commentaire_parent;
//
//        return $this;
//    }

    public function getTexte(): ?string
    {
        return $this->texte;
    }

    public function setTexte(?string $texte): static
    {
        $this->texte = $texte;

        return $this;
    }

//    public function getCeUtilisateur(): ?DelUtilisateurInfos
//    {
//        return $this->ce_utilisateur;
//    }

    #[Groups(['commentaires'])]
    #[SerializedName('auteur.id')]
    public function getAuteurId(): ?int
    {
        return $this->ce_utilisateur ? $this->ce_utilisateur->getIdUtilisateur() : null;
    }

    public function setCeUtilisateur(?DelUtilisateurInfos $ce_utilisateur): static
    {
        $this->ce_utilisateur = $ce_utilisateur;

        return $this;
    }

    public function getUtilisateurPrenom(): ?string
    {
        return $this->utilisateur_prenom;
    }

    public function setUtilisateurPrenom(?string $utilisateur_prenom): static
    {
        $this->utilisateur_prenom = $utilisateur_prenom;

        return $this;
    }

    public function getUtilisateurNom(): ?string
    {
        return $this->utilisateur_nom;
    }

    public function setUtilisateurNom(?string $utilisateur_nom): static
    {
        $this->utilisateur_nom = $utilisateur_nom;

        return $this;
    }

    public function getUtilisateurCourriel(): ?string
    {
        return $this->utilisateur_courriel;
    }

    public function setUtilisateurCourriel(?string $utilisateur_courriel): static
    {
        $this->utilisateur_courriel = $utilisateur_courriel;

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

    public function getNomSelNn(): ?string
    {
        return $this->nom_sel_nn;
    }

    public function setNomSelNn(?string $nom_sel_nn): static
    {
        $this->nom_sel_nn = $nom_sel_nn;

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

    public function getNomRetNn(): ?string
    {
        return $this->nom_ret_nn;
    }

    public function setNomRetNn(?string $nom_ret_nn): static
    {
        $this->nom_ret_nn = $nom_ret_nn;

        return $this;
    }

    public function getNt(): ?string
    {
        return $this->nt;
    }

    public function setNt(?string $nt): static
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

    public function getNomReferentiel(): ?string
    {
        return $this->nom_referentiel;
    }

    public function setNomReferentiel(?string $nom_referentiel): static
    {
        $this->nom_referentiel = $nom_referentiel;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function isPropositionInitiale(): ?int
    {
        return $this->proposition_initiale;
    }

    public function setPropositionInitiale(int $proposition_initiale): static
    {
        $this->proposition_initiale = $proposition_initiale;

        return $this;
    }

    public function isPropositionRetenue(): ?int
    {
        return $this->proposition_retenue;
    }

    public function setPropositionRetenue(int $proposition_retenue): static
    {
        $this->proposition_retenue = $proposition_retenue;

        return $this;
    }

    public function getCeValidateur(): ?int
    {
        return $this->ce_validateur;
    }

    public function setCeValidateur(?int $ce_validateur): static
    {
        $this->ce_validateur = $ce_validateur;

        return $this;
    }

    public function getDateValidation(): ?\DateTimeInterface
    {
        return $this->date_validation;
    }

    public function setDateValidation(?\DateTimeInterface $date_validation): static
    {
        $this->date_validation = $date_validation;

        return $this;
    }

    public function getCeProposition(): ?int
    {
        return $this->ce_proposition;
    }

    public function setCeProposition(int $ce_proposition): static
    {
        $this->ce_proposition = $ce_proposition;

        return $this;
    }

    public function getVotes(): ?array
    {
        return $this->votes;
    }


}
