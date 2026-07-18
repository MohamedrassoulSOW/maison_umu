<?php

namespace App\Service;

final class BrandLogo
{
    public const CID = 'brand_logo';

    public function __construct(private string $projectDir)
    {
    }

    public function getPath(): ?string
    {
        foreach ([
            $this->projectDir.'/public/images/logo.jpeg',
            $this->projectDir.'/public/images/logo.jpg',
            $this->projectDir.'/public/images/logo.png',
            $this->projectDir.'/public/images/slider-logo.jpeg',
        ] as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    public function getMime(): string
    {
        $path = $this->getPath();
        if (!$path) {
            return 'image/jpeg';
        }

        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/jpeg',
        };
    }

    public function getBase64(): ?string
    {
        $path = $this->getPath();
        if (!$path) {
            return null;
        }

        $contents = file_get_contents($path);

        return $contents === false ? null : base64_encode($contents);
    }

    public function getDataUri(): ?string
    {
        $base64 = $this->getBase64();
        if ($base64 === null) {
            return null;
        }

        return 'data:'.$this->getMime().';base64,'.$base64;
    }

    public function getPublicPath(): string
    {
        return 'images/logo.jpeg';
    }
}
