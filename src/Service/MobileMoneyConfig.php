<?php

namespace App\Service;

/**
 * Liens pour orienter le client vers Wave / Orange Money (Sénégal).
 * Sans API marchand : ouverture d’app + USSD ; lien Wave Business optionnel.
 */
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

    /** Chiffres uniquement, ex. 221774507808 */
    public function getPhoneDigits(): string
    {
        return preg_replace('/\D+/', '', $this->phone) ?: '221774507808';
    }

    /** Numéro local SN sans indicatif, ex. 774507808 */
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

    /**
     * URL pour ouvrir Wave (lien marchand si configuré, sinon intention d’ouvrir l’app).
     */
    public function waveOpenUrl(): string
    {
        if ($this->hasWavePayLink()) {
            return $this->wavePayLink;
        }

        // Chrome Android : tente d’ouvrir l’app Wave, sinon Play Store
        return 'intent://#Intent;package=com.wave.personal;scheme=https;'
            .'S.browser_fallback_url='.rawurlencode('https://play.google.com/store/apps/details?id=com.wave.personal')
            .';end';
    }

    public function waveStoreUrl(): string
    {
        return 'https://play.google.com/store/apps/details?id=com.wave.personal';
    }

    /**
     * USSD Orange Money SN : transfert national prérempli (le client saisit son code secret).
     * Format : #144#21*numéro*montant#
     */
    public function orangeUssdUrl(int|float $amount): string
    {
        $amountInt = (int) round((float) $amount);
        $ussd = sprintf('#144#21*%s*%d#', $this->getLocalPhone(), $amountInt);

        return 'tel:'.rawurlencode($ussd);
    }

    /** Ouvre Max it / Orange Money (Android Intent + fallback store). */
    public function orangeAppUrl(): string
    {
        return 'intent://#Intent;package=com.orange.myorange.osn;scheme=https;'
            .'S.browser_fallback_url='.rawurlencode('https://play.google.com/store/apps/details?id=com.orange.myorange.osn')
            .';end';
    }

    public function orangeStoreUrl(): string
    {
        return 'https://play.google.com/store/apps/details?id=com.orange.myorange.osn';
    }

    /**
     * @return array{
     *     phone: string,
     *     localPhone: string,
     *     amount: int,
     *     openUrl: string,
     *     secondaryUrl: string,
     *     ussd: string|null,
     *     label: string,
     *     autoOpen: bool
     * }
     */
    public function paymentLinksFor(string $method, int|float $amount): array
    {
        $amountInt = (int) round((float) $amount);

        if ($method === 'orange_money') {
            return [
                'phone' => $this->getDisplayPhone(),
                'localPhone' => $this->getLocalPhone(),
                'amount' => $amountInt,
                'openUrl' => $this->orangeUssdUrl($amountInt),
                'secondaryUrl' => $this->orangeAppUrl(),
                'ussd' => sprintf('#144#21*%s*%d#', $this->getLocalPhone(), $amountInt),
                'label' => 'Orange Money',
                'autoOpen' => true,
            ];
        }

        return [
            'phone' => $this->getDisplayPhone(),
            'localPhone' => $this->getLocalPhone(),
            'amount' => $amountInt,
            'openUrl' => $this->waveOpenUrl(),
            'secondaryUrl' => $this->waveStoreUrl(),
            'ussd' => null,
            'label' => 'Wave',
            'autoOpen' => true,
        ];
    }
}
