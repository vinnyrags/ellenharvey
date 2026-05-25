<?php
/**
 * Front page.
 *
 * Extends IX's front-page behavior by also gathering the reviews flagged
 * "Feature on homepage" so front-page.twig can rotate them as highlights
 * over the hero (replacing the original's static quote slides).
 *
 * @package EllenHarvey
 */

use Timber\Timber;

$context         = Timber::context();
$context['post'] = Timber::get_post();

$context['featured_reviews'] = Timber::get_posts([
    'post_type'      => 'review',
    'posts_per_page' => -1,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
    'meta_query'     => [
        [
            'key'     => 'featured',
            'value'   => '1',
            'compare' => '=',
        ],
    ],
]);

Timber::render(['front-page.twig', 'page.twig'], $context);
