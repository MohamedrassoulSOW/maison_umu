<?php

namespace App\Entity;

use App\Repository\HeroSettingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HeroSettingsRepository::class)]
class HeroSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $brandLabel = 'Maison UMU';

    #[ORM\Column(length: 255)]
    private string $headline = 'L’essentiel, choisi avec soin';

    #[ORM\Column(type: Types::TEXT)]
    private string $text = 'Découvrez une collection élégante pour votre intérieur et votre quotidien.';

    #[ORM\Column(length: 120)]
    private string $primaryCtaLabel = 'Découvrir';

    #[ORM\Column(length: 255)]
    private string $primaryCtaUrl = '#collection';

    #[ORM\Column(length: 120)]
    private string $secondaryCtaLabel = 'Voir le panier';

    #[ORM\Column(length: 255)]
    private string $secondaryCtaUrl = '/cart';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBrandLabel(): string
    {
        return $this->brandLabel;
    }

    public function setBrandLabel(string $brandLabel): static
    {
        $this->brandLabel = $brandLabel;

        return $this;
    }

    public function getHeadline(): string
    {
        return $this->headline;
    }

    public function setHeadline(string $headline): static
    {
        $this->headline = $headline;

        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): static
    {
        $this->text = $text;

        return $this;
    }

    public function getPrimaryCtaLabel(): string
    {
        return $this->primaryCtaLabel;
    }

    public function setPrimaryCtaLabel(string $primaryCtaLabel): static
    {
        $this->primaryCtaLabel = $primaryCtaLabel;

        return $this;
    }

    public function getPrimaryCtaUrl(): string
    {
        return $this->primaryCtaUrl;
    }

    public function setPrimaryCtaUrl(string $primaryCtaUrl): static
    {
        $this->primaryCtaUrl = $primaryCtaUrl;

        return $this;
    }

    public function getSecondaryCtaLabel(): string
    {
        return $this->secondaryCtaLabel;
    }

    public function setSecondaryCtaLabel(string $secondaryCtaLabel): static
    {
        $this->secondaryCtaLabel = $secondaryCtaLabel;

        return $this;
    }

    public function getSecondaryCtaUrl(): string
    {
        return $this->secondaryCtaUrl;
    }

    public function setSecondaryCtaUrl(string $secondaryCtaUrl): static
    {
        $this->secondaryCtaUrl = $secondaryCtaUrl;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;

        return $this;
    }
}
