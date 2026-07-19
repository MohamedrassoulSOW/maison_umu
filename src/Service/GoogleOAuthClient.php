<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleOAuthClient
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://openidconnect.googleapis.com/v1/userinfo';

    private readonly string $clientId;
    private readonly string $clientSecret;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly UrlGeneratorInterface $urlGenerator,
        ?string $clientId = null,
        ?string $clientSecret = null,
    ) {
        $this->clientId = trim((string) $clientId);
        $this->clientSecret = trim((string) $clientSecret);
    }

    public function isConfigured(): bool
    {
        return $this->clientId !== '' && $this->clientSecret !== '';
    }

    public function getAuthorizationUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->getRedirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'access_type' => 'online',
            'prompt' => 'select_account',
        ], '', '&', PHP_QUERY_RFC3986);

        return self::AUTH_URL.'?'.$query;
    }

    /**
     * @return array{sub: string, email: string, email_verified?: bool, given_name?: string, family_name?: string, name?: string}
     */
    public function fetchUser(string $code): array
    {
        $tokenResponse = $this->httpClient->request('POST', self::TOKEN_URL, [
            'body' => [
                'code' => $code,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->getRedirectUri(),
                'grant_type' => 'authorization_code',
            ],
        ]);

        $tokenData = $tokenResponse->toArray(false);
        $accessToken = $tokenData['access_token'] ?? null;
        if (!$accessToken) {
            throw new \RuntimeException('Impossible d’obtenir le jeton Google.');
        }

        $userResponse = $this->httpClient->request('GET', self::USERINFO_URL, [
            'headers' => [
                'Authorization' => 'Bearer '.$accessToken,
            ],
        ]);

        $user = $userResponse->toArray(false);
        if (empty($user['sub']) || empty($user['email'])) {
            throw new \RuntimeException('Profil Google incomplet.');
        }

        return $user;
    }

    public function createState(): string
    {
        return bin2hex(random_bytes(24));
    }

    public function getRedirectUri(): string
    {
        return $this->urlGenerator->generate('app_connect_google_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function extractCodeAndState(Request $request): array
    {
        return [
            $request->query->getString('code'),
            $request->query->getString('state'),
            $request->query->getString('error'),
        ];
    }
}
