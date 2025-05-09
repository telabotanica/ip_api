<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Controller\DelCommentaireVoteController;
use App\Controller\DelObservationController;
use App\Repository\DelCommentaireVoteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: DelCommentaireVoteRepository::class)]
//#[ApiResource(
//    operations: [
//        new Get(
//            uriTemplate: '/observations/{id_observation}/vote',
//            openapiContext: [
//                'summary' => 'Get observation votes',
//                'description' => 'Get observation votes',
//            ],
//            denormalizationContext: ['groups' => ['votes']],
//            name: 'observation_vote'),
//        new Get(
//            uriTemplate: '/observations/{id_observation}/{id_commentaire}/vote',
//            openapiContext: [
//                'summary' => 'Get all votes from a proposition',
//                'description' => 'Get all votes from a proposition',
//            ],
//            denormalizationContext: ['groups' => ['votes']],
//            name: 'proposition_vote'),
//    ],
//    formats: ["json"],
//    controller: DelCommentaireVoteController::class
//)]
class DelCommentaireVote
{
    #[Groups(['votes', 'commentaires'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[SerializedName('vote.id')]
    #[ORM\Column(name: 'id_vote', type: 'bigint')]
    private ?int $id_vote = null;

    #[Groups(['votes', 'commentaires'])]
    #[ORM\ManyToOne]
    #[SerializedName('proposition.id')]
    #[ORM\JoinColumn(name: 'ce_proposition', referencedColumnName: 'id_commentaire',nullable: false)]
    private ?DelCommentaire $ce_proposition = null;

    #[Groups(['votes', 'commentaires'])]
//    #[ORM\ManyToOne(targetEntity: DelUtilisateurInfos::class)]
    #[SerializedName('auteur.id')]
    #[ORM\Column(type: 'string', nullable: false)]
    private ?string $ce_utilisateur = null;

    #[Groups(['votes', 'commentaires'])]
    #[SerializedName('vote')]
    #[ORM\Column(type: Types::SMALLINT, length: 1)]
    private ?int $valeur = null;

    #[Groups(['votes', 'commentaires'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    public function getIdVote(): ?int
    {
        return $this->id_vote;
    }

    public function getCeProposition(): ?DelCommentaire
    {
        return $this->ce_proposition;
    }

    #[Groups(['votes', 'commentaires'])]
    #[SerializedName('proposition.id')]
    public function getProposition(): ?int
    {
        return $this->ce_proposition ? $this->ce_proposition->getIdCommentaire() : null;
    }

    public function setCeProposition(?DelCommentaire $ce_proposition): static
    {
        $this->ce_proposition = $ce_proposition;

        return $this;
    }

    #[Groups(['votes', 'commentaires'])]
    #[SerializedName('auteur.id')]
    public function getAuteurId(): ?string
    {
        return $this->ce_utilisateur;
    }

//    public function getCeUtilisateur(): ?string
//    {
//        return $this->ce_utilisateur;
//    }

    public function setCeUtilisateur(?string $ce_utilisateur): static
    {
        $this->ce_utilisateur = $ce_utilisateur;

        return $this;
    }

    public function getValeur(): ?int
    {
        return $this->valeur;
    }

    public function setValeur(int $valeur): static
    {
        $this->valeur = $valeur;

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
}
