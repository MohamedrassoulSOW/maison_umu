<?php

namespace App\Service;

/** Config Wave / Orange Money (numéro + liens utiles après l’e-mail de validation). */
class MobileMoneyConfig
{
    public function __construct(
        private string $phone = '+221 77 450 78 08',
        private string $wavePayLink = '',
    ) {
    }

    public function getDisplayPhone(): string
    {
        return $this->phone;
    }

    public function getPhoneDigits(): string
    {
        return preg_replace('/\D+/', '', $this->phone) ?: '221774507808';
    }

    public function getLocalPhone(): string
    {
        $digits = $this->getPhoneDigits();
        if (str_starts_with($digits, '221') && \strlen($digits) >= 12) {
            return substr($digits, 3);
        }

        return $digits;
    }

    public function hasWavePayLink(): bool
    {
        return $this->wavePayLink !== '';
    }

    public function getWavePayLink(): string
    {
        return $this->wavePayLink;
    }

    /** USSD Orange Money SN : #144#21*numéro*montant# */
    public function orangeUssd(int|float $amount): string
    {
        return sprintf('#144#21*%s*%d#', $this->getLocalPhone(), (int) round((float) $amount));
    }

    public function orangeUssdUrl(int|float $amount): string
    {
        return 'tel:'.rawurlencode($this->orangeUssd($amount));
    }

    /**
     * @return array{
     *     phone: string,
     *     amount: int,
     *     label: string,
     *     ussd: string|null,
     *     openUrl: string|null
     * }
     */
    public function paymentLinksFor(string $method, int|float $amount): array
    {
        $amountInt = (int) round((float) $amount);

        if ($method === 'orange_money') {
            return [
                'phone' => $this->getDisplayPhone(),
                'amount' => $amountInt,
                'label' => 'Orange Money',
                'ussd' => $this->orangeUssd($amountInt),
                'openUrl' => $this->orangeUssdUrl($amountInt),
            ];
        }

        return [
            'phone' => $this->getDisplayPhone(),
            'amount' => $amountInt,
            'label' => 'Wave',
            'ussd' => null,
            'openUrl' => $this->hasWavePayLink() ? $this->wavePayLink : null,
        ];
    }
}
