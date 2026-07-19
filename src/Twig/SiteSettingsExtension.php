<?php

namespace App\Twig;

use App\Entity\SiteSettings;
use App\Repository\SiteSettingsRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

final class SiteSettingsExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly SiteSettingsRepository $siteSettingsRepository,
    ) {
    }

    public function getGlobals(): array
    {
        try {
            $site = $this->siteSettingsRepository->getCurrent();
        } catch (\Throwable) {
            $site = new SiteSettings();
        }

        return [
            'site' => $site,
            'brand_name' => $site->getBrandName(),
            'brand_logo_path' => $site->getBrandLogoPath(),
            'brand_tagline' => $site->getBrandTagline(),
            'contact_email' => $site->getContactEmail(),
            'whatsapp_numbers' => $site->getWhatsappNumbers(),
        ];
    }
}
