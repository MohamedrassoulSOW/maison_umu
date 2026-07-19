<?php

namespace App\Entity;

use App\Repository\SiteSettingsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteSettingsRepository::class)]
class SiteSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $brandName = 'Maison UMU';

    #[ORM\Column(length: 255)]
    private string $brandTagline = "L'art des senteurs";

    #[ORM\Column(length: 255)]
    private string $brandLogoPath = 'images/logo.jpeg';

    #[ORM\Column(length: 180)]
    private string $contactEmail = 'contact@maisionumu.sowcoder.com';

    #[ORM\Column(length: 255)]
    private string $address = 'Dakar, Sénégal';

    #[ORM\Column(length: 120)]
    private string $hours = '24h/24 — 7j/7';

    #[ORM\Column(length: 180)]
    private string $responseSla = 'Sous 24 à 48 heures';

    #[ORM\Column(type: Types::TEXT)]
    private string $contactLead = 'Une question, une commande ou une demande particulière ? Écrivez-nous, nous vous répondons rapidement.';

    #[ORM\Column(type: Types::TEXT)]
    private string $contactInfoText = 'Service client disponible pour vos demandes boutique et suivi de commande.';

    #[ORM\Column(type: Types::TEXT)]
    private string $mapEmbedUrl = 'https://www.openstreetmap.org/export/embed.html?bbox=-17.4805%2C14.6680%2C-17.4205%2C14.7160&layer=mapnik&marker=14.6928%2C-17.4467';

    #[ORM\Column(type: Types::TEXT)]
    private string $mapLinkUrl = 'https://www.openstreetmap.org/?mlat=14.6928&mlon=-17.4467#map=14/14.6928/-17.4467';

    #[ORM\Column(length: 255)]
    private string $footerBlurb = 'une sélection soignée pour votre quotidien.';

    /** @var list<array{country: string, label: string, href: string}> */
    #[ORM\Column(type: Types::JSON)]
    private array $whatsappNumbers = [
        ['country' => 'Sénégal', 'label' => '+221 78 450 78 08', 'href' => 'https://wa.me/221784507808'],
        ['country' => 'Maroc', 'label' => '+212 708-142802', 'href' => 'https://wa.me/212708142802'],
    ];

    #[ORM\Column(type: Types::TEXT)]
    private string $aboutLead = 'Maison UMU célèbre l’élégance des senteurs et sélectionne des créations raffinées pour accompagner votre quotidien.';

    #[ORM\Column(length: 120)]
    private string $aboutBlock1Title = 'Notre promesse';

    #[ORM\Column(type: Types::TEXT)]
    private string $aboutBlock1Text = 'Nous croyons qu’un objet bien choisi transforme l’ambiance d’un espace. Chaque produit publié sur la boutique est pensé pour durer, se vivre et se transmettre.';

    #[ORM\Column(length: 120)]
    private string $aboutBlock2Title = 'Une sélection soignée';

    #[ORM\Column(type: Types::TEXT)]
    private string $aboutBlock2Text = 'Qualité, esthétique et usage réel guident nos choix. Notre équipe suit les stocks, les commandes et la livraison pour une expérience fluide de bout en bout.';

    #[ORM\Column(length: 120)]
    private string $aboutBlock3Title = 'Proche de vous';

    #[ORM\Column(type: Types::TEXT)]
    private string $aboutBlock3Text = 'Une question sur un produit, une commande ou une livraison ? Notre équipe est disponible pour vous accompagner avec clarté et réactivité.';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBrandName(): string
    {
        return $this->brandName;
    }

    public function setBrandName(string $brandName): static
    {
        $this->brandName = $brandName;

        return $this;
    }

    public function getBrandTagline(): string
    {
        return $this->brandTagline;
    }

    public function setBrandTagline(string $brandTagline): static
    {
        $this->brandTagline = $brandTagline;

        return $this;
    }

    public function getBrandLogoPath(): string
    {
        return $this->brandLogoPath;
    }

    public function setBrandLogoPath(string $brandLogoPath): static
    {
        $this->brandLogoPath = $brandLogoPath;

        return $this;
    }

    public function getContactEmail(): string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;

        return $this;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getHours(): string
    {
        return $this->hours;
    }

    public function setHours(string $hours): static
    {
        $this->hours = $hours;

        return $this;
    }

    public function getResponseSla(): string
    {
        return $this->responseSla;
    }

    public function setResponseSla(string $responseSla): static
    {
        $this->responseSla = $responseSla;

        return $this;
    }

    public function getContactLead(): string
    {
        return $this->contactLead;
    }

    public function setContactLead(string $contactLead): static
    {
        $this->contactLead = $contactLead;

        return $this;
    }

    public function getContactInfoText(): string
    {
        return $this->contactInfoText;
    }

    public function setContactInfoText(string $contactInfoText): static
    {
        $this->contactInfoText = $contactInfoText;

        return $this;
    }

    public function getMapEmbedUrl(): string
    {
        return $this->mapEmbedUrl;
    }

    public function setMapEmbedUrl(string $mapEmbedUrl): static
    {
        $this->mapEmbedUrl = $mapEmbedUrl;

        return $this;
    }

    public function getMapLinkUrl(): string
    {
        return $this->mapLinkUrl;
    }

    public function setMapLinkUrl(string $mapLinkUrl): static
    {
        $this->mapLinkUrl = $mapLinkUrl;

        return $this;
    }

    public function getFooterBlurb(): string
    {
        return $this->footerBlurb;
    }

    public function setFooterBlurb(string $footerBlurb): static
    {
        $this->footerBlurb = $footerBlurb;

        return $this;
    }

    /**
     * @return list<array{country: string, label: string, href: string}>
     */
    public function getWhatsappNumbers(): array
    {
        return $this->whatsappNumbers;
    }

    /**
     * @param list<array{country?: string, label?: string, href?: string}> $whatsappNumbers
     */
    public function setWhatsappNumbers(array $whatsappNumbers): static
    {
        $normalized = [];
        foreach ($whatsappNumbers as $row) {
            if (!\is_array($row)) {
                continue;
            }
            $country = trim((string) ($row['country'] ?? ''));
            $label = trim((string) ($row['label'] ?? ''));
            $href = trim((string) ($row['href'] ?? ''));
            if ($country === '' && $label === '' && $href === '') {
                continue;
            }
            $normalized[] = [
                'country' => $country !== '' ? $country : 'WhatsApp',
                'label' => $label,
                'href' => $href,
            ];
        }
        $this->whatsappNumbers = $normalized;

        return $this;
    }

    public function getAboutLead(): string
    {
        return $this->aboutLead;
    }

    public function setAboutLead(string $aboutLead): static
    {
        $this->aboutLead = $aboutLead;

        return $this;
    }

    public function getAboutBlock1Title(): string
    {
        return $this->aboutBlock1Title;
    }

    public function setAboutBlock1Title(string $aboutBlock1Title): static
    {
        $this->aboutBlock1Title = $aboutBlock1Title;

        return $this;
    }

    public function getAboutBlock1Text(): string
    {
        return $this->aboutBlock1Text;
    }

    public function setAboutBlock1Text(string $aboutBlock1Text): static
    {
        $this->aboutBlock1Text = $aboutBlock1Text;

        return $this;
    }

    public function getAboutBlock2Title(): string
    {
        return $this->aboutBlock2Title;
    }

    public function setAboutBlock2Title(string $aboutBlock2Title): static
    {
        $this->aboutBlock2Title = $aboutBlock2Title;

        return $this;
    }

    public function getAboutBlock2Text(): string
    {
        return $this->aboutBlock2Text;
    }

    public function setAboutBlock2Text(string $aboutBlock2Text): static
    {
        $this->aboutBlock2Text = $aboutBlock2Text;

        return $this;
    }

    public function getAboutBlock3Title(): string
    {
        return $this->aboutBlock3Title;
    }

    public function setAboutBlock3Title(string $aboutBlock3Title): static
    {
        $this->aboutBlock3Title = $aboutBlock3Title;

        return $this;
    }

    public function getAboutBlock3Text(): string
    {
        return $this->aboutBlock3Text;
    }

    public function setAboutBlock3Text(string $aboutBlock3Text): static
    {
        $this->aboutBlock3Text = $aboutBlock3Text;

        return $this;
    }
}
