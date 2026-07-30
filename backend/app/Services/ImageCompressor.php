<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;

/**
 * Re-encodes uploaded photos before they hit storage. Two things do almost
 * all of the file-size reduction with no perceptible quality loss for how
 * these are actually viewed (gallery grid/lightbox, payment proof review):
 * capping resolution to what a screen can show, and stripping EXIF metadata
 * (which can be a meaningful chunk of a modern phone photo's file size).
 * JPEG/WebP quality 85 is the conventional "visually lossless" threshold —
 * below it artifacts start being visible, above it file size grows for
 * savings nobody can see.
 */
class ImageCompressor
{
    private const MAX_DIMENSION = 2560;

    private const QUALITY = 85;

    private const COMPRESSIBLE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function isCompressible(UploadedFile $file): bool
    {
        return in_array(strtolower((string) $file->getClientOriginalExtension()), self::COMPRESSIBLE_EXTENSIONS, true);
    }

    public function compress(UploadedFile $file): string
    {
        $image = ImageManager::gd()->read($file->getRealPath());
        $image->scaleDown(width: self::MAX_DIMENSION, height: self::MAX_DIMENSION);

        $encoded = match (strtolower((string) $file->getClientOriginalExtension())) {
            'png' => $image->toPng(),
            'webp' => $image->toWebp(quality: self::QUALITY, strip: true),
            default => $image->toJpeg(quality: self::QUALITY, strip: true),
        };

        return (string) $encoded;
    }
}
