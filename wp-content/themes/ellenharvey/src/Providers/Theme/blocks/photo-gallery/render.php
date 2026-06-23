<?php
/**
 * Photo Gallery block — server-side render.
 *
 * Renders the grid of production posters (every `gallery` post, in admin/menu
 * order). Each poster carries its production's photos as JSON; the theme's
 * gallery-lightbox.js wires the click-to-open lightbox on the front end.
 *
 * This block replaces the former /photos/ CPT archive — the Photos page is now
 * an ordinary CMS page that drops this block into its content panel.
 */

use Timber\Timber;

$context              = Timber::context();
$context['galleries'] = Timber::get_posts([
    'post_type'      => 'gallery',
    'posts_per_page' => -1,
    'orderby'        => 'menu_order',
    'order'          => 'ASC',
]);

$wrapper = get_block_wrapper_attributes();

echo '<div ' . $wrapper . '>';
Timber::render(__DIR__ . '/photo-gallery.twig', $context);
echo '</div>';
