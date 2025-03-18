<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use App\Repository\DelUtilisateurInfosRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DelUtilisateurInfosRepository::class)]
class DelUtilisateurInfos
{
    #[Groups(['user', 'commentaires'])]
    #[ORM\Id]
//    #[ORM\GeneratedValue]
    #[ApiProperty(identifier: true)]
    #[ORM\Column(name: 'id_utilisateur', type: 'string')]
    private int|string|null $id_utilisateur = null;

    #[Groups(['user'])]
    #[ORM\Column(length: 128, nullable: true)]
    private ?string $intitule = null;

    #[Groups(['user'])]
    #[ORM\Column(length: 32, nullable: true)]
    private ?string $prenom = null;

    #[Groups(['user'])]
    #[ORM\Column(length: 32, nullable: true)]
    private ?string $nom = null;

    #[Groups(['user'])]
    #[ORM\Column(length: 128, nullable: true)]
    private ?string $courriel = null;

    #[Groups(['user'])]
    #[ORM\Column(type: Types::TEXT)]
    private ?string $preferences = null;

    #[Groups(['user'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date_premiere_utilisation = null;

    #[Groups(['user'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_derniere_consultation_evenements = null;

    #[Groups(['user'])]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $admin = null;

//    public function __toString() {
//        return "DelUtilisateurInfos with ID: " . $this->getIdUtilisateur();
//    }

    public function getId(): string|int|null
    {
        return $this->getIdUtilisateur();
    }

    public function getIdUtilisateur(): string|int
    {
        return $this->id_utilisateur;
    }

    public function setIdUtilisateur(int|string|null $id_utilisateur): void
    {
        $this->id_utilisateur = $id_utilisateur;
    }

    public function getIntitule(): ?string
    {
        return $this->intitule;
    }

    public function setIntitule(?string $intitule): static
    {
        $this->intitule = $intitule;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getCourriel(): ?string
    {
        return $this->courriel;
    }

    public function setCourriel(?string $courriel): static
    {
        $this->courriel = $courriel;

        return $this;
    }

//    public function isDelAdmin(): ?bool
//    {
//        return $this->delAdmin;
//    }
//
//    public function setDelAdmin(bool $delAdmin): static
//    {
//        $this->delAdmin = $delAdmin;
//
//        return $this;
//    }

    public function getPreferences(): ?string
    {
        return $this->preferences;
    }

    public function setPreferences(string $preferences): static
    {
        $this->preferences = $preferences;

        return $this;
    }

    public function getDatePremiereUtilisation(): ?\DateTimeInterface
    {
        return $this->date_premiere_utilisation;
    }

    public function setDatePremiereUtilisation(\DateTimeInterface $date_premiere_utilisation): static
    {
        $this->date_premiere_utilisation = $date_premiere_utilisation;

        return $this;
    }

    public function getDateDerniereConsultationEvenements(): ?\DateTimeInterface
    {
        return $this->date_derniere_consultation_evenements;
    }

    public function setDateDerniereConsultationEvenements(?\DateTimeInterface $date_derniere_consultation_evenements): static
    {
        $this->date_derniere_consultation_evenements = $date_derniere_consultation_evenements;

        return $this;
    }

    public function getAdmin(): ?int
    {
        return $this->admin;
    }

    public function setAdmin(int $admin): static
    {
        $this->admin = $admin;

        return $this;
    }
}
