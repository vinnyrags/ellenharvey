<?php

declare(strict_types=1);

namespace EllenHarvey\Providers\Gallery;

use IX\Models\Post;
use Timber\Timber;

/**
 * Gallery post model.
 *
 * One production's photo set: a poster (the grid thumbnail) plus an ordered
 * list of photos. Both come from ACF — the poster is an attachment id, the
 * photos a list of attachment ids — and are resolved to Timber images so
 * templates get sizes, alt text, and captions.
 */
class GalleryPost extends Post
{
    public const POST_TYPE = 'gallery';

    /**
     * The poster image (show logo) shown in the /photos/ grid.
     */
    public function poster(): ?\Timber\Image
    {
        $id = $this->meta('poster');

        return $id ? Timber::get_image((int) $id) : null;
    }

    /**
     * The production photos, in order.
     *
     * @return \Timber\Image[]
     */
    public function photos(): array
    {
        $ids = $this->meta('photos');

        if (!is_array($ids) || $ids === []) {
            return [];
        }

        $images = [];
        foreach ($ids as $id) {
            $image = Timber::get_image((int) $id);
            if ($image) {
                $images[] = $image;
            }
        }

        return $images;
    }
}
