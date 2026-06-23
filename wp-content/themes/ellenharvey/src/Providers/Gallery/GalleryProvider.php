<?php

declare(strict_types=1);

namespace EllenHarvey\Providers\Gallery;

use IX\Providers\Provider;

/**
 * Gallery provider.
 *
 * Registers the `gallery` CPT — one production's set of production photos.
 * Each gallery has a poster (the show logo) and an ordered set of photos. The
 * CPT is admin-only data (no public archive/single): the Photos page is an
 * ordinary CMS page that drops in the `ellenharvey/photo-gallery` block, which
 * lays the posters out in a grid; clicking one opens a dependency-free lightbox
 * that pages through that production's photos (replacing the original's
 * jQuery/mootools lightbox).
 */
class GalleryProvider extends Provider
{
    public function register(): void
    {
        add_action('init', [$this, 'registerPostType']);

        parent::register();

        $this->acfManager->registerSavePath();
    }

    public function registerPostType(): void
    {
        $this->registerPostTypeFromConfig('post-type.json');
    }
}
