<?php

namespace App\Entity;

use App\Repository\FooterSettingsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FooterSettingsRepository::class)]
class FooterSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private bool $showBrand = true;

    #[ORM\Column]
    private bool $showNavigation = true;

    #[ORM\Column]
    private bool $showAccount = true;

    #[ORM\Column]
    private bool $showCategories = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isShowBrand(): bool
    {
        return $this->showBrand;
    }

    public function setShowBrand(bool $showBrand): static
    {
        $this->showBrand = $showBrand;

        return $this;
    }

    public function isShowNavigation(): bool
    {
        return $this->showNavigation;
    }

    public function setShowNavigation(bool $showNavigation): static
    {
        $this->showNavigation = $showNavigation;

        return $this;
    }

    public function isShowAccount(): bool
    {
        return $this->showAccount;
    }

    public function setShowAccount(bool $showAccount): static
    {
        $this->showAccount = $showAccount;

        return $this;
    }

    public function isShowCategories(): bool
    {
        return $this->showCategories;
    }

    public function setShowCategories(bool $showCategories): static
    {
        $this->showCategories = $showCategories;

        return $this;
    }

    public function hasExtraSections(): bool
    {
        return $this->showBrand || $this->showNavigation || $this->showAccount;
    }
}
