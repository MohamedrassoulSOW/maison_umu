<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\ProductImage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProductImageUploader
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        #[Autowire('%image_dir%')]
        private readonly string $uploadDir,
    ) {
    }

    /**
     * @param UploadedFile[] $files
     */
    public function addFiles(Product $product, array $files): void
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0775, true);
        }

        $position = $this->nextPosition($product);

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $filename = $this->storeFile($file);
            $image = (new ProductImage())
                ->setFilename($filename)
                ->setPosition($position++);

            $product->addImage($image);
        }

        $this->syncCover($product);
    }

    public function removeImage(Product $product, ProductImage $image): void
    {
        $this->unlinkFile($image->getFilename());
        $product->removeImage($image);
        $this->reindex($product);
        $this->syncCover($product);
    }

    public function removeAll(Product $product): void
    {
        foreach ($product->getImages()->toArray() as $image) {
            $this->unlinkFile($image->getFilename());
            $product->removeImage($image);
        }

        if ($product->getImage()) {
            // Legacy cover not in gallery
            $stillUsed = false;
            foreach ($product->getImages() as $image) {
                if ($image->getFilename() === $product->getImage()) {
                    $stillUsed = true;
                    break;
                }
            }
            if (!$stillUsed) {
                $this->unlinkFile($product->getImage());
            }
            $product->setImage(null);
        }
    }

    public function syncCover(Product $product): void
    {
        $first = $product->getImages()->first();
        $product->setImage($first instanceof ProductImage ? $first->getFilename() : null);
    }

    public function migrateLegacyCover(Product $product): void
    {
        if (!$product->getImage() || $product->getImages()->count() > 0) {
            return;
        }

        $image = (new ProductImage())
            ->setFilename($product->getImage())
            ->setPosition(0);

        $product->addImage($image);
    }

    private function storeFile(UploadedFile $file): string
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFileName = $this->slugger->slug($originalName);
        $extension = $file->guessExtension() ?: 'jpg';
        $newFileName = $safeFileName.'-'.uniqid().'.'.$extension;

        try {
            $file->move($this->uploadDir, $newFileName);
        } catch (FileException $e) {
            throw $e;
        }

        return $newFileName;
    }

    private function unlinkFile(?string $filename): void
    {
        if (!$filename) {
            return;
        }

        $path = rtrim($this->uploadDir, '/\\').DIRECTORY_SEPARATOR.$filename;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    private function nextPosition(Product $product): int
    {
        $max = -1;
        foreach ($product->getImages() as $image) {
            $max = max($max, $image->getPosition());
        }

        return $max + 1;
    }

    private function reindex(Product $product): void
    {
        $i = 0;
        foreach ($product->getImages() as $image) {
            $image->setPosition($i++);
        }
    }
}
