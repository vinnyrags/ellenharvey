<?php
/**
 * Reviews archive.
 *
 * Groups press quotes by production (the `production` taxonomy), in term
 * order, mirroring the original reviews.htm where each show heads a block
 * of pull-quotes. Productions are ordered by term id so they appear in the
 * order they were created/seeded; within a show, reviews follow menu_order.
 *
 * @package EllenHarvey
 */

use Timber\Timber;

$context          = Timber::context();
$context['title'] = post_type_archive_title('', false) ?: 'Reviews';

$productions = Timber::get_terms([
    'taxonomy'   => 'production',
    'hide_empty' => true,
    'orderby'    => 'term_id',
    'order'      => 'ASC',
]);

$groups = [];
foreach ($productions as $production) {
    $reviews = Timber::get_posts([
        'post_type'      => 'review',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'tax_query'      => [
            [
                'taxonomy' => 'production',
                'field'    => 'term_id',
                'terms'    => $production->id,
            ],
        ],
    ]);

    if (count($reviews)) {
        $groups[] = [
            'production' => $production,
            'reviews'    => $reviews,
        ];
    }
}

$context['groups'] = $groups;

Timber::render(['archive-review.twig'], $context);
