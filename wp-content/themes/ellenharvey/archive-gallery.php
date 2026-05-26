<?php
/**
 * Photos archive (/photos/).
 *
 * A grid of production posters, in menu order, mirroring the original
 * photos.htm. Each poster opens a lightbox that pages through that
 * production's photos (see archive-gallery.twig + the gallery lightbox JS).
 *
 * @package EllenHarvey
 */

use Timber\Timber;

$context            = Timber::context();
$context['title']   = 'Photos';
$context['galleries'] = Timber::get_posts([
    'post_type'      => 'gallery',
    'posts_per_page' => -1,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
]);

Timber::render(['archive-gallery.twig'], $context);
