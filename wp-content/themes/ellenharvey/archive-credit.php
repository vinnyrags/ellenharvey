<?php
/**
 * Résumé archive (/resume/).
 *
 * Groups credits by section (the `credit_category` taxonomy) in term order,
 * mirroring resume.htm: a section heading followed by a three-column table
 * of production / role / venue. Sections order by term id (seed order);
 * within a section, credits follow menu_order. The static header
 * credentials and the training/skills/awards footer come from the Résumé
 * Details options page.
 *
 * @package EllenHarvey
 */

use Timber\Timber;

$context          = Timber::context();
$context['title'] = 'Resume';

$sections = Timber::get_terms([
    'taxonomy'   => 'credit_category',
    'hide_empty' => true,
    'orderby'    => 'term_id',
    'order'      => 'ASC',
]);

$groups = [];
foreach ($sections as $section) {
    $credits = Timber::get_posts([
        'post_type'      => 'credit',
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'tax_query'      => [
            [
                'taxonomy' => 'credit_category',
                'field'    => 'term_id',
                'terms'    => $section->id,
            ],
        ],
    ]);

    if (count($credits)) {
        $groups[] = [
            'section' => $section,
            'credits' => $credits,
        ];
    }
}

$context['groups']  = $groups;
$context['details'] = [
    'unions'      => get_field('unions', 'option'),
    'eyes'        => get_field('eyes', 'option'),
    'hair'        => get_field('hair', 'option'),
    'vocal_range' => get_field('vocal_range', 'option'),
    'training'    => get_field('training', 'option'),
    'skills'      => get_field('skills', 'option'),
    'awards'      => get_field('awards', 'option'),
    'footer_note' => get_field('footer_note', 'option'),
    'pdf'         => get_field('pdf', 'option'),
    'headshots'   => get_field('headshots', 'option'),
];

Timber::render(['archive-credit.twig'], $context);
