<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 191)]
    private ?string $firstName = null;

    #[ORM\Column(length: 191)]
    private ?string $lastName = null;

    #[ORM\Column(length: 191)]
    private ?string $phone = null;

    #[ORM\Column(length: 191)]
    private ?string $adress = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?City $city = null;

    #[ORM\Column]
    private ?bool $payOnDelivery = null;

    /** cod | wave | orange_money | stripe */
    #[ORM\Column(length: 32)]
    private string $paymentMethod = 'cod';

    /**
     * @var Collection<int, OrderProducts>
     */
    #[ORM\OneToMany(targetEntity: OrderProducts::class, mappedBy: '_order', orphanRemoval: true)]
    private Collection $orderProducts;

    #[ORM\Column]
    private ?float $totalPrice = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isComplted = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column]
    private ?bool $isPaymentCompleted = null;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    private ?string $trackingToken = null;

    #[ORM\Column(nullable: true)]
    private ?int $satisfactionScore = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $satisfactionComment = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $satisfactionSubmittedAt = null;

    public function __construct()
    {
        $this->orderProducts = new ArrayCollection();
    }

    public function ensureTrackingToken(): string
    {
        if ($this->trackingToken === null || $this->trackingToken === '') {
            $this->trackingToken = bin2hex(random_bytes(32));
        }

        return $this->trackingToken;
    }

    public function getTrackingToken(): ?string
    {
        return $this->trackingToken;
    }

    public function setTrackingToken(?string $trackingToken): static
    {
        $this->trackingToken = $trackingToken;

        return $this;
    }

    /**
     * Étapes de suivi pour la page client.
     *
     * @return list<array{key: string, label: string, detail: string, state: string}>
     */
    public function getTrackingSteps(): array
    {
        $delivered = (bool) $this->isComplted;
        $paid = (bool) $this->isPaymentCompleted;
        $cod = $this->paymentMethod === 'cod';
        $awaitingPay = !$paid && !$cod && $this->isMobileMoney();

        $paymentDetail = match (true) {
            $cod && $delivered => 'Réglé à la livraison',
            $cod => 'À régler à la remise / livraison',
            $paid => 'Paiement reçu ('.$this->getPaymentMethodLabel().')',
            $awaitingPay => 'En attente de votre transfert '.$this->getPaymentMethodLabel(),
            default => $this->getPaymentMethodLabel(),
        };

        $paymentState = match (true) {
            $cod && $delivered, $paid => 'done',
            $cod, $awaitingPay => 'current',
            default => 'pending',
        };

        $prepState = match (true) {
            $delivered => 'done',
            $paid || $cod => 'current',
            default => 'pending',
        };

        return [
            [
                'key' => 'registered',
                'label' => 'Commande enregistrée',
                'detail' => 'Nous avons bien reçu votre commande.',
                'state' => 'done',
            ],
            [
                'key' => 'payment',
                'label' => 'Paiement',
                'detail' => $paymentDetail,
                'state' => $paymentState,
            ],
            [
                'key' => 'preparing',
                'label' => 'Préparation',
                'detail' => $delivered
                    ? 'Colis préparé et remis.'
                    : (($paid || $cod) ? 'Votre commande est en cours de préparation.' : 'Dès validation du paiement.'),
                'state' => $prepState,
            ],
            [
                'key' => 'delivered',
                'label' => 'Livrée / réceptionnée',
                'detail' => $delivered
                    ? 'Commande réceptionnée. Merci pour votre confiance.'
                    : 'En attente de réception.',
                'state' => $delivered ? 'done' : 'pending',
            ],
        ];
    }

    public function getCurrentTrackingLabel(): string
    {
        if ($this->isComplted) {
            return 'Livrée / réceptionnée';
        }

        foreach ($this->getTrackingSteps() as $step) {
            if ($step['state'] === 'current') {
                return $step['label'];
            }
        }

        return 'En cours';
    }

    public function getSatisfactionScore(): ?int
    {
        return $this->satisfactionScore;
    }

    public function setSatisfactionScore(?int $satisfactionScore): static
    {
        $this->satisfactionScore = $satisfactionScore;

        return $this;
    }

    public function getSatisfactionComment(): ?string
    {
        return $this->satisfactionComment;
    }

    public function setSatisfactionComment(?string $satisfactionComment): static
    {
        $this->satisfactionComment = $satisfactionComment;

        return $this;
    }

    public function getSatisfactionSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->satisfactionSubmittedAt;
    }

    public function setSatisfactionSubmittedAt(?\DateTimeImmutable $satisfactionSubmittedAt): static
    {
        $this->satisfactionSubmittedAt = $satisfactionSubmittedAt;

        return $this;
    }

    public function hasSatisfactionFeedback(): bool
    {
        return $this->satisfactionSubmittedAt !== null;
    }

    public function canSubmitSatisfaction(): bool
    {
        return (bool) $this->isComplted && !$this->hasSatisfactionFeedback();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getAdress(): ?string
    {
        return $this->adress;
    }

    public function setAdress(string $adress): static
    {
        $this->adress = $adress;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCity(): ?City
    {
        return $this->city;
    }

    public function setCity(?City $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function isPayOnDelivery(): ?bool
    {
        return $this->payOnDelivery;
    }

    public function setPayOnDelivery(bool $payOnDelivery): static
    {
        $this->payOnDelivery = $payOnDelivery;

        return $this;
    }

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function isMobileMoney(): bool
    {
        return \in_array($this->paymentMethod, ['wave', 'orange_money'], true);
    }

    public function isManualPayment(): bool
    {
        return \in_array($this->paymentMethod, ['cod', 'wave', 'orange_money'], true);
    }

    public function getPaymentMethodLabel(): string
    {
        return match ($this->paymentMethod) {
            'wave' => 'Wave Sénégal',
            'orange_money' => 'Orange Money Sénégal',
            'stripe' => 'Carte bancaire',
            default => 'Paiement à la livraison',
        };
    }

    /**
     * @return Collection<int, OrderProducts>
     */
    public function getOrderProducts(): Collection
    {
        return $this->orderProducts;
    }

    public function addOrderProduct(OrderProducts $orderProduct): static
    {
        if (!$this->orderProducts->contains($orderProduct)) {
            $this->orderProducts->add($orderProduct);
            $orderProduct->setOrder($this);
        }

        return $this;
    }

    public function removeOrderProduct(OrderProducts $orderProduct): static
    {
        if ($this->orderProducts->removeElement($orderProduct)) {
            // set the owning side to null (unless already changed)
            if ($orderProduct->getOrder() === $this) {
                $orderProduct->setOrder(null);
            }
        }

        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(float $totalPrice): static
    {
        $this->totalPrice = $totalPrice;

        return $this;
    }

    public function isComplted(): ?bool
    {
        return $this->isComplted;
    }

    public function setIsComplted(?bool $isComplted): static
    {
        $this->isComplted = $isComplted;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function isPaymentCompleted(): ?bool
    {
        return $this->isPaymentCompleted;
    }

    public function setIsPaymentCompleted(bool $isPaymentCompleted): static
    {
        $this->isPaymentCompleted = $isPaymentCompleted;

        return $this;
    }
}
