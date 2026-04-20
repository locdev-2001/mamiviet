<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class ImageTransformationService
{
    private const VARIANT_WIDTHS = [480, 768, 1280, 1920];

    private const WEBP_QUALITY = 80;

    private const MAX_DIMENSION = 8000;

    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new GdDriver());
    }

    public function processImage(UploadedFile $file, string $folder): array
    {
        if (! str_starts_with((string) $file->getMimeType(), 'image/')) {
            throw new InvalidArgumentException('Uploaded file is not an image.');
        }

        $folder = $this->sanitizeFolder($folder);
        $name = (string) Str::uuid();
        $base = "{$folder}/{$name}";

        $image = $this->manager->read($file->getPathname());
        $width = $image->width();
        $height = $image->height();

        if ($width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            throw new InvalidArgumentException('Image exceeds maximum allowed dimensions.');
        }

        $written = [];

        try {
            $originalPath = "{$base}.webp";
            Storage::disk('public')->put($originalPath, (string) $image->toWebp(self::WEBP_QUALITY));
            $written[] = $originalPath;

            $variants = [];
            foreach (self::VARIANT_WIDTHS as $size) {
                if ($size >= $width) {
                    continue;
                }

                $variantPath = "{$base}_w{$size}.webp";
                $resized = (clone $image)->scaleDown(width: $size);
                Storage::disk('public')->put($variantPath, (string) $resized->toWebp(self::WEBP_QUALITY));
                $written[] = $variantPath;
                $variants["w{$size}"] = Storage::disk('public')->url($variantPath);
            }

            return [
                'original' => Storage::disk('public')->url($originalPath),
                'original_path' => $originalPath,
                'variants' => $variants,
                'width' => $width,
                'height' => $height,
            ];
        } catch (Throwable $e) {
            Storage::disk('public')->delete($written);
            throw new RuntimeException('Image processing failed: ' . $e->getMessage(), 0, $e);
        }
    }

    private function sanitizeFolder(string $folder): string
    {
        $clean = preg_replace('#[^a-z0-9/_-]#i', '', trim($folder, '/'));

        return $clean !== '' ? $clean : 'uploads';
    }
}
