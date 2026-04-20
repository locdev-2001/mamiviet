<?php

namespace App\Observers;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaDimensionObserver
{
    public function created(Media $media): void
    {
        if (! str_starts_with((string) $media->mime_type, 'image/')) {
            return;
        }

        if ($media->getCustomProperty('width') && $media->getCustomProperty('height')) {
            return;
        }

        $path = $media->getPath();
        if (! is_readable($path)) {
            return;
        }

        $info = @getimagesize($path);
        if ($info === false) {
            return;
        }

        $media->setCustomProperty('width', (int) $info[0]);
        $media->setCustomProperty('height', (int) $info[1]);
        $media->saveQuietly();
    }
}
