<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\DelObservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: DelObservationRepository::class)]
#[ApiResource]
class DelObservation
{
    #[Groups(['observations'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id_observation = null;

    #[Groups(['observations'])]
    #[ORM\Column(nullable: true)]
    private ?int $ce_utilisateur = null;

    #[Groups(['observations'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom_utilisateur = null;

    #[Groups(['observations'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prenom_utilisateur = null;

    #[Groups(['observations'])]
    #[ORM\Column(length: 155, nullable: true)]
    private ?string $courriel_utilisateur = null;

    #[Groups(['observations'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom_sel = null;

    #[Groups(['observations'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom_ret = null;

    #[Groups(['observations'])]
    #[ORM\Column(nullable: true)]
    private ?int $nom_sel_nn = null;

    #[Groups(['observations'])]
    #[ORM\Column(nullable: true)]
    private ?int $nom_ret_nn = null;

    #[Groups(['observations'])]
    #[ORM\Column(nullable: true)]
    private ?int $nt = null;

    #[Groups(['observations'])]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $famille = null;

    #[Groups(['observations'])]
    #[ORM\Column(length: 5, nullable: true)]
    private ?string $ce_zone_geo = null;

    #[Groups(['observations'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zone_geo = null;

    #[Groups(['observations'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lieudit = null;

    #[Groups(['observations'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $station = null;

    #[Groups(['observations'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $milieu = null;

    #[Groups(['observations'])]
    #[ORM\Column(length: 25, nullable: true)]
    private ?string $nom_referentiel = null;

    #[Groups(['observations'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_observation = null;

    #[Groups(['observations'])]
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

    #[Groups(['observations'])]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $date_transmission = null;

    #[Groups(['observations'])]
    #[ORM\Column(length: 25, nullable: true)]
    private ?string $certitude = null;

    #[Groups(['observations'])]
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $pays = null;

    #[Groups(['observations'])]
    #[ORM\Column(length: 15, nullable: true)]
    private ?string $input_source = null;

    #[Groups(['observations'])]
    #[ORM\Column(nullable: true)]
    private ?bool $donnees_standard = null;

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
}
